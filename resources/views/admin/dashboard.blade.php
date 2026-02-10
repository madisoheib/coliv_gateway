@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="border-4 border-dashed border-gray-200 rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Webhook Configurations Overview</h2>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium bg-blue-100 text-blue-800">
                    Total: {{ $webhooks->total() }} webhooks
                </span>
                <a href="{{ url('/admin/webhooks') }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Manage Webhooks
                </a>
            </div>
        </div>

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
                                @if ($webhook->is_production)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    Production
                                </span>
                                @endif
                            </div>
                            <p class="text-sm font-medium text-gray-900 truncate mt-1">
                                {{ $webhook->name }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $webhook->endpoint_url }}
                            </p>
                            <div class="mt-2 flex items-center text-xs text-gray-500 space-x-4">
                                <span>User ID: {{ $webhook->user_id }}</span>
                                <span>Security: {{ ucfirst($webhook->security_type) }}</span>
                                <span>Service: {{ ucfirst($webhook->service_type) }}</span>
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
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-6 py-8 text-center text-gray-500">
                    <div class="text-lg font-medium">No webhook configurations found</div>
                    <div class="text-sm">Webhook configurations will appear here when partners configure them.</div>
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
@endsection
