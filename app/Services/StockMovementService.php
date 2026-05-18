<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Jobs\RefreshDashboardCache;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function paginateForProduct(Product $product, int $perPage = 20): LengthAwarePaginator
    {
        return StockMovement::query()
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function register(Product $product, array $data, User $user): array
    {
        return DB::transaction(function () use ($product, $data, $user) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            $type = StockMovementType::from($data['type']);
            $quantity = (int) $data['quantity'];

            if ($type === StockMovementType::Out && $product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient stock for this outbound movement.'],
                ]);
            }

            $product->stock = $type === StockMovementType::In
                ? $product->stock + $quantity
                : $product->stock - $quantity;

            $product->save();

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $data['reason'] ?? null,
                'user_id' => $user->id,
            ]);

            RefreshDashboardCache::dispatch();

            return [
                'product' => $product->fresh(['category']),
                'movement' => $movement,
            ];
        });
    }
}
