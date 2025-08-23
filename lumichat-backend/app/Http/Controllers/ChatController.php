<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;     // signed links
use Illuminate\Support\Facades\DB;      // optional logging
use Illuminate\Support\Facades\Schema;  // safe table checks
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /* -----------------------------------------------------------------------
     | High-risk detector (extend as you get more data)
     * ---------------------------------------------------------------------*/
    private function isHighRisk(string $text): bool
    {
        $t = mb_strtolower($text);

        $patterns = [
            '\bi (?:want|plan|intend)\s+to\s*(?:die|kill myself|end (?:it|my life)|suicide)\b',
            '\bi(?:’|\'| am|m)\s*(?:done|tired of living|suicidal|hopeless)\b',
            '\b(?:end it all|no reason to live|life is pointless)\b',
            '\bkill myself\b',
            '\bcommit suicide\b',
            '\bi (?:can\'t|cannot) go on\b',
            '\bself[- ]harm\b',
            '\bcut(ting)? myself\b',
            '\b(?:jump off|overdose|poison myself)\b',
        ];
        foreach ($patterns as $p) {
            if (preg_match('/' . $p . '/iu', $t)) return true;
        }

        // co-occurrence heuristic
        $kwA = ['suicide','die','kill myself','end my life','end it','jump','overdose','poison','cut'];
        $kwB = ['want','plan','thinking','feel like','i should','i will','i might'];
        foreach ($kwA as $a) foreach ($kwB as $b) {
            if (str_contains($t, $a) && str_contains($t, $b)) return true;
        }
        return false;
    }

    /* -----------------------------------------------------------------------
     | Crisp crisis block + CTA token (HTML). {APPOINTMENT_LINK} is replaced
     | below with a signed button.
     * ---------------------------------------------------------------------*/
    private function crisisMessageWithLink(): string
    {
        $c   = config('services.crisis');
        $emg = e($c['emergency_number'] ?? '911');
        $hn  = e($c['hotline_name'] ?? '988 Suicide & Crisis Lifeline');
        $hp  = e($c['hotline_phone'] ?? '988');
        $ht  = e($c['hotline_text'] ?? 'Text HOME to 741741');
        $url = e($c['hotline_url'] ?? 'https://988lifeline.org/');

        return <<<HTML
<div class="space-y-2 leading-relaxed">
  <p class="font-semibold">We’re here to help.</p>
  <ul class="list-disc pl-5 text-sm">
    <li>If you’re in immediate danger, call <strong>{$emg}</strong>.</li>
    <li>24/7 support: <strong>{$hn}</strong> — call <strong>{$hp}</strong>, {$ht}, or visit
      <a href="{$url}" target="_blank" rel="noopener" class="underline">{$url}</a>.
    </li>
  </ul>
  <p class="text-sm">If you want to talk with someone safe from school right now, you can book a time with a counselor:</p>
  <div class="pt-1">
    {APPOINTMENT_LINK}
  </div>
</div>
HTML;
    }

    /* =======================================================================
     | UI pages
     * =====================================================================*/
    public function index()
    {
        if (!session('chat_session_id')) {
            return redirect()->route('chat.new');
        }

        $showGreeting = session()->pull('show_greeting', false);

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

        return view('chat', compact('chats', 'showGreeting'));
    }

    public function newChat()
    {
        $userId = Auth::id();

        if (session('chat_session_id')) {
            $session = ChatSession::where('id', session('chat_session_id'))
                ->where('user_id', $userId)
                ->first();
            if ($session) {
                $hasMessages = Chat::where('chat_session_id', $session->id)->exists();
                if (!$hasMessages) {
                    session(['show_greeting' => true]);
                    return redirect()->route('chat.index');
                }
            }
        }

        $reuse = ChatSession::where('user_id', $userId)
            ->whereDoesntHave('chats')
            ->latest('id')
            ->first();

        if ($reuse) {
            session([
                'chat_session_id' => $reuse->id,
                'show_greeting'   => true,
            ]);
            return redirect()->route('chat.index');
        }

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

    /* =======================================================================
     | Store a user message, call Rasa, then add safety + booking logic
     * =====================================================================*/
    public function store(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        $sessionId = session('chat_session_id');
        if (!$sessionId) {
            $s = ChatSession::create([
                'user_id'       => Auth::id(),
                'topic_summary' => 'Starting conversation...',
            ]);
            $sessionId = $s->id;
            session(['chat_session_id' => $sessionId]);
        }

        $text = $request->message;

        $userMsg = Chat::create([
            'user_id'         => Auth::id(),
            'chat_session_id' => $sessionId,
            'sender'          => 'user',
            'message'         => Crypt::encryptString($text),
            'sent_at'         => now(),
        ]);

        $count = Chat::where('chat_session_id', $sessionId)->where('sender', 'user')->count();
        if ($count === 1) {
            preg_match('/\b(sad|depress|help|anxious|angry|lonely|stress|tired|happy|excited|not okay)\b/i', $text, $m);
            $summary = $m[0] ?? Str::limit($text, 40, '…');
            ChatSession::find($sessionId)?->update(['topic_summary' => ucfirst($summary)]);
        }

        // --- Call Rasa safely
        $rasaUrl    = config('services.rasa.url', env('RASA_URL', 'http://127.0.0.1:5005/webhooks/rest/webhook'));
        $botReplies = [];
        try {
            $r = Http::timeout(7)->post($rasaUrl, [
                'sender'  => 'u_' . Auth::id() . '_s_' . $sessionId,
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
            $botReplies = ["It’s normal to feel that way. I’m here to listen. Would you like to share what happened?"];
        }

        // ===== Crisis detection (once per session)
        $crisisAlreadyShown = session('crisis_prompted_for_session_' . $sessionId, false);
        if (!$crisisAlreadyShown && $this->isHighRisk($text)) {
            session(['crisis_prompted_for_session_' . $sessionId => true]);

            try {
                if (Schema::hasTable('tbl_activity_log')) {
                    DB::table('tbl_activity_log')->insert([
                        'user_id'    => Auth::id(),
                        'activity'   => 'crisis_prompt',
                        'details'    => json_encode(['chat_session_id' => $sessionId]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) { /* ignore */ }

            array_unshift($botReplies, $this->crisisMessageWithLink());
        }

        // ===== Simple appointment intent (works even if Rasa is "dumb")
        $wantsAppointment = (bool) preg_match(
            '/\b(appoint|appointment|schedule|book|booking|meet|talk)\b.*\b(counsel|counselor|therap|advisor)\b|'
            . '\bsee (?:a )?counselor\b|'
            . '\b(counselor|therapist)\b.*\b(available|when|talk|meet)\b/i',
            $text
        );

        $alreadyHasLink = collect($botReplies)->contains(function ($r) {
            return is_string($r) && str_contains($r, '{APPOINTMENT_LINK}');
        });

        if ($wantsAppointment && !$alreadyHasLink) {
            if (Auth::user()->appointments_enabled ?? false) {
                $directBtn = '<a href="' . route('appointment.index') . '" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Book an appointment</a>';
                $botReplies[] = 'Book a session with a counselor:<br>' . $directBtn;
            } else {
                $botReplies[] = "Book a session with a counselor:<br>{APPOINTMENT_LINK}";
            }
        }

        // ===== Build signed appointment CTA
        $signed  = URL::signedRoute('features.enable_appointment');
        $ctaHtml = '<a href="' . $signed . '" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Book an appointment</a>';

        // Save replies & return payload
        $botPayload = [];
        foreach ($botReplies as $reply) {
            if (is_string($reply) && str_contains($reply, '{APPOINTMENT_LINK}')) {
                $reply = str_replace('{APPOINTMENT_LINK}', $ctaHtml, $reply);
            }

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
            'bot_reply' => $botPayload,
        ]);
    }

    /* =======================================================================
     | History utilities
     * =====================================================================*/
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
