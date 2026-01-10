<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colis;
use App\Models\UserWebhook;
use App\Models\WebhookLog;
use App\Services\StatusMapper;
use App\Services\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    protected WebhookDispatcher $dispatcher;
    protected StatusMapper $statusMapper;

    public function __construct(WebhookDispatcher $dispatcher, StatusMapper $statusMapper)
    {
        $this->dispatcher = $dispatcher;
        $this->statusMapper = $statusMapper;
    }

    /**
     * Dispatch webhooks for a status change
     *
     * POST /api/dispatch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dispatch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'status_id' => 'required|integer',
            'service' => 'required|string|in:delivery,warehouse,call_center',
            'reason' => 'nullable|string|max:500',
            'lang' => 'nullable|string|in:fr,en,ar',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->dispatcher->dispatch(
                $request->input('order_id'),
                $request->input('status_id'),
                $request->input('service'),
                $request->input('reason'),
                $request->input('lang', 'fr')
            );

            $success = !isset($result['error']) && $result['webhooks_failed'] === 0;

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Webhooks dispatched successfully' : 'Some webhooks failed',
                'data' => $result,
            ], $success ? 200 : 207); // 207 Multi-Status for partial success

        } catch (\Exception $e) {
            Log::error('WebhookController: Dispatch failed', [
                'order_id' => $request->input('order_id'),
                'status_id' => $request->input('status_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Health check endpoint
     *
     * GET /api/health
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'coliv_gateway',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get status mappings for documentation
     *
     * GET /api/status-mappings
     *
     * @return JsonResponse
     */
    public function statusMappings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'delivery' => [
                    ['id' => 100, 'name' => 'delivery_on_way', 'description' => 'Package is on the way to destination'],
                    ['id' => 101, 'name' => 'delivery_delivered', 'description' => 'Package successfully delivered'],
                    ['id' => 102, 'name' => 'delivery_failed', 'description' => 'Delivery attempt failed'],
                    ['id' => 103, 'name' => 'delivery_returned', 'description' => 'Package returned to sender'],
                    ['id' => 104, 'name' => 'delivery_in_hub', 'description' => 'Package in distribution hub'],
                ],
                'warehouse' => [
                    ['id' => 200, 'name' => 'warehouse_on_process', 'description' => 'Being processed in warehouse'],
                    ['id' => 201, 'name' => 'warehouse_ready', 'description' => 'Ready for dispatch from warehouse'],
                ],
                'call_center' => [
                    ['id' => 300, 'name' => 'call_on_process', 'description' => 'Call in progress'],
                    ['id' => 301, 'name' => 'call_confirmed', 'description' => 'Order confirmed via call'],
                    ['id' => 302, 'name' => 'call_reported', 'description' => 'Issue reported during call'],
                    ['id' => 303, 'name' => 'call_failed', 'description' => 'Call attempt failed'],
                    ['id' => 304, 'name' => 'call_cancelled', 'description' => 'Order cancelled via call'],
                ],
            ],
        ]);
    }

    /**
     * Dispatch webhook to partner endpoints (called from ColivraisonExpress)
     *
     * POST /api/webhook/dispatch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dispatchToPartners(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'id_stats' => 'required|integer', 
            'id_partenaire' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $request->validated();

        Log::info('Webhook dispatch request received from ColivraisonExpress', [
            'order_id' => $payload['order_id'],
            'id_stats' => $payload['id_stats'],
            'id_partenaire' => $payload['id_partenaire']
        ]);

        try {
            // Get colis data from ColivraisonExpress database
            $colis = $this->getColisData($payload['order_id']);
            if (!$colis) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Get partner webhooks configuration
            $webhooks = $this->getPartnerWebhooks($payload['id_partenaire']);
            if ($webhooks->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active webhooks configured for partner',
                    'processed' => 0
                ]);
            }

            $results = [];
            foreach ($webhooks as $webhook) {
                $result = $this->sendWebhookToPartner($webhook, $colis, $payload['id_stats']);
                $results[] = $result;
            }

            return response()->json([
                'success' => true,
                'processed' => count($webhooks),
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process webhook dispatch', [
                'order_id' => $payload['order_id'],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get colis data using Eloquent
     */
    private function getColisData($orderId)
    {
        return Colis::where('id_colis', $orderId)->first();
    }

    /**
     * Get partner webhooks configuration using Eloquent
     */
    private function getPartnerWebhooks($partnerId)
    {
        return UserWebhook::where('user_id', $partnerId)
            ->where('is_active', true)
            ->where('event_type', 'status_change')
            ->get();
    }


    /**
     * Send webhook to individual partner endpoint
     */
    private function sendWebhookToPartner($webhook, $colis, $newStatusId)
    {
        $startTime = microtime(true);
        
        try {
            // Get default lang from webhook or use 'fr'
            $defaultLang = $webhook->getLanguage();
            
            // Determine service type from status ID
            $service = $this->statusMapper->determineServiceType(
                $this->statusMapper->getMainStatus($newStatusId)
            );
            
            // Build the structured payload for partner using StatusMapper
            $statusPayload = $this->statusMapper->buildStatusPayload($newStatusId, $defaultLang);
            
            $webhookPayload = [
                'tracking_id' => $colis->tracking_order ?? 'TRK-' . $colis->id_colis,
                'ref_order' => $colis->ref_order ?? 'CMD-' . $colis->id_colis,
                'status' => $statusPayload,
                'service' => $service,
            ];

            // Prepare HTTP client with security using new config
            $http = Http::timeout(config('webhook.partner.timeout', 10))
                ->retry(config('webhook.partner.max_retries', 3), config('webhook.partner.retry_delay', 1000))
                ->withHeaders(['User-Agent' => config('webhook.partner.user_agent', 'ColivraisonGateway/1.0')])
                ->when(!config('webhook.partner.verify_ssl'), function ($httpClient) {
                    return $httpClient->withoutVerifying();
                });

            // Add security headers if needed
            if ($webhook->security_type === 'bearer_token' && !empty($webhook->security_token)) {
                $http = $http->withToken($webhook->security_token);
            } elseif ($webhook->security_type === 'api_key' && !empty($webhook->security_token)) {
                $http = $http->withHeaders(['X-API-Key' => $webhook->security_token]);
            }

            // Send the webhook
            $response = $http->post($webhook->endpoint_url, $webhookPayload);
            
            $responseTime = (microtime(true) - $startTime) * 1000;

            // Log the webhook attempt using Eloquent
            $webhook->updateLastTrigger($response->status(), $response->body());
            $this->createWebhookLog($webhook, 'status_change', $webhookPayload, $response->status(), $response->body(), null, (int) $responseTime);

            Log::info("Webhook sent successfully to partner", [
                'webhook_id' => $webhook->id,
                'endpoint' => $webhook->endpoint_url,
                'response_code' => $response->status(),
                'response_time_ms' => $responseTime
            ]);

            return [
                'webhook_id' => $webhook->id,
                'endpoint' => $webhook->endpoint_url,
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time_ms' => (int) $responseTime
            ];

        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            // Log the error using Eloquent
            $webhook->incrementFailure();
            $this->createWebhookLog($webhook, 'status_change', $webhookPayload ?? [], null, null, $e->getMessage(), (int) $responseTime);

            Log::error("Webhook failed to send to partner", [
                'webhook_id' => $webhook->id,
                'endpoint' => $webhook->endpoint_url,
                'error' => $e->getMessage(),
                'response_time_ms' => $responseTime
            ]);

            return [
                'webhook_id' => $webhook->id,
                'endpoint' => $webhook->endpoint_url,
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => (int) $responseTime
            ];
        }
    }

    /**
     * Create webhook log entry using Eloquent
     */
    private function createWebhookLog(UserWebhook $webhook, string $eventType, array $payload, ?int $responseCode, ?string $responseBody, ?string $errorMessage, int $responseTime)
    {
        try {
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'user_id' => $webhook->user_id,
                'event_type' => $eventType,
                'payload' => json_encode($payload),
                'response_code' => $responseCode,
                'response_body' => $responseBody,
                'response_time_ms' => $responseTime,
                'error_message' => $errorMessage,
            ]);

            // Handle failure count
            if ($responseCode && $responseCode >= 400) {
                $webhook->incrementFailure();
            } else {
                $webhook->resetFailure();
            }

        } catch (\Exception $e) {
            Log::error("Failed to create webhook log", [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
