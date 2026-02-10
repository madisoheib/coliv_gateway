<?php

namespace App\Providers;

use App\Services\BackupConfigService;
use Illuminate\Support\ServiceProvider;

class BackupConfigProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BackupConfigService::class);
    }

    public function boot(): void
    {
        try {
            app(BackupConfigService::class)->applyConfig();
        } catch (\Exception) {
            // DB not available yet (e.g. during migration or when DB host is unreachable)
        }
    }
}
