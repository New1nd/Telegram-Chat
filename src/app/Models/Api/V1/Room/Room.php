<?php

namespace App\Models\Api\V1\Room;

use App\Models\Api\V1\Message\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
