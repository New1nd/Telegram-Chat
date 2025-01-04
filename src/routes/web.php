<?php

use App\Models\Api\V1\Room\Room;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('messages', function(Illuminate\Http\Request $request) {
    App\Events\PrivateChat::dispatch($request->all());
});

Route::get('/room/{room}', function(Room $room) {
    return view('room', ['room' => $room]);
})->name('index-room');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');



Route::get('/invite/{room_id}', [\App\Http\Controllers\Api\V1\Room\RoomController::class, 'inviteUser'])->name('inviteUser-room');
