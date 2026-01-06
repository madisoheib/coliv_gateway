<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalApiOnly
{
    /**
     * Allowed internal IP ranges (Docker networks, localhost)
     */
    protected array $allowedIpRanges = [
        '127.0.0.1',
        '::1',
        '172.16.0.0/12',   // Docker default bridge networks
        '10.0.0.0/8',      // Private network range
        '192.168.0.0/16',  // Private network range
    ];

    /**
     * Handle an incoming request.
     *
     * Check if the request comes from an internal source (Docker network)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in local/testing environments
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if ($this->isAllowedIp($clientIp)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied. This endpoint is for internal use only.',
        ], 403);
    }

    /**
     * Check if the IP is in allowed ranges
     */
    protected function isAllowedIp(string $ip): bool
    {
        foreach ($this->allowedIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is within a CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);

        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }
}
