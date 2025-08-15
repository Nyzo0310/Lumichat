<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $startOfWeek = now()->startOfWeek();

        // KPIs (use what you have now; appointments/critical are placeholders)
        $metrics = [
            'appointments' => Schema::hasTable('appointments')
                ? \DB::table('appointments')
                    ->whereBetween('scheduled_at', [$startOfWeek, now()])
                    ->count()
                : 0,
            'critical'     => 0, // TODO: replace with your own rule/table later
            'counselors'   => User::where('role', User::ROLE_COUNSELOR)->count(),
            'sessions'     => ChatSession::whereBetween('created_at', [$startOfWeek, now()])->count(),
        ];

        // Recent chat sessions (with last message preview)
        $recentChats = ChatSession::with(['chats' => function ($q) {
                $q->latest('sent_at')->limit(1);
            }])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(function ($session) {
                $last = optional($session->chats->first());
                $preview = '';
                if ($last) {
                    try {
                        $preview = Crypt::decryptString($last->message);
                    } catch (\Throwable $e) {
                        $preview = '[Encrypted]';
                    }
                }
                return [
                    'id'        => $session->id,
                    'title'     => $session->topic_summary ?: 'Untitled conversation',
                    'preview'   => str($preview)->limit(120),
                    'updated'   => $session->updated_at?->diffForHumans(),
                ];
            });

        // System activity: combine new users + new sessions (simple feed)
        $activity = new Collection();

        User::latest()->take(5)->get()->each(function ($u) use ($activity) {
            $activity->push([
                'ts'   => $u->created_at,
                'text' => "New user registered: {$u->name}",
            ]);
        });

        ChatSession::latest()->take(5)->get()->each(function ($s) use ($activity) {
            $title = $s->topic_summary ?: 'New chat session';
            $activity->push([
                'ts'   => $s->created_at,
                'text' => "Chat session started: {$title}",
            ]);
        });

        $systemActivity = $activity->sortByDesc('ts')->take(6)->map(function ($item) {
            return [
                'text' => $item['text'],
                'ago'  => optional($item['ts'])->diffForHumans(),
            ];
        });

        // (Optional) Recent appointments list if you add that table later
        $recentAppointments = collect(); // keep empty for now

        return view('admin.dashboard', compact('metrics','recentChats','systemActivity','recentAppointments'));
    }
}
