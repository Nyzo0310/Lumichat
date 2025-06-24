<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Chat::where('user_id', Auth::id())->orderBy('sent_at')->get();
        return view('chat', compact('chats'));
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // 1. Save the user message
            Chat::create([
                'user_id' => $user->id,
                'sender' => 'user',
                'message' => $request->message,
                'sent_at' => now(),
            ]);

            // 2. Send to Rasa server
            $rasaResponse = Http::post('http://localhost:5005/webhooks/rest/webhook', [
                'sender' => (string) $user->id,
                'message' => $request->message,
            ]);

            if (!$rasaResponse->successful()) {
                \Log::error('Rasa error', ['response' => $rasaResponse->body()]);
                return response()->json(['error' => 'Failed to get response from Rasa'], 500);
            }

            $botReplies = $rasaResponse->json();
            $botTexts = [];

            foreach ($botReplies as $reply) {
                if (isset($reply['text'])) {
                    Chat::create([
                        'user_id' => $user->id,
                        'sender' => 'bot',
                        'message' => $reply['text'],
                        'sent_at' => now(),
                    ]);
                    $botTexts[] = $reply['text'];
                }
            }

            return response()->json([
                'bot_reply' => $botTexts
            ]);
        } catch (\Exception $e) {
            \Log::error('ChatController@store failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
