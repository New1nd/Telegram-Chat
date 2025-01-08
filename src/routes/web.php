<?php

use App\Events\PrivateChat;
use App\Http\Controllers\Api\V1\Message\MessageController;
use App\Models\Api\V1\Message\Message;
use App\Models\Api\V1\Room\Room;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('messages', function(Illuminate\Http\Request $request) {
    $message = Message::create([
        'room_id' => $request->input('room_id'),
        'user_id' => auth()->id(),
        'message' => $request->input('message'),
    ]);

    PrivateChat::dispatch($message);
});

Route::get('/room/{room}', function(Room $room) {
    return view('room', ['room' => $room]);
})->name('index-room');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/getMessage/{room_id}', [MessageController::class, 'get'])->name('message-get');

Route::post('/messages/reaction', [MessageController::class, 'setReaction']);

Route::get('/invite/{room_id}', [\App\Http\Controllers\Api\V1\Room\RoomController::class, 'inviteUser'])->name('inviteUser-room');
