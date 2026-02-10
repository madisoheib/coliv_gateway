@extends('layouts.app')

@section('title', 'Application Logs')

@section('content')
    <div class="mb-8 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold text-gray-900">Application Logs</h2>
            <p class="mt-1 text-sm text-gray-500">
                @if($lastModified)
                    File size: {{ $fileSize }} &middot; Last modified: {{ $lastModified }}
                @else
                    No log file found.
                @endif
            </p>
        </div>
        <div class="mt-4 flex gap-3 md:ml-4 md:mt-0">
            <form method="POST" action="{{ route('backup.logs.clear') }}"
                  onsubmit="return confirm('Are you sure you want to clear the log file? This cannot be undone.')">
                @csrf
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                    Clear Log
                </button>
            </form>
        </div>
    </div>

    {{-- Level Filter --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('backup.logs') }}"
           class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $currentLevel === '' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
            All
        </a>
        @foreach(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level)
            <a href="{{ route('backup.logs', ['level' => $level]) }}"
               class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $currentLevel === $level ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                {{ ucfirst($level) }}
            </a>
        @endforeach
    </div>

    {{-- Log Entries --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-base font-semibold text-gray-900">
                Log Entries
                <span class="ml-2 text-sm font-normal text-gray-500">({{ count($entries) }} entries)</span>
            </h3>
        </div>
        <div class="border-t border-gray-200">
            @if(count($entries) > 0)
                <div class="divide-y divide-gray-200 max-h-[70vh] overflow-y-auto">
                    @foreach($entries as $i => $entry)
                        <div class="px-4 py-3 sm:px-6">
                            <div class="flex items-start gap-3">
                                @php
                                    $colors = [
                                        'EMERGENCY' => 'bg-red-700 text-white',
                                        'ALERT'     => 'bg-red-600 text-white',
                                        'CRITICAL'  => 'bg-red-500 text-white',
                                        'ERROR'     => 'bg-red-100 text-red-800',
                                        'WARNING'   => 'bg-yellow-100 text-yellow-800',
                                        'NOTICE'    => 'bg-blue-100 text-blue-800',
                                        'INFO'      => 'bg-blue-50 text-blue-700',
                                        'DEBUG'     => 'bg-gray-100 text-gray-700',
                                    ];
                                    $color = $colors[$entry['level']] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">
                                    {{ $entry['level'] }}
                                </span>
                                <span class="shrink-0 text-xs text-gray-400 pt-0.5">{{ $entry['timestamp'] }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-900 break-words">{{ Str::limit($entry['message'], 300) }}</p>
                                    @if($entry['stack_trace'])
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-xs text-indigo-600 hover:text-indigo-800">
                                                Show stack trace
                                            </summary>
                                            <pre class="mt-2 max-h-64 overflow-auto rounded-md bg-gray-50 p-3 text-xs text-gray-600">{{ $entry['stack_trace'] }}</pre>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-4 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No log entries</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($currentLevel)
                            No {{ $currentLevel }} entries found. Try a different filter.
                        @else
                            The log file is empty or does not exist.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
@endsection
