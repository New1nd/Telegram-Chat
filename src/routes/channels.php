<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('room.{room_id}', function ($user, $room_id) {
//    if($user->rooms->contains($room_id)){
        return $user;
//    }
});
