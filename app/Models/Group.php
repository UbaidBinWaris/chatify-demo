<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'avatar',
        'description',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChMessage::class, 'group_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChMessage::class, 'group_id')->latest('created_at');
    }

    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    public function isMember($userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function isAdmin($userId): bool
    {
        return $this->members()->where('user_id', $userId)->wherePivot('role', 'admin')->exists();
    }
}
