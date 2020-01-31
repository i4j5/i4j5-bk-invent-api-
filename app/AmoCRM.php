<?php

namespace App;

use \Curl\Curl;

class AmoCRM 
{
    public $request;

    public function __construct()
    {
        // $this->request = new Curl('https://' . env('AMO_DOMAIN') . '.amocrm.ru/api/v2/');

        // $this->request->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
        // $this->request->setOpt(CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        // $this->request->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
        // $this->request->setOpt(CURLOPT_HEADER, FALSE);
        // $this->request->setOpt(CURLOPT_COOKIEFILE, '');// dirname(__FILE__) . '/cookie.txt'
        // $this->request->setOpt(CURLOPT_COOKIEJAR, ''); // dirname(__FILE__) . '/cookie.txt'
        // $this->request->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        // $this->request->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        // $this->request->post();

        //https://github.com/amocrm/amocrm-oauth-client

    }
}
