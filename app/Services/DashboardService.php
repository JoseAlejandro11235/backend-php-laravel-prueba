<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\RedisHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function summary(): array
    {
        return Cache::remember('dashboard.summary', 60, function () {
            return [
                'products' => Product::count(),
                'categories' => DB::table('categories')->count(),
                'low_stock' => Product::query()
                    ->where('stock', '<', 10)
                    ->orderBy('stock')
                    ->limit(20)
                    ->get(['id', 'name', 'stock', 'category_id']),
                'last_movements' => StockMovement::query()
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get(['id', 'product_id', 'type', 'quantity', 'reason', 'created_at']),
            ];
        });
    }

    public function healthCheck(): array
    {
        DB::select('SELECT 1');

        $payload = [
            'status' => 'ok',
            'database' => 'mysql',
        ];

        if (RedisHealth::isConfigured()) {
            $payload['redis'] = RedisHealth::ping() ? 'connected' : 'unavailable';
            if ($payload['redis'] !== 'connected') {
                $payload['status'] = 'degraded';
            }
        }

        return $payload;
    }
}
