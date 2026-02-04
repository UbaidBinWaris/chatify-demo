<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Group chat channels for real-time reactions and messages
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    // Check if user is a member of this group
    return \DB::table('group_members')
        ->where('group_id', $groupId)
        ->where('user_id', $user->id)
        ->exists();
});

// Chatify private channel
Broadcast::channel('chatify', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
