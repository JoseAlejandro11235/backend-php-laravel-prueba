<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementStoreResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'message' => 'Stock updated',
            'product' => new ProductResource($this->resource['product']),
            'movement' => new StockMovementResource($this->resource['movement']),
        ];
    }
}
