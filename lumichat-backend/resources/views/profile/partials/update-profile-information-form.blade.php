@php
    $reg = $registration ?? null;

    // You can move these to config later if you want (config/lumichat.php)
    $courses = [
        'BSIT' => 'College of Information Technology',
        'EDUC' => 'College of Education',
        'CAS' => 'College of Arts and Sciences',
        'CRIM' => 'College of Criminal Justice and Public Safety',
        'BLIS' => 'College of Library Information Science',
        'MIDWIFERY' => 'College of Midwifery',
        'BSHM' => 'College of Hospitality Management',
        'BSBA' => 'College of Business',
    ];

    $yearLevels = [
        '1st year' => '1st year',
        '2nd year' => '2nd year',
        '3rd year' => '3rd year',
        '4th year' => '4th year',
    ];
@endphp

<div class="space-y-4">
  {{-- READ-ONLY CARD --}}
  <div class="rounded-xl bg-white shadow-sm p-6 relative">
    <div class="flex items-start justify-between mb-4">
      <div>
        <h3 class="text-lg font-semibold">Profile Information</h3>
        <p class="text-sm text-gray-500">Update your account’s profile information and email address.</p>
      </div>

      <button type="button"
              data-edit-profile-btn
              class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        Edit profile
      </button>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
      <div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Name</div>
        <div class="mt-1 font-medium text-gray-900">{{ $user->name }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Email</div>
        <div class="mt-1 font-medium text-gray-900 break-all">{{ $user->email }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Course</div>
        <div class="mt-1 font-medium text-gray-900">{{ $reg->course ?? '—' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Year Level</div>
        <div class="mt-1 font-medium text-gray-900">{{ $reg->year_level ?? '—' }}</div>
      </div>
      <div class="sm:col-span-2">
        <div class="text-xs text-gray-500 uppercase tracking-wide">Contact Number</div>
        <div class="mt-1 font-medium text-gray-900">{{ $reg->contact_number ?? '—' }}</div>
      </div>
    </div>
  </div>

  {{-- EDIT FORM (HIDDEN UNTIL CLICK) --}}
  <div data-edit-profile-form class="rounded-xl bg-white shadow-sm p-6 hidden">
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
      @csrf
      @method('PUT')

      <div class="grid gap-5 sm:grid-cols-2">
        {{-- Name --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Name</label>
          <input id="edit-name" name="name" type="text"
                 class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                 value="{{ old('name', $user->name) }}" required>
          @error('name') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input name="email" type="email"
                 class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 break-all"
                 value="{{ old('email', $user->email) }}" required>
          @error('email') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Course (select) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Course</label>
          <select name="course"
                  class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white">
            <option value="" disabled {{ old('course', $reg->course ?? '') === '' ? 'selected' : '' }}>
              Select your course
            </option>
            @foreach($courses as $value => $label)
              <option value="{{ $value }}"
                {{ old('course', $reg->course ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Year Level (select) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700">Year Level</label>
          <select name="year_level"
                  class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white">
            <option value="" disabled {{ old('year_level', $reg->year_level ?? '') === '' ? 'selected' : '' }}>
              Select your year level
            </option>
            @foreach($yearLevels as $value => $label)
              <option value="{{ $value }}"
                {{ old('year_level', $reg->year_level ?? '') === $value ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Contact Number --}}
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Contact Number</label>
          <input name="contact_number" type="text"
                 class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                 value="{{ old('contact_number', $reg->contact_number ?? '') }}">
        </div>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
          Save changes
        </button>

        <button type="button"
                data-edit-cancel
                class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">
          Cancel
        </button>
      </div>
    </form>
  </div>
</div>
