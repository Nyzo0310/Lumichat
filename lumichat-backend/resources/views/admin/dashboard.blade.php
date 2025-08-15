@extends('layouts.admin')

@section('title','Dashboard')

@section('content')
  <div class="space-y-6">

    {{-- Page intro --}}
    <div>
      <h2 class="text-3xl font-extrabold tracking-tight">Welcome back, Admin</h2>
      <p class="text-slate-600 mt-1">Here’s what’s happening with your students today.</p>
    </div>

    {{-- KPI / Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

      {{-- Total Appointments --}}
      <div
        class="relative overflow-hidden rounded-2xl border-[1px] p-5 bg-sky-50 border-sky-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-sky-400 focus-within:border-sky-500 focus-within:ring-2 focus-within:ring-sky-200"
        role="region" aria-labelledby="kpi-appointments-title">

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-sky-200">
            <img src="{{ asset('images/icons/appointment.png') }}" class="w-6 h-6" alt="Appointments icon">
          </span>
          <div class="min-w-0">
            <div id="kpi-appointments-title" class="text-sm text-slate-600 font-medium">Total Appointments</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($totalAppointments ?? 0) }}</div>
            <div class="mt-0.5 text-xs text-slate-500">0% from last week</div>
          </div>
        </div>

        <div class="pointer-events-none absolute -right-6 -bottom-8 w-44 h-44 rounded-full bg-sky-200/40 blur-2xl"></div>
      </div>

      {{-- Critical Cases --}}
      <div
        class="relative overflow-hidden rounded-2xl border-[1px] p-5 bg-rose-50 border-rose-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-rose-400 focus-within:border-rose-500 focus-within:ring-2 focus-within:ring-rose-200"
        role="region" aria-labelledby="kpi-critical-title">

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-rose-200">
            <img src="{{ asset('images/icons/diagnosis.png') }}" class="w-6 h-6" alt="Critical cases icon">
          </span>
          <div class="min-w-0">
            <div id="kpi-critical-title" class="text-sm text-slate-600 font-medium">Critical Cases</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($criticalCases ?? 0) }}</div>
            <div class="mt-0.5 text-xs text-slate-500">Requires attention</div>
          </div>
        </div>

        <div class="pointer-events-none absolute -right-6 -bottom-8 w-44 h-44 rounded-full bg-rose-200/40 blur-2xl"></div>
      </div>

      {{-- Active Counselor --}}
      <div
        class="relative overflow-hidden rounded-2xl border-[1px] p-5 bg-amber-50 border-amber-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-amber-400 focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-200"
        role="region" aria-labelledby="kpi-counselor-title">

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-amber-200">
            <img src="{{ asset('images/icons/counselor.png') }}" class="w-6 h-6" alt="Counselor icon">
          </span>
          <div class="min-w-0">
            <div id="kpi-counselor-title" class="text-sm text-slate-600 font-medium">Active Counselor</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($activeCounselors ?? 0) }}</div>
            <div class="mt-0.5 text-xs text-slate-500">Available counselors</div>
          </div>
        </div>

        <div class="pointer-events-none absolute -right-6 -bottom-8 w-44 h-44 rounded-full bg-amber-200/50 blur-2xl"></div>
      </div>

      {{-- Chat Sessions (this week) --}}
      <div
        class="relative overflow-hidden rounded-2xl border-[1px] p-5 bg-indigo-50 border-indigo-300 shadow-sm
               transition motion-safe:hover:-translate-y-0.5 hover:shadow-md
               hover:border-indigo-400 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200"
        role="region" aria-labelledby="kpi-chats-title">

        <div class="relative z-10 flex items-start gap-4">
          <span class="shrink-0 inline-flex w-12 h-12 items-center justify-center rounded-xl bg-white/80 ring-1 ring-indigo-200">
            <img src="{{ asset('images/icons/chatbot-session.png') }}" class="w-6 h-6" alt="Chat sessions icon">
          </span>
          <div class="min-w-0">
            <div id="kpi-chats-title" class="text-sm text-slate-600 font-medium">Chat Sessions</div>
            <div class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($chatSessionsThisWeek ?? 0) }}</div>
            <div class="mt-0.5 text-xs text-slate-500">This week</div>
          </div>
        </div>

        <div class="pointer-events-none absolute -right-6 -bottom-8 w-44 h-44 rounded-full bg-indigo-200/40 blur-2xl"></div>
      </div>

    </div>

    {{-- Two-up content --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      {{-- Recent appointments --}}
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-md transition-shadow hover:shadow-lg">
        <div class="flex items-baseline justify-between mb-3">
          <h3 class="font-semibold">Recent Appointments</h3>
          <a class="text-sm text-indigo-600 hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 rounded"
             href="#">View all</a>
        </div>

        @if(isset($recentAppointments) && $recentAppointments->isNotEmpty())
          <ul class="divide-y divide-slate-100">
            @foreach($recentAppointments as $appt)
              <li class="py-3 flex items-center justify-between">
                <div>
                  <div class="font-medium">
                    {{ $appt->title ?? 'Appointment' }}
                  </div>
                  <div class="text-xs text-slate-400">
                    {{ $appt->student->name ?? 'Student' }}
                  </div>
                </div>
                <div class="text-xs text-slate-400">
                  {{ optional($appt->scheduled_at ?? $appt->created_at)->diffForHumans() }}
                </div>
              </li>
            @endforeach
          </ul>
        @else
          <p class="text-sm text-slate-500">No appointments yet.</p>
        @endif
      </div>

      {{-- System Activity --}}
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-md transition-shadow hover:shadow-lg">
        <div class="flex items-baseline justify-between mb-3">
          <h3 class="font-semibold">System Activity</h3>
        </div>

        @if(isset($activityFeed) && $activityFeed->isNotEmpty())
          <ul class="space-y-3 text-sm">
            @foreach($activityFeed as $item)
              @php
                $badge = $item['badge'] ?? '';
                $dot   = match($badge){
                  'User'        => 'bg-emerald-500',
                  'Appointment' => 'bg-sky-500',
                  'Alert'       => 'bg-rose-500',
                  'Chat'        => 'bg-indigo-500',
                  default       => 'bg-slate-400',
                };
              @endphp
              <li class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="w-2 h-2 rounded-full {{ $dot }}"></span>
                  <span>{{ $item['text'] ?? '' }}</span>
                </div>
                <span class="text-slate-400 text-xs">
                  {{ optional($item['when'] ?? null)->diffForHumans() }}
                </span>
              </li>
            @endforeach
          </ul>
        @else
          <p class="text-sm text-slate-500">No recent activity.</p>
        @endif
      </div>
    </div>

    {{-- Recent chats --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-md transition-shadow hover:shadow-lg">
      <div class="flex items-baseline justify-between mb-3">
        <h3 class="font-semibold">Recent Chat Sessions</h3>
        <a class="text-sm text-indigo-600 hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 rounded"
           href="#">Open history</a>
      </div>

      @if(isset($recentChats) && $recentChats->isNotEmpty())
        <div class="divide-y divide-slate-100">
          @foreach($recentChats as $s)
            <div class="py-3 flex items-center justify-between">
              <div>
                <div class="font-medium">
                  {{ $s->topic_summary ?? 'Conversation' }}
                </div>
                <div class="text-xs text-slate-400">
                  {{ $s->user->name ?? 'Anonymous' }}
                </div>
              </div>
              <div class="text-xs text-slate-400">
                {{ $s->created_at->diffForHumans() }}
              </div>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-sm text-slate-500">No chat sessions yet.</p>
      @endif
    </div>

  </div>
@endsection
