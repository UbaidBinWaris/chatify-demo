<?php

namespace App\Http\Controllers;

use App\Models\MessageReaction;
use App\Models\ChMessage;
use App\Events\MessageReactionUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MessageReactionController extends Controller
{
    /**
     * Toggle a reaction on a message
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:ch_messages,id',
            'emoji' => 'required|string|max:10',
        ]);

        $messageId = $request->message_id;
        $emoji = $request->emoji;
        $userId = Auth::id();

        // Verify user has access to this message (either sender, receiver, or group member)
        $message = ChMessage::findOrFail($messageId);
        
        if ($message->group_id) {
            // Check if user is a member of the group
            $isMember = DB::table('group_members')
                ->where('group_id', $message->group_id)
                ->where('user_id', $userId)
                ->exists();
            
            if (!$isMember) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            // Check if user is sender or receiver
            if ($message->from_id != $userId && $message->to_id != $userId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // Toggle the reaction
        $result = MessageReaction::toggle($messageId, $userId, $emoji);

        // Clear cache for this message's reactions BEFORE getting updated data
        Cache::forget("message_reactions_{$messageId}");
        
        // Get updated reactions for this message (now fresh from DB)
        $reactions = $this->getMessageReactions($messageId);

        // Broadcast to other users in real-time
        if ($message->group_id) {
            // Broadcast to group channel
            broadcast(new MessageReactionUpdated(
                $messageId,
                $reactions,
                $result['action'],
                $userId,
                $emoji,
                $message->group_id
            ))->toOthers();
        } else {
            // Broadcast to both participants in private chat
            broadcast(new MessageReactionUpdated(
                $messageId,
                $reactions,
                $result['action'],
                $userId,
                $emoji,
                null
            ))->toOthers();
        }

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'reactions' => $reactions,
        ]);
    }

    /**
     * Get all reactions for a message
     */
    public function getReactions($messageId)
    {
        $reactions = $this->getMessageReactions($messageId);
        
        return response()->json([
            'success' => true,
            'reactions' => $reactions,
        ]);
    }
    
    /**
     * Get reactions for multiple messages at once (batch loading for performance)
     */
    public function getBatchReactions(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:ch_messages,id',
        ]);
        
        $messageIds = $request->message_ids;
        $results = [];
        
        foreach ($messageIds as $messageId) {
            $results[$messageId] = $this->getMessageReactions($messageId);
        }
        
        return response()->json([
            'success' => true,
            'reactions' => $results,
        ]);
    }

    /**
     * Get frequent emojis for the current user
     */
    public function getFrequentEmojis()
    {
        $userId = Auth::id();
        $frequentEmojis = MessageReaction::getFrequentEmojis($userId, 6);
        
        // Default emojis if user hasn't reacted before
        $defaultEmojis = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
        
        // Merge frequent with defaults, ensuring we have at least 6
        $emojis = array_unique(array_merge($frequentEmojis, $defaultEmojis));
        $emojis = array_slice($emojis, 0, 6);
        
        return response()->json([
            'success' => true,
            'emojis' => array_values($emojis),
        ]);
    }

    /**
     * Get all available emojis (for the "more" option)
     */
    public function getAllEmojis()
    {
        // WhatsApp-style emoji categories
        $emojis = [
            'frequent' => MessageReaction::getFrequentEmojis(Auth::id(), 6) ?: ['👍', '❤️', '😂', '😮', '😢', '🙏'],
            'smileys' => ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '☺️', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '😎', '🤓', '🧐'],
            'hearts' => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝'],
            'gestures' => ['👍', '👎', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👏', '🙌', '👐', '🤲', '🤝', '🙏'],
            'emotions' => ['😮', '😯', '😲', '😳', '🥺', '😦', '😧', '😨', '😰', '😥', '😢', '😭', '😱', '😖', '😣', '😞', '😓', '😩', '😫', '🥱', '😤', '😡', '😠', '🤬', '😈', '👿', '💀', '☠️'],
        ];

        return response()->json([
            'success' => true,
            'emojis' => $emojis,
        ]);
    }

    /**
     * Helper method to get formatted reactions for a message
     * Uses caching for better performance
     */
    private function getMessageReactions($messageId)
    {
        // Cache key for this message's reactions
        $cacheKey = "message_reactions_{$messageId}";
        
        return Cache::remember($cacheKey, 300, function () use ($messageId) {
            return MessageReaction::where('message_id', $messageId)
                ->with('user:id,name')
                ->get()
                ->groupBy('emoji')
                ->map(function ($group) {
                    return [
                        'emoji' => $group->first()->emoji,
                        'count' => $group->count(),
                        'users' => $group->map(function ($reaction) {
                            return [
                                'id' => $reaction->user_id,
                                'name' => $reaction->user->name,
                            ];
                        })->values(),
                        'hasReacted' => $group->contains('user_id', Auth::id()),
                    ];
                })
                ->values();
        });
    }
}
