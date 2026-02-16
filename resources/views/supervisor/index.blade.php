@extends('layouts.app')

@section('title', 'Supervisor Status')

@section('content')
    <div class="mb-8 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold text-gray-900">Supervisor Status</h2>
            <p class="mt-1 text-sm text-gray-500">Multi-container supervisor management &mdash; auto-refreshes every 10s</p>
        </div>
        <div class="mt-4 flex items-center gap-3 md:mt-0">
            <span class="inline-flex items-center gap-1.5 text-sm text-gray-500" id="poll-indicator">
                <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                Live
            </span>
            <a href="{{ route('supervisor.programs.create') }}"
               class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                + New Program
            </a>
            <button onclick="fetchStatus()" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                Refresh
            </button>
            <button onclick="restartAll()" id="restart-all-btn"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <svg class="mr-1.5 -ml-0.5 h-4 w-4 hidden animate-spin" id="restart-all-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Restart All
            </button>
        </div>
    </div>

    {{-- Container Tabs --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-6" aria-label="Containers">
            @foreach($containers as $key => $info)
                <a href="{{ route('supervisor.index', ['container' => $key]) }}"
                   class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium {{ $active === $key ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    {{ $info['label'] }}
                    <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 container-count" data-container="{{ $key }}"></span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Summary Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Processes</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-gray-900" id="stat-total">{{ count($processes) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Running</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-green-600" id="stat-running">{{ count(array_filter($processes, fn($p) => $p['state'] === 'RUNNING')) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Stopped / Fatal</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-red-600" id="stat-stopped">{{ count(array_filter($processes, fn($p) => in_array($p['state'], ['STOPPED', 'FATAL', 'EXITED']))) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Config Files</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-gray-900" id="stat-configs">{{ count($configs) }}</dd>
        </div>
    </div>

    {{-- Process Table --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-base font-semibold text-gray-900">Processes &mdash; {{ $containers[$active]['label'] ?? $active }}</h3>
        </div>
        <div class="border-t border-gray-200">
            @if(count($processes) > 0)
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Process</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">State</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Info</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white" id="process-tbody">
                        @foreach($processes as $p)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $p['name'] }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">{!! stateBadge($p['state']) !!}</td>
                                <td class="px-3 py-4 text-sm text-gray-500">{{ $p['info'] }}</td>
                                <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-1">
                                    @if($p['state'] !== 'RUNNING')
                                        <button onclick="processAction('start', '{{ $p['name'] }}')"
                                                class="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1.5 text-xs font-semibold text-green-700 shadow-sm ring-1 ring-green-600/20 ring-inset hover:bg-green-100">
                                            Start
                                        </button>
                                    @endif
                                    @if($p['state'] === 'RUNNING')
                                        <button onclick="processAction('stop', '{{ $p['name'] }}')"
                                                class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-red-600/20 ring-inset hover:bg-red-100">
                                            Stop
                                        </button>
                                    @endif
                                    <button onclick="processAction('restart', '{{ $p['name'] }}')"
                                            class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-600/20 ring-inset hover:bg-indigo-100"
                                            id="proc-btn-{{ str_replace([':', '.'], '-', $p['name']) }}">
                                        Restart
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="px-4 py-12 text-center">
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No processes found</h3>
                    <p class="mt-1 text-sm text-gray-500">Supervisor may not be running or accessible in this container.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Config Files --}}
    @if(count($configs) > 0)
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Configuration Files</h3>
            </div>
            <div class="border-t border-gray-200 divide-y divide-gray-200">
                @foreach($configs as $config)
                    <details class="group">
                        <summary class="flex cursor-pointer items-center justify-between px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 text-gray-400 transition-transform group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm font-medium text-gray-900">{{ $config['file'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400">{{ $config['path'] }}</span>
                                <a href="{{ route('supervisor.programs.edit', ['container' => $active, 'file' => $config['file']]) }}"
                                   onclick="event.stopPropagation()"
                                   class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('supervisor.programs.delete', ['container' => $active, 'file' => $config['file']]) }}"
                                      onsubmit="return confirm('Delete {{ $config['file'] }}? The associated process will be stopped.')"
                                      onclick="event.stopPropagation()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 shadow-sm ring-1 ring-red-600/20 ring-inset hover:bg-red-100">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </summary>
                        <div class="px-4 pb-4 sm:px-6">
                            <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 p-4 rounded-md overflow-x-auto">{{ $config['content'] }}</pre>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Action Output --}}
    <div id="action-output" class="mt-6 hidden">
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Last Action</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 p-4 rounded-md" id="action-output-text"></pre>
            </div>
        </div>
    </div>
@endsection

@php
function stateBadge(string $state): string {
    return match($state) {
        'RUNNING' => '<span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset">'.$state.'</span>',
        'STOPPED', 'EXITED' => '<span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-500/20 ring-inset">'.$state.'</span>',
        'FATAL' => '<span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset">'.$state.'</span>',
        'STARTING', 'BACKOFF' => '<span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-yellow-600/20 ring-inset">'.$state.'</span>',
        default => '<span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-500/20 ring-inset">'.$state.'</span>',
    };
}
@endphp

@push('scripts')
<script>
    const activeContainer = '{{ $active }}';
    const statusUrl = '{{ url("/supervisor/status") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let pollTimer = null;

    function stateBadgeJs(state) {
        const map = {
            RUNNING: { bg: 'bg-green-50', text: 'text-green-700', ring: 'ring-green-600/20' },
            STOPPED: { bg: 'bg-gray-50', text: 'text-gray-600', ring: 'ring-gray-500/20' },
            EXITED: { bg: 'bg-gray-50', text: 'text-gray-600', ring: 'ring-gray-500/20' },
            FATAL: { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-600/20' },
            STARTING: { bg: 'bg-yellow-50', text: 'text-yellow-700', ring: 'ring-yellow-600/20' },
            BACKOFF: { bg: 'bg-yellow-50', text: 'text-yellow-700', ring: 'ring-yellow-600/20' },
        };
        const s = map[state] || { bg: 'bg-gray-50', text: 'text-gray-600', ring: 'ring-gray-500/20' };
        return `<span class="inline-flex items-center rounded-full ${s.bg} px-2 py-1 text-xs font-medium ${s.text} ring-1 ${s.ring} ring-inset">${state}</span>`;
    }

    function safeName(name) {
        return name.replace(/[:.]/g, '-');
    }

    function actionButtons(p) {
        let html = '';
        if (p.state !== 'RUNNING') {
            html += `<button onclick="processAction('start', '${p.name}')" class="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1.5 text-xs font-semibold text-green-700 shadow-sm ring-1 ring-green-600/20 ring-inset hover:bg-green-100">Start</button> `;
        }
        if (p.state === 'RUNNING') {
            html += `<button onclick="processAction('stop', '${p.name}')" class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-red-600/20 ring-inset hover:bg-red-100">Stop</button> `;
        }
        html += `<button onclick="processAction('restart', '${p.name}')" class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm ring-1 ring-indigo-600/20 ring-inset hover:bg-indigo-100" id="proc-btn-${safeName(p.name)}">Restart</button>`;
        return html;
    }

    function fetchStatus() {
        fetch(statusUrl + '?container=' + encodeURIComponent(activeContainer))
            .then(r => r.json())
            .then(data => {
                const procs = data.processes;
                const running = procs.filter(p => p.state === 'RUNNING').length;
                const stopped = procs.filter(p => ['STOPPED', 'FATAL', 'EXITED'].includes(p.state)).length;

                document.getElementById('stat-total').textContent = procs.length;
                document.getElementById('stat-running').textContent = running;
                document.getElementById('stat-stopped').textContent = stopped;

                const tbody = document.getElementById('process-tbody');
                if (tbody) {
                    tbody.innerHTML = procs.map(p => `
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">${p.name}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">${stateBadgeJs(p.state)}</td>
                            <td class="px-3 py-4 text-sm text-gray-500">${p.info}</td>
                            <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-1">${actionButtons(p)}</td>
                        </tr>
                    `).join('');
                }
            })
            .catch(() => {});
    }

    function processAction(action, name) {
        if (!confirm(action.charAt(0).toUpperCase() + action.slice(1) + ' process ' + name + '?')) return;

        const url = `/supervisor/${encodeURIComponent(activeContainer)}/${encodeURIComponent(name)}/${action}`;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                showOutput(data.message);
                fetchStatus();
            })
            .catch(() => {});
    }

    function restartAll() {
        if (!confirm('Restart ALL supervisor processes in this container?')) return;

        const btn = document.getElementById('restart-all-btn');
        const spin = document.getElementById('restart-all-spin');
        btn.disabled = true;
        spin.classList.remove('hidden');

        fetch(`/supervisor/${encodeURIComponent(activeContainer)}/restart-all`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                showOutput(data.message);
                btn.disabled = false;
                spin.classList.add('hidden');
                fetchStatus();
            })
            .catch(() => {
                btn.disabled = false;
                spin.classList.add('hidden');
            });
    }

    function showOutput(text) {
        const el = document.getElementById('action-output');
        document.getElementById('action-output-text').textContent = text;
        el.classList.remove('hidden');
    }

    pollTimer = setInterval(fetchStatus, 10000);
</script>
@endpush
