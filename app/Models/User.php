<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_seen_at',
        'timezone',
        'detected_timezone',
        'last_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the groups the user belongs to
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Friend requests sent by this user
     */
    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Friend requests received by this user
     */
    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Get all accepted friends
     */
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
                    ->wherePivot('status', 'accepted')
                    ->withPivot('status')
                    ->withTimestamps()
                    ->union(
                        $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                            ->wherePivot('status', 'accepted')
                            ->withPivot('status')
                            ->withTimestamps()
                    );
    }

    /**
     * Check if this user is friends with another user
     */
    public function isFriendWith($userId)
    {
        return Friendship::where(function ($query) use ($userId) {
            $query->where('user_id', $this->id)
                  ->where('friend_id', $userId);
        })->orWhere(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('friend_id', $this->id);
        })->where('status', 'accepted')->exists();
    }

    /**
     * Check if this user has sent a friend request to another user
     */
    public function hasSentFriendRequestTo($userId)
    {
        return $this->sentFriendRequests()
                    ->where('friend_id', $userId)
                    ->where('status', 'pending')
                    ->exists();
    }

    /**
     * Check if this user has received a friend request from another user
     */
    public function hasReceivedFriendRequestFrom($userId)
    {
        return $this->receivedFriendRequests()
                    ->where('user_id', $userId)
                    ->where('status', 'pending')
                    ->exists();
    }

    /**
     * Get friendship status with another user
     */
    public function getFriendshipStatus($userId)
    {
        $friendship = Friendship::where(function ($query) use ($userId) {
            $query->where('user_id', $this->id)
                  ->where('friend_id', $userId);
        })->orWhere(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->where('friend_id', $this->id);
        })->first();

        if (!$friendship) {
            return 'none';
        }

        if ($friendship->status === 'accepted') {
            return 'friends';
        }

        if ($friendship->user_id == $this->id) {
            return 'request_sent';
        }

        return 'request_received';
    }
}
