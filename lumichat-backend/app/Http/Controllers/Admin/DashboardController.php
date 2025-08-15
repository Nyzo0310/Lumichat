<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // === KPIs ===
        $totalAppointments = Appointment::count();

        // Active counselors — adapt to available column on tbl_users
        $usersTable = (new User)->getTable();
        $chatSessionsTable = (new ChatSession)->getTable();

        $activeCounselorsQuery = User::query()->where('role', 'counselor');

        if (Schema::hasColumn($usersTable, 'is_active')) {
            $activeCounselorsQuery->where('is_active', 1);
        } elseif (Schema::hasColumn($usersTable, 'status')) {
            $activeCounselorsQuery->where('status', 'active');
        }
        $activeCounselors = $activeCounselorsQuery->count();

        $chatSessionsThisWeek = ChatSession::whereBetween('created_at', [
            Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()
        ])->count();

        $criticalCases = Schema::hasColumn($chatSessionsTable, 'is_critical')
            ? ChatSession::where('is_critical', 1)->count()
            : 0;

        // === Panels ===

        // Recent appointments
        $recentAppointments = Appointment::with(['student:id,name'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // System Activity
        $userEvents = User::orderByDesc('created_at')->take(5)->get()->map(fn ($u) => [
            'when'  => $u->created_at,
            'text'  => "New user registered: {$u->name}",
            'badge' => 'User',
        ]);

        $chatEvents = ChatSession::orderByDesc('created_at')->take(5)->get()->map(function ($s) {
            $label = $s->topic_summary ?: 'Starting conversation';
            return [
                'when'  => $s->created_at,
                'text'  => 'Chat session started: ' . Str::limit($label, 60),
                'badge' => 'Chat',
            ];
        });

        $appointmentEvents = Appointment::orderByDesc('created_at')->take(5)->get()->map(function ($a) {
            $name = optional($a->student)->name ?: 'Student';
            return [
                'when'  => $a->created_at,
                'text'  => "Appointment created for {$name}",
                'badge' => 'Appointment',
            ];
        });

        $activityFeed = collect()
            ->merge($userEvents)
            ->merge($chatEvents)
            ->merge($appointmentEvents)
            ->sortByDesc('when')
            ->values()
            ->take(8);

        // Recent chats
        $recentChats = ChatSession::with(['user:id,name'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalAppointments',
            'criticalCases',
            'activeCounselors',
            'chatSessionsThisWeek',
            'recentAppointments',
            'activityFeed',
            'recentChats'
        ));
    }
}
