@extends('layouts.app')

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

    {{-- Backup Progress Bar --}}
    <div id="backup-progress" class="mb-8 hidden">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900" id="progress-title">Backup in progress...</span>
                </div>
                <span class="text-sm text-gray-500" id="progress-elapsed"></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div id="progress-bar" class="bg-indigo-600 h-3 rounded-full transition-all duration-1000 ease-out" style="width: 5%"></div>
            </div>
            <p class="mt-2 text-sm text-gray-500" id="progress-message">Starting...</p>
        </div>
    </div>

    {{-- Backup Complete Banner --}}
    <div id="backup-complete" class="mb-8 hidden">
        <div class="rounded-md p-4" id="backup-complete-inner">
            <div class="flex">
                <div class="shrink-0" id="backup-complete-icon"></div>
                <div class="ml-3">
                    <p class="text-sm font-medium" id="backup-complete-message"></p>
                </div>
                <div class="ml-auto pl-3">
                    <button type="button" onclick="document.getElementById('backup-complete').classList.add('hidden'); location.reload();"
                            class="inline-flex rounded-md p-1.5 focus:outline-none">
                        <span class="text-sm font-medium underline" id="backup-complete-action">Refresh</span>
                    </button>
                </div>
            </div>
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
    const statusUrl = '{{ route("backup.status") }}';
    let polling = null;
    let simulatedProgress = 5;

    function formatElapsed(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        if (m > 0) return m + 'm ' + s + 's';
        return s + 's';
    }

    function showProgress(data) {
        const el = document.getElementById('backup-progress');
        const bar = document.getElementById('progress-bar');
        const msg = document.getElementById('progress-message');
        const elapsed = document.getElementById('progress-elapsed');
        const title = document.getElementById('progress-title');
        const btn = document.getElementById('backup-btn');

        el.classList.remove('hidden');
        btn.disabled = true;
        document.getElementById('backup-spinner').classList.remove('hidden');
        document.getElementById('backup-btn-text').textContent = 'Running...';

        msg.textContent = data.message || 'Processing...';
        elapsed.textContent = data.elapsed ? formatElapsed(data.elapsed) : '';

        // Simulate progress based on stage
        if (data.message && data.message.includes('Dumping')) {
            simulatedProgress = Math.min(simulatedProgress + 2, 50);
        } else if (data.message && data.message.includes('Compress')) {
            simulatedProgress = Math.min(simulatedProgress + 3, 75);
        } else if (data.message && data.message.includes('Upload')) {
            simulatedProgress = Math.min(simulatedProgress + 4, 95);
        } else {
            simulatedProgress = Math.min(simulatedProgress + 1, 40);
        }

        bar.style.width = simulatedProgress + '%';
    }

    function showComplete(data) {
        document.getElementById('backup-progress').classList.add('hidden');
        const el = document.getElementById('backup-complete');
        const inner = document.getElementById('backup-complete-inner');
        const icon = document.getElementById('backup-complete-icon');
        const msg = document.getElementById('backup-complete-message');
        const action = document.getElementById('backup-complete-action');

        el.classList.remove('hidden');

        if (data.status === 'completed') {
            inner.className = 'rounded-md bg-green-50 p-4 flex';
            icon.innerHTML = '<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>';
            msg.className = 'text-sm font-medium text-green-800';
            msg.textContent = data.message + (data.elapsed ? ' (' + formatElapsed(data.elapsed) + ')' : '');
            action.className = 'text-sm font-medium text-green-600 underline hover:text-green-500';
        } else {
            inner.className = 'rounded-md bg-red-50 p-4 flex';
            icon.innerHTML = '<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>';
            msg.className = 'text-sm font-medium text-red-800';
            msg.textContent = data.message;
            action.className = 'text-sm font-medium text-red-600 underline hover:text-red-500';
        }

        // Re-enable button
        document.getElementById('backup-btn').disabled = false;
        document.getElementById('backup-spinner').classList.add('hidden');
        document.getElementById('backup-btn-text').textContent = 'Run DB Backup Now';
    }

    function pollStatus() {
        fetch(statusUrl)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'running') {
                    showProgress(data);
                } else if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(polling);
                    polling = null;
                    showComplete(data);
                } else {
                    // idle - stop polling
                    clearInterval(polling);
                    polling = null;
                }
            })
            .catch(() => {});
    }

    // Start polling on form submit
    document.getElementById('backup-form').addEventListener('submit', function() {
        simulatedProgress = 5;
        showProgress({ message: 'Starting backup...', elapsed: 0 });
        // Start polling after a short delay to let the POST complete
        setTimeout(() => {
            polling = setInterval(pollStatus, 3000);
        }, 2000);
    });

    // Check on page load if a backup is running
    fetch(statusUrl)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'running') {
                simulatedProgress = 20;
                showProgress(data);
                polling = setInterval(pollStatus, 3000);
            }
        })
        .catch(() => {});
</script>
@endpush
