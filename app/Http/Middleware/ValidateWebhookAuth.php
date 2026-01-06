<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ValidateWebhookAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip validation if authentication is not required
        if (!config('webhook.auth.required')) {
            return $next($request);
        }

        $expectedToken = config('webhook.auth.token');
        $headerName = config('webhook.auth.header');

        // Check if token is configured
        if (empty($expectedToken)) {
            Log::error('Webhook auth token not configured', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication not properly configured'
            ], 500);
        }

        // Get token from request header
        $providedToken = $request->header($headerName);

        // Check if token is provided
        if (empty($providedToken)) {
            Log::warning('Webhook request missing authentication token', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'expected_header' => $headerName
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication token required',
                'expected_header' => $headerName
            ], 401);
        }

        // Validate token
        if (!hash_equals($expectedToken, $providedToken)) {
            Log::error('Webhook request with invalid authentication token', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'provided_token_length' => strlen($providedToken),
                'expected_token_length' => strlen($expectedToken)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token'
            ], 401);
        }

        // Validate User-Agent if required
        if (config('webhook.security.verify_user_agent')) {
            $expectedUserAgent = config('webhook.security.expected_user_agent');
            $providedUserAgent = $request->userAgent();

            if ($expectedUserAgent && $providedUserAgent !== $expectedUserAgent) {
                Log::warning('Webhook request with unexpected User-Agent', [
                    'ip' => $request->ip(),
                    'expected_user_agent' => $expectedUserAgent,
                    'provided_user_agent' => $providedUserAgent,
                    'url' => $request->fullUrl()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid User-Agent'
                ], 401);
            }
        }

        // Log successful authentication
        if (config('webhook.processing.log_requests')) {
            Log::info('Webhook request authenticated successfully', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'content_length' => $request->header('Content-Length', 0)
            ]);
        }

        return $next($request);
    }
}