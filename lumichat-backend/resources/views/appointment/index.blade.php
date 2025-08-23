@extends('layouts.app')
@section('title','Appointment')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Book Appointment</h2>
    <a href="{{ route('appointment.history') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
      View Appointment
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </a>
  </div>

  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <form method="POST" action="{{ route('appointment.store') }}" class="space-y-6">
      @csrf

      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Select a counselor and time slot *
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <select id="counselorSelect" name="counselor_id"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
              <option value="">Select a counselor</option>
              @foreach($counselors as $c)
                <option value="{{ $c->id }}" @selected(old('counselor_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
            @error('counselor_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            <p class="mt-2 text-xs text-gray-500">Available time slots load after picking a counselor and date.</p>
          </div>

          <div>
            <select id="timeSelect" name="time" disabled
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
              <option value="">No available slots</option>
            </select>
            <div id="timeLoading" class="hidden mt-2 text-xs text-gray-500">Loading available times…</div>
            @error('time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Choose a preferred date *</label>
          <input id="dateInput" type="date" name="date" value="{{ old('date') }}"
                 min="{{ now()->toDateString() }}"
                 class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
          @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div></div>
      </div>

      <label class="flex items-center gap-2">
        <input type="checkbox" name="consent" value="1"
               class="h-4 w-4 rounded border-gray-300 dark:border-gray-600" {{ old('consent') ? 'checked' : '' }}>
        <span class="text-sm text-gray-600 dark:text-gray-300">
          I understand that my information will be handled according to LumiCHAT’s privacy policy.
        </span>
      </label>
      @error('consent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

      <div class="flex items-center gap-4 pt-2">
        <a href="{{ route('chat.index') }}"
           class="inline-flex justify-center rounded-lg bg-gray-100 px-6 py-3 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
          Cancel
        </a>
        <button type="submit"
                class="inline-flex justify-center rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white hover:bg-indigo-700">
          Confirm Appointment
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const counselorSel = document.getElementById('counselorSelect');
  const dateInput    = document.getElementById('dateInput');
  const timeSel      = document.getElementById('timeSelect');
  const loadingEl    = document.getElementById('timeLoading');

  // GUARANTEED trailing slash (prevents /appointment/slots8 404)
  const slotsBase = (@json(url('/appointment/slots')) + '/');

  /* ========== SweetAlert helpers ========== */
  const toast = (title, icon='info', timer=2500) => {
    Swal.fire({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer,
      icon,
      title
    });
  };
  const alertHtmlList = (title, items, icon='error') => {
    const html = '<ul style="text-align:left;margin:0;padding-left:1rem">'
               + items.map(i => `<li>• ${i}</li>`).join('')
               + '</ul>';
    Swal.fire({ icon, title, html });
  };

  /* --- Show success or validation errors after POST redirect --- */
  const successMsg = @json(session('status'));
  const pageErrors = @json($errors->all());
  if (successMsg) {
    Swal.fire({ icon: 'success', title: 'Success', text: successMsg, timer: 2200, showConfirmButton: false });
  }
  if (Array.isArray(pageErrors) && pageErrors.length) {
    alertHtmlList('Please fix the following', pageErrors, 'error');
  }

  /* ========== Slots loader ========== */
  function clearTimes(placeholder = 'Choose a preferred time *') {
    timeSel.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    timeSel.appendChild(opt);
    timeSel.disabled = true;
  }

  function isWeekend(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    const day = d.getDay(); // 0=Sun..6=Sat
    return day === 0 || day === 6;
  }

  async function loadSlots() {
    const cid  = counselorSel.value;
    const date = dateInput.value;
    if (!cid || !date) { clearTimes('No available slots'); return; }

    if (isWeekend(date)) {
      clearTimes('No counselor availability on weekends (Mon–Fri)');
      toast('Counselors are available Mon–Fri only.', 'info');
      return;
    }

    loadingEl.classList.remove('hidden');
    clearTimes('Loading…');

    try {
      const url = slotsBase + encodeURIComponent(cid) + '?date=' + encodeURIComponent(date);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

      // network/server error
      if (!res.ok) {
        clearTimes('Unable to load slots');
        toast('Failed to load time slots.', 'error');
        return;
      }

      const data = await res.json();

      timeSel.innerHTML = '';
      if (Array.isArray(data.slots) && data.slots.length) {
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = 'Choose a preferred time *';
        timeSel.appendChild(ph);

        data.slots.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.value;
          opt.textContent = s.label;
          timeSel.appendChild(opt);
        });

        const oldVal = @json(old('time'));
        if (oldVal) {
          [...timeSel.options].forEach(o => { if (o.value === oldVal) o.selected = true; });
        }
        timeSel.disabled = false;
      } else {
        const reason  = data.reason || '';
        const message = data.message || '';

        if (reason === 'weekend') {
          clearTimes('No counselor availability on weekends (Mon–Fri)');
        } else if (reason === 'no_availability') {
          clearTimes('No availability for that day');
        } else if (reason === 'fully_booked') {
          clearTimes('All slots are booked for that date');
        } else if (reason === 'no_slots') {
          clearTimes('No available slots within working hours');
        } else {
          clearTimes('No available slots');
        }

        if (message) toast(message, 'info');
      }
    } catch (e) {
      console.error('Failed to load slots', e);
      clearTimes('Unable to load slots');
      toast('Something went wrong while loading slots.', 'error');
    } finally {
      loadingEl.classList.add('hidden');
    }
  }

  counselorSel.addEventListener('change', loadSlots);
  dateInput.addEventListener('change', loadSlots);

  // Pre-load on page open if values are preselected
  if (counselorSel.value && dateInput.value) loadSlots();
});
</script>
@endpush
@endsection
