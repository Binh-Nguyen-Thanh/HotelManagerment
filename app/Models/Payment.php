<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'booking_code', 'amount', 'payment_method', 'status', 'transaction_id', 'paid_at'
    ];

    public function booking() {
        return $this->belongsTo(Booking::class, 'booking_code', 'booking_code');
    }
}
