@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h2 class="text-3xl font-bold mb-6">Your Chat History</h2>

    @foreach ($sessions as $session)
        <div class="bg-white rounded-xl shadow-md p-6 mb-5 border border-gray-200">
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h3 class="text-xl font-semibold text-purple-700">{{ $session->topic_summary ?? '[No Title]' }}</h3>
                    <p class="text-sm text-gray-500">
                        Last interaction: {{ $session->updated_at->diffForHumans() }}
                    </p>
                </div>
                <form method="POST" action="{{ route('chat.deleteSession', $session->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                        Delete
                    </button>
                </form>
            </div>

            <a href="{{ url('/chat/view/' . $session->id) }}"
               class="text-blue-600 hover:underline text-sm">
                View full conversation →
            </a>
        </div>
    @endforeach

    @if ($sessions->isEmpty())
        <p class="text-gray-600">No chat sessions found yet.</p>
    @endif
</div>
@endsection
