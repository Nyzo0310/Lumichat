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

        $chats = Chat::where('user_id', Auth::id())
            ->where('chat_session_id', session('chat_session_id'))
            ->orderBy('sent_at')
            ->get()
            ->map(fn($chat) => tap($chat, function($c) {
                try {
                    $c->message = Crypt::decryptString($c->message);
                } catch (\Exception $e) {
                    $c->message = '[Encrypted]';
                }
            }));

        return view('chat', compact('chats','showGreeting'));
    }

    public function newChat()
    {
        $session = ChatSession::create([
            'user_id'       => Auth::id(),
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
        $sessionId = session('chat_session_id');

        if (! $sessionId) {
            return response()->json(['error'=>'No active chat session'], 400);
        }

        $text = $request->message;
        Chat::create([
            'user_id'         => Auth::id(),
            'chat_session_id' => $sessionId,
            'sender'          => 'user',
            'message'         => Crypt::encryptString($text),
            'sent_at'         => now(),
        ]);

        // On first user message, update ChatSession.topic_summary
        $count = Chat::where('chat_session_id',$sessionId)
                    ->where('sender','user')
                    ->count();
        if ($count === 1) {
            preg_match('/\b(sad|depress|help|anxious|angry|lonely|stress|tired|happy|excited|not okay)\b/i',
                       $text, $m);
            $summary = $m[0] ?? Str::limit($text, 40, '…');
            ChatSession::find($sessionId)->update([
                'topic_summary' => ucfirst($summary),
            ]);
        }

        // Send to Rasa
        $resp = Http::post('http://localhost:5005/webhooks/rest/webhook', [
            'sender'  => (string)Auth::id(),
            'message' => $text,
        ])->json();

        $botReplies = [];
        foreach ($resp as $r) {
            if (isset($r['text'])) {
                Chat::create([
                    'user_id'         => Auth::id(),
                    'chat_session_id' => $sessionId,
                    'sender'          => 'bot',
                    'message'         => Crypt::encryptString($r['text']),
                    'sent_at'         => now(),
                ]);
                $botReplies[] = $r['text'];
            }
        }

        return response()->json(['bot_reply'=>$botReplies]);
    }

    public function history(Request $request)
    {
        $q = trim($request->get('q', ''));

        // Load only the latest chat for preview (encrypted in DB, we decrypt below)
        $sessions = ChatSession::with(['chats' => function ($query) {
                $query->latest('sent_at')->limit(1);
            }])
            ->where('user_id', Auth::id())
            ->when($q !== '', function ($query) use ($q) {
                $query->where('topic_summary', 'like', "%{$q}%");
            })
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        // Decrypt the last message preview (if present)
        foreach ($sessions as $session) {
            foreach ($session->chats as $chat) {
                try {
                    $chat->message = \Illuminate\Support\Facades\Crypt::decryptString($chat->message);
                } catch (\Exception $e) {
                    $chat->message = '[Unreadable]';
                }
            }
        }

        return view('chat-history', compact('sessions', 'q'));
    }

    public function deleteSession($id)
    {
        ChatSession::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return redirect()
            ->route('chat.history')
            ->with('status', 'Session deleted');
    }

    public function bulkDelete(Request $request)
    {
        $ids = explode(',', $request->input('ids', ''));
        ChatSession::whereIn('id', $ids)
            ->where('user_id', Auth::id())
            ->delete();

        return redirect()->route('chat.history')->with('status', 'Selected sessions deleted');
    }
    
    public function activate($id)
    {
        // Make sure this session belongs to the logged-in user
        $session = ChatSession::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Set it as the active chat session
        session([
            'chat_session_id' => $session->id,
            'show_greeting'   => false, // don't show greeting when resuming
        ]);

        // Optional: refresh "last used" timestamp
        $session->touch();

        return redirect()
            ->route('chat.index')
            ->with('status', 'session-activated');
    }


}
