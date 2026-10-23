<?php

use App\Http\Controllers\API\SitemapController;
use App\Http\Controllers\CompanyController;
use App\Livewire\CompanySearch;
use Illuminate\Support\Facades\Route;

// Route::get('/', CompanySearch::class)->name('company.search');
// Route::get('/companie/{cui}', [CompanyController::class, 'show'])
//    ->name('company.show')
//    ->where('cui', '[0-9]+');

// Container/orchestrator health probe (cheap: no Laravel boot through artisan).
Route::get('/up', fn () => response('OK', 200))->name('health');

Route::fallback(function () {
    abort(401, 'Unauthorized');
});

// Sitemap routes - use API middleware to avoid session cookies
// Route::withoutMiddleware(['web'])->group(function () {
//    Route::get('/sitemap.xml', [SitemapController::class, 'index']);
//    Route::get('/api/sitemap-{number}.xml', [SitemapController::class, 'chunk'])
//        ->where('number', '[0-9]+');
// });
