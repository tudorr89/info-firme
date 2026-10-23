# syntax=docker/dockerfile:1
#
# Build order matters: everything below is ordered from "changes almost never"
# to "changes on every commit" so BuildKit can reuse expensive layers (PHP
# extensions, composer install, frontend build) between deploys.

# Pinned to PHP 8.4.27 (Laravel 13 needs the Pdo\Mysql class; also fixes
# CVE-2026-109618 / CVE-2026-109619).
ARG PHP_VERSION=8.4.27
ARG FRANKENPHP_VERSION=1.13.2
ARG COMPOSER_VERSION=2.9
ARG BUN_VERSION=1.4.2
ARG SUPERCRONIC_VERSION=0.2.49

###########################################
# Rebuild FrankenPHP with brotli support and a smaller binary
###########################################

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-builder-php${PHP_VERSION}-alpine AS upstream

COPY --from=caddy:builder /usr/bin/xcaddy /usr/bin/xcaddy

# Persistent Go caches: a version bump recompiles FrankenPHP in ~1min instead
# of re-downloading the whole module graph and rebuilding Caddy from scratch.
RUN --mount=type=cache,target=/go/pkg/mod \
    --mount=type=cache,target=/REDACTED/.cache/go-build \
    CGO_ENABLED=1 \
    XCADDY_SETCAP=1 \
    XCADDY_GO_BUILD_FLAGS="-ldflags='-w -s' -tags=nobadger,nomysql,nopgx" \
    CGO_CFLAGS=$(php-config --includes) \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
    xcaddy build \
    --output /usr/local/bin/frankenphp \
    --with github.com/dunglas/frankenphp=./ \
    --with github.com/dunglas/frankenphp/caddy=./caddy/ \
    --with github.com/dunglas/caddy-cbrotli

###########################################

FROM composer:${COMPOSER_VERSION} AS vendor

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-alpine

COPY --from=upstream /usr/local/bin/frankenphp /usr/local/bin/frankenphp

LABEL maintainer="Mortexa <seyed.me720@gmail.com>"
LABEL org.opencontainers.image.title="Laravel Docker Setup"
LABEL org.opencontainers.image.description="Production-ready Docker Setup for Laravel"
LABEL org.opencontainers.image.source=https://github.com/exaco/laravel-docktane
LABEL org.opencontainers.image.licenses=MIT

ARG USER_ID=1000
ARG GROUP_ID=1000
ARG TZ=UTC
ARG ENV_FILE=.env
ARG APP_ENV=production
ARG BUN_VERSION
# Re-declared here: a global ARG is only visible in FROM lines otherwise.
ARG SUPERCRONIC_VERSION
ARG GIT_COMMIT_HASH=unknown
ARG GIT_COMMIT_DATE=unknown

ENV TERM=xterm-color \
    GIT_COMMIT_HASH=${GIT_COMMIT_HASH} \
    GIT_COMMIT_DATE=${GIT_COMMIT_DATE} \
    OCTANE_SERVER=frankenphp \
    TZ=${TZ} \
    LANG=C.UTF-8 \
    USER=laravel \
    ROOT=/var/www/html \
    APP_ENV=${APP_ENV} \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=48 \
    WITH_HORIZON=false \
    WITH_SCHEDULER=false \
    WITH_REVERB=false \
    WITH_SSR=false

ENV XDG_CONFIG_HOME=${ROOT}/.config XDG_DATA_HOME=${ROOT}/.data

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

# `apk upgrade` is intentionally NOT used: it changes this layer whenever the
# Alpine repositories move, which invalidates every layer below it (most
# importantly the install-php-extensions layer). Security updates come from
# bumping the pinned PHP_VERSION / FRANKENPHP_VERSION base images.
# The cache mount keeps the package index + .apk files between builds.
RUN --mount=type=cache,target=/var/cache/apk \
    apk update \
    && apk add \
    bash \
    ca-certificates \
    curl \
    git \
    libsodium-dev \
    ncdu \
    procps \
    supervisor \
    tzdata \
    unzip \
    vim \
    wget \
    && rm -rf /tmp/* /var/tmp/*

RUN curl -fsSL https://bun.sh/install | BUN_INSTALL=/usr bash -s "bun-v${BUN_VERSION}" \
    && rm -rf /tmp/* /var/tmp/*

# Install PHP extensions (dunglas/frankenphp already ships the toolchain).
# MAKEFLAGS lets every extension build use all CPUs available to the builder.
RUN MAKEFLAGS="-j$(nproc)" install-php-extensions \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    opcache \
    exif \
    pdo_mysql \
    zip \
    intl \
    gd \
    redis \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

RUN arch="$(apk --print-arch)" \
    && case "$arch" in \
    armhf) _cronic_fname='supercronic-linux-arm' ;; \
    aarch64) _cronic_fname='supercronic-linux-arm64' ;; \
    x86_64) _cronic_fname='supercronic-linux-amd64' ;; \
    x86) _cronic_fname='supercronic-linux-386' ;; \
    *) echo >&2 "error: unsupported architecture: $arch"; exit 1 ;; \
    esac \
    && curl -fsSL "https://github.com/aptible/supercronic/releases/download/v${SUPERCRONIC_VERSION}/${_cronic_fname}" \
    -o /usr/bin/supercronic \
    && chmod +x /usr/bin/supercronic \
    && mkdir -p /etc/supercronic \
    && echo "*/1 * * * * php ${ROOT}/artisan schedule:run --no-interaction" > /etc/supercronic/laravel

RUN addgroup -g ${GROUP_ID} ${USER} \
    && adduser -D -h ${ROOT} -G ${USER} -u ${USER_ID} -s /bin/sh ${USER}

# Hardening: drop SUID/SGID bits from the base image once. Running this in its
# own early layer keeps it cached instead of re-scanning on every app build.
RUN { find / -xdev \( -perm -4000 -o -perm -2000 \) -type f -exec chmod a-s {} + 2>/dev/null || true; }

RUN cp ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

COPY --link --from=vendor /usr/bin/composer /usr/bin/composer
COPY --link deployment/supervisord.conf /etc/
COPY --link deployment/octane/FrankenPHP/supervisord.frankenphp.conf /etc/supervisor/conf.d/
COPY --link deployment/supervisord.*.conf /etc/supervisor/conf.d/
COPY --link deployment/start-container /usr/local/bin/start-container
COPY --link deployment/healthcheck /usr/local/bin/healthcheck
COPY --link deployment/php.ini ${PHP_INI_DIR}/conf.d/99-php.ini
# FrankenPHP's embedded PHP reads this one.
COPY --link deployment/php.ini /lib/php.ini

COPY --link --chown=${USER_ID}:${GROUP_ID} composer.* ./

# Composer tarballs live in a BuildKit cache, not in the image, so a lockfile
# change only re-downloads what actually changed.
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-scripts \
    --no-progress

COPY --link --chown=${USER_ID}:${GROUP_ID} package.json bun.lock* ./

# Keep Bun's tarball cache between builds (it is not part of the image).
RUN --mount=type=cache,target=/REDACTED/.bun/install/cache \
    bun install --frozen-lockfile

COPY --link --chown=${USER_ID}:${GROUP_ID} . .

# Laravel prefers .env.{APP_ENV} over .env. Stale env files copied from the
# build context used to shadow the selected ENV_FILE; keep .env as the single
# baked environment file.
RUN rm -f .env .env.local .env.production .env.staging .env.testing .env.preview
COPY --link --chown=${USER_ID}:${GROUP_ID} ${ENV_FILE:-.env} .env

RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    /tmp/opcache-file-cache \
    && chmod +x /usr/local/bin/start-container /usr/local/bin/healthcheck \
    && rm -f bootstrap/cache/*.php

# One autoload dump instead of a second full composer install; the dump runs
# the post-autoload-dump hooks (package discovery, Filament assets).
RUN composer dump-autoload \
    --optimize \
    --no-dev \
    && composer clear-cache

# Compile views and Filament components at build time as a smoke test; the
# entrypoint clears and rebuilds them at runtime so injected env vars win.
RUN php artisan view:cache \
    && php artisan filament:optimize

RUN bun run build

# Only what is written at runtime needs to be owned by the app user: storage/,
# bootstrap/cache/, public/ (storage:link + Vite build output) and the XDG
# dirs Caddy/FrankenPHP writes to (config autosave + instance ID). Recursively
# chowning vendor/ forced a copy-up into a new layer and added ~80s per build.
RUN mkdir -p ${ROOT}/.config ${ROOT}/.data \
    && chown ${USER_ID}:${GROUP_ID} ${ROOT} \
    && chown -R ${USER_ID}:${GROUP_ID} storage bootstrap/cache public .config .data

USER ${USER}

EXPOSE 8000
EXPOSE 2019
EXPOSE 8080

ENTRYPOINT ["start-container"]

# `php artisan octane:status` boots Laravel for every probe; with the old 1s
# interval that kept a core busy. The script now hits /up through an existing
# worker, every 30s.
HEALTHCHECK --start-period=30s --interval=30s --timeout=5s --retries=3 CMD healthcheck || exit 1
