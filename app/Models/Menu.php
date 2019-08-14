<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';

    protected $fillable = [
        'parent_id', 
        'title', 
        'link',
        'position',
        'icon',
        'permission'
    ];
}
