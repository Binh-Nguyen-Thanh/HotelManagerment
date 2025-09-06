<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'price',
        'amenities',
        'capacity',
        'image',
    ];

    public function getAmenityNames()
    {
        $ids = json_decode($this->amenities, true) ?? [];
        return Services::whereIn('id', $ids)->pluck('name');
    }

    public function getAmenityIds()
    {
        return json_decode($this->amenities, true) ?? [];
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'room_type_id');
    }
}
