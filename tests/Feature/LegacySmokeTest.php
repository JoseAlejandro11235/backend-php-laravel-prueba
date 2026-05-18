<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_framework_health_endpoint(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_api_health_endpoint_exists(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
    }

    // Legacy issue: tests are superficial and do not validate business rules.
}
