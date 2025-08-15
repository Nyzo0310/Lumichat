<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index()
    {
        if (! session('chat_session_id')) {
            return redirect()->route('chat.new');
        }

        // Pull & clear greeting flag
        $showGreeting = session()->pull('show_greeting', false);

        // Load current session chats (decrypt each)
        $chats = Chat::where('user_id', Auth::id())
            ->where('chat_session_id', session('chat_session_id'))
            ->orderBy('sent_at')
            ->get()
            ->map(function ($chat) {
                try {
                    $chat->message = Crypt::decryptString($chat->message);
                } catch (\Throwable $e) {
                    $chat->message = '[Encrypted]';
                }
                return $chat;
            });

        return view('chat', compact('chats','showGreeting'));
    }

    public function newChat()
    {
        $userId = Auth::id();

        // If there's an active session id already, and it has no messages, reuse it
        if (session('chat_session_id')) {
            $session = ChatSession::where('id', session('chat_session_id'))
                ->where('user_id', $userId)
                ->first();
            if ($session) {
                $hasMessages = Chat::where('chat_session_id', $session->id)->exists();
                if (! $hasMessages) {
                    session(['show_greeting' => true]);
                    return redirect()->route('chat.index');
                }
            }
        }

        // Or, if the user has any *latest* session with no messages, reuse that one
        $reuse = ChatSession::where('user_id', $userId)
            ->whereDoesntHave('chats')  // requires chats() relation on ChatSession (you already have it)
            ->latest('id')
            ->first();

        if ($reuse) {
            session([
                'chat_session_id' => $reuse->id,
                'show_greeting'   => true,
            ]);
            return redirect()->route('chat.index');
        }

        // Otherwise create a fresh session
        $session = ChatSession::create([
            'user_id'       => $userId,
            'topic_summary' => 'Starting conversation...',
        ]);

        session([
            'chat_session_id' => $session->id,
            'show_greeting'   => true,
        ]);

        return redirect()->route('chat.index');
    }

    public function store(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        // Use active session; if none (e.g., direct from greeting), create one
        $sessionId = session('chat_session_id');
        if (! $sessionId) {
            $s = ChatSession::create([
                'user_id'       => Auth::id(),
                'topic_summary' => 'Starting conversation...',
            ]);
            $sessionId = $s->id;
            session(['chat_session_id' => $sessionId]);
        }

        $text = $request->message;

        // Save USER message (encrypted) with timestamp
        $userMsg = Chat::create([
            'user_id'         => Auth::id(),
            'chat_session_id' => $sessionId,
            'sender'          => 'user',
            'message'         => Crypt::encryptString($text),
            'sent_at'         => now(),
        ]);

        // On first user message, set topic_summary
        $count = Chat::where('chat_session_id', $sessionId)->where('sender', 'user')->count();
        if ($count === 1) {
            preg_match('/\b(sad|depress|help|anxious|angry|lonely|stress|tired|happy|excited|not okay)\b/i', $text, $m);
            $summary = $m[0] ?? Str::limit($text, 40, '…');
            ChatSession::find($sessionId)?->update(['topic_summary' => ucfirst($summary)]);
        }

        // --- Talk to Rasa (guarded + timeout) ---
        $rasaUrl = config('services.rasa.url', env('RASA_URL', 'http://127.0.0.1:5005/webhooks/rest/webhook'));
        $botReplies = [];
        try {
            $r = Http::timeout(7)->post($rasaUrl, [
                'sender'  => 'u_'.Auth::id().'_s_'.$sessionId,
                'message' => $text,
            ]);
            if ($r->ok()) {
                $payload = $r->json() ?? [];
                foreach ($payload as $piece) {
                    if (!empty($piece['text'])) {
                        $botReplies[] = $piece['text'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // fallback if Rasa is down
            $botReplies = ["It’s normal to feel that way. I’m here to listen. Would you like to share what happened?"];
        }

        // Save BOT replies (encrypted) + prepare payload with times
        $botPayload = [];
        foreach ($botReplies as $reply) {
            $bot = Chat::create([
                'user_id'         => Auth::id(),
                'chat_session_id' => $sessionId,
                'sender'          => 'bot',
                'message'         => Crypt::encryptString($reply),
                'sent_at'         => now(),
            ]);
            $botPayload[] = [
                'text'       => $reply,
                'time_human' => $bot->sent_at->timezone(config('app.timezone'))->format('H:i'),
                'sent_at'    => $bot->sent_at->toIso8601String(),
            ];
        }

        return response()->json([
            'user_message' => [
                'text'       => $text,
                'time_human' => $userMsg->sent_at->timezone(config('app.timezone'))->format('H:i'),
                'sent_at'    => $userMsg->sent_at->toIso8601String(),
            ],
            'bot_reply' => $botPayload, // array of {text, time_human, sent_at}
        ]);
    }

    public function history(Request $request)
    {
        $q = trim($request->get('q', ''));

        $sessions = ChatSession::with(['chats' => function ($query) {
                $query->latest('sent_at')->limit(1);
            }])
            ->where('user_id', Auth::id())
            ->when($q !== '', fn($query) => $query->where('topic_summary', 'like', "%{$q}%"))
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        foreach ($sessions as $session) {
            foreach ($session->chats as $chat) {
                try {
                    $chat->message = Crypt::decryptString($chat->message);
                } catch (\Throwable $e) {
                    $chat->message = '[Unreadable]';
                }
            }
        }

        return view('chat-history', compact('sessions', 'q'));
    }

    public function viewSession($id)
    {
        $session = ChatSession::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $messages = Chat::where('chat_session_id', $id)
            ->where('user_id', Auth::id())
            ->orderBy('sent_at')
            ->get()
            ->map(function ($c) {
                try {
                    $c->message = Crypt::decryptString($c->message);
                } catch (\Throwable $e) {
                    $c->message = '[Unreadable]';
                }
                return $c;
            });

        return view('chat-view', compact('session', 'messages'));
    }

    public function deleteSession($id)
    {
        ChatSession::where('id', $id)->where('user_id', Auth::id())->delete();
        return redirect()->route('chat.history')->with('status', 'Session deleted');
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter(explode(',', $request->input('ids', '')));
        if ($ids) {
            ChatSession::whereIn('id', $ids)->where('user_id', Auth::id())->delete();
        }
        return redirect()->route('chat.history')->with('status', 'Selected sessions deleted');
    }

    public function activate($id)
    {
        $session = ChatSession::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        session(['chat_session_id' => $session->id, 'show_greeting' => false]);
        $session->touch();
        return redirect()->route('chat.index')->with('status', 'session-activated');
    }
}
