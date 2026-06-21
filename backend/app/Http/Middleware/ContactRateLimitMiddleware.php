<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContactRateLimitMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $ip = $request->ip();
        $key = "contact-form:{$ip}";

        $limit = config('contact.rate_limit.limit', 5);
        $window = config('contact.rate_limit.window_seconds', 60);

        $hits = (int) Cache::get($key, 0);

        if ($hits >= $limit) {
            return response()->json([
                'success' => false,
                'message' => 'Too many contact requests. Please try again later.',
            ], 429);
        }

        Cache::put($key, $hits + 1, $window);

        return $next($request);
    }
}
