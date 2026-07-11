<?php

declare(strict_types=1);

namespace App\Domains\Health\Http\Controllers;

use App\Domains\Health\Services\HealthCheckService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $health)
    {
    }

    public function public(): JsonResponse
    {
        return response()->json($this->health->publicStatus());
    }

    public function detailed(Request $request): JsonResponse
    {
        abort_unless((bool) $request->user()?->canAccessAdmin(), 403);

        return response()->json($this->health->detailedStatus());
    }
}
