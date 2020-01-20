<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Number extends Model
{
    protected $table = 'numbers';

    protected $fillable = [
        'number', 
        'type', 
        'visit_id',
        'source',
        'reservation_at',
    ];
}
