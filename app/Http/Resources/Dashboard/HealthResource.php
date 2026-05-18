<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return $this->resource;
    }

    public static function failure(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'fail',
            'error' => $message,
        ], 500);
    }
}
