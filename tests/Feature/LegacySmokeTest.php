<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacySmokeTest extends TestCase
{
    public function test_health_endpoint_exists()
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
    }

    // Legacy issue: tests are superficial and do not validate business rules.
}
