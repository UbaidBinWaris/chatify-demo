<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimezoneController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Timezone API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/timezone', [TimezoneController::class, 'update']);
    Route::get('/user/timezone', [TimezoneController::class, 'show']);
    Route::get('/timezones', [TimezoneController::class, 'list']);
});
