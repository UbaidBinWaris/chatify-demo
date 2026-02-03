<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Group routes
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        Route::get('/{id}', [GroupController::class, 'show'])->name('show');
        Route::put('/{id}', [GroupController::class, 'update'])->name('update');
        Route::delete('/{id}', [GroupController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/members', [GroupController::class, 'addMembers'])->name('addMembers');
        Route::delete('/{groupId}/members/{userId}', [GroupController::class, 'removeMember'])->name('removeMember');
        Route::post('/{id}/messages', [GroupController::class, 'sendMessage'])->name('sendMessage');
        Route::get('/{id}/messages', [GroupController::class, 'getMessages'])->name('getMessages');
        Route::get('/search/users', [GroupController::class, 'searchUsers'])->name('searchUsers');
    });
});


// Optimized Chatify Routes Override
Route::group(['prefix' => 'chatify', 'middleware' => ['web', 'auth']], function () {
    Route::post('/getContacts', [App\Http\Controllers\OptimizedMessagesController::class, 'getContacts']);
    Route::post('/fetchMessages', [App\Http\Controllers\OptimizedMessagesController::class, 'fetch']);
    Route::get('/search', [App\Http\Controllers\OptimizedMessagesController::class, 'search']);
    Route::post('/favorites', [App\Http\Controllers\OptimizedMessagesController::class, 'getFavorites']);
    Route::post('/favorite', [App\Http\Controllers\OptimizedMessagesController::class, 'favorite']);
    Route::post('/shared', [App\Http\Controllers\OptimizedMessagesController::class, 'sharedPhotos']);
});

// Override Chatify setActiveStatus
Route::post('/chatify/setActiveStatus', [App\Http\Controllers\MessengerControllerOverride::class, 'setActiveStatus'])->name('chatify.setActiveStatus');

require __DIR__.'/auth.php';