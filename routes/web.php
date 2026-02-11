<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DockerController;
use App\Http\Controllers\QuickCommandController;
use App\Http\Controllers\SupervisorController;

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

// Docker stats (internal access only)
Route::prefix('docker')->middleware('internal')->group(function () {
    Route::get('/', [DockerController::class, 'index'])->name('docker.index');
    Route::get('/stats', [DockerController::class, 'stats'])->name('docker.stats');
    Route::post('/restart/{container}', [DockerController::class, 'restart'])->name('docker.restart');
});

// Quick commands (internal access only)
Route::prefix('commands')->middleware('internal')->group(function () {
    Route::get('/', [QuickCommandController::class, 'index'])->name('commands.index');
    Route::post('/run', [QuickCommandController::class, 'run'])->name('commands.run');
});

// Supervisor status (internal access only)
Route::prefix('supervisor')->middleware('internal')->group(function () {
    Route::get('/', [SupervisorController::class, 'index'])->name('supervisor.index');
    Route::get('/status', [SupervisorController::class, 'status'])->name('supervisor.status');
    Route::post('/restart/{process}', [SupervisorController::class, 'restartProcess'])->name('supervisor.restart');
    Route::post('/restart-all', [SupervisorController::class, 'restartAll'])->name('supervisor.restart-all');
});
