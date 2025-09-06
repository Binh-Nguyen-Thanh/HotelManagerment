<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'booking_code', 'room_type_id', 'rating', 'content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
