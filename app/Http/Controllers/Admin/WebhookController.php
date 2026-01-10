<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserWebhook;
use App\Models\User;

class WebhookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $webhooks = UserWebhook::with('user')->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.webhooks.index', compact('webhooks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('admin.webhooks.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'endpoint_url' => 'required|url|max:500',
            'event_type' => 'required|in:all,status_change,order_created,order_updated',
            'service_type' => 'required|in:all,delivery,pickup,return',
            'security_type' => 'required|in:none,bearer_token,api_key',
            'security_token' => 'nullable|string|max:500',
            'lang' => 'required|in:fr,en,ar',
            'is_production' => 'nullable',
            'is_active' => 'nullable',
        ]);

        // Convert checkbox values to proper booleans
        $validated['is_production'] = (bool) $request->input('is_production', 0);
        $validated['is_active'] = (bool) $request->input('is_active', 0);

        // Encrypt security token if provided
        if (isset($validated['security_token']) && !empty($validated['security_token']) && $validated['security_type'] !== 'none') {
            $validated['security_token'] = encrypt($validated['security_token']);
        } elseif ($validated['security_type'] === 'none') {
            $validated['security_token'] = null;
        }

        UserWebhook::create($validated);

        return redirect(rtrim(config('app.url'), '/') . '/admin/webhooks')
            ->with('success', 'Webhook configuration created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $webhook = UserWebhook::with(['user', 'logs' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }])->findOrFail($id);
        
        return view('admin.webhooks.show', compact('webhook'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $webhook = UserWebhook::findOrFail($id);
        $users = User::orderBy('name')->get();
        
        // Decrypt token for editing
        if ($webhook->security_token && $webhook->security_type !== 'none') {
            $webhook->security_token = $webhook->getDecryptedToken();
        }
        
        return view('admin.webhooks.edit', compact('webhook', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $webhook = UserWebhook::findOrFail($id);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'endpoint_url' => 'required|url|max:500',
            'event_type' => 'required|in:all,status_change,order_created,order_updated',
            'service_type' => 'required|in:all,delivery,pickup,return',
            'security_type' => 'required|in:none,bearer_token,api_key',
            'security_token' => 'nullable|string|max:500',
            'lang' => 'required|in:fr,en,ar',
            'is_production' => 'nullable',
            'is_active' => 'nullable',
        ]);

        // Convert checkbox values to proper booleans
        $validated['is_production'] = (bool) $request->input('is_production', 0);
        $validated['is_active'] = (bool) $request->input('is_active', 0);

        // Encrypt security token if provided
        if (isset($validated['security_token']) && !empty($validated['security_token']) && $validated['security_type'] !== 'none') {
            $validated['security_token'] = encrypt($validated['security_token']);
        } elseif ($validated['security_type'] === 'none') {
            $validated['security_token'] = null;
        } elseif (!isset($validated['security_token']) || empty($validated['security_token'])) {
            // Keep existing token if not provided
            unset($validated['security_token']);
        }

        $webhook->update($validated);

        return redirect(rtrim(config('app.url'), '/') . '/admin/webhooks')
            ->with('success', 'Webhook configuration updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $webhook = UserWebhook::findOrFail($id);
        $webhook->delete();

        return redirect(rtrim(config('app.url'), '/') . '/admin/webhooks')
            ->with('success', 'Webhook configuration deleted successfully!');
    }

    /**
     * Toggle webhook status (active/inactive)
     */
    public function toggle(string $id)
    {
        $webhook = UserWebhook::findOrFail($id);
        $webhook->update(['is_active' => !$webhook->is_active]);
        
        $status = $webhook->is_active ? 'activated' : 'deactivated';
        return redirect(rtrim(config('app.url'), '/') . '/admin/webhooks')
            ->with('success', "Webhook {$status} successfully!");
    }
}
