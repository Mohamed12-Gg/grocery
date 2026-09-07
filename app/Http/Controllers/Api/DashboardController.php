<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            data: $this->dashboardService->getDashboardData($request->user()),
            message: 'Dashboard data retrieved successfully',
        );
    }
}