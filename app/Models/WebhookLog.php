<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    public $timestamps = false;

    protected $fillable = [
        'webhook_id',
        'user_id',
        'event_type',
        'payload',
        'response_code',
        'response_body',
        'response_time_ms',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_code' => 'integer',
            'response_time_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the webhook that this log belongs to
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(UserWebhook::class, 'webhook_id', 'id');
    }

    /**
     * Get the user that this log belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Create a log entry for a failed webhook call
     */
    public static function logFailure(
        int $webhookId,
        int $userId,
        string $eventType,
        array $payload,
        ?int $responseCode,
        ?string $responseBody,
        int $responseTimeMs,
        string $errorMessage
    ): self {
        return self::create([
            'webhook_id' => $webhookId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'payload' => $payload,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? substr($responseBody, 0, 1000) : null,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }
}
