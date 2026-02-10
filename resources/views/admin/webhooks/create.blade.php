@extends('layouts.app')

@section('title', 'Add New Webhook')

@section('content')
    <div class="bg-white shadow rounded-lg p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Add New Webhook Configuration</h2>
            <p class="mt-1 text-sm text-gray-600">Create a new webhook configuration for a partner.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.webhooks.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">Partner/User</label>
                    <select id="user_id" name="user_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select a partner...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Webhook Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Status Update Webhook">
                </div>
            </div>

            <div>
                <label for="endpoint_url" class="block text-sm font-medium text-gray-700">Endpoint URL</label>
                <input type="url" id="endpoint_url" name="endpoint_url" value="{{ old('endpoint_url') }}" required
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="https://partner.com/webhook/status">
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type</label>
                    <select id="event_type" name="event_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select event type...</option>
                        <option value="all" {{ old('event_type') == 'all' ? 'selected' : '' }}>All Events</option>
                        <option value="status_change" {{ old('event_type') == 'status_change' ? 'selected' : '' }}>Status Changes</option>
                        <option value="order_created" {{ old('event_type') == 'order_created' ? 'selected' : '' }}>Order Created</option>
                        <option value="order_updated" {{ old('event_type') == 'order_updated' ? 'selected' : '' }}>Order Updated</option>
                    </select>
                </div>

                <div>
                    <label for="service_type" class="block text-sm font-medium text-gray-700">Service Type</label>
                    <select id="service_type" name="service_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select service type...</option>
                        <option value="all" {{ old('service_type') == 'all' ? 'selected' : '' }}>All Services</option>
                        <option value="delivery" {{ old('service_type') == 'delivery' ? 'selected' : '' }}>Delivery</option>
                        <option value="pickup" {{ old('service_type') == 'pickup' ? 'selected' : '' }}>Pickup</option>
                        <option value="return" {{ old('service_type') == 'return' ? 'selected' : '' }}>Return</option>
                    </select>
                </div>

                <div>
                    <label for="lang" class="block text-sm font-medium text-gray-700">Language</label>
                    <select id="lang" name="lang" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="fr" {{ old('lang') == 'fr' ? 'selected' : '' }}>French</option>
                        <option value="en" {{ old('lang') == 'en' ? 'selected' : '' }}>English</option>
                        <option value="ar" {{ old('lang') == 'ar' ? 'selected' : '' }}>Arabic</option>
                    </select>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Security Configuration</h3>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="security_type" class="block text-sm font-medium text-gray-700">Security Type</label>
                        <select id="security_type" name="security_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="none" {{ old('security_type') == 'none' ? 'selected' : '' }}>None</option>
                            <option value="bearer_token" {{ old('security_type') == 'bearer_token' ? 'selected' : '' }}>Bearer Token</option>
                            <option value="api_key" {{ old('security_type') == 'api_key' ? 'selected' : '' }}>API Key</option>
                        </select>
                    </div>

                    <div>
                        <label for="security_token" class="block text-sm font-medium text-gray-700">Security Token</label>
                        <input type="text" id="security_token" name="security_token" value="{{ old('security_token') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Enter token/key (optional for none)">
                        <p class="mt-1 text-xs text-gray-500">Leave empty if security type is "None"</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="flex items-center">
                    <input type="hidden" name="is_production" value="0">
                    <input type="checkbox" id="is_production" name="is_production" value="1" {{ old('is_production') ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_production" class="ml-2 block text-sm text-gray-900">Production Mode</label>
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.webhooks.index') }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Webhook
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('security_type').addEventListener('change', function() {
        const tokenField = document.getElementById('security_token');
        if (this.value === 'none') {
            tokenField.disabled = true;
            tokenField.value = '';
            tokenField.placeholder = 'Not required for "None" security type';
        } else {
            tokenField.disabled = false;
            tokenField.placeholder = 'Enter token/key';
        }
    });

    document.getElementById('security_type').dispatchEvent(new Event('change'));
</script>
@endpush
