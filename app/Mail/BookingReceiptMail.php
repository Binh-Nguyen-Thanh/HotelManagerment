<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BookingReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $bookingCode,
        public float $amount,
        public string $method,
        public Carbon $paidAt,
        public ?string $barcodeBase64,

        // Chi tiết đặt phòng
        public $bookings,            // Illuminate\Support\Collection
        public $services,            // Illuminate\Support\Collection (keyBy id)
        public int $nights,
        public float $roomTotal,
        public float $serviceTotal,
        public float $grandTotal,
    ) {}

    public function build()
    {
        return $this->subject('Xác nhận đặt phòng - ' . $this->bookingCode)
            ->view('user.booking.pay_mail')
            ->with([
                'bookingCode'   => $this->bookingCode,
                'payment'       => (object)[
                    'payment_method' => $this->method,
                    'amount'         => $this->amount,
                    'paid_at'        => $this->paidAt,
                ],
                'barcodeBase64' => $this->barcodeBase64,
                'bookings'      => $this->bookings,
                'services'      => $this->services,
                'nights'        => $this->nights,
                'roomTotal'     => $this->roomTotal,
                'serviceTotal'  => $this->serviceTotal,
                'grandTotal'    => $this->grandTotal,
            ]);
    }
}