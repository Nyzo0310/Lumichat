@extends('layouts.student-guest')

@section('content')
<div class="bg-white p-10 rounded-2xl shadow-xl w-full max-w-md animate__animated animate__fadeIn">
    <div class="flex flex-col items-center mb-6">
        <img src="{{ asset('images/chatbot.png') }}" alt="LumiChat Logo" class="w-14 h-14 rounded-full mb-3 shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 font-poppins">LumiChat</h2>
        <p class="text-sm text-gray-500">Your mental health support companion</p>
    </div>

    @if ($errors->any())
    <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email or Student ID -->
        <div>
            <label class="block text-gray-700 text-sm mb-1 font-medium">Email or Student ID</label>
            <div class="flex items-center bg-gray-100 border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 ring-blue-300">
                <img src="{{ asset('images/icons/mail.png') }}" alt="Mail Icon" class="w-5 h-5 mr-2">
                <input
                    type="text"
                    name="email"
                    placeholder="Enter your email or Student ID"
                    required
                    autofocus
                    class="w-full bg-transparent text-sm placeholder-gray-400 appearance-none border-none shadow-none
                        hover:shadow-none hover:border-transparent
                        focus:outline-none focus:ring-0 focus:border-transparent"
                />
            </div>
        </div>

        <!-- Password -->
        <div>
            <label class="block text-gray-700 text-sm mb-1 font-medium">Password</label>
            <div class="flex items-center bg-gray-100 border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-2 ring-blue-300">
                <img src="{{ asset('images/icons/lock.png') }}" alt="Lock Icon" class="w-5 h-5 mr-2">
                <input
                    type="password"
                    name="password"
                    autocomplete="off"
                    placeholder="Enter your password"
                    required
                    class="w-full bg-transparent text-sm placeholder-gray-400 appearance-none border-none shadow-none
                        hover:shadow-none hover:border-transparent
                        focus:outline-none focus:ring-0 focus:border-transparent"
                />
            </div>
        </div>

        <!-- Options -->
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center">
                <input type="checkbox" class="form-checkbox text-blue-500 rounded">
                <span class="ml-2 text-gray-700">Remember me</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">Forgot password?</a>
        </div>

        <!-- Login Button -->
        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 transition text-white py-2.5 rounded-lg font-medium shadow">
            Login
        </button>

        <!-- Footer -->
        <p class="text-center text-sm text-gray-600 mt-4">
            Don't have an account? <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-medium">Sign up here</a>
        </p>
    </form>
</div>

@if (session('success'))
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Registration Successful!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Continue'
        });
    </script>
@endif

@endsection

