<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * The container is never reached directly: traffic arrives through a
     * Cloudflare tunnel and then Caddy, both of which prefix the request with
     * X-Forwarded-* headers. Trusting every proxy is safe here because the app
     * port is not exposed publicly, and without it Laravel believes every
     * request is plain HTTP — which made the admin panel redirect to
     * `http://…/admin` after login and emitted http:// canonical/OG URLs.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
