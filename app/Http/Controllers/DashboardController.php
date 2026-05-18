<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Legacy issue: multiple heavy queries without caching or optimized indexes.
        return response()->json([
            'products' => DB::table('products')->count(),
            'categories' => DB::table('categories')->count(),
            'low_stock' => DB::table('products')->where('stock', '<', 10)->get(),
            'last_movements' => DB::table('stock_movements')->orderBy('created_at', 'desc')->limit(20)->get(),
        ]);
    }

    public function health()
    {
        try {
            DB::select('SELECT 1');
            return response()->json(['status' => 'ok', 'database' => 'connected']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'fail', 'error' => $e->getMessage()], 500);
        }
    }
}
