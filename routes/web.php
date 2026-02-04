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
        Route::get('/{id}/members', [GroupController::class, 'getMembers'])->name('getMembers');
        Route::delete('/{groupId}/members/{userId}', [GroupController::class, 'removeMember'])->name('removeMember');
        Route::post('/{id}/messages', [GroupController::class, 'sendMessage'])->name('sendMessage');
        Route::get('/{id}/messages', [GroupController::class, 'getMessages'])->name('getMessages');
        Route::get('/search/users', [GroupController::class, 'searchUsers'])->name('searchUsers');
    });
    
    // Friendship routes
    Route::prefix('friendships')->name('friendships.')->group(function () {
        Route::post('/send', [App\Http\Controllers\FriendshipController::class, 'sendRequest'])->name('send');
        Route::post('/{id}/accept', [App\Http\Controllers\FriendshipController::class, 'acceptRequest'])->name('accept');
        Route::post('/{id}/reject', [App\Http\Controllers\FriendshipController::class, 'rejectRequest'])->name('reject');
        Route::delete('/{id}/cancel', [App\Http\Controllers\FriendshipController::class, 'cancelRequest'])->name('cancel');
        Route::delete('/{id}/remove', [App\Http\Controllers\FriendshipController::class, 'removeFriend'])->name('remove');
        Route::get('/pending', [App\Http\Controllers\FriendshipController::class, 'getPendingRequests'])->name('pending');
        Route::get('/sent', [App\Http\Controllers\FriendshipController::class, 'getSentRequests'])->name('sent');
        Route::get('/friends', [App\Http\Controllers\FriendshipController::class, 'getFriends'])->name('friends');
        Route::get('/status/{userId}', [App\Http\Controllers\FriendshipController::class, 'getStatus'])->name('status');
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
    
    // Message Reactions Routes
    Route::post('/reactions/toggle', [App\Http\Controllers\MessageReactionController::class, 'toggle'])->name('reactions.toggle');
    Route::get('/reactions/{messageId}', [App\Http\Controllers\MessageReactionController::class, 'getReactions'])->name('reactions.get');
    Route::post('/reactions/batch', [App\Http\Controllers\MessageReactionController::class, 'getBatchReactions'])->name('reactions.batch');
    Route::get('/reactions/frequent/emojis', [App\Http\Controllers\MessageReactionController::class, 'getFrequentEmojis'])->name('reactions.frequent');
    Route::get('/reactions/all/emojis', [App\Http\Controllers\MessageReactionController::class, 'getAllEmojis'])->name('reactions.all');
});

// Override Chatify setActiveStatus
Route::post('/chatify/setActiveStatus', [App\Http\Controllers\MessengerControllerOverride::class, 'setActiveStatus'])->name('chatify.setActiveStatus');

require __DIR__.'/auth.php';