<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Booking;

class Customer extends Model {
    use HasFactory;

    protected $table = 'users'; // Trỏ đến bảng users vì customer được lưu ở đây

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'P_ID', 'address', 'role'
    ];

    // Chỉ lấy những user có role là 'customer'
    protected static function boot() {
        parent::boot();
        static::addGlobalScope('customer', function ($query) {
            $query->where('role', 'customer');
        });
    }

    // Một khách hàng có thể có nhiều booking
    public function bookings() {
        return $this->hasMany(Booking::class, 'user_id');
    }
}
