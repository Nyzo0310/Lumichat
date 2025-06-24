@extends('layouts.app')

@section('title', 'Chat')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="chat-container">
    <div id="chat-wrapper" class="chat-panel fade-in">
        {{-- Header --}}
        <div class="chat-header">
            <img src="{{ asset('images/chatbot.png') }}" class="w-6 h-6" alt="Bot">
            LumiCHAT Assistant
        </div>

        {{-- Messages --}}
        <div id="chat-messages" class="chat-messages">
            @foreach ($chats as $chat)
                @php($mine = $chat->sender !== 'bot')
                <div class="{{ $mine ? 'self-end text-right' : '' }}">
                    <span class="{{ $mine ? 'bubble-user' : 'bubble-bot' }}">
                        {{ $chat->message }}
                    </span>
                    <div class="bubble-timestamp">
                        {{ $chat->sent_at->format('H:i') }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Chat input --}}
        <div class="chat-input">
            <form id="chat-form" class="flex w-full gap-2">
                @csrf
                <input id="chat-message"
                       name="message"
                       class="input-field"
                       placeholder="Type your message..."
                       required
                       autocomplete="off">
                <button type="submit" class="send-button">Send</button>
            </form>
        </div>
    </div>
</div>

<div class="chat-footer-note">
    Your conversations are encrypted and private.
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('#chat-form');
    const input = document.querySelector('#chat-message');
    const chatContainer = document.querySelector('#chat-messages');

    if (!form || !input || !chatContainer) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        // Append user message to chat
        chatContainer.innerHTML += `
            <div class="self text-right">
                <div class="chat-message bg-indigo-500 text-white">${message}</div>
            </div>
        `;
        input.value = '';

        try {
            const rasaResponse = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message })
            });

            if (!rasaResponse.ok) {
                console.error('Rasa server error:', rasaResponse.statusText);
                return;
            }

            const data = await rasaResponse.json();
            console.log('Rasa response:', data);

            if (data.bot_reply && Array.isArray(data.bot_reply)) {
                data.bot_reply.forEach(reply => {
                    chatContainer.innerHTML += `
                        <div class="bot text-left">
                            <div class="chat-message bg-gray-200 text-gray-800">${reply}</div>
                        </div>
                    `;
                });
            }
        } catch (error) {
            console.error('Chat fetch error:', error);
        }
    });
});
</script>

<style>
.fade-in {
    animation: fadeIn 0.6s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endpush
