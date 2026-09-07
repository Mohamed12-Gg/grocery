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
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponse;

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order = $order->load(['items.meal', 'address']);
        $data = OrderResource::make($order)->toArray($request);

        return $this->success('Order retrieved successfully', $data);
    }

    /**
     * Create a new order.
     */
    public function store(StoreOrderRequest $request, StoreOrderAction $action): JsonResponse
    {
        try {
            $order = $action->execute($request->validated(), $request->user());

            $data = OrderResource::make($order)->toArray($request);

            return $this->success('Order created successfully', $data);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to create order', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all user orders.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $orders = Order::with(['items.meal.category', 'items.meal.subcategory', 'address'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    return OrderResource::make($order)->toArray($request);
                });

            return $this->success('Orders retrieved successfully', [
                'orders' => $orders,
                'total_count' => $orders->count(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve orders', ['error' => $e->getMessage()], 500);
        }
    }
}
