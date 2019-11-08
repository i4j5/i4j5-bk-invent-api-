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

    /**
     * Проверка номера на дубли и приведение к единому формату
     *
     * @param string $phone
     * @param integer $enum
     * @return array 
     */
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

        return [
            'phone' => $phone,
            'enum' => $enum
        ];
    }
    
    
    /**
     * Поиск контакта
     *
     * @param string $phone 
     * @return integer
     */
    public function contactSearch($phone)
    {
        $res = [];
        
        $data = $this->amocrm->contact->apiList([
            'query' => $phone,
            'limit_rows' => 1,
            'type' => 'contact' 
        ]);

        foreach ( $data as $contact )
        {
            foreach ( $contact['custom_fields'] as $field )
            {
                if(isset($field['code']) && $field['code'] == 'PHONE') 
                {
                    foreach ( $field['values'] as $item )
                    {
                        if ($phone == $item['value']) 
                        {
                            $res = $contact;
                            break(3);
                        }
                    }  
                }
            }
        }

        return $res;
    }
}
