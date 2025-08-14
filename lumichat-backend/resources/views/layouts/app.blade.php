<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>LumiCHAT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts & Tailwind/Vite -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Animate.css (optional) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>

<body class="bg-gray-50">
  {{-- Always‐visible “open sidebar” button --}}
  <button class="sidebar-open-btn text-2xl p-2 rounded-md shadow-md hover:bg-white transition" aria-label="Open sidebar">☰</button>

  <div class="layout-wrapper">

    <!-- Sidebar -->
   <div id="sidebar"
     class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b
            from-indigo-800 to-indigo-900 text-white shadow-xl
            flex flex-col transition-transform duration-300 rounded-br-lg">

     <!-- Logo + Close (match main header height & padding) -->
        <div class="flex items-center justify-between px-4 py-5 border-b border-indigo-700">
            <div class="flex items-center gap-2">
            <img src="{{ asset('images/chatbot.png') }}" alt="Logo" class="w-7 h-7">
            <span class="text-lg font-semibold bg-gradient-to-r
                        from-blue-300 to-indigo-400 bg-clip-text text-transparent">
                LumiCHAT
            </span>
            </div>
            <button class="sidebar-close-btn p-2 rounded-md hover:bg-white/20 transition" title="Close sidebar">✕</button>
        </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-3  pt-5 space-y-1">
        {{-- Home --}}
        <a href="{{ route('chat.index') }}"
           class="flex items-center gap-3 px-4 py-2 rounded-md transition-colors duration-200
                  {{ request()->routeIs('chat.index') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Home
        </a>

        {{-- Profile --}}
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 px-4 py-2 rounded-md transition-colors duration-200
                  {{ request()->routeIs('profile.edit') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.746 6.879 2.021M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Profile
        </a>

        {{-- Chat History --}}
        <a href="{{ route('chat.history') }}"
           class="flex items-center gap-3 px-4 py-2 rounded-md transition-colors duration-200
                  {{ request()->routeIs('chat.history') ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Chat History
        </a>

        {{-- Appointments --}}
       @if(false)
        <a href="#"
           class="flex items-center gap-3 px-4 py-2 rounded-md text-white/80 hover:bg-white/10 hover:text-white transition-colors duration-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12
                     a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Appointments
        </a>
        @endif
        
        {{-- Settings --}}
        <a href="#"
           class="flex items-center gap-3 px-4 py-2 rounded-md text-white/80 hover:bg-white/10 hover:text-white transition-colors duration-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 4v2m6.364 1.636l-1.414 1.414M18 12h-2
                     M6 12H4m1.636 6.364l1.414-1.414M12 20v-2
                     M5.636 5.636l1.414 1.414"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Settings
        </a>

        {{-- New Chat Button --}}
        <a href="{{ route('chat.new') }}"
           class="mt-6 flex items-center justify-center gap-2 px-4 py-3
                  bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl
                  shadow-lg transition-transform duration-200 hover:-translate-y-1">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          New Chat
        </a>
      </nav>

      <!-- Logout -->
      <form method="POST" action="{{ route('logout') }}" class="px-4 py-4 border-t border-indigo-700 mt-auto">
        @csrf
        <button type="submit" class="w-full text-left text-red-400 hover:text-red-200 transition-colors duration-200">
          Logout
        </button>
      </form>
    </div>

    <!-- Main content -->
    <div class="main-content ml-64 transition-all duration-300">
      <!-- Header -->
      <div class="header-bar bg-white shadow-sm flex justify-between items-center px-6 py-4">
        <h1 class="page-title text-xl font-medium">@yield('title', 'Home')</h1>
        <div class="header-right text-gray-600">
          @auth
            {{ Auth::user()->name }}
          @endauth
        </div>
      </div>

      <!-- Panel -->
      <div class="main-panel h-[calc(100vh-64px)] overflow-auto">
        @yield('content')
      </div>
    </div>
  </div>

  <!-- Sidebar toggle script -->
  <script>
    function setupSidebarToggle() {
      const hidden = localStorage.getItem('sidebarHidden') === 'true';
      document.body.classList.toggle('sidebar-hidden', hidden);
      document.querySelectorAll('.sidebar-open-btn, .sidebar-close-btn')
              .forEach(btn => btn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-hidden');
                localStorage.setItem('sidebarHidden', document.body.classList.contains('sidebar-hidden'));
              }));
    }
    document.addEventListener('DOMContentLoaded', setupSidebarToggle);
  </script>

  <script src="https://unpkg.com/htmx.org@1.9.2"></script>
  @stack('scripts')
</body>
</html>
