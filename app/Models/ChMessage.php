<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Chatify\Traits\UUID;

class ChMessage extends Model
{
    use UUID;

    protected $fillable = [
        'id',
        'from_id',
        'to_id',
        'group_id',
        'body',
        'attachment',
        'seen',
    ];

    protected $casts = [
        'seen' => 'boolean',
    ];

    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class, 'message_id');
    }

    public function isGroupMessage()
    {
        return !is_null($this->group_id);
    }
}
