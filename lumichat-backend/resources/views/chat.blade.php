@extends('layouts.app')
@section('title', 'Chat')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="chat-container relative">

  <!-- 🎉 Welcome Overlay -->
  <div id="greeting-overlay"
       class="fixed inset-0 bg-gradient-to-br from-purple-600 via-violet-500 to-indigo-500
              flex flex-col items-center justify-center text-white z-50 px-4
              animate__animated animate__fadeIn">

    <!-- Back Button -->
    <a href="{{ route('profile.edit') }}"
       class="absolute top-4 left-4 flex items-center gap-2 px-4 py-2
              bg-white/20 backdrop-blur-sm border border-white/30 rounded-full
              hover:bg-white/30 transition text-sm animate__animated animate__fadeInLeft">
      <svg class="w-4 h-4" …>…</svg> Back
    </a>

    <!-- Logo & Heading -->
    <img src="{{ asset('images/chatbot.png') }}" alt="Bot" class="w-16 h-16 mb-4 animate__animated animate__zoomIn">
    <h1 class="text-4xl font-extrabold leading-snug text-center mb-6
               animate__animated animate__bounceInDown">
      Hey there!<br>How are you feeling today?
    </h1>

    <!-- Quick-pick emotion chips -->
    <div class="flex flex-wrap justify-center gap-3 mb-6">
      @foreach(['Happy','Sad','Anxious','Stressed','Curious'] as $feel)
        <button class="px-5 py-2 bg-white/20 backdrop-blur-sm rounded-full
                       hover:bg-white/40 transition font-medium animate__animated animate__pulse animate__infinite animate__slow"
                onclick="document.getElementById('greeting-input').value='I am feeling {{ strtolower($feel) }}'; document.getElementById('greeting-send').click()">
          {{ $feel }}
        </button>
      @endforeach
    </div>

    <!-- Input + Send -->
    <div class="relative w-full max-w-md mx-auto">
      <input id="greeting-input" type="text" placeholder="Type a feeling or question..."
             class="w-full py-3 pl-5 pr-14 rounded-full text-gray-800 text-sm shadow-lg focus:outline-none focus:ring-2 focus:ring-white"/>

      <button id="greeting-send"
              class="absolute top-1/2 right-2 -translate-y-1/2
                     p-3 bg-indigo-500 hover:bg-indigo-600 rounded-full
                     shadow-lg transition animate__animated animate__fadeInUp">
        <svg class="w-5 h-5" fill="currentColor" …>…</svg>
      </button>
    </div>

    <p class="text-sm opacity-90 mt-6 animate__animated animate__fadeInUp">
      We prioritize your mental health and privacy. Your chats are safe with us.
    </p>
  </div>

  <!-- 💬 Chat Panel -->
  <div id="chat-wrapper"
       class="chat-panel bg-white rounded-xl overflow-hidden shadow-lg
              hidden animate__animated animate__slideInRight">
    <div class="chat-header flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4">
      <img src="{{ asset('images/chatbot.png') }}" class="w-6 h-6" alt="Bot">
      <strong class="text-lg">LumiCHAT Assistant</strong>
    </div>

    <div id="chat-messages" class="chat-messages flex flex-col gap-3 p-4 overflow-y-auto">
      <div id="loading-indicator"
           class="self-start italic text-gray-500 hidden animate__animated animate__fadeIn">
        Typing...
      </div>
      @foreach ($chats as $chat)
        @php($mine = $chat->sender !== 'bot')
        <div class="{{ $mine ? 'self-end text-right' : 'self-start' }}">
          <div class="inline-block max-w-xs px-4 py-2 rounded-2xl text-sm
                      {{ $mine ? 'bg-indigo-500 text-white animate__animated animate__zoomIn' 
                               : 'bg-gray-200 text-gray-800 animate__animated animate__fadeIn' }}">
            {{ $chat->message }}
          </div>
          <div class="text-[10px] text-gray-400 mt-1">{{ \Carbon\Carbon::parse($chat->sent_at)->format('H:i') }}</div>
        </div>
      @endforeach
    </div>

    <form id="chat-form" class="chat-input flex gap-2 px-4 py-3 border-t">
      @csrf
      <input id="chat-message" name="message"
             class="flex-1 border border-indigo-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
             placeholder="Type your message..." autocomplete="off" required>
      <button type="submit"
              class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow
                     transition transform hover:-translate-y-0.5 hover:shadow-lg">
        Send
      </button>
    </form>
  </div>

</div>

<p class="chat-footer-note text-center text-gray-400 text-xs mt-4">
  Your conversations are encrypted and private.
</p>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
  const overlay    = document.getElementById('greeting-overlay');
  const chatWrap   = document.getElementById('chat-wrapper');
  const showGreet  = @json($showGreeting);
  const greetingIn = document.getElementById('greeting-input');
  const greetingBt = document.getElementById('greeting-send');
  const input      = document.getElementById('chat-message');
  const messages   = document.getElementById('chat-messages');
  const form       = document.getElementById('chat-form');
  const STORE_URL  = @json(route('chat.store'));

  setTimeout(() => {
    if (showGreet) overlay.classList.remove('hidden');
    else {
      overlay.classList.add('hidden');
      chatWrap.classList.remove('hidden');
    }
  }, 100);

  async function sendMessage(text) {
    if (!text.trim()) return;
    // append user
    messages.innerHTML += `
      <div class="self-end text-right animate__animated animate__zoomIn">
        <div class="inline-block bg-indigo-500 text-white px-4 py-2 rounded-2xl max-w-xs mb-2">${text}</div>
      </div>`;
    messages.scrollTop = messages.scrollHeight;

    // fetch bot replies
    const res = await fetch(STORE_URL, {
      method: 'POST',
      headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
      body: JSON.stringify({ message: text })
    });
    const data = await res.json();
    if (data.bot_reply) {
      for (let reply of data.bot_reply) {
        messages.innerHTML += `
          <div class="self-start animate__animated animate__fadeIn">
            <div class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-2xl max-w-xs mb-2">${reply}</div>
          </div>`;
        messages.scrollTop = messages.scrollHeight;
      }
    }
  }

  greetingBt.addEventListener('click', async () => {
    const txt = greetingIn.value.trim();
    if (!txt) return;
    await fetch("{{ route('chat.new') }}");
    overlay.classList.add('animate__fadeOut');
    setTimeout(()=> overlay.classList.add('hidden'), 400);
    chatWrap.classList.remove('hidden');
    await sendMessage(txt);
    greetingIn.value = '';
  });

  form.onsubmit = async e => {
    e.preventDefault();
    const txt = input.value.trim();
    if (!txt) return;
    input.value = '';
    await sendMessage(txt);
  };

  greetingIn.onkeydown = e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      greetingBt.click();
    }
  };
});
</script>
@endpush
