<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the files written by `php artisan sitemap:generate`.
 *
 * They are generated to disk (not streamed from the database) because the
 * company sitemap holds about four million URLs, which no crawler would get
 * back from a live query. The chunks are gzipped: ~4M URLs weigh ~50 MB
 * compressed instead of ~530 MB.
 */
class SitemapController extends Controller
{
    private const CACHE_SECONDS = 86400;

    private const NOT_FOUND = 'Sitemap not found. Please run: php artisan sitemap:generate';

    /**
     * The index is served uncompressed: it is tiny and points at `.xml.gz`
     * files, which is the layout Google expects for gzipped sitemaps.
     */
    public function index(): Response
    {
        return $this->serve('sitemap.xml', 'application/xml; charset=UTF-8');
    }

    public function pages(): Response
    {
        return $this->serve('sitemap-pages.xml.gz', 'application/x-gzip');
    }

    public function chunk(int $number): Response
    {
        if ($number < 1) {
            abort(404, 'Sitemap chunk not found');
        }

        // `.xml` is still answered for files generated before the switch to
        // gzip, so a crawler holding the old index never sees a 404.
        foreach (["sitemap-{$number}.xml.gz" => 'application/x-gzip', "sitemap-{$number}.xml" => 'application/xml; charset=UTF-8'] as $name => $contentType) {
            $path = $this->path($name);

            if (is_file($path)) {
                return $this->serve($name, $contentType);
            }
        }

        abort(404, 'Sitemap chunk not found');
    }

    private function serve(string $name, string $contentType): Response
    {
        $path = $this->path($name);

        if (! is_file($path)) {
            abort(404, self::NOT_FOUND);
        }

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age='.self::CACHE_SECONDS,
        ]);
    }

    private function path(string $name): string
    {
        return storage_path('app/sitemaps/'.$name);
    }
}
