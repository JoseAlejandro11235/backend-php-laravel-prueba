<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_returns_summary(): void
    {
        Sanctum::actingAs(User::factory()->create());
        Product::factory()->count(5)->create();

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure(['products', 'categories', 'low_stock', 'last_movements']);
    }
}
