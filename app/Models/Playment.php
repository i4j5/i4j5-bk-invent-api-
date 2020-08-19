<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playment extends Model
{
    protected $table = 'playments';

    protected $fillable = [
        'order_id', 'amount', 'status', 'description', 'payment_url', 'phone', 'email', 'fio', 'date', 'deal_id'
    ];
}
