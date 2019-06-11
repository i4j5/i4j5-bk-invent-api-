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
     * Детальный поиск дубликатов
     *
     * @param integer $id
     * @return boolean
     */
    public function  checkDuplicateInLead($id)
    {
        $lead_id = (int) $id;
        $double = false;

        $data = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ])[0];

        $tags = [];
        foreach ( $data['tags'] as $tag )
        {
            array_push($tags, $tag['name']);
        }

        $contact_id = (int) $data['main_contact_id'];
        $contact = $this->amocrm->contact;

        $phones = [];
        foreach ( $data['custom_fields'] as $field )
        {
            if ($field['code'] == 'PHONE') {

                foreach ( $field['values'] as $item )
                {
                    $phone = $item['value'];
                    $phones[] = $res['phone'];
                }  
            }
        }

        foreach ( $phones as $phone ) {

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

        if($double) {
            $lead = $this->amocrm->lead;
            array_push($tags, 'Дубль');
            $lead['tags'] = $tags;
            $lead->apiUpdate($lead_id, 'now');
        }

        return $double;
    }
}
