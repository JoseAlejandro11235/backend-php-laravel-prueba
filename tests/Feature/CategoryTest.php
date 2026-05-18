<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_categories_index_returns_collection(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonStructure(['categories', 'meta'])
            ->assertJsonCount(3, 'categories');
    }

    public function test_category_requires_name_on_create(): void
    {
        $this->postJson('/api/categories', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
