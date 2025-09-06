<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ServiceBookingReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection $items  
     */
    public function __construct(
        public string $serviceBookingCode,
        public float $amount,
        public string $method,
        public Carbon $paidAt,
        public ?string $barcodeBase64,
        public Collection $items,
        public ?Carbon $bookingDate = null,
    ) {}

    public function build()
    {
        return $this->subject('Xác nhận đặt dịch vụ - ' . $this->serviceBookingCode)
            ->view('user.services.pay_mail')
            ->with([
                'code'         => $this->serviceBookingCode,
                'amount'       => $this->amount,
                'method'       => $this->method,
                'paidAt'       => $this->paidAt,
                'barcodeBase64'=> $this->barcodeBase64,
                'items'        => $this->items,
                'grandTotal'   => $this->amount,
                'bookingDate'  => $this->bookingDate,
            ]);
    }
}
