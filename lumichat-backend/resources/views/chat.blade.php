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

    <!-- Back inside the overlay -->
    <a href="{{ route('profile.edit') }}"
      class="absolute top-4 left-4 z-[60] flex items-center gap-2 px-4 py-2
              bg-white/20 backdrop-blur-sm border border-white/30 rounded-full
              hover:bg-white/30 transition text-sm text-white shadow-sm">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 18l-6-6 6-6"/>
      </svg>
      Back
    </a>

    <!-- Logo & Heading -->
    <img src="{{ asset('images/chatbot.png') }}" alt="Bot" class="w-16 h-16 mb-4 animate__animated animate__zoomIn">
    <h1 class="text-4xl font-extrabold leading-snug text-center mb-6 animate__animated animate__bounceInDown">
      Hey there!<br>How are you feeling today?
    </h1>

    <!-- Quick chips -->
    <div class="flex flex-wrap justify-center gap-3 mb-6">
      @foreach(['Happy','Sad','Anxious','Stressed','Curious'] as $feel)
        <button
          class="px-5 py-2 bg-white/20 backdrop-blur-sm rounded-full hover:bg-white/40 transition font-medium animate__animated animate__pulse animate__infinite animate__slow"
          onclick="document.getElementById('greeting-input').value='I am feeling {{ strtolower($feel) }}'; document.getElementById('greeting-send').click()">
          {{ $feel }}
        </button>
      @endforeach
    </div>

    <!-- Input + Start button -->
    <form id="greeting-form" class="w-full max-w-md mx-auto" onsubmit="return false;">
      <div class="relative">
        <input id="greeting-input" type="text"
              placeholder="Type a feeling or question..."
              class="w-full py-4 pl-5 pr-28 rounded-full text-gray-900 text-base
                      shadow-lg focus:outline-none focus:ring-2 focus:ring-white/80
                      placeholder:text-gray-700 bg-white/90"/>
        <button id="greeting-send" type="button"
                class="absolute top-1/2 right-2 -translate-y-1/2
                      px-5 py-2 rounded-full text-white text-sm font-medium
                      bg-indigo-500 disabled:bg-indigo-400/60 hover:bg-indigo-600 transition shadow">
          Start
        </button>
      </div>
      <p class="text-sm opacity-90 mt-6 text-white/90 animate__animated animate__fadeInUp">
        We prioritize your mental health and privacy. Your chats are safe with us.
      </p>
    </form>
  </div>

  <!-- 💬 Chat Panel -->
  <div id="chat-wrapper"
       class="chat-panel bg-white rounded-xl overflow-hidden shadow-lg hidden animate__animated animate__slideInRight
              flex flex-col w-full max-w-[1040px] h-[calc(100vh-160px)]">

    <!-- Header (NO back button here) -->
    <div class="chat-header flex items-center gap-3 bg-gradient-to-r from-indigo-600 to-purple-600
                text-white px-5 py-3 rounded-t-xl shadow">
      <img src="{{ asset('images/chatbot.png') }}" class="w-6 h-6" alt="Bot">
      <strong class="text-lg">LumiCHAT Assistant</strong>
    </div>

    <!-- Messages -->
    <div id="chat-messages" class="flex-1 min-h-0 flex flex-col gap-3 p-4 overflow-y-auto bg-gray-50">
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
          <div class="text-[10px] text-gray-400 mt-1">
            {{ \Carbon\Carbon::parse($chat->sent_at)->format('H:i') }}
          </div>
        </div>
      @endforeach
    </div>

    <!-- Input bar -->
    <form id="chat-form" class="flex items-center gap-2 px-4 py-3 border-t bg-white">
      @csrf
      <div class="group flex-1 flex items-center rounded-full bg-white
                  ring-1 ring-indigo-200 focus-within:ring-2 focus-within:ring-indigo-400
                  transition shadow-sm">
        <input id="chat-message" name="message"
               class="flex-1 px-4 py-2 rounded-l-full
                      bg-transparent placeholder:text-gray-400
                      border-0 outline-none ring-0 focus:ring-0 focus:outline-none appearance-none"
               placeholder="Type your message..." autocomplete="off" required>
        <button type="submit"
                class="px-5 py-2 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow
                       transition m-1">
          Send
        </button>
      </div>
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
  const overlay      = document.getElementById('greeting-overlay');
  const chatWrap     = document.getElementById('chat-wrapper');
  const showGreet    = @json($showGreeting);

  // Greeting controls
  const greetingForm = document.getElementById('greeting-form');
  const greetingIn   = document.getElementById('greeting-input');
  const greetingBt   = document.getElementById('greeting-send');

  // Chat controls
  const messages     = document.getElementById('chat-messages');
  const form         = document.getElementById('chat-form');
  const input        = document.getElementById('chat-message');

  // Routes
  const STORE_URL    = @json(route('chat.store'));
  const NEW_URL      = @json(route('chat.new'));

  // Initial visibility
  setTimeout(() => {
    if (showGreet) overlay.classList.remove('hidden');
    else { overlay.classList.add('hidden'); chatWrap.classList.remove('hidden'); }
  }, 100);

  // Enable/disable "Start"
  const toggleStartBtn = () => { greetingBt.disabled = !greetingIn.value.trim(); };
  greetingIn.addEventListener('input', toggleStartBtn);
  toggleStartBtn();

  async function sendMessage(text) {
    if (!text.trim()) return;

    // Append user bubble
    messages.insertAdjacentHTML('beforeend', `
      <div class="self-end text-right animate__animated animate__zoomIn">
        <div class="inline-block bg-indigo-500 text-white px-4 py-2 rounded-2xl max-w-xs mb-2">${text}</div>
      </div>
    `);
    messages.scrollTop = messages.scrollHeight;

    // Request bot replies
    const res = await fetch(STORE_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ message: text })
    });

    let data; try { data = await res.json(); } catch (e) { data = {}; }
    if (data.bot_reply && Array.isArray(data.bot_reply)) {
      for (let reply of data.bot_reply) {
        messages.insertAdjacentHTML('beforeend', `
          <div class="self-start animate__animated animate__fadeIn">
            <div class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-2xl max-w-xs mb-2">${reply}</div>
          </div>
        `);
        messages.scrollTop = messages.scrollHeight;
      }
    }
  }

  async function startFromGreeting() {
    const txt = greetingIn.value.trim();
    if (!txt) return;

    // New chat session
    try { await fetch(NEW_URL, { method: 'GET' }); } catch(e) {}

    // Hide overlay, show chat
    overlay.classList.add('animate__fadeOut');
    setTimeout(() => overlay.classList.add('hidden'), 300);
    chatWrap.classList.remove('hidden');

    // Send first message
    await sendMessage(txt);
    greetingIn.value = '';
    toggleStartBtn();
  }

  // Greeting events
  greetingBt.addEventListener('click', startFromGreeting);
  greetingForm.addEventListener('submit', (e) => { e.preventDefault(); startFromGreeting(); });
  greetingIn.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); startFromGreeting(); }
  });

  // Chat form
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const txt = input.value.trim();
    if (!txt) return;
    input.value = '';
    await sendMessage(txt);
  });
});
</script>
@endpush
