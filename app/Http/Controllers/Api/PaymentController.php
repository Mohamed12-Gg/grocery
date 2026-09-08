<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\Api\PaymentResource;
use App\Http\Resources\Api\ReceiptResource;
use App\Services\InvoiceService;

class PaymentController extends Controller
{
    use ApiResponse;
    /**
     * Get payment history for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request
            ->user()
            ->orders()
            ->where('status', '!=', 'cancelled')
            ->with(['items.meal.category', 'address'])
            ->latest()
            ->get();

        return $this->success('Payment history retrieved successfully', PaymentResource::collection($orders), 200);
    }

    /**
     * Get receipt/invoice for a specific order.
     */
    

    /**
     * Get invoice for a specific order (alias for receipt).
     */
    public function invoice(Order $order, InvoiceService $invoiceService): Response
    {
        $this->authorize('view', $order);

        $order->load(['items.meal.category', 'items.meal.subcategory', 'address', 'user']);
      

        return $invoiceService->download($order);
    }
}
