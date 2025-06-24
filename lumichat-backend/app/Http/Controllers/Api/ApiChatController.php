<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ApiChatController extends Controller
{
    public function handleMessage(Request $request)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        // Replace with your actual Rasa server URL
        $rasaUrl = 'http://localhost:5005/webhooks/rest/webhook';

        $response = Http::post($rasaUrl, [
            'sender' => $request->user()->id,
            'message' => $request->message,
        ]);

        $botMessages = collect($response->json())
                        ->pluck('text')
                        ->filter()
                        ->values();

        return response()->json([
            'bot_reply' => $botMessages
        ]);
    }
}
