@extends('layouts.admin')
@section('title', 'Admin · Appointment #'.$appointment->id)

@section('content')
@php
  use Carbon\Carbon;

  $dt  = Carbon::parse($appointment->scheduled_at);
  $now = Carbon::now();

  $when = $now->isBefore($dt)
      ? 'Starts in '.$dt->diffForHumans($now, ['parts'=>2,'short'=>true,'syntax'=>Carbon::DIFF_RELATIVE_TO_NOW])
      : 'Started '.$dt->diffForHumans($now, ['parts'=>2,'short'=>true,'syntax'=>Carbon::DIFF_RELATIVE_TO_NOW]);

  $badgeMap = [
    'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
    'canceled'  => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  ];
  $dotMap = [
    'pending'   => 'bg-amber-500',
    'confirmed' => 'bg-blue-500',
    'canceled'  => 'bg-rose-500',
    'completed' => 'bg-emerald-500',
  ];
  $cls = $badgeMap[$appointment->status] ?? 'bg-gray-100 text-gray-700';
  $dot = $dotMap[$appointment->status] ?? 'bg-gray-400';
@endphp

<div class="max-w-5xl mx-auto p-6">
  <a href="{{ route('admin.appointments.index') }}" class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10 19l-7-7 7-7v14zM21 19H11v-2h10v2z"/></svg>
    Back to list
  </a>

  <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="px-6 pt-6 flex items-start justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
          Appointment #{{ $appointment->id }}
        </h2>

        <div class="mt-1 text-sm text-gray-500 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 8v5l3.5 3.5 1.5-1.5-3-3V8z"/><path d="M12 22a10 10 0 110-20 10 10 0 010 20zm0-2a8 8 0 100-16 8 8 0 000 16z"/>
          </svg>
          {{ $when }}
        </div>
      </div>

      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $cls }}">
        <span class="inline-block w-1.5 h-1.5 rounded-full {{ $dot }} mr-2 align-middle"></span>
        {{ ucfirst($appointment->status) }}
      </span>
    </div>

    <div class="px-6 pb-6 mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-3">
        <div class="text-xs uppercase tracking-wide text-gray-500">Student</div>
        <div class="text-gray-900 dark:text-gray-100 font-medium">{{ $appointment->student_name }}</div>
        @if(!empty($appointment->student_email))
          <div class="text-gray-600 dark:text-gray-300 text-sm">{{ $appointment->student_email }}</div>
        @endif
      </div>

      <div class="space-y-3">
        <div class="text-xs uppercase tracking-wide text-gray-500">Scheduled</div>
        <div class="text-gray-900 dark:text-gray-100 font-medium">
          {{ $dt->format('l, M d, Y · g:i A') }}
        </div>
      </div>

      <div class="space-y-3">
        <div class="text-xs uppercase tracking-wide text-gray-500">Counselor</div>
        <div class="text-gray-900 dark:text-gray-100 font-medium">{{ $appointment->counselor_name }}</div>
        <div class="text-gray-600 dark:text-gray-300 text-sm">
          {{ $appointment->counselor_email }}
          @if(!empty($appointment->counselor_phone)) · {{ $appointment->counselor_phone }} @endif
        </div>
      </div>

      <div class="space-y-3 md:col-span-2">
        <div class="text-xs uppercase tracking-wide text-gray-500">Notes</div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900">
          {!! $appointment->notes ? nl2br(e($appointment->notes)) : '<span class="text-gray-400">—</span>' !!}
        </div>
      </div>
    </div>

    {{-- Actions (No Cancel for Admin) --}}
    <div class="px-6 pb-6 flex items-center gap-3">
      <a href="{{ route('admin.appointments.index') }}"
         class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100">
        Close
      </a>

      {{-- Confirm --}}
      <form method="POST" action="{{ route('admin.appointments.status', $appointment->id) }}"
            onsubmit="return askAction(event, this, 'confirm')">
        @csrf @method('PATCH')
        <input type="hidden" name="action" value="confirm">
        <button type="submit"
          class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          {{ $appointment->status !== 'pending' ? 'disabled' : '' }}>
          Confirm
        </button>
      </form>

      {{-- Done --}}
      <form method="POST" action="{{ route('admin.appointments.status', $appointment->id) }}"
            onsubmit="return askAction(event, this, 'done')">
        @csrf @method('PATCH')
        <input type="hidden" name="action" value="done">
        <button type="submit"
          class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
          {{ $appointment->status !== 'confirmed' ? 'disabled' : '' }}>
          Done
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function askAction(e, form, action) {
    e.preventDefault();

    const cfg = {
      confirm: {
        title: 'Confirm Appointment?',
        text: 'Are you sure you want to confirm this appointment?',
        icon: 'question',
        confirmButtonColor: '#2563eb',
      },
      done: {
        title: 'Mark as Completed?',
        text: 'This will mark the appointment as done.',
        icon: 'success',
        confirmButtonColor: '#059669',
      }
    }[action] || {
      title: 'Are you sure?',
      text: '',
      icon: 'info',
      confirmButtonColor: '#2563eb'
    };

    return Swal.fire({
      title: cfg.title,
      text: cfg.text,
      icon: cfg.icon,
      showCancelButton: true,
      confirmButtonText: 'Yes, proceed',
      cancelButtonText: 'No, keep it',
      confirmButtonColor: cfg.confirmButtonColor,
      cancelButtonColor: '#6b7280',
      reverseButtons: true,
      focusCancel: true
    }).then(res => {
      if (res.isConfirmed) form.submit();
      return false;
    });
  }

  @if (session('swal'))
    Swal.fire(@json(session('swal')));
  @endif
</script>
@endpush
