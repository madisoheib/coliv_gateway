<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - ColivGateway
|--------------------------------------------------------------------------
|
| These routes handle webhook dispatching for the ColivraisonExpress system.
| All routes are prefixed with /api
|
*/

// Public endpoints (no authentication)
Route::get('/health', [WebhookController::class, 'health']);
Route::get('/status-mappings', [WebhookController::class, 'statusMappings']);

// Webhook endpoints with authentication (restricted to authenticated internal requests)
Route::middleware(['internal', 'webhook.auth'])->group(function () {
    Route::post('/dispatch', [WebhookController::class, 'dispatch']);
    Route::post('/webhook/dispatch', [WebhookController::class, 'dispatchToPartners']);
});
