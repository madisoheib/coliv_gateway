@extends('layouts.app')

@section('title', 'Edit Supervisor Program')

@section('content')
    <div class="mb-8 md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold text-gray-900">Edit Program: {{ $settings['program_name'] ?? $file }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $containers[$container]['label'] ?? $container }} &mdash; {{ $file }}</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('supervisor.index', ['container' => $container]) }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                &larr; Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('supervisor.programs.update', ['container' => $container, 'file' => $file]) }}" id="program-form" class="bg-white shadow sm:rounded-lg">
                @csrf
                @method('PUT')
                <input type="hidden" name="container" value="{{ $container }}">

                <div class="px-4 py-5 sm:p-6 space-y-6">

                    @if($errors->any())
                        <div class="rounded-md bg-red-50 p-4">
                            <ul class="list-disc list-inside text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Container (readonly) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Container</label>
                        <input type="text" disabled value="{{ $containers[$container]['label'] ?? $container }} ({{ $container }})"
                               class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm">
                    </div>

                    {{-- Program Name --}}
                    <div>
                        <label for="program_name" class="block text-sm font-medium text-gray-700">Program Name</label>
                        <input type="text" name="program_name" id="program_name" required maxlength="64"
                               pattern="[a-zA-Z0-9_-]+" value="{{ old('program_name', $settings['program_name'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    {{-- Command --}}
                    <div>
                        <label for="command" class="block text-sm font-medium text-gray-700">Command</label>
                        <input type="text" name="command" id="command" required maxlength="500"
                               value="{{ old('command', $settings['command'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    {{-- Directory --}}
                    <div>
                        <label for="directory" class="block text-sm font-medium text-gray-700">Working Directory</label>
                        <input type="text" name="directory" id="directory" maxlength="255"
                               value="{{ old('directory', $settings['directory'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    {{-- User --}}
                    <div>
                        <label for="user" class="block text-sm font-medium text-gray-700">User</label>
                        <input type="text" name="user" id="user" maxlength="64"
                               value="{{ old('user', $settings['user'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="numprocs" class="block text-sm font-medium text-gray-700">Num Procs</label>
                            <input type="number" name="numprocs" id="numprocs" min="1" max="20"
                                   value="{{ old('numprocs', $settings['numprocs'] ?? 1) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="stopwaitsecs" class="block text-sm font-medium text-gray-700">Stop Wait (s)</label>
                            <input type="number" name="stopwaitsecs" id="stopwaitsecs" min="0" max="600"
                                   value="{{ old('stopwaitsecs', $settings['stopwaitsecs'] ?? 10) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="stdout_logfile_backups" class="block text-sm font-medium text-gray-700">Log Backups</label>
                            <input type="number" name="stdout_logfile_backups" id="stdout_logfile_backups" min="0" max="50"
                                   value="{{ old('stdout_logfile_backups', $settings['stdout_logfile_backups'] ?? 5) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="stdout_logfile" class="block text-sm font-medium text-gray-700">Log File</label>
                            <input type="text" name="stdout_logfile" id="stdout_logfile" maxlength="255"
                                   value="{{ old('stdout_logfile', $settings['stdout_logfile'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="stdout_logfile_maxbytes" class="block text-sm font-medium text-gray-700">Max Log Size</label>
                            <input type="text" name="stdout_logfile_maxbytes" id="stdout_logfile_maxbytes" maxlength="20"
                                   value="{{ old('stdout_logfile_maxbytes', $settings['stdout_logfile_maxbytes'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    {{-- Boolean Toggles --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach(['autostart' => 'Auto Start', 'autorestart' => 'Auto Restart', 'redirect_stderr' => 'Redirect Stderr', 'stopasgroup' => 'Stop as Group', 'killasgroup' => 'Kill as Group'] as $field => $label)
                            @php
                                $checked = old($field, isset($settings[$field]) ? ($settings[$field] === true || $settings[$field] === 'true' ? '1' : '0') : '0');
                            @endphp
                            <label class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                    <input type="hidden" name="{{ $field }}" value="0">
                                    <input type="checkbox" name="{{ $field }}" value="1"
                                           {{ $checked == '1' ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="font-medium text-gray-700">{{ $label }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 text-right sm:px-6 rounded-b-lg">
                    <button type="submit"
                            class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Update Program
                    </button>
                </div>
            </form>
        </div>

        {{-- Live Preview --}}
        <div class="lg:col-span-1">
            <div class="sticky top-8 bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold text-gray-900">Config Preview</h3>
                </div>
                <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                    <pre class="whitespace-pre-wrap text-sm text-gray-700 bg-gray-50 p-4 rounded-md overflow-x-auto" id="config-preview"></pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function updatePreview() {
        const f = document.getElementById('program-form');
        const name = f.program_name.value || 'my-worker';
        let lines = [`[program:${name}]`];

        const text = ['command', 'directory', 'user', 'stdout_logfile', 'stdout_logfile_maxbytes'];
        const nums = ['numprocs', 'stdout_logfile_backups', 'stopwaitsecs'];
        const bools = ['autostart', 'autorestart', 'redirect_stderr', 'stopasgroup', 'killasgroup'];

        text.forEach(k => { const v = f[k]?.value; if (v) lines.push(`${k}=${v}`); });
        nums.forEach(k => { const v = f[k]?.value; if (v) lines.push(`${k}=${v}`); });
        bools.forEach(k => { const cb = f.querySelector(`input[name="${k}"][type="checkbox"]`); if (cb) lines.push(`${k}=${cb.checked ? 'true' : 'false'}`); });

        document.getElementById('config-preview').textContent = lines.join('\n');
    }

    document.getElementById('program-form').addEventListener('input', updatePreview);
    document.getElementById('program-form').addEventListener('change', updatePreview);
    updatePreview();
</script>
@endpush
