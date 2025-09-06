<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'user_id',
        'room_id',
        'room_type_id',
        'guest_number',
        'extra_services',
        'booking_date_in',
        'booking_date_out',
        'check_in',
        'check_out',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'guest_number'     => 'array',    
        'extra_services'   => 'array',    
        'booking_date_in'  => 'date',     
        'booking_date_out' => 'date',     
        'check_in'         => 'datetime', 
        'check_out'        => 'datetime',
    ];

    // Người đặt (users.id)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Phòng cụ thể (rooms.id) - nếu có lưu room_id
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    // Loại phòng (room_types.id)
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    // Thanh toán theo booking_code (payments.booking_code -> bookings.booking_code)
    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_code', 'booking_code');
    }

    // Review theo booking_code (reviews.booking_code -> bookings.booking_code)
    public function reviews()
    {
        return $this->hasMany(Review::class, 'booking_code', 'booking_code');
    }

    /* =========================
     |         SCOPES
     |=========================*/

    public function scopeOfUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActiveStatuses($query)
    {
        return $query->whereIn('status', ['success', 'checked_in', 'checked_out', 'checked', 'cancel']);
    }
}