<?php

use App\Http\Controllers\CompanyController;
use App\Livewire\CompanySearch;
use Illuminate\Support\Facades\Route;

Route::get('/', CompanySearch::class)->name('company.search');
Route::get('/companie/{cui}', [CompanyController::class, 'show'])
    ->name('company.show')
    ->where('cui', '[0-9]+');

Route::fallback(function () {
    abort(401, 'Unauthorized');
});
