<?php

namespace Tests\Feature;

use App\Jobs\RefreshDashboardCache;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_outbound_movement_reduces_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson("/api/products/{$product->id}/stock-movements", [
            'type' => 'salida',
            'quantity' => 3,
            'reason' => 'Test outbound',
        ])->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7]);
    }

    public function test_outbound_fails_when_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->postJson("/api/products/{$product->id}/stock-movements", [
            'type' => 'salida',
            'quantity' => 5,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_stock_movement_dispatches_dashboard_cache_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock' => 10]);

        $this->postJson("/api/products/{$product->id}/stock-movements", [
            'type' => 'entrada',
            'quantity' => 1,
        ])->assertOk();

        Queue::assertPushed(RefreshDashboardCache::class);
    }
}
