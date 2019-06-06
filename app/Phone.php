<?php

namespace App;

use Dotzero\LaravelAmoCrm\Facades\AmoCrm;

class Phone
{
    private $amocrm;
    protected static $_instance; 

    public function __construct()
    {
        $this->amocrm = AmoCrm::getClient();

    }

    public static function getInstance() 
    {
        if (self::$_instance === null) {
            self::$_instance = new self;   
        }
 
        return self::$_instance;
        
    }

    public function fix($phone = '', $enum = null)
    {
        $phone = str_replace(['+', '(', ')', ' ', '-', '_', '*','–'], '', $phone);
        
        if(strlen($phone) >= 11) {
            if($phone[0] == 8) {
                $phone[0] = 7;	
            }
        }

        if(strlen($phone) == 10) {
            $phone = '7' . $phone;	
        }
        
        if ($enum == 214340) $enum = 214336;
        
        $double = $this->amocrm->contact->apiList([
            'query' => $phone,
            'limit_rows' => 1,
        ]);

        return [
            'phone' => $phone,
            'enum' => $enum,
            'double' => $double
        ];
    } 

    public function findDuplicates($phone = '')
    {}
}
