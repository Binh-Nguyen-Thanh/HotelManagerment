<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relationship with employees
    public function employees()
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    // Scope for active positions (example)
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessor for formatted name
    public function getFormattedNameAttribute()
    {
        return ucwords(strtolower($this->name));
    }
}