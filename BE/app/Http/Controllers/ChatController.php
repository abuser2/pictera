<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // dostat listy chatu pro prihlaseneho uzivatele
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $chats = Chat::where('user1_id', $userId)
            ->orWhere('user2_id', $userId)
            ->with(['user1', 'user2', 'messages' => function($q) {
                $q->latest()->limit(1); 
            }])
            ->get()
            ->map(function ($chat) use ($userId) {

                $partner = $chat->user1_id === $userId ? $chat->user2 : $chat->user1;
                return [
                    'id' => $chat->id,
                    'partner' => $partner,
                    'last_message' => $chat->messages->first(),
                    'updated_at' => $chat->updated_at,
                ];
            })
            ->sortByDesc('updated_at') 
            ->values();

        return response()->json($chats);
    }


    public function show(Request $request, Chat $chat)
    {

        if ($chat->user1_id !== $request->user()->id && $chat->user2_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($chat->messages()->with('sender')->get());
    }


    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $me = $request->user()->id;
        $other = $validated['recipient_id'];

        if ($me === $other) {
            return response()->json(['message' => 'Cannot chat with yourself'], 400);
        }


        $u1 = min($me, $other);
        $u2 = max($me, $other);


        $chat = Chat::firstOrCreate(['user1_id' => $u1, 'user2_id' => $u2]);
        $chat->touch(); 

        $message = $chat->messages()->create(['sender_id' => $me, 'content' => $validated['message']]);

        return response()->json($message->load('sender'), 201);
    }
}