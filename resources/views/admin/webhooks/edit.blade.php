<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Webhook - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">Webhook Gateway Admin</h1>
                    <nav class="flex space-x-4">
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="{{ route('admin.webhooks.index') }}" class="text-gray-600 hover:text-gray-900">Webhooks</a>
                        <span class="text-indigo-600 font-medium">Edit Webhook</span>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, {{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Edit Webhook Configuration</h2>
                    <p class="mt-1 text-sm text-gray-600">Modify the webhook configuration for {{ $webhook->name }}.</p>
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

                <form method="POST" action="{{ route('admin.webhooks.update', $webhook->id) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Partner/User Selection -->
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">Partner/User</label>
                            <select id="user_id" name="user_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select a partner...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (old('user_id', $webhook->user_id) == $user->id) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Webhook Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Webhook Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $webhook->name) }}" required 
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                   placeholder="Status Update Webhook">
                        </div>
                    </div>

                    <!-- Endpoint URL -->
                    <div>
                        <label for="endpoint_url" class="block text-sm font-medium text-gray-700">Endpoint URL</label>
                        <input type="url" id="endpoint_url" name="endpoint_url" value="{{ old('endpoint_url', $webhook->endpoint_url) }}" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="https://partner.com/webhook/status">
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <!-- Event Type -->
                        <div>
                            <label for="event_type" class="block text-sm font-medium text-gray-700">Event Type</label>
                            <select id="event_type" name="event_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select event type...</option>
                                <option value="all" {{ old('event_type', $webhook->event_type) == 'all' ? 'selected' : '' }}>All Events</option>
                                <option value="status_change" {{ old('event_type', $webhook->event_type) == 'status_change' ? 'selected' : '' }}>Status Changes</option>
                                <option value="order_created" {{ old('event_type', $webhook->event_type) == 'order_created' ? 'selected' : '' }}>Order Created</option>
                                <option value="order_updated" {{ old('event_type', $webhook->event_type) == 'order_updated' ? 'selected' : '' }}>Order Updated</option>
                            </select>
                        </div>

                        <!-- Service Type -->
                        <div>
                            <label for="service_type" class="block text-sm font-medium text-gray-700">Service Type</label>
                            <select id="service_type" name="service_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select service type...</option>
                                <option value="all" {{ old('service_type', $webhook->service_type) == 'all' ? 'selected' : '' }}>All Services</option>
                                <option value="delivery" {{ old('service_type', $webhook->service_type) == 'delivery' ? 'selected' : '' }}>Delivery</option>
                                <option value="pickup" {{ old('service_type', $webhook->service_type) == 'pickup' ? 'selected' : '' }}>Pickup</option>
                                <option value="return" {{ old('service_type', $webhook->service_type) == 'return' ? 'selected' : '' }}>Return</option>
                            </select>
                        </div>

                        <!-- Language -->
                        <div>
                            <label for="lang" class="block text-sm font-medium text-gray-700">Language</label>
                            <select id="lang" name="lang" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="fr" {{ old('lang', $webhook->lang) == 'fr' ? 'selected' : '' }}>French</option>
                                <option value="en" {{ old('lang', $webhook->lang) == 'en' ? 'selected' : '' }}>English</option>
                                <option value="ar" {{ old('lang', $webhook->lang) == 'ar' ? 'selected' : '' }}>Arabic</option>
                            </select>
                        </div>
                    </div>

                    <!-- Security Configuration -->
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Security Configuration</h3>
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Security Type -->
                            <div>
                                <label for="security_type" class="block text-sm font-medium text-gray-700">Security Type</label>
                                <select id="security_type" name="security_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="none" {{ old('security_type', $webhook->security_type) == 'none' ? 'selected' : '' }}>None</option>
                                    <option value="bearer_token" {{ old('security_type', $webhook->security_type) == 'bearer_token' ? 'selected' : '' }}>Bearer Token</option>
                                    <option value="api_key" {{ old('security_type', $webhook->security_type) == 'api_key' ? 'selected' : '' }}>API Key</option>
                                </select>
                            </div>

                            <!-- Security Token -->
                            <div>
                                <label for="security_token" class="block text-sm font-medium text-gray-700">Security Token</label>
                                <input type="text" id="security_token" name="security_token" value="{{ old('security_token', $webhook->security_token) }}" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="Enter token/key (optional for none)">
                                <p class="mt-1 text-xs text-gray-500">Leave empty if security type is "None" or to keep current token</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Options -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Production Mode -->
                        <div class="flex items-center">
                            <input type="hidden" name="is_production" value="0">
                            <input type="checkbox" id="is_production" name="is_production" value="1" {{ old('is_production', $webhook->is_production) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="is_production" class="ml-2 block text-sm text-gray-900">Production Mode</label>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $webhook->is_active) ? 'checked' : '' }}
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">Active</label>
                        </div>
                    </div>

                    <!-- Webhook Stats -->
                    @if($webhook->last_triggered_at || $webhook->failure_count > 0)
                    <div class="bg-blue-50 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Webhook Statistics</h3>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
                            @if($webhook->last_triggered_at)
                            <div>
                                <span class="text-gray-600">Last Triggered:</span>
                                <span class="font-medium">{{ $webhook->last_triggered_at->diffForHumans() }}</span>
                            </div>
                            @endif
                            @if($webhook->last_response_code)
                            <div>
                                <span class="text-gray-600">Last Response:</span>
                                <span class="font-medium {{ $webhook->last_response_code >= 200 && $webhook->last_response_code < 300 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $webhook->last_response_code }}
                                </span>
                            </div>
                            @endif
                            <div>
                                <span class="text-gray-600">Failure Count:</span>
                                <span class="font-medium {{ $webhook->failure_count > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $webhook->failure_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="{{ url('/admin/webhooks') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update Webhook
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Hide/show security token field based on security type
        document.getElementById('security_type').addEventListener('change', function() {
            const tokenField = document.getElementById('security_token');
            if (this.value === 'none') {
                tokenField.disabled = true;
                tokenField.placeholder = 'Not required for "None" security type';
            } else {
                tokenField.disabled = false;
                tokenField.placeholder = 'Enter token/key or leave empty to keep current';
            }
        });
        
        // Trigger on page load
        document.getElementById('security_type').dispatchEvent(new Event('change'));
    </script>
</body>
</html>