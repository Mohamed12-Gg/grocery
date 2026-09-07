<?php

namespace App\Http\Controllers\Api;

use App\Actions\Api\Order\StoreOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items.meal.category', 'items.meal.subcategory', 'address'])
            ->latest()
            ->paginate(Controller::PAGINATION_SIZE)
            ->map(function ($order) {
                return OrderResource::make($order)->toArray($request);
            });

        return $this->success('Orders retrieved successfully', [
            'orders' => $orders,
            'total_count' => $orders->count(),
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order = $order->load(['items.meal', 'address']);
        $data = OrderResource::make($order)->toArray($request);

        return $this->success('Order retrieved successfully', $data);
    }

    public function store(StoreOrderRequest $request, StoreOrderAction $action): JsonResponse
    {
        $order = $action->execute($request->validated(), $request->user());

        $data = OrderResource::make($order)->toArray($request);

        return $this->success('Order created successfully', $data);
    }
}
