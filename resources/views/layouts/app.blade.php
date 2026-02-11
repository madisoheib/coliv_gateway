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
                            <a href="{{ url('/docker') }}"
                               class="{{ request()->is('docker*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Docker
                            </a>
                            <a href="{{ url('/commands') }}"
                               class="{{ request()->is('commands*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Commands
                            </a>
                            <a href="{{ url('/supervisor') }}"
                               class="{{ request()->is('supervisor*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-gray-900' }}">
                                Supervisor
                            </a>
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

    @stack('scripts')
</body>
</html>
