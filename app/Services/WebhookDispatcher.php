<?php

namespace App\Services;

use App\Models\Colis;
use App\Models\Stats;
use App\Models\UserWebhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    protected StatusMapper $statusMapper;
    protected int $timeout;

    public function __construct(StatusMapper $statusMapper)
    {
        $this->statusMapper = $statusMapper;
        $this->timeout = (int) config('app.webhook_timeout', 10);
    }

    /**
     * Dispatch webhooks for a status change
     *
     * Note: The `lang` parameter is used as a fallback if webhook doesn't have a language set.
     * Each webhook uses its own configured language (from user_webhooks.lang column).
     */
    public function dispatch(int $orderId, int $statusId, string $service, ?string $reason = null, string $lang = 'fr'): array
    {
        $results = [
            'order_id' => $orderId,
            'status_id' => $statusId,
            'webhooks_triggered' => 0,
            'webhooks_succeeded' => 0,
            'webhooks_failed' => 0,
            'details' => [],
        ];

        // Fetch the order
        $colis = Colis::find($orderId);
        if (!$colis) {
            Log::warning('WebhookDispatcher: Order not found', ['order_id' => $orderId]);
            $results['error'] = 'Order not found';
            return $results;
        }

        // Get main webhook status ID and event type
        $mainStatusId = $this->statusMapper->getMainStatus($statusId);
        $subStatusId = $this->statusMapper->getSubStatus($statusId);
        $eventType = $this->statusMapper->determineEventType($mainStatusId);
        $serviceType = $service ?: $this->statusMapper->determineServiceType($mainStatusId);

        // Add mapping info to results
        $results['main_status'] = $mainStatusId;
        $results['sub_status'] = $subStatusId;

        // Find all matching webhooks for this user
        $webhooks = UserWebhook::where('user_id', $colis->id_partenaire)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            // Check if webhook should receive this event
            if (!$webhook->matchesEventType($eventType)) {
                continue;
            }

            if (!$webhook->matchesServiceType($serviceType)) {
                continue;
            }

            $results['webhooks_triggered']++;

            // Build payload using webhook's configured language (with fallback)
            $webhookLang = $webhook->getLanguage() ?: $lang;
            $payload = $this->buildPayload($colis, $statusId, $serviceType, $reason, $webhookLang);

            $sendResult = $this->sendToWebhook($webhook, $payload, $eventType);
            $sendResult['lang'] = $webhookLang; // Include lang in result for transparency
            $results['details'][] = $sendResult;

            if ($sendResult['success']) {
                $results['webhooks_succeeded']++;
            } else {
                $results['webhooks_failed']++;
            }
        }

        return $results;
    }

    /**
     * Send payload to a specific webhook
     */
    protected function sendToWebhook(UserWebhook $webhook, array $payload, string $eventType): array
    {
        $startTime = microtime(true);
        $result = [
            'webhook_id' => $webhook->id,
            'webhook_name' => $webhook->name,
            'endpoint' => $webhook->endpoint_url,
            'success' => false,
            'response_code' => null,
            'response_body' => null,
            'response_time_ms' => 0,
            'error' => null,
        ];

        try {
            // Build headers
            $headers = $this->buildHeaders($webhook, $eventType);

            // Send the request
            $response = Http::withHeaders($headers)
                ->timeout($this->timeout)
                ->post($webhook->endpoint_url, $payload);

            $endTime = microtime(true);
            $responseTimeMs = (int) (($endTime - $startTime) * 1000);

            $result['response_code'] = $response->status();
            $result['response_body'] = substr($response->body(), 0, 1000);
            $result['response_time_ms'] = $responseTimeMs;
            $result['success'] = $response->successful();

            // Update webhook record
            $webhook->updateLastTrigger($response->status(), $response->body());

            if ($response->successful()) {
                $webhook->resetFailure();
            } else {
                $webhook->incrementFailure();
                $this->logFailure($webhook, $eventType, $payload, $response->status(), $response->body(), $responseTimeMs, 'HTTP error: ' . $response->status());
            }

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTimeMs = (int) (($endTime - $startTime) * 1000);

            $result['response_time_ms'] = $responseTimeMs;
            $result['error'] = $e->getMessage();

            Log::error('WebhookDispatcher: Request failed', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            $webhook->incrementFailure();
            $this->logFailure($webhook, $eventType, $payload, null, null, $responseTimeMs, $e->getMessage());
        }

        return $result;
    }

    /**
     * Build the webhook payload
     *
     * Payload structure:
     * {
     *   "tracking_id": "TRK-123456",
     *   "ref_order": "CMD-2025-001",
     *   "status": {
     *     "id": 303,
     *     "name": "call_failed",
     *     "sub_status": {
     *       "id": 3031,
     *       "name": "call_failed_31",
     *       "reason": "Rupture de stock"
     *     }
     *   },
     *   "service": "call_center"
     * }
     */
    protected function buildPayload(Colis $colis, int $statusId, string $service, ?string $reason = null, string $lang = 'fr'): array
    {
        $statusPayload = $this->statusMapper->buildStatusPayload($statusId, $lang);

        // Override reason if provided
        if ($reason) {
            $statusPayload['sub_status']['reason'] = $reason;
        }

        return [
            'tracking_id' => $colis->tracking_order ?? 'TRK-' . $colis->id_colis,
            'ref_order' => $colis->ref_order ?? 'CMD-' . $colis->id_colis,
            'status' => $statusPayload,
            'service' => $service,
        ];
    }

    /**
     * Build headers for the webhook request
     */
    protected function buildHeaders(UserWebhook $webhook, string $eventType): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Webhook-Source' => 'ColivraisonExpress',
            'X-Webhook-Event' => $eventType,
            'X-Webhook-Timestamp' => now()->toIso8601String(),
        ];

        // Add security headers based on type
        if ($webhook->security_type !== 'none') {
            $token = $webhook->getDecryptedToken();

            if ($token) {
                switch ($webhook->security_type) {
                    case 'bearer_token':
                        $headers['Authorization'] = 'Bearer ' . $token;
                        break;
                    case 'api_key':
                        $headers['X-API-Key'] = $token;
                        break;
                    case 'public_key':
                        $headers['X-Public-Key'] = $token;
                        break;
                    case 'basic_auth':
                        $headers['Authorization'] = 'Basic ' . base64_encode($token);
                        break;
                }
            }
        }

        return $headers;
    }

    /**
     * Log a failed webhook call (basic logging - errors only)
     */
    protected function logFailure(
        UserWebhook $webhook,
        string $eventType,
        array $payload,
        ?int $responseCode,
        ?string $responseBody,
        int $responseTimeMs,
        string $errorMessage
    ): void {
        try {
            WebhookLog::logFailure(
                $webhook->id,
                $webhook->user_id,
                $eventType,
                $payload,
                $responseCode,
                $responseBody,
                $responseTimeMs,
                $errorMessage
            );
        } catch (\Exception $e) {
            Log::error('WebhookDispatcher: Failed to log webhook failure', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
