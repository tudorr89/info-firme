<?php

use App\Services\LastUpdateService;
use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    abort(401, 'Unauthorized');
});