<?php
namespace App\Services;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceService
{
     public function download(Order $order): Response
    {
        $pdf = Pdf::loadView('invoices.show', [
            'order' => $order,
        ]);

        return $pdf->download(
            "invoice-{$order->id}.pdf"
        );
    }
    
}