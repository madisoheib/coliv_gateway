<?php

namespace App\Providers;

use App\Services\StatusMapper;
use App\Services\WebhookDispatcher;
use Illuminate\Support\ServiceProvider;

class WebhookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register StatusMapper as a singleton
        $this->app->singleton(StatusMapper::class, function ($app) {
            return new StatusMapper();
        });

        // Register WebhookDispatcher with StatusMapper dependency
        $this->app->singleton(WebhookDispatcher::class, function ($app) {
            return new WebhookDispatcher(
                $app->make(StatusMapper::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
