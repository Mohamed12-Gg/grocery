<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService,
    ) {}

    /**
     * Loyalty & rewards summary for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success(
            data: $this->loyaltyService->buildSummary($request->user()),
            message: 'Loyalty data retrieved successfully',
        );
    }
}