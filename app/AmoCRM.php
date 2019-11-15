<?php

namespace App;

use \Curl\Curl;

class AmoCRM 
{ 
    protected static $_instance;
    public $url;
    public $request;
    
    public function __construct()
    {
        $this->url = 'https://' . env('AMO_DOMAIN') . '.amocrm.ru/api/v2/';
        $this->request = new Curl();
        
//        $this->request->post('https://' . env('AMO_DOMAIN') . '.amocrm.ru/private/api/auth.php?type=json', [
//            'USER_LOGIN' => env('AMO_LOGIN'),
//            'USER_HASH' => env('AMO_HASH'),
//        ]);
        
//        $this->request->responseCookies;
//        $this->request->getResponseCookies();
//        $this->response->cookies->foo === 'bar';
        
        $this->request->setOpt(CURLOPT_RETURNTRANSFER, 'true');
        $this->request->setOpt(CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        $this->request->setOpt(CURLOPT_URL, 'https://' . env('AMO_DOMAIN') . '.amocrm.ru/private/api/auth.php?type=json');
        $this->request->setOpt(CURLOPT_POST, true);
        $this->request->setOpt(CURLOPT_POSTFIELDS, http_build_query([
            'USER_LOGIN' => env('AMO_LOGIN'),
            'USER_HASH' => env('AMO_HASH'),
        ]));
        $this->request->setOpt(CURLOPT_HEADER, false);
        $this->request->setOpt(CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); ///////////////
        $this->request->setOpt(CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); //////////////
        $this->request->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $this->request->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        $this->request->setOpt(); 
        $this->request->exec();
        
        
        $this->request->setHeader('Authorization', 'Bearer ' . env('AMO_HASH')); 

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