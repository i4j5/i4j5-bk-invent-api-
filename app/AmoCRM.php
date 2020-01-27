<?php

namespace App;

use \Curl\Curl;

class AmoCRM 
{
    public $request;

    public function __construct()
    {
        $this->request = new Curl('https://' . env('AMO_DOMAIN') . '.amocrm.ru/api/v2/');
    }
}
