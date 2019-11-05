<?php

namespace App;

use \Curl\Curl;

class SalesapAPI 
{ 
    protected static $_instance;
    protected $url;
    public $curl;
    
    public function __construct()
    {
        $this->url = 'https://app.salesap.ru/api/v1/';
        $this->curl = new Curl();
        $this->curl->setHeader('Authorization', 'Bearer ' . env('SALESAP_KEY'));
        $this->curl->setHeader('Content-Type', 'application/vnd.api+json');
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
     * Добавление контакты
     * @param scting $phone
     * @param scting $name
     * @param scting $email
     * @return array|boolean
     */
    public function addConcat($phone='', $name=null, $email='', $responsibleID=0)
    {
        $url = $this->url . 'contacts';
        
        $responsibleID = (int)$responsibleID;
        
        $phone = str_replace(['+', '(', ')', ' ', '-', '_', '*','–'], '', $phone);
        
        if(strlen($phone) >= 11) {
            if($phone[0] == 8) {
                $phone[0] = 7;	
            }
        }
        
        if( is_null($email) ) $email = ''; 

        if(strlen($phone) == 10) {
            $phone = '7' . $phone;	
        }
        
        $phone = '+' . $phone;
        
        if ( is_null($name) ) $name = $phone;
       
        $data = [
            'data' => [
                'type' => 'contacts',
                'attributes' => [
                    'first-name' => $name,
                    'last-name' => '',
                    'email' => $email,
                    'general-phone' => $phone,
                    'customs' => [
                        'custom-48582' => 'private',
                    ]
                ]
            ]
        ]; 
        
        if($responsibleID)
        {
            $data['data']['relationships']['responsible'] = [
                'data' => [
                    'type' => 'users',
                    'id' => $responsibleID
                ]
            ];
        }
        
        $this->curl->post($url, $data);
        
        $response = $this->curl->response;
        
        if(!isset($response->error))
        {
            return $response->data;
        }
        
        return false;
    }
    
    /**
     * Получить контакт
     * @param int $id
     * @return array|boolean
     */
    public function getConcat($id=null)
    {
        if ($id === null) return false;
        
        $url = $this->url . 'contacts/' . $id;
        
        $this->curl->get($url);
        
        $response = $this->curl->response;
        
        if(!isset($response->error))
        {
            return $response->data;
        }
        
        return false;
    }
    
    public function editConcat($id = null, $attributes = []) 
    {
        if ($id === null) return false;

        $url = $this->url . 'contacts/' . $id;

        $data = [
            'data' => [
                'type' => 'contacts',
                'id' => $id,
                'attributes' => $attributes
            ]
        ];

        $this->curl->patch($url, $data);

        $response = $this->curl->response;

        if (!isset($response->error)) {
            return $response;
        }

        return false;
    }

    /**
     * Поиск контакта
     * @param scting $phone
     * @return array|boolean
     */
    public function searchConcat($phone=null)
    {
        if ($phone === null) return false;
        
        $phone = str_replace(['+', '(', ')', ' ', '-', '_', '*','–'], '', $phone);
        $phone = '%2B' . $phone;
        
        $url = $this->url . 'contacts?filter[any-phone]=' . $phone;
        
        $this->curl->get($url);
        
        $response = $this->curl->response;
        
        if(!isset($response->error))
        {
            return $response->data;
        }
        
        return false;
    }
    
    public function addОrder($name='', $contactID=0, $responsibleID=0, $landing_page='', $comment='', $roistat='', $utm=[])
    {
        $url = $this->url . 'orders';
        
        $contactID = (int) $contactID;
        $responsibleID = (int)$responsibleID;
        
        if( is_null($roistat) ) $roistat = ''; 
             
        $data = [
            'data' => [
                'type' => 'orders',
                'attributes' => [
                    'name' => $name,
                    'description' => $comment,
                    'customs' => [
                        'custom-48449' => isset($utm['utm_medium']) && !is_null($utm['utm_medium']) ? $utm['utm_medium'] : '',
                        'custom-48450' => isset($utm['utm_source']) && !is_null($utm['utm_source']) ? $utm['utm_source'] : '',
                        'custom-48451' => isset($utm['utm_campaign']) && !is_null($utm['utm_campaign']) ? $utm['utm_campaign'] : '',
                        'custom-48452' => isset($utm['utm_term']) && !is_null($utm['utm_term']) ? $utm['utm_term'] : '',
                        'custom-48453' => isset($utm['utm_content']) && !is_null($utm['utm_content']) ? $utm['utm_content'] : '',
                        'custom-48455' => $landing_page,
                        'custom-49132' => $roistat,
                    ]
                ],
                'relationships' => [
                    'source' => [
                       'data' => [
                           'type' => 'sources',
                           'id' => 1
                       ]
                   ],
                ]
            ]
        ]; 
                
        if($contactID)
        {
             $data['data']['relationships']['contacts'] = [
                'data' => [
                    [
                        'type' => 'contacts',
                        'id' => $contactID
                    ]
                ]
            ];
             
            // Поставить задачу
        }
        
        if($responsibleID)
        {
            $data['data']['relationships']['responsible'] = [
                'data' => [
                    'type' => 'users',
                    'id' => $responsibleID
                ]
            ];
        }
        
        $this->curl->post($url, $data);
        
        $response = $this->curl->response;
        
        
        if(!isset($response->error))
        {
            return $response;
        }
        
        return false;
    }
    
    /**
     * Редактирование сделки
     * @param int $id
     * @return array|boolean
     */
    public function editDeal($id=null, $attributes=[])
    {
        if ($id === null) return false;
        
        $url = $this->url . 'deals/' . $id;
        
        $data = [
            'data' => [
                'type' => 'deals',
                'id' => $id,
                'attributes' => $attributes
            ]
        ];
             
        $this->curl->patch($url, $data);

        $response = $this->curl->response;

        if (!isset($response->error)) {
            return $response;
        }

        return false;
    }
    
    public function sipuni($phone = '', $redirection = false)
    {
        $data = [ 
            'name' => $phone,
            'choice' => '0',
            'number' => '',
            'timeout' => '0',
        ];
        
        $contacts = $this->searchConcat($phone);
        
        if ($contacts && !$redirection) {
            
            foreach ($contacts as $contact) 
            {
                if ($contact->attributes->{'first-name'} != '') {
                    $data['name'] = 
                            $contact->attributes->{'last-name'}
                            . ' ' .
                            $contact->attributes->{'first-name'}
                            . ' ' .
                            $contact->attributes->{'middle-name'};
                    break;
                }
            }
            
        } elseif ($contacts) {
            
            $number = null;
            
            foreach ($contacts as $contact) 
            {
                if ($contact->attributes->{'first-name'} != '') {
                    $data['name'] = 
                            $contact->attributes->{'last-name'}
                            . ' ' .
                            $contact->attributes->{'first-name'}
                            . ' ' .
                            $contact->attributes->{'middle-name'};
                }
                
                if ( isset($contact->attributes->customs->{'custom-49127'}[0]) ) {
                    $number = $contact->attributes->customs->{'custom-49127'}[0];
                    break;
                } else {
                    $response = $this->curl->get($contact->relationships->responsible->links->related);

                    if ($response->data) {
                        $responsible = $this->curl->get($this->url . 'telephony-phones?filter[user]=' . $response->data->id);

                        if (isset($responsible->data[0])) {
                            $number = $responsible->data[0]->attributes->number;
                        }
                        break;
                    }
                } 
            }
            
            if ($number) {
                $data['number'] = $number;
                $data['timeout'] = '8';
                $data['choice'] = '1';
            }
            
        }
        
        return $data;
    }
    
}