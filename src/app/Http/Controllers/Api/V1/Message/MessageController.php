<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Events\ReactionMessage;
use App\Http\Controllers\Controller;
use App\Models\Api\V1\Message\Message;
use App\Models\User;
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
                'reactions' => $message->reactions,
            ];
        }

        $messagesFormatted = $messages->map(function ($msg) {
            return [
                'id'       => $msg->id,
                'room_id'  => $msg->room_id,
                'name'     => $msg->user->name, // имя автора, если храните
                'message'     => $msg->message,
                // Собираем все реакции
                'reactions' => $msg->reactions->map(function ($user) {
                    return [
                        'user_id'   => $user->id,
                        'user_name' => $user->name,
                        'reaction'  => $user->pivot->reaction,
                    ];
                })->values()
            ];
        });

        return response()->json(['messages' => $messagesFormatted]);
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
            'user_id' => 'required|integer',
            'reaction'   => 'nullable|string',
        ]);

        // Находим сообщение
        $message = Message::findOrFail($data['message_id']);
        $message->load('user');

        $user = User::find($data['user_id']);

        $existing = $message->reactions()
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            // Смотрим, совпадает ли текущая реакция c переданной
            $currentReaction = $existing->pivot->reaction;

            if ($currentReaction === $data['reaction']) {
                // Нажали ту же реакцию -> убираем реакцию (detach)
                $message->reactions()->detach($user->id);
            } else {
                // Ставим новую реакцию (update pivot)
                $message->reactions()
                    ->updateExistingPivot($user->id, ['reaction' => $data['reaction']]);
            }
        } else {
            // Ещё нет реакции, просто attach
            $message->reactions()
                ->attach($user->id, ['reaction' => $data['reaction']]);
        }

        $allReactions = $message->reactions->map(function ($u) use ($user, $message) {
            return [
                'user_id'   => $u->id,
                'message_id' => $message->id,
                'user_name' => $u->name,
                'reaction'  => $u->pivot->reaction
            ];
        });

        // Рассылаем событие ReactionUpdated
        broadcast(new ReactionMessage(
            $message->id,
            $allReactions,
            $message->room_id,
        ))->toOthers();

        return response()->json([
            'status' => 'ok',
            'message' => $message
        ], 200);
    }
}
