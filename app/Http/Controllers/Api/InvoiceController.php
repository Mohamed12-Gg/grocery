<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use Illuminate\Http\Response;
class InvoiceController extends Controller
{
    //
    public function index(Order $order, InvoiceService $invoiceService): Response
    {
        $this->authorize('view', $order);

        $order->load(['items.meal.category', 'items.meal.subcategory', 'address', 'user']);
      

        return $invoiceService->download($order);
    }
}
