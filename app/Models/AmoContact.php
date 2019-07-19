<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmoContact extends Model
{
    protected $table = 'amo-contacts';

    protected $fillable = [
        'name', 
        'amo_id', 
    ];

    public function values()
    {
        return $this->hasMany('App\Models\AmoContactValue', 'contact_id');
    }
}
