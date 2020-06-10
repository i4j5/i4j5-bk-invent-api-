<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{

    protected $table = 'messages';

    protected $fillable = [
        'type',
        'status',
        'text',
        'media',
        'thumbnail',
        'file_name',
        'file_size',
        'timestamp',
        'amo_message_id',
        'whatsapp_message_id',
    ];
}
