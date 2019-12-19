<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{

    protected $table = 'leads';

    protected $fillable = [
        'deal_id', 'visitor_id', 'session_id', 'hit_id', 'hash_id',
        'name', 'phone', 'email', 'title', 'comment',
        'url', 'utm_medium', 'utm_source', 'utm_campaign', 'utm_term', 'utm_content',
    ];
}