<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Admin Legacy',
            'email' => 'admin@legacy.test',
            'password' => Hash::make('password'),
            'api_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 1; $i <= 100; $i++) {
            DB::table('categories')->insert([
                'name' => 'Categoria ' . $i,
                'description' => 'Descripción de categoría ' . $i,
                'status' => $i % 7 !== 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Legacy issue: large inserts without chunks optimized for very large datasets.
        for ($i = 1; $i <= 10000; $i++) {
            DB::table('products')->insert([
                'name' => 'Producto Legacy ' . $i,
                'description' => 'Producto generado para prueba de rendimiento ' . $i,
                'price' => rand(1000, 30000) / 100,
                'stock' => rand(0, 200),
                'category_id' => rand(1, 100),
                'status' => $i % 9 !== 0,
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ]);
        }

        for ($i = 1; $i <= 30000; $i++) {
            DB::table('stock_movements')->insert([
                'product_id' => rand(1, 10000),
                'type' => rand(0, 1) ? 'entrada' : 'salida',
                'quantity' => rand(1, 20),
                'reason' => 'Movimiento legacy ' . $i,
                'user_id' => 1,
                'created_at' => now()->subDays(rand(0, 180)),
                'updated_at' => now(),
            ]);
        }
    }
}
