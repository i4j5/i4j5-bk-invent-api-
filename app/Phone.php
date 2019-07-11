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
    
    
    /**
     * Детальный поиск дубликатов в Контактах
     *
     * @param integer $id
     * @return boolean
     */
    public function  checkDuplicateInСontact($id)
    {
        $lead_id = (int) $id;
        $double = false;

        $data = $this->amocrm->contact->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
            'type' => 'all'
        ])[0];
       
        $contact_id = (int) $data['id'];
        $contact = $this->amocrm->contact;
        
        // Формируеи список номеров
        $phones = [];
        if(isset($data['custom_fields'])) 
        {
            foreach ( $data['custom_fields'] as $field )
            {
                if (isset($field['code']) && $field['code'] == 'PHONE') 
                {
                    foreach ( $field['values'] as $item )
                    {
                        $phones[] = $item['value'];
                    }  
                }
            }
        }

        // Поиск дублей
        foreach ( $phones as $phone ) 
        {
            $items = $this->amocrm->contact->apiList([
                'query' => $phone,
                'type' => 'contact',
            ]);
            
            foreach ( $items as $item )
            {
                
                foreach ( $item['custom_fields'] as $field )
                {
                    if(isset($field['code']) && $field['code'] == 'PHONE') {
                        foreach ( $field['values'] as $el )
                        {
                            if (in_array($el['value'], $phones)) 
                            {
                                $double = true;
                                break(4);
                            }
                        }  
                    }
                }
            }
           
        }

        return $double;
    }
}
