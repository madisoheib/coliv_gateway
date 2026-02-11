@extends('layouts.app')

@section('title', 'Docker Stats')

@section('content')
    <div class="mb-8 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold text-gray-900">Docker Containers</h2>
            <p class="mt-1 text-sm text-gray-500">Live container stats &mdash; auto-refreshes every 5 seconds</p>
        </div>
        <div class="mt-4 flex items-center gap-3 md:mt-0">
            <span class="inline-flex items-center gap-1.5 text-sm text-gray-500" id="poll-indicator">
                <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                Live
            </span>
            <button onclick="fetchStats()" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                Refresh Now
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Containers</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-gray-900" id="stat-total">{{ count($containers) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Running</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-green-600" id="stat-running">{{ count(array_filter($containers, fn($c) => ($c['state'] ?? '') === 'running')) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Stopped</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-red-600" id="stat-stopped">{{ count(array_filter($containers, fn($c) => ($c['state'] ?? '') !== 'running')) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Last Updated</dt>
            <dd class="mt-1 text-lg font-semibold tracking-tight text-gray-900" id="stat-updated">{{ now()->format('H:i:s') }}</dd>
        </div>
    </div>

    {{-- Container Table --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-base font-semibold text-gray-900">Container Stats</h3>
        </div>
        <div class="border-t border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Container</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">CPU</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Memory</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Mem %</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Net I/O</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Block I/O</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">PIDs</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white" id="container-tbody">
                    @foreach($containers as $c)
                        <tr data-container="{{ $c['name'] }}">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $c['name'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm">
                                @if(($c['state'] ?? '') === 'running')
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset">{{ $c['status'] }}</span>
                                @elseif(($c['state'] ?? '') === 'exited')
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset">{{ $c['status'] }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-500/20 ring-inset">{{ $c['status'] }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $c['cpu'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $c['mem_usage'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $c['mem_perc'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $c['net_io'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $c['block_io'] }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $c['pids'] }}</td>
                            <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                <button onclick="restartContainer('{{ $c['name'] }}')"
                                        class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-red-600/20 ring-inset hover:bg-red-100 disabled:opacity-50"
                                        id="restart-btn-{{ $c['name'] }}">
                                    Restart
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Restart Output --}}
    <div id="restart-output" class="mt-6 hidden">
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Last Action</h3>
            </div>
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 p-4 rounded-md" id="restart-output-text"></pre>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const statsUrl = '{{ url("/docker/stats") }}';
    const restartUrl = '{{ url("/docker/restart") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let pollTimer = null;

    function statusBadge(state, status) {
        const map = {
            running: { bg: 'bg-green-50', text: 'text-green-700', ring: 'ring-green-600/20' },
            exited:  { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-600/20' },
        };
        const s = map[state] || { bg: 'bg-gray-50', text: 'text-gray-600', ring: 'ring-gray-500/20' };
        return `<span class="inline-flex items-center rounded-full ${s.bg} px-2 py-1 text-xs font-medium ${s.text} ring-1 ${s.ring} ring-inset">${status}</span>`;
    }

    function fetchStats() {
        fetch(statsUrl)
            .then(r => r.json())
            .then(data => {
                const containers = data.containers;
                const tbody = document.getElementById('container-tbody');
                const running = containers.filter(c => c.state === 'running').length;
                const stopped = containers.length - running;

                document.getElementById('stat-total').textContent = containers.length;
                document.getElementById('stat-running').textContent = running;
                document.getElementById('stat-stopped').textContent = stopped;
                document.getElementById('stat-updated').textContent = new Date().toLocaleTimeString();

                tbody.innerHTML = containers.map(c => `
                    <tr data-container="${c.name}">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">${c.name}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">${statusBadge(c.state, c.status)}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${c.cpu}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${c.mem_usage}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${c.mem_perc}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${c.net_io}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${c.block_io}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">${c.pids}</td>
                        <td class="whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <button onclick="restartContainer('${c.name}')"
                                    class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 shadow-sm ring-1 ring-red-600/20 ring-inset hover:bg-red-100 disabled:opacity-50"
                                    id="restart-btn-${c.name}">
                                Restart
                            </button>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(() => {});
    }

    function restartContainer(name) {
        const warning = name === 'coliv_app'
            ? 'WARNING: Restarting coliv_app will disconnect this page! Continue?'
            : `Restart container ${name}?`;

        if (!confirm(warning)) return;

        const btn = document.getElementById('restart-btn-' + name);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Restarting...';
        }

        fetch(restartUrl + '/' + encodeURIComponent(name), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                const output = document.getElementById('restart-output');
                const text = document.getElementById('restart-output-text');
                output.classList.remove('hidden');
                text.textContent = data.message;
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Restart';
                }
                fetchStats();
            })
            .catch(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Restart';
                }
            });
    }

    // Start polling
    pollTimer = setInterval(fetchStats, 5000);
</script>
@endpush
