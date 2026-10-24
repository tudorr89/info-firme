<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the files written by `php artisan sitemap:generate`.
 *
 * They are generated to disk (not streamed from the database) because the
 * company sitemap holds about four million URLs, which no crawler would get
 * back from a live query.
 */
class SitemapController extends Controller
{
    private const CACHE_SECONDS = 86400;

    public function index(): Response
    {
        return $this->file('sitemap.xml', 'Sitemap not found. Please run: php artisan sitemap:generate');
    }

    public function pages(): Response
    {
        return $this->file('sitemap-pages.xml', 'Sitemap not found. Please run: php artisan sitemap:generate');
    }

    public function chunk(int $number): Response
    {
        if ($number < 1) {
            abort(404, 'Sitemap chunk not found');
        }

        return $this->file("sitemap-{$number}.xml", 'Sitemap chunk not found');
    }

    private function file(string $name, string $notFoundMessage): Response
    {
        $path = storage_path('app/sitemaps/'.$name);

        if (! is_file($path)) {
            abort(404, $notFoundMessage);
        }

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age='.self::CACHE_SECONDS,
        ]);
    }
}
