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

// Sitemap routes - use API middleware to avoid session cookies
Route::withoutMiddleware(['web'])->group(function () {
    Route::get('/sitemap.xml', [SitemapController::class, 'index']);
    Route::get('/api/sitemap-{number}.xml', [SitemapController::class, 'chunk'])
        ->where('number', '[0-9]+');
});

Route::fallback(function () {
    abort(404);
});
