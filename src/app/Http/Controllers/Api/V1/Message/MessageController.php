<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Events\ReactionMessage;
use App\Http\Controllers\Controller;
use App\Models\Api\V1\Message\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{

    public function get($room_id)
    {
        $messages = Message::where(['room_id' => $room_id])->orderBy('created_at')->take(100)->get();

        $arrayMessages = [];

        foreach ($messages as $message){
            $arrayMessages[] = [
                'id' => $message->id,
                'name' => $message->user->name,
                'message' => $message->message,
                'reaction' => $message->reaction,
            ];
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

    /**
     * Установить/обновить реакцию.
     */
    public function setReaction(Request $request)
    {
        // Валидируем
        $data = $request->validate([
            'message_id' => 'required|integer',
            'reaction'   => 'nullable|string',
        ]);

        // Находим сообщение
        $message = Message::findOrFail($data['message_id']);

        // Обновляем реакцию

        if ($message->reaction === $data['reaction']){
            $message->reaction = null;
        } else {
            $message->reaction = $data['reaction'];
        }

        $message->save();

        // Сбрасываем событие ReactionUpdated, чтобы все в комнате узнали
        broadcast(new ReactionMessage($message->id, $message->reaction, $message->room_id))->toOthers();

        return response()->json([
            'status' => 'ok',
            'message' => $message
        ], 200);
    }
}
