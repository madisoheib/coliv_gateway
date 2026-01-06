<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserWebhook extends Model
{
    use HasFactory;
    protected $table = 'user_webhooks';

    protected $fillable = [
        'user_id',
        'name',
        'endpoint_url',
        'event_type',
        'service_type',
        'security_type',
        'security_token',
        'lang',
        'is_production',
        'is_active',
        'response_status_code',
        'last_triggered_at',
        'last_response_code',
        'last_response_body',
        'failure_count',
    ];

    /**
     * Supported languages for webhook responses
     */
    public const SUPPORTED_LANGUAGES = ['fr', 'en', 'ar'];
    public const DEFAULT_LANGUAGE = 'fr';

    protected function casts(): array
    {
        return [
            'is_production' => 'boolean',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'response_status_code' => 'integer',
            'last_response_code' => 'integer',
            'failure_count' => 'integer',
        ];
    }

    /**
     * Get the user that owns this webhook
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the logs for this webhook
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class, 'webhook_id', 'id');
    }

    /**
     * Check if webhook should receive this event type
     */
    public function matchesEventType(string $eventType): bool
    {
        return $this->event_type === 'all' || $this->event_type === $eventType;
    }

    /**
     * Check if webhook should receive this service type
     */
    public function matchesServiceType(string $serviceType): bool
    {
        return $this->service_type === 'all' || $this->service_type === $serviceType;
    }

    /**
     * Decrypt the security token
     */
    public function getDecryptedToken(): ?string
    {
        if (empty($this->security_token) || $this->security_type === 'none') {
            return null;
        }

        try {
            return decrypt($this->security_token);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Increment failure count
     */
    public function incrementFailure(): void
    {
        $this->increment('failure_count');
    }

    /**
     * Reset failure count on success
     */
    public function resetFailure(): void
    {
        $this->update(['failure_count' => 0]);
    }

    /**
     * Update last trigger info
     */
    public function updateLastTrigger(int $responseCode, ?string $responseBody = null): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'last_response_code' => $responseCode,
            'last_response_body' => $responseBody ? substr($responseBody, 0, 1000) : null,
        ]);
    }

    /**
     * Get the validated language for this webhook
     * Falls back to default if not set or invalid
     */
    public function getLanguage(): string
    {
        if (!empty($this->lang) && in_array($this->lang, self::SUPPORTED_LANGUAGES)) {
            return $this->lang;
        }

        return self::DEFAULT_LANGUAGE;
    }
}
