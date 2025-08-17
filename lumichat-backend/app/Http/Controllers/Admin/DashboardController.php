<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();

        // ---------------- KPIs ----------------
        $appointmentsTotal = Schema::hasTable('tbl_appointment')
            ? DB::table('tbl_appointment')->count()
            : 0;

        // If you later create tbl_counselors, this will count active ones. Otherwise 0.
        $activeCounselors = Schema::hasTable('tbl_counselors')
            ? DB::table('tbl_counselors')
                ->when(Schema::hasColumn('tbl_counselors', 'status'), fn ($q) => $q->where('status', 'active'))
                ->count()
            : 0;

        $chatSessionsThisWeek = Schema::hasTable('chat_sessions')
            ? DB::table('chat_sessions')->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count()
            : 0;

        $criticalCases = 0; // keep 0 for now (no source yet)

        // ---------------- Helpers ----------------
        // Build a safe COALESCE expression based on columns that actually exist
        $coalesceActor = function (string $table, string $alias): string {
            $parts = [];
            if (Schema::hasColumn($table, 'name'))       $parts[] = "$alias.name";
            if (Schema::hasColumn($table, 'full_name'))  $parts[] = "$alias.full_name";
            $hasFirst = Schema::hasColumn($table, 'first_name');
            $hasLast  = Schema::hasColumn($table, 'last_name');
            if ($hasFirst && $hasLast)                   $parts[] = "CONCAT($alias.first_name,' ',$alias.last_name)";
            if (Schema::hasColumn($table, 'email'))      $parts[] = "$alias.email";
            $parts[] = "'User'"; // final fallback
            return 'COALESCE(' . implode(', ', $parts) . ')';
        };

        // ---------------- System Activity ----------------
        $activities = collect();

        if (Schema::hasTable('chat_sessions')) {
            $cq = DB::table('chat_sessions as cs')->orderByDesc('cs.created_at')->limit(5);

            if (Schema::hasTable('tbl_registration')) {
                $cq->leftJoin('tbl_registration as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('tbl_registration', 'u');
            } elseif (Schema::hasTable('users')) {
                $cq->leftJoin('users as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('users', 'u');
            } else {
                $actorExpr = "'User'";
            }

            $chatActs = $cq->selectRaw("cs.created_at, cs.topic_summary, $actorExpr as actor_name")
                ->get()
                ->map(fn ($r) => (object)[
                    'event'      => 'chat_session.started',
                    'actor'      => $r->actor_name,
                    'meta'       => $r->topic_summary,
                    'created_at' => Carbon::parse($r->created_at),
                ]);

            $activities = $activities->merge($chatActs);
        }

        if (Schema::hasTable('tbl_registration')) {
            $regActs = DB::table('tbl_registration')
                ->orderByDesc('created_at')->limit(5)
                ->get()
                ->map(function ($r) {
                    $name = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                    $display = $name !== '' ? $name : ($r->email ?? 'User');
                    return (object)[
                        'event'      => 'user.registered',
                        'actor'      => $display,
                        'meta'       => null,
                        'created_at' => Carbon::parse($r->created_at),
                    ];
                });

            $activities = $activities->merge($regActs);
        }

        // Sort newest first and take 5
        $activities = $activities->sortByDesc('created_at')->values()->take(5);

        // ---------------- Recent Appointments ----------------
        $recentAppointments = collect();
        if (Schema::hasTable('tbl_appointment')) {
            $recentAppointments = DB::table('tbl_appointment')
                ->orderByDesc(Schema::hasColumn('tbl_appointment', 'scheduled_at') ? 'scheduled_at' : 'created_at')
                ->limit(5)
                ->get()
                ->map(function ($r) {
                    $when = $r->scheduled_at ?? $r->created_at;
                    return (object)[
                        'id'           => $r->id ?? null,
                        'status'       => $r->status ?? null,
                        'when'         => Carbon::parse($when),
                        'student_id'   => $r->student_id ?? null,
                        'counselor_id' => $r->counselor_id ?? null,
                        'notes'        => $r->notes ?? null,
                    ];
                });
        }

        // ---------------- Recent Chat Sessions ----------------
        $recentChatSessions = collect();
        if (Schema::hasTable('chat_sessions')) {
            $cq = DB::table('chat_sessions as cs')->orderByDesc('cs.created_at')->limit(5);

            if (Schema::hasTable('tbl_registration')) {
                $cq->leftJoin('tbl_registration as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('tbl_registration', 'u');
            } elseif (Schema::hasTable('users')) {
                $cq->leftJoin('users as u', 'u.id', '=', 'cs.user_id');
                $actorExpr = $coalesceActor('users', 'u');
            } else {
                $actorExpr = "'User'";
            }

            $recentChatSessions = $cq->selectRaw("cs.created_at, cs.topic_summary, $actorExpr as actor_name")
                ->get()
                ->map(fn ($r) => (object)[
                    'created_at'    => Carbon::parse($r->created_at),
                    'topic_summary' => $r->topic_summary,
                    'actor'         => $r->actor_name,
                ]);
        }

        return view('admin.dashboard', [
            'appointmentsTotal'     => $appointmentsTotal,
            'criticalCasesTotal'    => $criticalCases,
            'activeCounselors'      => $activeCounselors,
            'chatSessionsThisWeek'  => $chatSessionsThisWeek,
            'recentAppointments'    => $recentAppointments,
            'activities'            => $activities,
            'recentChatSessions'    => $recentChatSessions,
        ]);
    }
}
