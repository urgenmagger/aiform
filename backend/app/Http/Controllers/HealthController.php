<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController
{
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'aiform',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
