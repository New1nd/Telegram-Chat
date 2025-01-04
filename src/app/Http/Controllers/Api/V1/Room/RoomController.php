<?php

namespace App\Http\Controllers\Api\V1\Room;

use App\Http\Controllers\Controller;
use App\Models\Api\V1\Room\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function inviteUser($roomId, $userId = ''): \Illuminate\Http\RedirectResponse
    {
        $room = Room::find($roomId);

        if(!$room){
            Room::create([]);
        }

        if($userId === ''){
            if(Auth::check()){
                $userId = Auth::user()->id;
            }else{
                return redirect('/register');
            }
        }

        if($room){
            // if(!isset($room->users[0]) and $room->users[0]->id !== $userId) {
            $room->users()->attach(Auth::user()->id);
            // }
        }

        return redirect()->route('index-room', ['room' => $roomId]);
    }
}
