<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Controller;
use App\Models\Api\V1\Message\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{

    public function get($room_id)
    {
        $messages = Message::where(['room_id' => $room_id])->take(100)->get();

        $arrayMessages = [];

        foreach ($messages as $message){
            $arrayMessages[] = $message->user->name . ': ' . $message->message;
        }

        return response()->json(['messages' => $arrayMessages]);
    }

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
}
