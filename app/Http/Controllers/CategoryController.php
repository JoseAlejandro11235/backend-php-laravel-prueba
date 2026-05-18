<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\IndexCategoryRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\Category\CategoryIndexResource;
use App\Http\Resources\Category\CategoryUpdateResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MessageResource;
use App\Models\Category;
use App\Services\CategoryService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Categories')]
class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(IndexCategoryRequest $request): CategoryIndexResource
    {
        $perPage = $request->validated('per_page') ?? 50;

        return new CategoryIndexResource(
            $this->categoryService->paginate($perPage)
        );
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($this->categoryService->findOrFail($category->id));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryUpdateResource
    {
        $category = $this->categoryService->update($category, $request->validated());

        return new CategoryUpdateResource($category);
    }

    public function destroy(Category $category): MessageResource
    {
        $this->categoryService->delete($category);

        return new MessageResource(['ok' => true]);
    }
}
