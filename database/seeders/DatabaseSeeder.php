<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin Legacy',
            'email' => 'admin@legacy.test',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryCount = (int) env('SEED_CATEGORIES', 30);
        $productCount = (int) env('SEED_PRODUCTS', 200);
        $movementCount = (int) env('SEED_STOCK_MOVEMENTS', 500);

        $categories = [];
        for ($i = 1; $i <= $categoryCount; $i++) {
            $categories[] = [
                'name' => 'Categoria '.$i,
                'description' => 'Descripción de categoría '.$i,
                'status' => $i % 7 !== 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($categories, 100) as $chunk) {
            DB::table('categories')->insert($chunk);
        }

        $products = [];
        for ($i = 1; $i <= $productCount; $i++) {
            $products[] = [
                'name' => 'Producto '.$i,
                'description' => 'Producto de prueba '.$i,
                'price' => rand(1000, 30000) / 100,
                'stock' => rand(0, 200),
                'category_id' => rand(1, $categoryCount),
                'status' => $i % 9 !== 0,
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($products, 500) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        $movements = [];
        for ($i = 1; $i <= $movementCount; $i++) {
            $movements[] = [
                'product_id' => rand(1, $productCount),
                'type' => rand(0, 1) ? 'entrada' : 'salida',
                'quantity' => rand(1, 20),
                'reason' => 'Movimiento seed '.$i,
                'user_id' => 1,
                'created_at' => now()->subDays(rand(0, 180)),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($movements, 500) as $chunk) {
            DB::table('stock_movements')->insert($chunk);
        }
    }
}
