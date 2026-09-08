<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\http\Resources\Api\ReceiptResource;
class RecieptController extends Controller
{
    use ApiResponse;
    //
    public function index(Order $order): JsonResponse{
        $this->authorize('view', $order);

        $order->load(['items.meal.category', 'items.meal.subcategory', 'address', 'user']);

        return $this->success('Receipt retrieved successfully', new ReceiptResource($order), 200);
    }
}
