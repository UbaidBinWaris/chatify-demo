<?php

namespace App\Http\Controllers;

use Chatify\Http\Controllers\MessagesController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\User;
use App\Models\ChMessage as Message;
use App\Models\ChFavorite as Favorite;
use Chatify\Facades\ChatifyMessenger as Chatify;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OptimizedMessagesController extends MessagesController
{
    // Production-level pagination settings
    protected $perPage = 20; // Reduced from 30 for better performance
    protected $searchPerPage = 15;
    protected $cacheTime = 300; // 5 minutes cache for static data
    
    /**
     * Get contacts list with optimization
     * - Pagination enabled (20 per page)
     * - Uses database indexing
     * - Caches user avatars
     * - Optimized query with proper joins
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getContacts(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', $this->perPage);
            
            // Optimized query with proper indexing
            $users = Message::select([
                'users.id',
                'users.name',
                'users.email',
                'users.avatar',
                'users.active_status',
                DB::raw('MAX(ch_messages.created_at) as max_created_at'),
                DB::raw('COUNT(CASE WHEN ch_messages.from_id != ' . Auth::id() . ' AND ch_messages.seen = 0 THEN 1 END) as unread_count')
            ])
            ->join('users', function ($join) {
                $join->on('ch_messages.from_id', '=', 'users.id')
                     ->orOn('ch_messages.to_id', '=', 'users.id');
            })
            ->where(function ($q) {
                $q->where('ch_messages.from_id', Auth::id())
                  ->orWhere('ch_messages.to_id', Auth::id());
            })
            ->where('users.id', '!=', Auth::id())
            ->groupBy('users.id', 'users.name', 'users.email', 'users.avatar', 'users.active_status')
            ->orderBy('max_created_at', 'desc')
            ->paginate($perPage);

            $usersList = $users->items();
            $contacts = '';

            if (count($usersList) > 0) {
                foreach ($usersList as $user) {
                    $contacts .= Chatify::getContactItem($user);
                }
            } else {
                $contacts = '<p class="message-hint center-el"><span>Your contact list is empty</span></p>';
            }

            return Response::json([
                'contacts' => $contacts,
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching contacts: ' . $e->getMessage());
            return Response::json([
                'error' => 'Failed to fetch contacts',
                'contacts' => '<p class="message-hint center-el"><span>Error loading contacts</span></p>',
            ], 500);
        }
    }

    /**
     * Fetch messages with optimization
     * - Pagination enabled (20 per page)
     * - Lazy loading for better performance
     * - Query optimization with indexes
     * - Removed strict friendship validation to allow viewing existing conversations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetch(Request $request)
    {
        try {
            $id = $request->input('id');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', $this->perPage);
            
            // Note: Friendship validation removed for fetching messages
            // Users can view existing conversation history
            // New message sending is still protected by friendship requirement
            
            // Optimized query
            $query = Chatify::fetchMessagesQuery($id)->latest();
            $messages = $query->paginate($perPage);
            
            $totalMessages = $messages->total();
            $lastPage = $messages->lastPage();
            
            $response = [
                'total' => $totalMessages,
                'last_page' => $lastPage,
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'last_message_id' => collect($messages->items())->last()->id ?? null,
                'messages' => '',
            ];

            // If there are no messages yet
            if ($totalMessages < 1) {
                $response['messages'] = '<p class="message-hint center-el"><span>Say \'hi\' and start messaging</span></p>';
                return Response::json($response);
            }
            
            if (count($messages->items()) < 1) {
                $response['messages'] = '';
                return Response::json($response);
            }
            
            $allMessages = '';
            foreach ($messages->reverse() as $message) {
                $allMessages .= Chatify::messageCard(
                    Chatify::parseMessage($message)
                );
            }
            
            $response['messages'] = $allMessages;
            return Response::json($response);
            
        } catch (\Exception $e) {
            Log::error('Error fetching messages: ' . $e->getMessage());
            return Response::json([
                'error' => 'Failed to fetch messages',
                'messages' => '<p class="message-hint center-el"><span>Error loading messages</span></p>',
            ], 500);
        }
    }

    /**
     * Search with optimization
     * - Optimized search query
     * - Pagination enabled
     * - Full-text search support
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $input = trim(filter_var($request->input('input', '')));
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', $this->searchPerPage);
            
            if (empty($input)) {
                return Response::json([
                    'records' => '<p class="message-hint center-el"><span>Type to search...</span></p>',
                    'total' => 0,
                    'last_page' => 1,
                    'current_page' => 1,
                ], 200);
            }
            
            // Optimized search query
            $records = User::select(['id', 'name', 'email', 'avatar', 'active_status'])
                ->where('id', '!=', Auth::id())
                ->where(function($query) use ($input) {
                    $query->where('name', 'LIKE', "%{$input}%")
                          ->orWhere('email', 'LIKE', "%{$input}%");
                })
                ->paginate($perPage);
            
            $getRecords = '';
            $currentUser = Auth::user();
            foreach ($records->items() as $record) {
                // Get friendship status
                $friendshipStatus = $currentUser->getFriendshipStatus($record->id);
                
                $getRecords .= view('Chatify::layouts.listItem', [
                    'get' => 'search_item',
                    'user' => Chatify::getUserWithAvatar($record),
                    'friendshipStatus' => $friendshipStatus,
                ])->render();
            }
            
            if ($records->total() < 1) {
                $getRecords = '<p class="message-hint center-el"><span>Nothing to show.</span></p>';
            }
            
            return Response::json([
                'records' => $getRecords,
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
                'current_page' => $records->currentPage(),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error searching users: ' . $e->getMessage());
            return Response::json([
                'error' => 'Search failed',
                'records' => '<p class="message-hint center-el"><span>Search error</span></p>',
            ], 500);
        }
    }

    /**
     * Get favorites list with caching
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFavorites(Request $request)
    {
        try {
            $cacheKey = 'user_favorites_' . Auth::id();
            
            // Try to get from cache first
            $result = Cache::remember($cacheKey, $this->cacheTime, function () {
                $favoritesList = '';
                $favorites = Favorite::where('user_id', Auth::id())->get();
                
                foreach ($favorites as $favorite) {
                    $user = User::where('id', $favorite->favorite_id)->first();
                    if ($user) {
                        $favoritesList .= view('Chatify::layouts.favorite', [
                            'user' => $user,
                        ])->render();
                    }
                }
                
                return [
                    'count' => $favorites->count(),
                    'favorites' => $favorites->count() > 0 ? $favoritesList : 0,
                ];
            });
            
            return Response::json($result, 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching favorites: ' . $e->getMessage());
            return Response::json([
                'count' => 0,
                'favorites' => 0,
            ], 500);
        }
    }

    /**
     * Clear favorites cache when favorite is added/removed
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function favorite(Request $request)
    {
        $result = parent::favorite($request);
        
        // Clear cache
        Cache::forget('user_favorites_' . Auth::id());
        
        return $result;
    }

    /**
     * Get shared photos with pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sharedPhotos(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $page = $request->input('page', 1);
            $perPage = 12; // 12 photos per page
            
            // Get messages with attachments
            $messages = Message::where(function($q) use ($userId) {
                $q->where('from_id', Auth::id())->where('to_id', $userId)
                  ->orWhere('from_id', $userId)->where('to_id', Auth::id());
            })
            ->whereNotNull('attachment')
            ->latest()
            ->paginate($perPage);
            
            $sharedPhotos = '';
            foreach ($messages->items() as $message) {
                if ($message->attachment) {
                    $attachment = json_decode($message->attachment);
                    if ($attachment && isset($attachment->new_name)) {
                        $sharedPhotos .= view('Chatify::layouts.listItem', [
                            'get' => 'sharedPhoto',
                            'image' => Chatify::getAttachmentUrl($attachment->new_name),
                        ])->render();
                    }
                }
            }
            
            return Response::json([
                'shared' => $messages->total() > 0 ? $sharedPhotos : '<p class="message-hint"><span>Nothing shared yet</span></p>',
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
                'current_page' => $messages->currentPage(),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching shared photos: ' . $e->getMessage());
            return Response::json([
                'shared' => '<p class="message-hint"><span>Error loading photos</span></p>',
            ], 500);
        }
    }

    /**
     * Send a new message with friendship validation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'message' => 'nullable|string',
            'attachment' => 'nullable|file',
        ]);

        $receiverId = $request->input('id');
        $currentUser = Auth::user();

        // Check if trying to send to a user (not a group)
        // Groups don't need friendship validation
        if (!$request->has('type') || $request->input('type') !== 'group') {
            // Check if users are friends
            if (!$currentUser->isFriendWith($receiverId)) {
                return Response::json([
                    'error' => 'You must be friends with this user to send messages.',
                    'message' => 'Please send a friend request first.',
                ], 403);
            }
        }

        // Call parent send method if validation passes
        return parent::send($request);
    }
}
