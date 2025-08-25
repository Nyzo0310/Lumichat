@extends('layouts.app')
@section('title', 'Appointment #'.$appointment->id)

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4">

  <a href="{{ route('appointment.history') }}"
     class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
      <path fill-rule="evenodd" d="M9.707 4.293a1 1 0 010 1.414L5.414 10H20a1 1 0 110 2H5.414l4.293 4.293a1 1 0 01-1.414 1.414l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 0z" clip-rule="evenodd"/>
    </svg>
    <span>Back to history</span>
  </a>

  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-start justify-between">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
        Appointment #{{ $appointment->id }}
      </h2>

      @php
        $styles = [
          'pending'   => ['chip'=>'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200','dot'=>'bg-amber-500','pulse'=>true],
          'confirmed' => ['chip'=>'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200','dot'=>'bg-blue-500','pulse'=>false],
          'canceled'  => ['chip'=>'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200','dot'=>'bg-rose-500','pulse'=>false],
          'completed' => ['chip'=>'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200','dot'=>'bg-emerald-500','pulse'=>false],
        ];
        $s = $styles[$appointment->status] ?? ['chip'=>'bg-gray-100 text-gray-700','dot'=>'bg-gray-400','pulse'=>false];
      @endphp
      <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $s['chip'] }}">
        <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }} {{ $s['pulse'] ? 'animate-pulse' : '' }}"></span>
        {{ ucfirst($appointment->status) }}
      </span>
    </div>

    @php
      $now   = \Carbon\Carbon::now();
      $start = \Carbon\Carbon::parse($appointment->scheduled_at);
      $mins  = $now->diffInMinutes($start, false);
      $abs   = abs($mins);
      $d     = intdiv($abs, 1440); $r=$abs%1440; $h=intdiv($r,60); $m=$r%60;
      $parts = [];
      if ($d) $parts[] = "{$d}d";
      if ($h) $parts[] = "{$h}h";
      if (!$d && $m) $parts[] = "{$m}m";
      $countdown = $mins === 0 ? 'Starting now'
                  : ($mins > 0 ? ('Starts in '.implode(' ', $parts)) : (implode(' ', $parts).' ago'));
      $countColor = $mins >= 0 ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200'
                               : 'bg-gray-100 text-gray-700 dark:bg-gray-700/40 dark:text-gray-200';
    @endphp

    <div class="mt-3">
      <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium {{ $countColor }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 8a1 1 0 011 1v3.382l2.447 1.224a1 1 0 11-.894 1.788l-3-1.5A1 1 0 0111 13V9a1 1 0 011-1z"></path>
          <path fill-rule="evenodd" d="M12 22a10 10 0 100-20 10 10 0 000 20zm0-2a8 8 0 110-16 8 8 0 010 16z" clip-rule="evenodd"></path>
        </svg>
        {{ $countdown }}
      </span>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Counselor</h3>
        <div class="space-y-1 text-sm">
          <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $appointment->counselor_name }}</p>
          @if(!empty($appointment->counselor_email))
            <p class="text-gray-600 dark:text-gray-300">{{ $appointment->counselor_email }}</p>
          @endif
          @if(!empty($appointment->counselor_phone))
            <p class="text-gray-600 dark:text-gray-300">{{ $appointment->counselor_phone }}</p>
          @endif
        </div>
      </div>

      <div>
        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Scheduled</h3>
        <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
          {{ \Carbon\Carbon::parse($appointment->scheduled_at)->format('l, M d, Y · g:i A') }}
        </p>
      </div>
    </div>

    <div class="mt-6">
      <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Notes</h3>
      <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-900/40">
        @if(!empty($appointment->notes))
          {!! nl2br(e($appointment->notes)) !!}
        @else
          <span class="text-gray-400">—</span>
        @endif
      </div>
    </div>

    {{-- Actions --}}
    <div class="mt-6 flex items-center gap-3">
      <a href="{{ route('appointment.history') }}"
         class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
        Close
      </a>

      @php
        $canCancel = !in_array($appointment->status, ['canceled','completed']) && $start->gt($now);
      @endphp
      @if ($canCancel)
        <form method="POST" action="{{ route('appointment.cancel', $appointment->id) }}"
              onsubmit="return confirmStudentCancel(event, this)">
          @csrf
          @method('PATCH')
          <button type="submit"
                  class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-white hover:bg-rose-700">
            Cancel
          </button>
        </form>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function confirmStudentCancel(e, form) {
  e.preventDefault();
  Swal.fire({
    icon: 'warning',
    title: 'Cancel this appointment?',
    text: 'This action cannot be undone.',
    showCancelButton: true,
    confirmButtonText: 'Yes, cancel',
    cancelButtonText: 'No, keep it',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
    focusCancel: true
  }).then(res => {
    if (res.isConfirmed) form.submit();
  });
  return false;
}
</script>
@endpush
