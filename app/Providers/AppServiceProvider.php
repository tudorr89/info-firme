<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // TLS is terminated at the edge (Cloudflare tunnel), so every generated
        // absolute URL has to be https even if the forwarded proto header is
        // lost somewhere along the way. `TrustProxies` covers the rest.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
