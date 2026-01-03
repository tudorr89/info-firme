<?php

use App\Livewire\CompanySearch;
use Illuminate\Support\Facades\Route;

Route::get('/', CompanySearch::class)->name('company.search');

Route::fallback(function () {
    abort(401, 'Unauthorized');
});
