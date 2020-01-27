<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
   
    protected $table = 'visits';

    protected $fillable = [
       'first_visit', 
       'google_client_id', 'metrika_client_id',
       'landing_page', 'referrer',
       'utm_sourse', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
       'trace'
    ];
}
