<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $filePath = storage_path('app/sitemaps/sitemap.xml');

        if (! file_exists($filePath)) {
            abort(404, 'Sitemap not found. Please run: php artisan sitemap:generate');
        }

        return response(file_get_contents($filePath), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function chunk(int $number): Response
    {
        $filePath = storage_path("app/sitemaps/sitemap-{$number}.xml");

        if (! file_exists($filePath)) {
            abort(404, 'Sitemap chunk not found');
        }

        return response(file_get_contents($filePath), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
