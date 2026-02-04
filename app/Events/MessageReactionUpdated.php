<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $reactions;
    public $action;
    public $userId;
    public $emoji;
    public $groupId;

    /**
     * Create a new event instance.
     */
    public function __construct($messageId, $reactions, $action, $userId, $emoji, $groupId = null)
    {
        $this->messageId = $messageId;
        $this->reactions = $reactions;
        $this->action = $action;
        $this->userId = $userId;
        $this->emoji = $emoji;
        $this->groupId = $groupId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        $channels = [];
        
        if ($this->groupId) {
            // Broadcast to all group members
            $channels[] = new PrivateChannel('group.' . $this->groupId);
        } else {
            // Broadcast to both participants in private chat
            // The controller will determine the participant IDs
            $channels[] = new PrivateChannel('chatify');
        }
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'message.reaction.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'reactions' => $this->reactions,
            'action' => $this->action,
            'user_id' => $this->userId,
            'emoji' => $this->emoji,
            'group_id' => $this->groupId,
        ];
    }
}
