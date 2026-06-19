<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class MetricsController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'uptime_seconds' => time() - (int) ($_SERVER['REQUEST_TIME'] ?? time()),
            'php_version' => PHP_VERSION,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'contact_requests_total' => \App\Models\ContactRequest::count(),
        ]);
    }
}
