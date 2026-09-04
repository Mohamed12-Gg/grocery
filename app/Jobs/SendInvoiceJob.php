<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function handle()
    {
        $invoice = [
            'invoice_no' => 100,
            'customer'   => 'samir elsayed',
            'amount'     =>2000,
        ];

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));

        Mail::to($this->email)->send(
            new InvoiceMail(
                'Attached invoice',
                $pdf->output()
            )
        );

        Log::info('Invoice sent successfully to ' . $this->email);
    }

    public function failed($exception)
    {
        Log::error('Failed sending invoice', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}