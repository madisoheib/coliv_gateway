<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect('http://local-webhook.colivraison:8180/login')->with('error', 'Please login to access admin area.');
        }

        // Check if user has admin role
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Access denied. Admin role required.');
        }

        return $next($request);
    }
}
