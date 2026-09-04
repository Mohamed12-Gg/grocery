<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $bodyText;
    public $pdfBytes;

    /**
     * Create a new message instance.
     */
    public function __construct(string $bodyText = 'Attached invoice', ?string $pdfBytes = null)
    {
        $this->bodyText = $bodyText;
        $this->pdfBytes = $pdfBytes;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $mail = $this->subject('فاتورتك')->view('emails.plain', ['body' => $this->bodyText]);

        if ($this->pdfBytes) {
            $mail->attachData($this->pdfBytes, 'invoice.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
