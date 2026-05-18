<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $forgetDashboard = fn () => Cache::forget('dashboard.summary');
        Product::saved($forgetDashboard);
        Product::deleted($forgetDashboard);
        Category::saved($forgetDashboard);
        Category::deleted($forgetDashboard);
        StockMovement::saved($forgetDashboard);
    }
}
