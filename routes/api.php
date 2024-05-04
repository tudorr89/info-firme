<?php

use App\Http\Controllers\API\CompanyController;
use Illuminate\Support\Facades\Route;

Route::get('info', CompanyController::class);

Route::fallback(function () {
    abort(401, 'Unauthorized');
});