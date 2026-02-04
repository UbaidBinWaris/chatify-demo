<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the message that was reacted to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ChMessage::class, 'message_id');
    }

    /**
     * Get the user who made the reaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get frequently used emojis for a user
     */
    public static function getFrequentEmojis($userId, $limit = 6)
    {
        return static::where('user_id', $userId)
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('emoji')
            ->toArray();
    }

    /**
     * Toggle a reaction (add if not exists, remove if exists, or update if different emoji)
     */
    public static function toggle($messageId, $userId, $emoji)
    {
        $reaction = static::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();

        if ($reaction) {
            if ($reaction->emoji === $emoji) {
                // Same emoji - remove reaction
                $reaction->delete();
                return ['action' => 'removed', 'reaction' => null];
            } else {
                // Different emoji - update reaction
                $reaction->update(['emoji' => $emoji]);
                return ['action' => 'updated', 'reaction' => $reaction];
            }
        } else {
            // No reaction - create new
            $reaction = static::create([
                'message_id' => $messageId,
                'user_id' => $userId,
                'emoji' => $emoji,
            ]);
            return ['action' => 'added', 'reaction' => $reaction];
        }
    }
}
