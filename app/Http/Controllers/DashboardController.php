<?php

namespace App\Http\Controllers;

use App\Http\Resources\Dashboard\DashboardResource;
use App\Http\Resources\Dashboard\HealthResource;
use App\Services\DashboardService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Dashboard')]
class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(): DashboardResource
    {
        return new DashboardResource($this->dashboardService->summary());
    }

    public function health(): HealthResource|\Illuminate\Http\JsonResponse
    {
        try {
            return new HealthResource($this->dashboardService->healthCheck());
        } catch (\Throwable $e) {
            return HealthResource::failure($e->getMessage());
        }
    }
}
