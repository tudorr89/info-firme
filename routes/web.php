<?php

use App\Services\AnafEnrichmentService;
use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    abort(401, 'Unauthorized');
});
Route::get('/test', function () {
    return AnafEnrichmentService::enrich();
});