<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Enforce strong security headers including Content Security Policy (CSP)
        $response->headers->set('X-Frame-Options', 'DENY'); // Prevents clickjacking
        $response->headers->set('X-XSS-Protection', '1; mode=block'); // Extra XSS protection
        $response->headers->set('X-Content-Type-Options', 'nosniff'); // Prevents MIME sniffing
        
        // Basic CSP: Allows scripts only from self and trusted CDNs (like jQuery)
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;");

        return $response;
    }
}
