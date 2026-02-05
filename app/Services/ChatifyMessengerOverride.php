<?php

namespace App\Services;

use Chatify\ChatifyMessenger as BaseChatifyMessenger;
use App\Models\ChMessage as Message;
use App\Services\TimezoneService;
use Illuminate\Support\Facades\Auth;
use Exception;

class ChatifyMessengerOverride extends BaseChatifyMessenger
{
    /**
     * Fetch & parse message and return the message card
     * view as a response.
     *
     * @param Message $prefetchedMessage
     * @param int $id
     * @return array
     */
    public function parseMessage($prefetchedMessage = null, $id = null)
    {
        $msg = null;
        $attachment = null;
        $attachment_type = null;
        $attachment_title = null;
        if (!!$prefetchedMessage) {
            $msg = $prefetchedMessage;
        } else {
            $msg = Message::where('id', $id)->first();
            if(!$msg){
                return [];
            }
        }
        if (isset($msg->attachment)) {
            $attachmentOBJ = json_decode($msg->attachment);
            $attachment = $attachmentOBJ->new_name;
            $attachment_title = htmlentities(trim($attachmentOBJ->old_name), ENT_QUOTES, 'UTF-8');
            $ext = pathinfo($attachment, PATHINFO_EXTENSION);
            
            // Determine attachment type
            if (in_array($ext, $this->getAllowedImages())) {
                $attachment_type = 'image';
            } elseif (in_array($ext, $this->getAllowedAudio())) {
                $attachment_type = 'audio';
            } else {
                $attachment_type = 'file';
            }
        }

        // Get user's timezone
        $timezone = Auth::check() ? Auth::user()->timezone : 'UTC';
        $timeDisplay = TimezoneService::getTimeDisplay($msg->created_at, $timezone);

        return [
            'id' => $msg->id,
            'from_id' => $msg->from_id,
            'to_id' => $msg->to_id,
            'message' => $msg->body,
            'attachment' => (object) [
                'file' => $attachment,
                'title' => $attachment_title,
                'type' => $attachment_type
            ],
            'timeAgo' => $timeDisplay['relative'], // Use relative time instead of diffForHumans
            'timeFormatted' => $timeDisplay['formatted'], // Full formatted time
            'created_at' => $timeDisplay['iso'], // ISO format for consistency
            'timestamp' => $timeDisplay['timestamp'], // Unix timestamp
            'isSender' => ($msg->from_id == Auth::user()->id),
            'seen' => $msg->seen,
        ];
    }

    /**
     * Get user list's item data [Contact Item]
     * (e.g. User data, Last message, Unseen Counter...)
     *
     * @param int $messenger_id
     * @param Collection $user
     * @return string
     */
    public function getContactItem($user)
    {
        try {
            // get last message
            $lastMessage = $this->getLastMessageQuery($user->id);
            // Get Unseen messages counter
            $unseenCounter = $this->countUnseenMessages($user->id);
            
            if ($lastMessage) {
                // Get user's timezone
                $timezone = Auth::check() ? Auth::user()->timezone : 'UTC';
                $timeDisplay = TimezoneService::getTimeDisplay($lastMessage->created_at, $timezone);
                
                $lastMessage->timeAgo = $timeDisplay['relative'];
                $lastMessage->timeFormatted = $timeDisplay['formatted'];
                $lastMessage->created_at = $timeDisplay['iso'];
                $lastMessage->timestamp = $timeDisplay['timestamp'];
            }
            
            return view('Chatify::layouts.listItem', [
                'get' => 'users',
                'user' => $this->getUserWithAvatar($user),
                'lastMessage' => $lastMessage,
                'unseenCounter' => $unseenCounter,
                ])->render();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }
}
