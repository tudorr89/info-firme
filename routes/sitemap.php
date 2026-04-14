<?php

use App\Http\Controllers\API\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/api/sitemap-{number}.xml', [SitemapController::class, 'chunk'])
    ->where('number', '[0-9]+');