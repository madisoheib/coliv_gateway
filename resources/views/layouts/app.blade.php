<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-full">
        <nav class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ url('/admin/dashboard') }}" class="flex items-center space-x-2">
                            <img src="{{ asset('logo.png') }}" alt="Colivraison" class="h-8 w-8 rounded">
                            <span class="text-xl font-semibold text-gray-900">Colivraison Gateway</span>
                        </a>
                        <nav class="flex space-x-4">
                            <a href="{{ url('/admin/dashboard') }}"
                               class="{{ request()->is('admin/dashboard') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Dashboard
                            </a>
                            <a href="{{ url('/admin/webhooks') }}"
                               class="{{ request()->is('admin/webhooks*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Webhooks
                            </a>
                            <a href="{{ url('/backup') }}"
                               class="{{ request()->is('backup') && !request()->is('backup/*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Backups
                            </a>
                            <a href="{{ url('/backup/logs') }}"
                               class="{{ request()->is('backup/logs*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Logs
                            </a>
                            <a href="{{ url('/backup/settings') }}"
                               class="{{ request()->is('backup/settings') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Settings
                            </a>
                            {{-- DevOps Tools Dropdown --}}
                            <div class="relative" id="devops-dropdown">
                                <button onclick="document.getElementById('devops-menu').classList.toggle('hidden')" type="button"
                                        class="inline-flex items-center gap-1 {{ request()->is('docker*') || request()->is('commands*') || request()->is('supervisor*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                    DevOps Tools
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div id="devops-menu" class="hidden absolute left-0 z-10 mt-2 w-48 origin-top-left rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5">
                                    <a href="{{ url('/docker') }}" class="block px-4 py-2 text-sm {{ request()->is('docker*') ? 'bg-gray-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                        Docker Stats
                                    </a>
                                    <a href="{{ url('/commands') }}" class="block px-4 py-2 text-sm {{ request()->is('commands*') ? 'bg-gray-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                        Quick Commands
                                    </a>
                                    <a href="{{ url('/supervisor') }}" class="block px-4 py-2 text-sm {{ request()->is('supervisor*') ? 'bg-gray-50 text-indigo-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">
                                        Supervisor
                                    </a>
                                </div>
                            </div>
                        </nav>
                    </div>
                    @auth
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">Welcome, {{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                        </form>
                    </div>
                    @endauth
                </div>
            </div>
        </nav>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function(e) {
            const dd = document.getElementById('devops-dropdown');
            const menu = document.getElementById('devops-menu');
            if (dd && menu && !dd.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
