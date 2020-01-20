<?php

namespace App;

use \Curl\Curl;

class Bitrix24 
{ 
    protected static $_instance;
    public $request;
    
    public function __construct()
    {
        $this->request = new Curl(env('BTRIX24_URL'));
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
    
    
    public function addConcat($phone = '', $name = null, $email = '', $responsibleID = 0)
    {
        
    }
    
    public function getConcat($id = null)
    {
        
    }
    
    public function editConcat($id = null, $attributes = []) 
    {
        
    }
    
    public function searchConcat($phone = null)
    {
        return $this->request->post('crm.contact.list.json', [
            'filter' => [
                'PHONE' => $phone
            ],
            'select' => ['ID']
        ])->result;
    }
    
    public function addLead($params)
    {
        $default_data = [
            'title' => 'LEAD',
            
            'contact' => [
                'name' => '',
                'phone' => '',
                'email' => '',
                
                'google_client_id' => '',
                'metrika_client_id' => '',
            ],
            
            'utm' => [
                'utm_source' => '',
                'utm_medium' => '',
                'utm_campaign' => '',
                'utm_content' => '',
                'utm_term' => '',
            ],
            
            'source' => 'OTHER',
            
            'landing_page' => '',
            'referrer' => '',
            'trace' => '',
           
            'comment' => '',
        ];
        
        $data = array_merge($default_data, $params);
        
        $contact_phone = $data['contact']['phone'];
        
        $contact_phone = str_replace(['+', '(', ')', ' ', '-', '_', '*', '–'], '', $contact_phone);
        
        if (strlen($contact_phone) >= 11) {
            if ($contact_phone[0] == 8) {
                $contact_phone[0] = 7;
            }
        }

        if (strlen($contact_phone) == 10) {
            $contact_phone = '7' . $contact_phone;
        }
        
        $concact_id = null;
        
        if (strlen($contact_phone) >= '5') {
            
            $res = $this->searchConcat();
 
            if ($res) $concact_id = $res[0]->ID;
        }
        
        $comment = 
            "{$data['comment']} <br>
            <b> {$data['title']} </b> <br>
            Имя: {$data['contact']['name']} <br>
            Телефон: $contact_phone <br>
            E-mail: {$data['contact']['email']} <br>
            Страница захвата: {$data['landing_page']} <br> 
            Ключевое слово: {$data['utm']['utm_term']} <br>";


        $arr = [
            'fields' => [
                'TRACE' => $data['trace'],
                'TITLE' => $data['title'],
                'UTM_CAMPAIGN' => $data['utm']['utm_campaign'],
                'UTM_CONTENT' => $data['utm']['utm_content'],
                'UTM_MEDIUM' => $data['utm']['utm_medium'],
                'UTM_SOURCE' => $data['utm']['utm_source'],
                'UTM_TERM' => $data['utm']['utm_term'],
                'COMMENTS' => $comment,
                'SOURCE_ID' => $data['source'],
                'ASSIGNED_BY_ID' => 9
            ],
            'params' => [
                'REGISTER_SONET_EVENT' => 'Y'
            ]
        ];

        if ($concact_id) {
            $arr['fields']['CONTACT_ID'] = $concact_id;
        } else {
            $arr['fields']['NAME'] = $data['contact']['name'];
            
            $arr['fields']['PHONE'] = [
                [
                    'VALUE' => $contact_phone,
                    'VALUE_TYPE' => 'MOBILE'
                ]
            ];
        }
        
        if (preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $data['contact']['email'])) {
            $arr['fields']['EMAIL'] = [
                [
                    'VALUE' => $data['contact']['email'],
                    'VALUE_TYPE' => 'MAILING'
                ]
            ];
        }
        
        $this->curl->post('crm.lead.add.json', $data);
        
        return '....';
    }
    
}