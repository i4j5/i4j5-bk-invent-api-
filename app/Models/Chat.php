<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    
    protected $table = 'chats';

    protected $fillable = [
        'amo_chat_id',
        'phone',
        'name',
    ];
}
