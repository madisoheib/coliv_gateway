<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Details - {{ $webhook->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">Webhook Gateway Admin</h1>
                    <nav class="flex space-x-4">
                        <a href="http://local-webhook.colivraison:8180/admin/dashboard" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="http://local-webhook.colivraison:8180/admin/webhooks" class="text-gray-600 hover:text-gray-900">Webhooks</a>
                        <span class="text-indigo-600 font-medium">Webhook Details</span>
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
            <!-- Header with actions -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $webhook->name }}</h2>
                        <p class="mt-1 text-sm text-gray-600">Webhook Configuration Details</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            {{ $webhook->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        @if ($webhook->is_production)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800">
                            Production
                        </span>
                        @endif
                        <a href="http://local-webhook.colivraison:8180/admin/webhooks/{{ $webhook->id }}/edit" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Edit Webhook
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Configuration Details -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Configuration</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Partner/User</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->user->name ?? 'Unknown' }} ({{ $webhook->user->email ?? 'N/A' }})</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Endpoint URL</dt>
                            <dd class="text-sm text-gray-900 break-all">{{ $webhook->endpoint_url }}</dd>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Event Type</dt>
                                <dd class="text-sm text-gray-900">{{ ucfirst($webhook->event_type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Service Type</dt>
                                <dd class="text-sm text-gray-900">{{ ucfirst($webhook->service_type) }}</dd>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Security Type</dt>
                                <dd class="text-sm text-gray-900">{{ ucfirst($webhook->security_type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Language</dt>
                                <dd class="text-sm text-gray-900">{{ strtoupper($webhook->lang) }}</dd>
                            </div>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->created_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->updated_at->format('M j, Y g:i A') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Performance Statistics -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Performance</h3>
                    <dl class="space-y-3">
                        @if($webhook->last_triggered_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Triggered</dt>
                            <dd class="text-sm text-gray-900">{{ $webhook->last_triggered_at->diffForHumans() }}</dd>
                            <dd class="text-xs text-gray-500">{{ $webhook->last_triggered_at->format('M j, Y g:i:s A') }}</dd>
                        </div>
                        @endif
                        
                        @if($webhook->last_response_code)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Response Code</dt>
                            <dd class="text-sm {{ $webhook->last_response_code >= 200 && $webhook->last_response_code < 300 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                {{ $webhook->last_response_code }}
                            </dd>
                        </div>
                        @endif

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Failure Count</dt>
                            <dd class="text-sm {{ $webhook->failure_count > 0 ? 'text-red-600' : 'text-green-600' }} font-medium">
                                {{ $webhook->failure_count }}
                            </dd>
                        </div>

                        @if($webhook->last_response_body)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Response</dt>
                            <dd class="text-xs text-gray-900 bg-gray-50 p-2 rounded break-all">{{ Str::limit($webhook->last_response_body, 200) }}</dd>
                        </div>
                        @endif
                    </dl>

                    <!-- Action buttons -->
                    <div class="mt-6 pt-4 border-t border-gray-200 flex space-x-3">
                        <form method="POST" action="http://local-webhook.colivraison:8180/admin/webhooks/{{ $webhook->id }}/toggle" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                {{ $webhook->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        
                        <form method="POST" action="http://local-webhook.colivraison:8180/admin/webhooks/{{ $webhook->id }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this webhook? This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Logs -->
            @if($webhook->logs && $webhook->logs->count() > 0)
            <div class="bg-white shadow rounded-lg p-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Webhook Logs (Last 10)</h3>
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Triggered</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time (ms)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($webhook->logs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucfirst($log->event_type) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($log->response_code)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $log->response_code >= 200 && $log->response_code < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $log->response_code }}
                                    </span>
                                    @else
                                    <span class="text-gray-400">No response</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->response_time_ms ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($log->error_message)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Error
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Success
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </main>
</body>
</html>