@extends('layouts.student-registration')

@section('content')
<div class="flex justify-center items-center min-h-screen py-20 bg-gray-200">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-2xl space-y-8 animate__animated animate__fadeIn">
        <h2 class="text-center text-xl font-bold text-gray-800">Registration Form</h2>

        <form method="POST" action="{{ route('register') }}" class="space-y-6">
            @csrf

            <!-- Personal Information Card -->
            <div class="bg-gray-100 p-6 rounded-xl shadow space-y-3">
                <h3 class="font-semibold text-gray-700">Personal Information</h3>
                <p class="text-sm text-gray-500">Share your name, email, and contact so we can stay in touch</p>

                <div class="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2">
                    <img src="{{ asset('images/icons/user.png') }}" class="w-5 h-5 mr-2" alt="User Icon">
                    <input name="full_name" type="text" placeholder="Enter your full name"
                    value="{{ old('full_name') }}"
                    class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400" required>
                </div>

                <div class="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2">
                    <img src="{{ asset('images/icons/mail.png') }}" class="w-5 h-5 mr-2" alt="Mail Icon">
                    <input name="email" type="email" placeholder="Your email@example.com"
                        value="{{ old('email') }}"
                        class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400" required>
                </div>

                <div class="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2">
                    <img src="{{ asset('images/icons/phone.png') }}" class="w-5 h-5 mr-2" alt="Phone Icon">
                    <input name="contact_number" type="text" placeholder="Enter your phone number"
                        value="{{ old('contact_number') }}"
                        class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400" required>
                </div>
            </div>

            <!-- Academic Information Card -->
            <div class="bg-gray-100 p-6 rounded-xl shadow space-y-3">
                <h3 class="font-semibold text-gray-700">Academic Information</h3>
                <p class="text-sm text-gray-500">Tell us your course and year level to access tailored support</p>

                <div class="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2">
                    <img src="{{ asset('images/icons/graduate.png') }}" class="w-5 h-5 mr-2" alt="Course Icon">
                    <select name="course" class="w-full bg-transparent text-sm outline-none border-none text-gray-700" required>
                        <option disabled {{ old('course') ? '' : 'selected' }}>Select your course</option>
                        <option value="BSIT" {{ old('course') == 'BSIT' ? 'selected' : '' }}>College of Information Technology</option>
                        <option value="EDUC" {{ old('course') == 'EDUC' ? 'selected' : '' }}>College of Education</option>
                        <option value="CAS" {{ old('course') == 'CAS' ? 'selected' : '' }}>College of Arts and Sciences</option>
                        <option value="CRIM" {{ old('course') == 'CRIM' ? 'selected' : '' }}>College of Criminal Justice and Public Safety</option>
                        <option value="BLIS" {{ old('course') == 'BLIS' ? 'selected' : '' }}>College of Library Information Science</option>
                        <option value="MIDWIFERY" {{ old('course') == 'MIDWIFERY' ? 'selected' : '' }}>College of Midwifery</option>
                        <option value="BSHM" {{ old('course') == 'BSHM' ? 'selected' : '' }}>College of Hospitality Management</option>
                        <option value="BSBA" {{ old('course') == 'BSBA' ? 'selected' : '' }}>College of Business</option>
                    </select>
                </div>
                <div class="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2">
                    <img src="{{ asset('images/icons/graduate.png') }}" class="w-5 h-5 mr-2" alt="Year Icon">
                    <select name="year_level" class="w-full bg-transparent text-sm outline-none border-none text-gray-700" required>
                        <option disabled {{ old('year_level') ? '' : 'selected' }}>Select your year level</option>
                        <option value="1st year" {{ old('year_level') == '1st year' ? 'selected' : '' }}>1st year</option>
                        <option value="2nd year" {{ old('year_level') == '2nd year' ? 'selected' : '' }}>2nd year</option>
                        <option value="3rd year" {{ old('year_level') == '3rd year' ? 'selected' : '' }}>3rd year</option>
                        <option value="4th year" {{ old('year_level') == '4th year' ? 'selected' : '' }}>4th year</option>
                    </select>
                </div>
            </div>
            <!-- Security Card -->
            <div class="bg-gray-100 p-6 rounded-xl shadow space-y-3">
                <h3 class="font-semibold text-gray-700">Security</h3>
                <p class="text-sm text-gray-500">Choose a strong password to keep your account secure</p>

                <div class="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2">
                    <img src="{{ asset('images/icons/lock.png') }}" class="w-5 h-5 mr-2" alt="Lock Icon">
                    <input name="password" type="password" placeholder="Create a strong password"
                        class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400" required>
                </div>

                <div class="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2">
                    <img src="{{ asset('images/icons/lock.png') }}" class="w-5 h-5 mr-2" alt="Lock Icon">
                    <input name="password_confirmation" type="password" placeholder="Confirm your password"
                        class="w-full bg-transparent text-sm outline-none border-none placeholder-gray-400" required>
                </div>
            </div>

            <!-- Terms + Submit -->
            <div class="space-y-3">
            <label class="inline-flex items-start text-sm text-gray-600">
                <input type="checkbox" id="agree" class="mt-1 mr-2 rounded border-gray-300">
                <span>
                    I agree to 
                    <a href="{{ route('privacy.policy') }}" class="text-blue-600 underline">LumiChat’s Privacy Policy</a>
                    and understand how my data will be used to provide mental health support services.
                </span>
            </label>

                <button type="submit" id="registerBtn" disabled
                    class="w-full bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-600 text-white py-2.5 rounded-lg font-medium shadow">
                    Register Account
                </button>
            </div>

            <p class="text-center text-sm text-gray-600 mt-4">
                Already have an account? <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Return to Login</a>
            </p>
        </form>
    </div>
</div>

<!-- SweetAlert CDN (MUST come before any SweetAlert usage) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                confirmButtonColor: '#e3342f'
            });
        @endif

        @if ($errors->has('email'))
            Swal.fire({
                icon: 'error',
                title: 'Email Already Used',
                text: '{{ $errors->first('email') }}',
                confirmButtonColor: '#e3342f'
            });
        @endif

        @if ($errors->has('password'))
            Swal.fire({
                icon: 'error',
                title: 'Password Issue',
                text: '{{ $errors->first('password') }}',
                confirmButtonColor: '#e3342f'
            });
        @endif
    });
</script>

<script>
    const agree = document.getElementById('agree');
    const registerBtn = document.getElementById('registerBtn');

    agree.addEventListener('change', function () {
        registerBtn.disabled = !this.checked;
    });
</script>
@endsection

