<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the coliv_gateway webhook service.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Authentication settings for incoming webhook requests from ColivraisonExpress.
    |
    */
    'auth' => [
        'token' => env('WEBHOOK_AUTH_TOKEN'),
        'header' => env('WEBHOOK_AUTH_HEADER', 'X-Gateway-Token'),
        'required' => env('WEBHOOK_AUTH_REQUIRED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Processing Settings
    |--------------------------------------------------------------------------
    |
    | Settings for webhook request processing.
    |
    */
    'processing' => [
        'timeout' => env('WEBHOOK_PROCESSING_TIMEOUT', 30),
        'max_retries' => env('WEBHOOK_PROCESSING_MAX_RETRIES', 3),
        'retry_delay' => env('WEBHOOK_PROCESSING_RETRY_DELAY', 1000), // milliseconds
        'log_requests' => env('WEBHOOK_LOG_REQUESTS', true),
        'log_responses' => env('WEBHOOK_LOG_RESPONSES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configuration for webhook processing.
    |
    */
    'security' => [
        'allowed_ips' => array_filter(explode(',', env('WEBHOOK_ALLOWED_IPS', ''))),
        'rate_limit' => env('WEBHOOK_RATE_LIMIT', 60), // requests per minute
        'verify_user_agent' => env('WEBHOOK_VERIFY_USER_AGENT', false),
        'expected_user_agent' => env('WEBHOOK_EXPECTED_USER_AGENT', 'ColivraisonExpress/1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Settings for dispatching webhooks to partner endpoints.
    |
    */
    'partner' => [
        'timeout' => env('PARTNER_WEBHOOK_TIMEOUT', 10),
        'max_retries' => env('PARTNER_WEBHOOK_MAX_RETRIES', 3),
        'retry_delay' => env('PARTNER_WEBHOOK_RETRY_DELAY', 1000), // milliseconds
        'verify_ssl' => env('PARTNER_WEBHOOK_VERIFY_SSL', true),
        'user_agent' => env('PARTNER_WEBHOOK_USER_AGENT', 'ColivraisonGateway/1.0'),
    ],
];