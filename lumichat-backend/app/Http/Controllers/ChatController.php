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
    /* =======================================================================
     | Risk evaluation helpers
     * =====================================================================*/

    /**
     * Return 'low' | 'moderate' | 'high' for a given text.
     * Designed to be conservative but sensitive to:
     *  - explicit suicidal intent (high)
     *  - severe self-directed distress without an explicit intent (moderate)
     */
    private function evaluateRiskLevel(string $text): string
    {
        $t = mb_strtolower($text);

        // 🔴 High Risk: explicit suicidal intent
        $highPatterns = [
            '\bi\s*(?:really\s*)?(?:want|plan|intend|gonna|going to)\s+(?:to\s*)?(?:die|kill myself|end (?:it|my life)|suicide)(?:\s*now|\s*soon)?\b',
            '\bi\s*(?:wanna|want to)\s*die\b',
            '\bwanna die\b',
            '\bi\s*(?:will|gonna|going to)\s*(?:kill myself|end my life|suicide)\b',
            '\bi(?:’|\'| am|m)?\s*(?:done|suicidal|hopeless)\b',
            '\b(end it all|no reason to live|life is pointless)\b',
            '\b(kill myself|commit suicide|self[- ]harm|cutting myself|overdose|poison myself|jump off)\b',
        ];
        foreach ($highPatterns as $p) {
            if (preg_match('/' . $p . '/iu', $t)) {
                return 'high';
            }
        }

        // 🟡 Moderate Risk: strong distress but no direct suicide phrase
        $moderatePatterns = [
            '\bi (?:hate myself|can\'t go on|don\'t want to live)\b',
            '\bi feel (?:worthless|empty|numb|like dying)\b',
            '\bi want to disappear\b',
            '\bnobody cares\b',
            '\bi really hate myself\b',
        ];
        foreach ($moderatePatterns as $p) {
            if (preg_match('/' . $p . '/iu', $t)) {
                return 'moderate';
            }
        }

        // 🟢 Default low
        return 'low';
    }

    /** Backwards-compat helper used by crisis gate */
    private function isHighRisk(string $text): bool
    {
        return $this->evaluateRiskLevel($text) === 'high';
    }

    /* =======================================================================
     | Crisis CTA block (HTML with placeholder for signed link)
     * =====================================================================*/
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

        // Make sure view has this variable every time
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

    /**
     * Start a new chat session (or reuse an empty one).
     * Honors ?anonymous=1 to set is_anonymous.
     */
    public function newChat(Request $request)
    {
        $userId     = Auth::id();
        $anonymous  = (int) $request->query('anonymous', 0) === 1 ? 1 : 0;

        // If a session is active and has no messages yet, just show greeting
        if (session('chat_session_id')) {
            $session = ChatSession::where('id', session('chat_session_id'))
                ->where('user_id', $userId)
                ->first();
            if ($session) {
                $hasMessages = Chat::where('chat_session_id', $session->id)->exists();
                if (!$hasMessages) {
                    // If user toggled anonymous now, persist it on the empty session
                    if ($session->is_anonymous != $anonymous) {
                        $session->is_anonymous = $anonymous;
                        $session->save();
                    }
                    session(['show_greeting' => true]);
                    return redirect()->route('chat.index');
                }
            }
        }

        // Reuse most recent empty session (and update anonymity)
        $reuse = ChatSession::where('user_id', $userId)
            ->whereDoesntHave('chats')
            ->latest('id')
            ->first();

        if ($reuse) {
            if ($reuse->is_anonymous != $anonymous) {
                $reuse->is_anonymous = $anonymous;
                $reuse->save();
            }
            session([
                'chat_session_id' => $reuse->id,
                'show_greeting'   => true,
            ]);
            return redirect()->route('chat.index');
        }

        // Create fresh session
        $session = ChatSession::create([
            'user_id'       => $userId,
            'is_anonymous'  => $anonymous,
            'topic_summary' => 'Starting conversation...',
            // risk_level default via migration is 'low'
        ]);

        session([
            'chat_session_id' => $session->id,
            'show_greeting'   => true,
        ]);

        return redirect()->route('chat.index');
    }

    /* =======================================================================
     | Store a user message, call Rasa, add safety + booking logic
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

        // Save user's message (encrypted)
        $userMsg = Chat::create([
            'user_id'         => Auth::id(),
            'chat_session_id' => $sessionId,
            'sender'          => 'user',
            'message'         => Crypt::encryptString($text),
            'sent_at'         => now(),
        ]);

        // If first user message, derive simple session title
        $countUserMsgs = Chat::where('chat_session_id', $sessionId)->where('sender', 'user')->count();
        if ($countUserMsgs === 1) {
            preg_match('/\b(sad|depress|help|anxious|angry|lonely|stress|tired|happy|excited|not okay)\b/i', $text, $m);
            $summary = $m[0] ?? Str::limit($text, 40, '…');
            ChatSession::find($sessionId)?->update(['topic_summary' => ucfirst($summary)]);
        }

        // 🧠 Risk evaluation (upgrade only; never downgrade)
        $incomingRisk = $this->evaluateRiskLevel($text);
        $session      = ChatSession::find($sessionId);
        if ($session) {
            $current = $session->risk_level ?? 'low';
            $rank = ['low' => 0, 'moderate' => 1, 'high' => 2];
            if (($rank[$incomingRisk] ?? 0) > ($rank[$current] ?? 0)) {
                $session->risk_level = $incomingRisk;
                $session->save();
            }
        }

        // === Call Rasa (gracefully fallback)
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

        // === Crisis detection (once per session)
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

        // === Appointment intent (works even if Rasa is basic)
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

        // Build signed appointment CTA
        $signed  = URL::signedRoute('features.enable_appointment');
        $ctaHtml = '<a href="' . $signed . '" class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">Book an appointment</a>';

        // Save bot replies & return payload
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
