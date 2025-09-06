<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'name',
        'code',
        'output_type',
        'date_count',
        'description',
        'sql_code',
    ];
}