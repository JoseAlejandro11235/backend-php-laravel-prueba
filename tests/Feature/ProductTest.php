<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_products_index_is_paginated(): void
    {
        Product::factory()->count(20)->create();

        $response = $this->getJson('/api/products?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJsonCount(10, 'data');
    }

    public function test_product_can_be_created_with_validation(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/products', [
            'name' => 'Test product',
            'price' => 10.5,
            'stock' => 5,
            'category_id' => $category->id,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Test product');

        $this->assertDatabaseHas('products', ['name' => 'Test product']);
    }

    public function test_product_create_requires_name(): void
    {
        $this->postJson('/api/products', [
            'price' => 10,
            'stock' => 1,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_product_can_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJson(['deleted' => true]);
    }
}
