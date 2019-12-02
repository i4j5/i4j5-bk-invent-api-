<?php

namespace App;

use \Curl\Curl;

class Bitrix24 
{ 
    protected static $_instance;
    public $url;
    public $request;
    
    public function __construct()
    {
        $this->url = env('BTRIX24_URL');
        $this->request = new Curl();
    }
    
    /**
     * Instance
     */
    public static function getInstance() 
    {
        if (self::$_instance === null) {
            self::$_instance = new self;   
        }
 
        return self::$_instance;
    }
    
}