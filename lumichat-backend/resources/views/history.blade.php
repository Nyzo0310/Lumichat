@extends('layouts.app')

@section('title', 'Chat History')

@section('content')
<div class="px-6 py-8 bg-gradient-to-b from-purple-800 to-purple-600 min-h-screen">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-purple-900 mb-6">History</h1>

        @forelse($chats as $date => $chatGroup)
            @php $firstMessage = $chatGroup->first(); @endphp
            <div class="bg-gray-100 rounded shadow p-4 mb-4 relative">
                <div class="text-lg font-medium text-gray-900 mb-1">
                    {{ $firstMessage->message }}
                </div>
                <div class="text-sm text-gray-600">
                    {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
                </div>
                <div class="text-xs text-gray-500 mt-2">
                    Last interaction: {{ $chatGroup->last()->sent_at->format('g:i A, F d, Y') }}
                </div>
                <form action="{{ route('chat.destroy', $firstMessage->id) }}" method="POST" class="absolute top-4 right-4">
                    @csrf
                    @method('DELETE')
                    <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition">
                        Delete
                    </button>
                </form>
            </div>
        @empty
            <p class="text-white text-center">No chat history yet.</p>
        @endforelse
    </div>
</div>
@endsection
