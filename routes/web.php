<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\WebhookController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin routes (protected by admin middleware)
Route::middleware(['admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Webhook management routes
    Route::resource('webhooks', WebhookController::class)->names([
        'index' => 'admin.webhooks.index',
        'create' => 'admin.webhooks.create',
        'store' => 'admin.webhooks.store',
        'show' => 'admin.webhooks.show',
        'edit' => 'admin.webhooks.edit',
        'update' => 'admin.webhooks.update',
        'destroy' => 'admin.webhooks.destroy',
    ]);
    
    // Additional webhook routes
    Route::post('/webhooks/{webhook}/toggle', [WebhookController::class, 'toggle'])->name('admin.webhooks.toggle');
});
