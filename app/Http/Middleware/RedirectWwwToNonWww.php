<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWwwToNonWww
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (str_starts_with($host, 'www.')) {
            $nonWwwHost = str_replace('www.', '', $host);
            $newUrl = $request->getScheme() . '://' . $nonWwwHost . $request->getRequestUri();

            return redirect()->to($newUrl, 301);
        }
        
        return $next($request);
    }
}
