@extends('layouts.app')

@section('title', 'Profile')

@section('content')
@php
    $hour = now()->hour;
    $greeting = match(true) {
        $hour < 12 => 'Good morning',
        $hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
@endphp

<div class="py-20">
    <div class="max-w-4xl mx-auto px-4 space-y-10">

        {{-- Dynamic Greeting --}}
        <div class="profile-greeting-wrapper">
            <h2>
                Good 
                {{ 
                    now()->hour < 12 ? 'morning' : 
                    (now()->hour < 18 ? 'afternoon' : 'evening') 
                }}, {{ Auth::user()->name }}
            </h2>
            <p>Welcome back! You can manage your personal information and security settings below.</p>
        </div>


        {{-- Update Profile Information --}}
        <div class="p-6 bg-white shadow-md rounded-md">
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- Update Password --}}
        <div class="p-6 bg-white shadow-md rounded-md">
            @include('profile.partials.update-password-form')
        </div>

        {{-- Delete Account --}}
        <div class="p-6 bg-white shadow-md rounded-md">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>

@endsection
