@extends('layouts.app')

@section('title', 'Backup Settings')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Backup Settings</h2>
        <p class="mt-1 text-sm text-gray-500">Configure S3 storage, scheduling, retention policies, and notifications.</p>
    </div>

    <form method="POST" action="{{ route('backup.settings.update') }}">
        @csrf

        {{-- S3 Configuration --}}
        <div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">S3 Configuration</h3>
                    <p class="mt-1 text-sm text-gray-500">Amazon S3 credentials for backup storage.</p>
                </div>
                <button type="button" id="test-connection-btn"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
                    Test Connection
                </button>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="s3_key" class="block text-sm font-medium text-gray-700">AWS Access Key ID</label>
                        <input type="text" name="s3_key" id="s3_key" value="{{ $settings['s3_key'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="s3_secret" class="block text-sm font-medium text-gray-700">AWS Secret Access Key</label>
                        <input type="password" name="s3_secret" id="s3_secret" value="{{ $settings['s3_secret'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="s3_region" class="block text-sm font-medium text-gray-700">AWS Region</label>
                        <select name="s3_region" id="s3_region"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                            @foreach(['us-east-1','us-east-2','us-west-1','us-west-2','eu-west-1','eu-west-2','eu-west-3','eu-central-1','eu-north-1','ap-southeast-1','ap-southeast-2','ap-northeast-1','ap-northeast-2','ap-south-1','sa-east-1','ca-central-1','af-south-1','me-south-1'] as $region)
                                <option value="{{ $region }}" {{ ($settings['s3_region'] ?? 'us-east-1') === $region ? 'selected' : '' }}>{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="s3_bucket" class="block text-sm font-medium text-gray-700">Bucket Name</label>
                        <input type="text" name="s3_bucket" id="s3_bucket" value="{{ $settings['s3_bucket'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="s3_path" class="block text-sm font-medium text-gray-700">Path Prefix</label>
                        <input type="text" name="s3_path" id="s3_path" value="{{ $settings['s3_path'] ?? 'backups' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- Database --}}
        <div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Database</h3>
                <p class="mt-1 text-sm text-gray-500">Select which database connection to back up.</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <div class="max-w-xs">
                    <label for="db_connection" class="block text-sm font-medium text-gray-700">Connection</label>
                    <select name="db_connection" id="db_connection"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                        @foreach(['mysql', 'mariadb', 'pgsql', 'sqlite'] as $conn)
                            <option value="{{ $conn }}" {{ ($settings['db_connection'] ?? 'mysql') === $conn ? 'selected' : '' }}>{{ $conn }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Schedule</h3>
                <p class="mt-1 text-sm text-gray-500">Configure automatic backup scheduling.</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="schedule_enabled" value="1" class="peer sr-only"
                                   {{ ($settings['schedule_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Enable automatic backups</span>
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="schedule_frequency" class="block text-sm font-medium text-gray-700">Frequency</label>
                        <select name="schedule_frequency" id="schedule_frequency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                            @foreach(['hourly', 'daily', 'weekly'] as $freq)
                                <option value="{{ $freq }}" {{ ($settings['schedule_frequency'] ?? 'daily') === $freq ? 'selected' : '' }}>{{ ucfirst($freq) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="schedule_time" class="block text-sm font-medium text-gray-700">Time (HH:MM)</label>
                        <input type="time" name="schedule_time" id="schedule_time" value="{{ $settings['schedule_time'] ?? '01:30' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- Cleanup / Rotation --}}
        <div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Rotation / Retention Policy</h3>
                <p class="mt-1 text-sm text-gray-500">Control how old backups are cleaned up.</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-6">
                    <div class="sm:col-span-6 mb-2">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="cleanup_enabled" value="1" class="peer sr-only"
                                   {{ ($settings['cleanup_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}>
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Enable automatic cleanup</span>
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="cleanup_time" class="block text-sm font-medium text-gray-700">Cleanup Time (HH:MM)</label>
                        <input type="time" name="cleanup_time" id="cleanup_time" value="{{ $settings['cleanup_time'] ?? '01:00' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-4"></div>
                    <div class="sm:col-span-2">
                        <label for="retention_keep_all_days" class="block text-sm font-medium text-gray-700">Keep all backups (days)</label>
                        <input type="number" name="retention_keep_all_days" id="retention_keep_all_days" min="1"
                               value="{{ $settings['retention_keep_all_days'] ?? '7' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="retention_daily_days" class="block text-sm font-medium text-gray-700">Daily retention (days)</label>
                        <input type="number" name="retention_daily_days" id="retention_daily_days" min="1"
                               value="{{ $settings['retention_daily_days'] ?? '16' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="retention_weekly_weeks" class="block text-sm font-medium text-gray-700">Weekly retention (weeks)</label>
                        <input type="number" name="retention_weekly_weeks" id="retention_weekly_weeks" min="1"
                               value="{{ $settings['retention_weekly_weeks'] ?? '8' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="retention_monthly_months" class="block text-sm font-medium text-gray-700">Monthly retention (months)</label>
                        <input type="number" name="retention_monthly_months" id="retention_monthly_months" min="1"
                               value="{{ $settings['retention_monthly_months'] ?? '4' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="retention_max_size_mb" class="block text-sm font-medium text-gray-700">Max total size (MB)</label>
                        <input type="number" name="retention_max_size_mb" id="retention_max_size_mb" min="100"
                               value="{{ $settings['retention_max_size_mb'] ?? '5000' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                    </div>
                </div>
            </div>
        </div>

        {{-- Notifications --}}
        <div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-base font-semibold text-gray-900">Notifications</h3>
                <p class="mt-1 text-sm text-gray-500">Get notified when backups fail.</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <div class="max-w-md">
                    <label for="notifications_email" class="block text-sm font-medium text-gray-700">Notification Email</label>
                    <input type="email" name="notifications_email" id="notifications_email"
                           value="{{ $settings['notifications_email'] ?? '' }}" placeholder="admin@example.com"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2 border">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Save All Settings
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.getElementById('test-connection-btn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Testing...';

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("backup.test-connection") }}';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);

        ['s3_key', 's3_secret', 's3_region', 's3_bucket', 's3_path'].forEach(function(name) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = document.getElementById(name).value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });
</script>
@endpush
