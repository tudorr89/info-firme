#!/usr/bin/env bash
#
# Rolling, health-gated deploy of registry.artemis.casa/info-firme:production.
#
# Shape (taken from notice-api, adapted to this stack): the two HTTP replicas
# behind deployment/Caddyfile.lb are replaced one at a time, so at least one
# replica always answers. Caddy retries a request on the healthy upstream for up
# to 5s (lb_try_duration), which is what makes the swap itself invisible.
#
# Migrations run once, in a throwaway container, BEFORE any replica is touched:
# both replicas set RUNNING_MIGRATIONS_AND_SEEDERS=false, so a replica restart
# never triggers a migration and a slow migration cannot hold up the swap.
#
# Usage:
#   ./deploy-zero-downtime.sh                 # pull, migrate, roll replicas
#   ./deploy-zero-downtime.sh --skip-migrations
#   ./deploy-zero-downtime.sh --tag=production
#   IMAGE_TAG=prod_1a2b3c ./deploy-zero-downtime.sh
#
# Production is normally deployed by the info_firme Ansible role (infra repo).
# This script is the manual/on-host equivalent and uses the same compose layout.

set -euo pipefail

cd "$(dirname "$0")"

IMAGE_REPO="registry.artemis.casa/info-firme"
IMAGE_TAG="${IMAGE_TAG:-production}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.zero-downtime.yml}"
ENV_FILE="${ENV_FILE:-.env.production}"

REPLICAS=(app-1 app-2)
SIDE_SERVICES=(horizon scheduler)

HEALTH_RETRIES="${HEALTH_RETRIES:-60}"
HEALTH_INTERVAL="${HEALTH_INTERVAL:-3}"
DRAIN_TIME="${DRAIN_TIME:-3}"

SKIP_MIGRATIONS="false"

log() { printf '\033[1;34m[%s]\033[0m %s\n' "$(date +%H:%M:%S)" "$*"; }
ok() { printf '\033[1;32m[ OK ]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[WARN]\033[0m %s\n' "$*"; }
die() {
    printf '\033[1;31m[FAIL]\033[0m %s\n' "$*" >&2
    exit 1
}

usage() {
    sed -n '2,22p' "$0" | sed 's/^# \{0,1\}//'
    exit "${1:-0}"
}

for arg in "$@"; do
    case "$arg" in
        --skip-migrations) SKIP_MIGRATIONS="true" ;;
        --tag=*) IMAGE_TAG="${arg#*=}" ;;
        -h | --help) usage 0 ;;
        *) die "Unknown argument: $arg (try --help)" ;;
    esac
done

IMAGE="${IMAGE_REPO}:${IMAGE_TAG}"

preflight() {
    command -v docker > /dev/null || die "docker is not installed"
    docker info > /dev/null 2>&1 || die "the docker daemon is not reachable"
    [ -f "$COMPOSE_FILE" ] || die "compose file not found: $COMPOSE_FILE"
    [ -f "$ENV_FILE" ] || die "env file not found: $ENV_FILE (rendered by the infra playbook)"
    [ -f deployment/Caddyfile.lb ] || die "missing deployment/Caddyfile.lb (the LB has no config)"
    docker compose version > /dev/null 2>&1 || die "docker compose v2 is required"
}

# Image ID a container is currently running, so a failed swap can go back to
# exactly what was being served - not to "whatever :production now points at".
running_image_id() {
    docker inspect --format '{{.Image}}' "$1" 2> /dev/null || true
}

wait_healthy() {
    local container=$1
    local status

    for _ in $(seq 1 "$HEALTH_RETRIES"); do
        status=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container" 2> /dev/null || echo missing)

        case "$status" in
            healthy) return 0 ;;
            unhealthy) warn "$container reported unhealthy"; return 1 ;;
        esac

        sleep "$HEALTH_INTERVAL"
    done

    warn "$container did not become healthy after $((HEALTH_RETRIES * HEALTH_INTERVAL))s (last status: ${status:-unknown})"
    return 1
}

migrate() {
    if [ "$SKIP_MIGRATIONS" = "true" ]; then
        warn "Skipping migrations (--skip-migrations)"
        return 0
    fi

    log "Running migrations once, in a throwaway container"
    # --no-deps: redis/scheduler do not need to be up for a migration, and this
    # must not recreate anything that is currently serving traffic.
    if ! docker compose -f "$COMPOSE_FILE" run --rm --no-deps app-1 \
        php artisan migrate --force --no-interaction; then
        die "Migrations failed - nothing was swapped. Fix the migration and re-run."
    fi
    ok "Migrations applied"
}

roll_replica() {
    local container=$1
    local previous_image
    previous_image=$(running_image_id "$container")

    log "Rolling $container"
    if [ -n "$previous_image" ]; then
        log "$container is running image ${previous_image:0:19}"
    fi

    # One replica at a time, and never touch a dependency: replacing app-1 must
    # not take redis or the other replica with it.
    if ! docker compose -f "$COMPOSE_FILE" up -d --no-deps --force-recreate "$container"; then
        rollback "$container" "$previous_image"
        die "Could not start $container with $IMAGE"
    fi

    if ! wait_healthy "$container"; then
        docker logs --tail 50 "$container" 2>&1 | sed 's/^/    /' || true
        rollback "$container" "$previous_image"
        die "$container failed its health check - rolled back"
    fi

    ok "$container is healthy"
    log "Waiting ${DRAIN_TIME}s for the load balancer to stop sending to the old container"
    sleep "$DRAIN_TIME"
}

rollback() {
    local container=$1
    local previous_image=$2

    [ -n "$previous_image" ] || {
        warn "No previous image recorded for $container - leaving it stopped"
        return 0
    }

    warn "Rolling $container back to ${previous_image:0:19}"

    # The tag is mutable, so point it at the old image ID locally before
    # recreating; otherwise compose would just start the new image again.
    docker tag "$previous_image" "$IMAGE" > /dev/null
    docker compose -f "$COMPOSE_FILE" up -d --no-deps --force-recreate "$container" || true

    if wait_healthy "$container"; then
        ok "$container is back on the previous image"
    else
        warn "$container is still unhealthy after the rollback - needs a human"
    fi
}

main() {
    preflight
    log "Deploying $IMAGE from $COMPOSE_FILE"

    log "Pulling $IMAGE"
    docker pull "$IMAGE" || die "Could not pull $IMAGE"
    ok "Image pulled"

    migrate

    for replica in "${REPLICAS[@]}"; do
        roll_replica "$replica"
    done

    # Horizon and the scheduler are single-instance and can be down for a few
    # seconds without affecting visitors, so they go last.
    log "Rolling ${SIDE_SERVICES[*]}"
    docker compose -f "$COMPOSE_FILE" up -d --no-deps "${SIDE_SERVICES[@]}"

    sleep 5
    docker compose -f "$COMPOSE_FILE" ps

    log "Checking the load balancer"
    if curl -fsS --max-time 5 -o /dev/null http://127.0.0.1:8000/health; then
        ok "LB health endpoint answers"
    else
        warn "The LB health endpoint did not answer - check caddy-lb and the Caddyfile.lb"
    fi

    ok "Deployed $IMAGE with no downtime"
}

main
