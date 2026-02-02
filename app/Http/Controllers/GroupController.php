<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\ChMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    /**
     * Display a listing of groups the user is part of
     */
    public function index()
    {
        $user = Auth::user();
        $groups = $user->groups()
            ->with(['creator', 'members', 'latestMessage.from'])
            ->get()
            ->sortByDesc(function($group) {
                return $group->latestMessage ? $group->latestMessage->created_at : $group->created_at;
            })
            ->values();
        
        return response()->json([
            'groups' => $groups->map(function($group) {
                $lastMsg = $group->latestMessage;
                $senderName = $lastMsg && $lastMsg->from ? ($lastMsg->from->id == Auth::id() ? 'You' : $lastMsg->from->name) : '';
                
                // Safe message preview
                $messagePreview = null;
                if ($lastMsg) {
                    if ($lastMsg->attachment) {
                        $messagePreview = 'Attachment';
                    } elseif ($lastMsg->body) {
                        $messagePreview = Str::limit($lastMsg->body, 30);
                    }
                }
                
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'avatar' => $group->avatar ? asset('storage/' . $group->avatar) : asset('images/group-default.png'),
                    'description' => $group->description,
                    'created_by' => $group->creator->name,
                    'members_count' => $group->members->count(),
                    'created_at' => $group->created_at->diffForHumans(),
                    'last_message' => $messagePreview,
                    'last_message_time' => $lastMsg ? $lastMsg->created_at->diffForHumans() : null,
                    'last_message_sender' => $senderName
                ];
            })
        ]);
    }

    /**
     * Store a newly created group
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        // Add creator as admin
        $group->members()->attach(Auth::id(), ['role' => 'admin']);

        // Add other members
        foreach ($request->members as $memberId) {
            if ($memberId != Auth::id()) {
                $group->members()->attach($memberId, ['role' => 'member']);
            }
        }

        $group->load('members', 'creator');

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully',
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'avatar' => $group->avatar ? asset('storage/' . $group->avatar) : asset('images/group-default.png'),
                'description' => $group->description,
                'members_count' => $group->members->count(),
            ]
        ]);
    }

    /**
     * Display the specified group
     */
    public function show($id)
    {
        $group = Group::with(['members', 'creator'])->findOrFail($id);
        
        // Check if user is a member
        if (!$group->isMember(Auth::id())) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        return response()->json([
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'avatar' => $group->avatar ? asset('storage/' . $group->avatar) : asset('images/group-default.png'),
                'description' => $group->description,
                'created_by' => $group->creator->name,
                'is_admin' => $group->isAdmin(Auth::id()),
                'members' => $group->members->map(function($member) use ($group) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'avatar' => $member->avatar ?? asset('images/default-avatar.png'),
                        'role' => $member->pivot->role,
                        'is_admin' => $member->pivot->role === 'admin',
                    ];
                }),
            ]
        ]);
    }

    /**
     * Add members to a group
     */
    public function addMembers(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        // Check if user is admin
        if (!$group->isAdmin(Auth::id())) {
            return response()->json(['error' => 'Only admins can add members'], 403);
        }

        $request->validate([
            'members' => 'required|array|min:1',
            'members.*' => 'exists:users,id',
        ]);

        $addedMembers = [];
        foreach ($request->members as $memberId) {
            if (!$group->isMember($memberId)) {
                $group->members()->attach($memberId, ['role' => 'member']);
                $user = User::find($memberId);
                $addedMembers[] = $user->name;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Members added successfully',
            'added_members' => $addedMembers,
        ]);
    }

    /**
     * Remove a member from the group
     */
    public function removeMember($groupId, $userId)
    {
        $group = Group::findOrFail($groupId);
        
        // Check if user is admin or removing themselves
        if (!$group->isAdmin(Auth::id()) && Auth::id() != $userId) {
            return response()->json(['error' => 'You do not have permission to remove this member'], 403);
        }

        // Can't remove the creator
        if ($group->created_by == $userId) {
            return response()->json(['error' => 'Cannot remove the group creator'], 400);
        }

        $group->members()->detach($userId);

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully',
        ]);
    }

    /**
     * Send a message to the group
     */
    public function sendMessage(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        // Check if user is a member
        if (!$group->isMember(Auth::id())) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        $request->validate([
            'message' => 'required_without:attachment|string',
            'attachment' => 'nullable|file|max:150000',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $message = ChMessage::create([
            'id' => Str::uuid(),
            'from_id' => Auth::id(),
            'to_id' => 0, // Not used for group messages
            'group_id' => $group->id,
            'body' => $request->message,
            'attachment' => $attachmentPath,
        ]);

        $message->load('from');

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'from_id' => $message->from_id,
                'from_name' => $message->from->name,
                'from_avatar' => $message->from->avatar ?? asset('images/default-avatar.png'),
                'attachment' => $message->attachment ? asset('storage/' . $message->attachment) : null,
                'created_at' => $message->created_at->diffForHumans(),
            ]
        ]);
    }

    /**
     * Get messages for a group
     */
    public function getMessages($id)
    {
        $group = Group::findOrFail($id);
        
        // Check if user is a member
        if (!$group->isMember(Auth::id())) {
            return response()->json(['error' => 'You are not a member of this group'], 403);
        }

        $messages = ChMessage::where('group_id', $id)
            ->with('from')
            ->oldest('created_at')
            ->get();

        return response()->json([
            'messages' => $messages->map(function($message) {
                return [
                    'id' => $message->id,
                    'from_id' => $message->from_id,
                    'group_id' => $message->group_id,
                    'body' => $message->body,
                    'attachment' => $message->attachment ? asset('storage/' . $message->attachment) : null,
                    'created_at' => $message->created_at->diffForHumans(),
                    'from' => [
                        'id' => $message->from->id,
                        'name' => $message->from->name,
                    ]
                ];
            }),
        ]);
    }

    /**
     * Update group details
     */
    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        // Check if user is admin
        if (!$group->isAdmin(Auth::id())) {
            return response()->json(['error' => 'Only admins can update group details'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->has('name')) {
            $group->name = $request->name;
        }

        if ($request->has('description')) {
            $group->description = $request->description;
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($group->avatar) {
                Storage::disk('public')->delete($group->avatar);
            }
            $group->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $group->save();

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully',
            'group' => $group,
        ]);
    }

    /**
     * Delete a group
     */
    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        
        // Only creator can delete the group
        if ($group->created_by != Auth::id()) {
            return response()->json(['error' => 'Only the group creator can delete this group'], 403);
        }

        // Delete avatar if exists
        if ($group->avatar) {
            Storage::disk('public')->delete($group->avatar);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully',
        ]);
    }

    /**
     * Search for users to add to group
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('query');
        $groupId = $request->get('group_id');
        
        $users = User::where('id', '!=', Auth::id())
            ->where('name', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        // If group_id is provided, filter out existing members
        if ($groupId) {
            $group = Group::find($groupId);
            if ($group) {
                $memberIds = $group->members()->pluck('user_id')->toArray();
                $users = $users->whereNotIn('id', $memberIds);
            }
        }

        return response()->json([
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? asset('images/default-avatar.png'),
                ];
            })
        ]);
    }
}

