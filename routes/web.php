<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\BackupController;

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

// Backup management routes (internal access only)
Route::prefix('backup')->name('backup.')->middleware('internal')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('index');
    Route::post('/run', [BackupController::class, 'runBackup'])->name('run');
    Route::post('/clean', [BackupController::class, 'clean'])->name('clean');
    Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
    Route::delete('/{filename}', [BackupController::class, 'delete'])->name('delete');
    Route::get('/settings', [BackupController::class, 'settings'])->name('settings');
    Route::post('/settings', [BackupController::class, 'updateSettings'])->name('settings.update');
    Route::post('/test-connection', [BackupController::class, 'testConnection'])->name('test-connection');
    Route::get('/status', [BackupController::class, 'backupStatus'])->name('status');
    Route::get('/logs', [BackupController::class, 'logs'])->name('logs');
    Route::post('/logs/clear', [BackupController::class, 'clearLogs'])->name('logs.clear');
});
