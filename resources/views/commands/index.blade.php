@extends('layouts.app')

@section('title', 'Quick Commands')

@section('content')
    <div class="mb-8 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold text-gray-900">Quick Commands</h2>
            <p class="mt-1 text-sm text-gray-500">Pre-defined commands for container management</p>
        </div>
    </div>

    @foreach($grouped as $category => $commands)
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $category }}</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($commands as $key => $cmd)
                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center justify-between">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-sm font-semibold text-gray-900">{{ $cmd['label'] }}</h4>
                                    <p class="mt-1 text-xs text-gray-500">
                                        <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">{{ $cmd['container'] }}</span>
                                        @if(!empty($cmd['command']))
                                            <code class="ml-1 text-gray-400">{{ $cmd['command'] }}</code>
                                        @elseif(!empty($cmd['docker_restart']))
                                            <code class="ml-1 text-gray-400">docker restart</code>
                                        @endif
                                    </p>
                                </div>
                                <button onclick="runCommand('{{ $key }}', '{{ $cmd['danger'] }}', '{{ $cmd['label'] }}')"
                                        id="cmd-btn-{{ $key }}"
                                        class="ml-4 inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold shadow-sm
                                        @if($cmd['danger'] === 'safe')
                                            bg-green-50 text-green-700 ring-1 ring-green-600/20 ring-inset hover:bg-green-100
                                        @elseif($cmd['danger'] === 'warning')
                                            bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20 ring-inset hover:bg-indigo-100
                                        @else
                                            bg-red-50 text-red-700 ring-1 ring-red-600/20 ring-inset hover:bg-red-100
                                        @endif
                                        disabled:opacity-50">
                                    <svg class="mr-1.5 -ml-0.5 h-4 w-4 hidden animate-spin" id="cmd-spin-{{ $key }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span id="cmd-text-{{ $key }}">Run</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Output Panel --}}
    <div id="command-output" class="hidden">
        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900" id="output-title">Command Output</h3>
                <button onclick="document.getElementById('command-output').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>
            <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 p-4 rounded-md max-h-96 overflow-y-auto" id="output-text"></pre>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const runUrl = '{{ url("/commands/run") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function runCommand(key, danger, label) {
        if (danger === 'danger') {
            if (!confirm('This is a dangerous operation: ' + label + '. Continue?')) return;
        }

        const btn = document.getElementById('cmd-btn-' + key);
        const spin = document.getElementById('cmd-spin-' + key);
        const text = document.getElementById('cmd-text-' + key);

        btn.disabled = true;
        spin.classList.remove('hidden');
        text.textContent = 'Running...';

        fetch(runUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ command: key }),
        })
            .then(r => r.json())
            .then(data => {
                const output = document.getElementById('command-output');
                const title = document.getElementById('output-title');
                const outputText = document.getElementById('output-text');

                output.classList.remove('hidden');
                title.textContent = label;
                outputText.textContent = data.output || '(no output)';

                output.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(err => {
                alert('Request failed: ' + err.message);
            })
            .finally(() => {
                btn.disabled = false;
                spin.classList.add('hidden');
                text.textContent = 'Run';
            });
    }
</script>
@endpush
