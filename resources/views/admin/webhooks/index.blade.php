<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Webhooks - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">Webhook Gateway Admin</h1>
                    <nav class="flex space-x-4">
                        <a href="{{ url('/admin/dashboard') }}" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="{{ url('/admin/webhooks') }}" class="text-indigo-600 font-medium">Webhooks</a>
                        <a href="{{ url('/backup') }}" class="text-gray-600 hover:text-gray-900">Backups</a>
                        <a href="{{ url('/backup/settings') }}" class="text-gray-600 hover:text-gray-900">Settings</a>
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
            <div class="border-4 border-dashed border-gray-200 rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Webhook Management</h2>
                    <div class="flex items-center space-x-4">
                        <span class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-blue-100 text-blue-800">
                            Total: {{ $webhooks->total() }} webhooks
                        </span>
                        <a href="{{ url('/admin/webhooks/create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add New Webhook
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        @forelse ($webhooks as $webhook)
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $webhook->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ ucfirst($webhook->event_type) }}
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $webhook->is_production ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $webhook->is_production ? 'Production' : 'Testing' }}
                                        </span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 truncate mt-1">
                                        {{ $webhook->name }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">
                                        {{ $webhook->endpoint_url }}
                                    </p>
                                    <div class="mt-2 flex items-center text-xs text-gray-500 space-x-4">
                                        <span>User: {{ $webhook->user->name ?? 'Unknown' }}</span>
                                        <span>Security: {{ ucfirst($webhook->security_type) }}</span>
                                        <span>Service: {{ ucfirst($webhook->service_type) }}</span>
                                        <span>Lang: {{ strtoupper($webhook->lang) }}</span>
                                        @if ($webhook->last_triggered_at)
                                        <span>Last triggered: {{ $webhook->last_triggered_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if ($webhook->last_response_code)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $webhook->last_response_code >= 200 && $webhook->last_response_code < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $webhook->last_response_code }}
                                    </span>
                                    @endif
                                    @if ($webhook->failure_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ $webhook->failure_count }} failures
                                    </span>
                                    @endif
                                    
                                    <!-- Action buttons -->
                                    <div class="flex items-center space-x-1">
                                        <!-- View button -->
                                        <a href="{{ url('/admin/webhooks/' . $webhook->id) }}" 
                                           class="inline-flex items-center p-1 border border-transparent rounded-md text-indigo-600 hover:bg-indigo-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        
                                        <!-- Edit button -->
                                        <a href="{{ url('/admin/webhooks/' . $webhook->id . '/edit') }}" 
                                           class="inline-flex items-center p-1 border border-transparent rounded-md text-yellow-600 hover:bg-yellow-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        
                                        <!-- Toggle status button -->
                                        <form method="POST" action="{{ url('/admin/webhooks/' . $webhook->id . '/toggle') }}" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center p-1 border border-transparent rounded-md text-blue-600 hover:bg-blue-50">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                                </svg>
                                            </button>
                                        </form>
                                        
                                        <!-- Delete button -->
                                        <form method="POST" action="{{ url('/admin/webhooks/' . $webhook->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this webhook?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center p-1 border border-transparent rounded-md text-red-600 hover:bg-red-50">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="px-6 py-8 text-center text-gray-500">
                            <div class="text-lg font-medium">No webhook configurations found</div>
                            <div class="text-sm">
                                <a href="{{ url('/admin/webhooks/create') }}" class="text-indigo-600 hover:text-indigo-500">
                                    Create your first webhook configuration
                                </a>
                            </div>
                        </li>
                        @endforelse
                    </ul>
                </div>

                @if ($webhooks->hasPages())
                <div class="mt-6">
                    {{ $webhooks->links() }}
                </div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>