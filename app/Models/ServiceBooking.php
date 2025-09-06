<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceBooking extends Model
{
    protected $fillable = [
        'user_id',
        'service_booking_code',
        'amount',
        'service_ids',
        'total_price',
        'payment_method',
        'booking_date',
        'come_date',
        'status',
    ];

    protected $casts = [
        'amount'      => 'array',
        'service_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}