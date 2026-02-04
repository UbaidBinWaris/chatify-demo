<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FriendshipController extends Controller
{
    /**
     * Send a friend request
     */
    public function sendRequest(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|exists:users,id',
        ]);

        $friendId = $request->friend_id;
        $userId = Auth::id();

        // Can't send request to yourself
        if ($friendId == $userId) {
            return response()->json(['error' => 'You cannot send a friend request to yourself'], 400);
        }

        // Check if already friends or request exists
        $existingFriendship = Friendship::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $userId);
        })->first();

        if ($existingFriendship) {
            if ($existingFriendship->status === 'accepted') {
                return response()->json(['error' => 'You are already friends'], 400);
            }
            if ($existingFriendship->status === 'pending') {
                return response()->json(['error' => 'Friend request already sent'], 400);
            }
        }

        // Create friend request
        $friendship = Friendship::create([
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent successfully',
            'friendship' => $friendship,
        ]);
    }

    /**
     * Accept a friend request
     */
    public function acceptRequest($id)
    {
        $friendship = Friendship::findOrFail($id);

        // Only the receiver can accept
        if ($friendship->friend_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($friendship->status !== 'pending') {
            return response()->json(['error' => 'This request is no longer pending'], 400);
        }

        $friendship->update(['status' => 'accepted']);

        return response()->json([
            'success' => true,
            'message' => 'Friend request accepted',
            'friendship' => $friendship,
        ]);
    }

    /**
     * Reject a friend request
     */
    public function rejectRequest($id)
    {
        $friendship = Friendship::findOrFail($id);

        // Only the receiver can reject
        if ($friendship->friend_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($friendship->status !== 'pending') {
            return response()->json(['error' => 'This request is no longer pending'], 400);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friend request rejected',
        ]);
    }

    /**
     * Cancel a sent friend request
     */
    public function cancelRequest($id)
    {
        $friendship = Friendship::findOrFail($id);

        // Only the sender can cancel
        if ($friendship->user_id != Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($friendship->status !== 'pending') {
            return response()->json(['error' => 'This request is no longer pending'], 400);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friend request cancelled',
        ]);
    }

    /**
     * Remove a friend
     */
    public function removeFriend($id)
    {
        $friendship = Friendship::where(function ($query) use ($id) {
            $query->where('user_id', Auth::id())->where('friend_id', $id);
        })->orWhere(function ($query) use ($id) {
            $query->where('user_id', $id)->where('friend_id', Auth::id());
        })->where('status', 'accepted')->first();

        if (!$friendship) {
            return response()->json(['error' => 'Friendship not found'], 404);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friend removed successfully',
        ]);
    }

    /**
     * Get pending friend requests
     */
    public function getPendingRequests()
    {
        $requests = Friendship::with('user')
            ->where('friend_id', Auth::id())
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'requests' => $requests,
        ]);
    }

    /**
     * Get sent friend requests
     */
    public function getSentRequests()
    {
        $requests = Friendship::with('friend')
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'requests' => $requests,
        ]);
    }

    /**
     * Get all friends
     */
    public function getFriends()
    {
        $userId = Auth::id();

        $friends = User::select('users.*')
            ->join('friendships', function ($join) use ($userId) {
                $join->on(function ($query) use ($userId) {
                    $query->where('friendships.user_id', '=', $userId)
                          ->whereColumn('friendships.friend_id', '=', 'users.id');
                })->orOn(function ($query) use ($userId) {
                    $query->where('friendships.friend_id', '=', $userId)
                          ->whereColumn('friendships.user_id', '=', 'users.id');
                });
            })
            ->where('friendships.status', 'accepted')
            ->get();

        return response()->json([
            'success' => true,
            'friends' => $friends,
        ]);
    }

    /**
     * Get friendship status with a specific user
     */
    public function getStatus($userId)
    {
        $currentUser = Auth::user();
        $status = $currentUser->getFriendshipStatus($userId);

        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }
}
