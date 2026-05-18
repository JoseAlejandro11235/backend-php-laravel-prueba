<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with('category')
            ->withCount('stockMovements');

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findOrFail(int $id): Product
    {
        return Product::with('category')->findOrFail($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh(['category']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', $filters['category_id']);
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
        }
    }
}
