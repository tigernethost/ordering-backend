<?php

namespace App\Mail;

use App\Models\MultisysPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $multisysPayment;
    public $business;
    public $data;

    public function __construct($multisysPayment)
    {
        $this->multisysPayment = $multisysPayment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Success Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $multisysPayment = $this->multisysPayment;
        $paymentMethod = $multisysPayment->paymentMethod;
        $amount = $multisysPayment->amount;
        $imageUrl = asset('images/makimura.jpg');
        $trackOrderUrl = env('ORDER_CONFIRMATION_URL') . '/my-order/' . $multisysPayment->txnid;

        $data = [
            'multisysPayment' => $multisysPayment,
            'paymentMethod' => $paymentMethod,
            'amount' => $amount,
            'imageUrl' => $imageUrl,
            'trackOrderUrl' => $trackOrderUrl,
        ];
        

        return new Content(
            view: 'mail.payment_successful',
            with: $data
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
