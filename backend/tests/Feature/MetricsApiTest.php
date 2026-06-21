<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_endpoint_returns_stats(): void
    {
        $response = $this->getJson('/api/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'uptime_seconds',
                'php_version',
                'memory_usage_mb',
                'contact_requests_total',
            ]);

        $data = $response->json();

        $this->assertSame(PHP_VERSION, $data['php_version']);
        $this->assertIsNumeric($data['memory_usage_mb']);
        $this->assertGreaterThan(0, $data['memory_usage_mb']);
        $this->assertGreaterThanOrEqual(0, $data['uptime_seconds']);
        $this->assertSame(0, $data['contact_requests_total']);
    }
}
