@extends('layouts.admin')
@section('title','Appointments')

@section('content')
{{-- ===== Page header ===== --}}
<div class="mb-4">
  <h2 class="text-2xl font-bold tracking-tight text-slate-800">Appointments</h2>
  <p class="text-sm text-slate-500">Review, confirm, or reschedule student counseling sessions.</p>
</div>

{{-- ===== Filters + Search row ===== --}}
<div class="flex items-center justify-between gap-3 mb-4">
  <!-- Filters button -->
  <button id="filtersToggle"
          class="inline-flex items-center gap-2 h-9 px-3 rounded-lg text-sm font-medium
                 bg-white border border-slate-200 shadow-sm hover:bg-slate-50 active:bg-slate-100">
    <img src="{{ asset('images/icons/filter.png') }}" alt="Filter" class="w-4 h-4 opacity-80">
    Filters
    @if(request('status') || request('date'))
      <span class="ml-1 inline-flex h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
    @endif
    <svg class="ml-1 w-3.5 h-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
      <path d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/>
    </svg>
  </button>

  <!-- Search box -->
  <form method="GET" action="{{ route('admin.appointments.index') }}" class="relative w-full max-w-xs">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <input type="hidden" name="date"   value="{{ request('date') }}">
    <label for="qInput" class="sr-only">Search student</label>
    <input id="qInput" name="q" value="{{ request('q') }}" placeholder="Search student..."
           class="w-full h-9 rounded-lg border border-slate-200 bg-white pl-10 pr-9 text-sm
                  placeholder:text-slate-400 shadow-sm
                  focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" />
    <img src="{{ asset('images/icons/search.png') }}"
         alt="Search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-70 pointer-events-none">
  </form>
</div>


  {{-- ===== Collapsible filter panel ===== --}}
  <div id="filtersPanel" class="hidden bg-white border border-slate-200 rounded-xl shadow-sm p-5">
    <form method="GET" action="{{ route('admin.appointments.index') }}">
      <input type="hidden" name="q" value="{{ request('q') }}">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="md:col-span-4">
          <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
          <select name="status"
                  class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm bg-white
                         focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
            <option value=""         {{ request('status')==='' ? 'selected' : '' }}>All statuses</option>
            <option value="pending"   {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
            <option value="confirmed" {{ request('status')==='confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="canceled"  {{ request('status')==='canceled' ? 'selected' : '' }}>Canceled</option>
          </select>
        </div>
        <div class="md:col-span-4">
          <label class="block text-xs font-medium text-slate-600 mb-1">Date</label>
          <input type="date" name="date" value="{{ request('date') }}"
                 class="w-full h-10 rounded-lg border border-slate-200 px-3 text-sm bg-white
                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
        </div>
        <div class="md:col-span-4 flex items-end gap-2 md:justify-end">
          <a href="{{ route('admin.appointments.index') }}"
             class="h-10 px-4 rounded-lg text-xs font-medium border border-slate-200 text-slate-700 hover:bg-slate-50">
            Reset
          </a>
          <button type="submit"
                  class="h-10 px-4 rounded-lg text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
            Apply Filters
          </button>
        </div>
      </div>
    </form>
  </div>

  {{-- ===== Table ===== --}}
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-[720px] w-full text-sm text-left">
        <thead class="bg-slate-200 text-slate-800 shadow-sm">
          <tr>
            <th class="px-6 py-3 font-semibold">Student Name</th>
            <th class="px-6 py-3 font-semibold">Counselor Name</th>
            <th class="px-6 py-3 font-semibold">Date &amp; Time</th>
            <th class="px-6 py-3 font-semibold">Status</th>
            <th class="px-6 py-3 text-right font-semibold">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @php
            $badge = function ($status) {
              $s = strtolower(trim($status));
              return [
                'pending'   => ['pill'=>'bg-amber-50 text-amber-700 ring-1 ring-amber-200','dot'=>'bg-amber-500','label'=>'Pending'],
                'confirmed' => ['pill'=>'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200','dot'=>'bg-emerald-500','label'=>'Confirmed'],
                'canceled'  => ['pill'=>'bg-rose-50 text-rose-700 ring-1 ring-rose-200','dot'=>'bg-rose-500','label'=>'Canceled'],
              ][$s] ?? ['pill'=>'bg-slate-100 text-slate-700','dot'=>'bg-slate-500','label'=>ucfirst($s ?: 'N/A')];
            };
          @endphp

          {{-- Example row (replace with @foreach $appointments as $appt) --}}
          @php $status = 'pending'; $b = $badge($status); @endphp
          <tr class="hover:bg-slate-50 transition">
            <td class="px-6 py-4">
              <div class="font-medium text-slate-800">Juan De La Cruz</div>
              <div class="text-xs text-slate-500">ID: STU-0001</div>
            </td>
            <td class="px-6 py-4">Ms. Grace Santos</td>
            <td class="px-6 py-4">July 30, 2025 · 10:30 AM</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $b['pill'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $b['dot'] }}"></span>
                {{ $b['label'] }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <a href="#"
                 class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-medium
                        bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                View
              </a>
            </td>
          </tr>

          {{-- Empty state --}}
          <tr>
            <td colspan="5" class="px-6 py-14 text-center">
              <div class="mx-auto w-full max-w-sm">
                <div class="mx-auto w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mt-4">
                <img src="{{ asset('images/icons/nodata.png') }}" alt="" class="w-6 h-6 opacity-60">
                </div>
                <p class="mt-3 text-sm font-medium text-slate-700">No appointments found</p>
                <p class="text-xs text-slate-500 mb-6">New bookings will appear here once students schedule a session.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- ===== Minimal JS: toggle filters, remember state, search clear ===== --}}
<script>
(function () {
  const panel = document.getElementById('filtersPanel');
  const toggleBtn = document.getElementById('filtersToggle');
  const LS_KEY = 'appointmentsFiltersOpen';

  // Restore panel state
  if (localStorage.getItem(LS_KEY) === '1') {
    panel.classList.remove('hidden');
  }

  toggleBtn?.addEventListener('click', () => {
    const willOpen = panel.classList.contains('hidden');
    panel.classList.toggle('hidden');
    localStorage.setItem(LS_KEY, willOpen ? '1' : '0');
  });

  // Search clear button
  const q = document.getElementById('qInput');
  const clear = document.getElementById('qClear');
  const updateClear = () => clear?.classList.toggle('hidden', !q?.value);
  q?.addEventListener('input', updateClear);
  clear?.addEventListener('click', () => { q.value=''; q.focus(); updateClear(); });
  updateClear();
})();
</script>
@endsection
