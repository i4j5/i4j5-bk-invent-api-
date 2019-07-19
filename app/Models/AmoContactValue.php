<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmoContactValue extends Model
{
    protected $table = 'amo-contact-values';
    
    protected $fillable = [
        'value', 
        'type', 
        'contact_id', 
    ];

    public function contact()
    {
        return $this->belongsTo('App\Models\AmoContact', 'concact_id');
    }
}
