@extends('layouts.admin')
@section('title','Self-Assessment Details')

@section('content')
@php
  // ---- DEMO FALLBACK (only used if $assessment isn't provided) ----
  if (!isset($assessment)) {
    $demo = [
      1 => [
        'id' => 1,
        'user_name' => 'Juan Dela Cruz',
        'result' => 'Mild Anxiety',
        'created_at' => now()->subDays(2)->toDateTimeString(),
        'answers' => [
          ['question' => 'Current feeling', 'answer' => 'Anxious about exams'],
          ['question' => 'Sleep quality',   'answer' => 'Fair'],
          ['question' => 'Need support?',   'answer' => 'Yes, counseling'],
        ],
        'notes' => null,
      ],
      2 => [
        'id' => 2,
        'user_name' => 'Earl Sepida',
        'result' => 'Normal',
        'created_at' => now()->subDays(3)->toDateTimeString(),
        'answers' => [
          ['question' => 'Current feeling', 'answer' => 'Curious about LumiCHAT'],
        ],
        'notes' => 'Follow-up optional.',
      ],
      3 => [
        'id' => 3,
        'user_name' => 'Faith Magayon',
        'result' => 'Starting conversation...',
        'created_at' => now()->subDays(4)->toDateTimeString(),
        'answers' => [],
        'notes' => null,
      ],
    ];
    $routeId   = request()->route('id'); // comes from /admin/self-assessments/{id}
    $assessment = (object) ($demo[$routeId] ?? $demo[1]);
  }

  // Unified helpers (work with model or demo array/object)
  $id = $assessment->id ?? $assessment['id'] ?? null;
  $code = 'ASS-' . now()->format('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
  $student = $assessment->user->name ?? ($assessment->user_name ?? '—');
  $submitted = isset($assessment->created_at)
      ? (\Carbon\Carbon::parse($assessment->created_at)->format('F d, Y · h:i A'))
      : '—';
  $resultVal = $assessment->result ?? $assessment->assessment_result ?? '—';

  // Answers normalize (array or json string)
  $answers = $assessment->answers ?? null;
  if (is_string($answers)) {
    try { $answers = json_decode($answers, true, 512, JSON_THROW_ON_ERROR); }
    catch (\Throwable $e) { $answers = null; }
  }
@endphp

<div class="max-w-5xl mx-auto space-y-6">

  {{-- Header --}}
  <div class="flex items-start justify-between gap-4">
    <div>
      <h2 class="text-2xl font-semibold tracking-tight text-slate-800">Self‑Assessment</h2>
      <p class="text-sm text-slate-500">Detailed responses and metadata for this assessment.</p>
    </div>
    <a href="{{ route('admin.self-assessments.index') }}"
       class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-white border border-slate-200 shadow-sm hover:bg-slate-50">
      ← Back to list
    </a>
  </div>

  {{-- Confidential note --}}
  <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
    🔐 <span class="font-medium">Confidential:</span> This information is restricted to counseling staff only.
  </div>

  {{-- Summary card --}}
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-2">
        <div class="text-xs uppercase text-slate-500">Assessment ID</div>
        <div class="font-semibold text-slate-900">{{ $code }}</div>

        <div class="text-xs uppercase text-slate-500 mt-4">Student Name</div>
        <div class="font-medium text-slate-900">{{ $student }}</div>

        <div class="text-xs uppercase text-slate-500 mt-4">Submitted On</div>
        <div class="font-medium text-slate-900">{{ $submitted }}</div>
      </div>

      <div class="space-y-2">
        <div class="text-xs uppercase text-slate-500">Assessment Result</div>
        @php
          $res = strtolower($resultVal);
          $badge = match ($res) {
            'mild anxiety'      => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
            'moderate anxiety'  => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
            'severe anxiety'    => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
            'normal'            => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
            default             => 'bg-slate-100 text-slate-700',
          };
        @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $badge }}">
          {{ $resultVal }}
        </span>

        @if(!empty($assessment->notes ?? null))
          <div class="text-xs uppercase text-slate-500 mt-4">Counselor Notes</div>
          <div class="text-slate-800">{{ $assessment->notes }}</div>
        @endif
      </div>
    </div>
  </div>

  {{-- Responses --}}
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200/70 p-5">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-base font-semibold text-slate-800">Student Responses</h3>
    </div>

    @if(is_array($answers) && count($answers))
      <div class="divide-y divide-slate-100">
        @foreach($answers as $idx => $row)
          @php
            $q = $row['question'] ?? (is_string($idx) ? $idx : 'Question '.($loop->iteration));
            $a = $row['answer']   ?? (is_scalar($row) ? $row : '—');
          @endphp
          <div class="py-4">
            <div class="text-xs uppercase text-slate-500">{{ $q }}</div>
            <div class="mt-1 text-slate-800">{{ $a }}</div>
          </div>
        @endforeach
      </div>
    @else
      <div class="py-12 text-center">
        <div class="mx-auto w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center">
          <img src="{{ asset('images/icons/nodata.png') }}" class="w-6 h-6 opacity-60" alt="">
        </div>
        <p class="mt-3 text-sm font-medium text-slate-700">No responses available</p>
        <p class="text-xs text-slate-500">This assessment doesn’t have recorded answers.</p>
      </div>
    @endif
  </div>

  {{-- Footer actions --}}
  <div class="flex items-center justify-end gap-2">
    <button type="button"
            onclick="window.print()"
            class="inline-flex items-center h-9 px-3 rounded-lg text-sm font-medium bg-white border border-slate-200 shadow-sm hover:bg-slate-50">
      Print
    </button>
  </div>

</div>
@endsection
