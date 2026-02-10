@extends('layouts.backup')

@section('title', 'Backup Dashboard')

@section('content')
    <div class="mb-8 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold text-gray-900">Backup Dashboard</h2>
        </div>
        <div class="mt-4 flex gap-3 md:ml-4 md:mt-0">
            <form method="POST" action="{{ route('backup.run') }}" id="backup-form">
                @csrf
                <button type="submit" id="backup-btn"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="mr-1.5 -ml-0.5 h-5 w-5 hidden animate-spin" id="backup-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span id="backup-btn-text">Run DB Backup Now</span>
                </button>
            </form>
            <form method="POST" action="{{ route('backup.clean') }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                    Clean Old Backups
                </button>
            </form>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Last Backup</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-gray-900">
                {{ $lastBackup ? $lastBackup['date'] : 'Never' }}
            </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Size</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-gray-900">{{ $totalSize }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Backup Count</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-gray-900">{{ $backupCount }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Auto Backup</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight {{ $scheduleEnabled ? 'text-green-600' : 'text-gray-400' }}">
                {{ $scheduleEnabled ? 'ON' : 'OFF' }}
                @if($scheduleEnabled)
                    <span class="text-sm font-normal text-gray-500">{{ ucfirst($scheduleFrequency) }} at {{ $scheduleTime }}</span>
                @endif
            </dd>
        </div>
    </div>

    {{-- Backups Table --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-base font-semibold text-gray-900">Backup Files</h3>
        </div>
        <div class="border-t border-gray-200">
            @if(count($backups) > 0)
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Filename</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Size</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($backups as $backup)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                    {{ $backup['basename'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $backup['size_human'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $backup['date'] }}
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <a href="{{ route('backup.download', $backup['basename']) }}"
                                       class="text-indigo-600 hover:text-indigo-900 mr-4">Download</a>
                                    <form method="POST" action="{{ route('backup.delete', $backup['basename']) }}" class="inline"
                                          onsubmit="return confirm('Delete this backup?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No backups yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Run your first backup or configure S3 in settings.</p>
                </div>
            @endif
        </div>
    </div>

    @if(session('output'))
        <div class="mt-6 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Command Output</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 p-4 rounded-md">{{ session('output') }}</pre>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.getElementById('backup-form').addEventListener('submit', function() {
        const btn = document.getElementById('backup-btn');
        const spinner = document.getElementById('backup-spinner');
        const text = document.getElementById('backup-btn-text');
        btn.disabled = true;
        spinner.classList.remove('hidden');
        text.textContent = 'Running...';
    });
</script>
@endpush
