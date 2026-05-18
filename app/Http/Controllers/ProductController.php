<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\IndexStockMovementRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\StoreStockMovementRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\Product\ProductUpdateResource;
use App\Http\Resources\Product\StockMovementStoreResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\StockMovementService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Products')]
class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected StockMovementService $stockMovementService
    ) {}

    public function index(IndexProductRequest $request)
    {
        return ProductResource::collection(
            $this->productService->paginate($request->validated())
        );
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($this->productService->findOrFail($product->id));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductUpdateResource
    {
        $product = $this->productService->update($product, $request->validated());

        return new ProductUpdateResource($product);
    }

    public function destroy(Product $product): MessageResource
    {
        $this->productService->delete($product);

        return new MessageResource(['deleted' => true]);
    }

    public function stockMovements(IndexStockMovementRequest $request, Product $product)
    {
        $perPage = $request->validated('per_page') ?? 20;

        return StockMovementResource::collection(
            $this->stockMovementService->paginateForProduct($product, $perPage)
        );
    }

    public function storeStockMovement(
        StoreStockMovementRequest $request,
        Product $product
    ): StockMovementStoreResource {
        $result = $this->stockMovementService->register(
            $product,
            $request->validated(),
            $request->user()
        );

        return new StockMovementStoreResource($result);
    }
}
