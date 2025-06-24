<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <meta charset="utf-8">
    <title>LumiCHAT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <div class="layout-wrapper">

        <!-- Sidebar -->
        <div id="sidebar" class="bg-gradient-to-b from-indigo-800 to-indigo-900 text-white shadow-xl">
            <div class="flex items-center justify-between px-4 py-4 border-b border-indigo-700">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/chatbot.png') }}" alt="Logo" class="w-7 h-7">
                    <span class="text-lg font-semibold bg-gradient-to-r from-blue-300 to-indigo-400 bg-clip-text text-transparent">
                        LumiCHAT
                    </span>
                </div>
                <button class="sidebar-close-btn" title="Close sidebar">✕</button>
            </div>
            
            <nav class="flex-1 px-4 pt-4">
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('chat.index') }}"
                        class="block py-2 px-4 rounded 
                                {{ request()->routeIs('chat.show') ? 'bg-indigo-700 text-white font-semibold' : 'hover:bg-indigo-700 hover:text-white' }}">
                            <i class="fas fa-home mr-2"></i> Home
                        </a>
                    </li>
                    <li>
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center gap-2 p-2 rounded-md transition
                        {{ request()->routeIs('profile.edit') ? 'bg-indigo-700 text-white font-semibold' : 'hover:bg-indigo-700' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.746 6.879 2.021M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Profile
                    </a>
                    </li>

                    <li>
                        <a href="{{ route('chat.history') }}" class="flex items-center gap-2 p-2 rounded-md hover:bg-indigo-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Chat History
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('appointments') }}" class="flex items-center gap-2 p-2 rounded-md hover:bg-indigo-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Appointments
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('settings') }}" class="flex items-center gap-2 p-2 rounded-md hover:bg-indigo-700 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v2m6.364 1.636l-1.414 1.414M18 12h-2M6 12H4m1.636 6.364l1.414-1.414M12 20v-2M5.636 5.636l1.414 1.414" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Settings
                        </a>
                    </li>
                </ul>

                <div class="mt-6">
                    <a href="{{ route('chat.new') }}" class="block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-500 transition">
                        + New Chat
                    </a>
                </div>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="px-4 py-4 border-t border-indigo-700 mt-auto">
                @csrf
                <button type="submit" class="w-full text-left text-red-400 hover:text-red-200 transition">
                    Logout
                </button>
            </form>
        </div>


        <!-- Main content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header-bar custom-header-space">
                <div class="header-left">
                    <button class="sidebar-open-btn">☰</button>
                    <span class="page-title">@yield('title', 'Home')</span>
                </div>
                <div class="header-right">{{ Auth::user()->name }}</div>
            </div>

            <!-- Panel -->
            <div class="main-panel" id="main-panel">
                @yield('content')
            </div>
        </div>

    </div>

    <script>
        function setupSidebarToggle() {
            const isHidden = localStorage.getItem('sidebarHidden') === 'true';
            if (isHidden) {
                document.body.classList.add('sidebar-hidden');
            }

            document.querySelectorAll('.sidebar-open-btn, .sidebar-close-btn').forEach(btn => {
                btn.removeEventListener('click', toggleSidebar); // prevent duplicates
                btn.addEventListener('click', toggleSidebar);
            });
        }

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-hidden');
            localStorage.setItem('sidebarHidden', document.body.classList.contains('sidebar-hidden'));
        }

        document.addEventListener('DOMContentLoaded', setupSidebarToggle);
        document.body.addEventListener('htmx:afterSettle', setupSidebarToggle);
    </script>
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
    <script src="https://unpkg.com/htmx.org/dist/ext/transition.js"></script>
    @stack('scripts')
</body>
</html>
