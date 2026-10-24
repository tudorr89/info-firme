<?php

use App\Http\Controllers\API\SitemapController;
use App\Http\Controllers\CompanyController;
use App\Livewire\CifRemovalForm;
use App\Livewire\CompanySearch;
use Illuminate\Support\Facades\Route;

Route::get('/', CompanySearch::class)->name('company.search');
Route::get('/companie/{cui}', [CompanyController::class, 'show'])
    ->name('company.show')
    ->where('cui', '[0-9]+');

Route::view('/termeni-si-conditii', 'legal.terms')->name('legal.terms');
Route::view('/politica-de-confidentialitate', 'legal.privacy')->name('legal.privacy');
Route::get('/solicitare-stergere', CifRemovalForm::class)->name('cif-removal.create');

// Container/orchestrator health probe (cheap: no Laravel boot through artisan).
Route::get('/up', fn () => response('OK', 200))->name('health');

// Sitemaps - no session middleware: crawlers must not get a session cookie or
// a Set-Cookie header on a file that rarely changes.
Route::withoutMiddleware(['web'])->group(function () {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
    Route::get('/sitemap-{number}.xml', [SitemapController::class, 'chunk'])
        ->where('number', '[0-9]+')
        ->name('sitemap.chunk');
});

Route::fallback(function () {
    abort(404);
});
