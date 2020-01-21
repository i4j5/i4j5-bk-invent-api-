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
            
            'name' => '',
            'phone' => '',
            'email' => '-',
            'google_client_id' => '',
            'metrika_client_id' => '',
            
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_content' => '',
            'utm_term' => '',
            
            'source' => 'OTHER',
            
            'landing_page' => '',
            'referrer' => '',
            'trace' => '',
           
            'comment' => '',
        ];
        
        $data = array_merge($default_data, $params);
        
        $contact_phone = $data['phone'];
        
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
            Имя: {$data['name']} <br>
            Телефон: $contact_phone <br>
            E-mail: {$data['email']} <br>
            Страница захвата: {$data['landing_page']} <br> 
            Ключевое слово: {$data['utm_term']} <br>";

        $arr = [
            'fields' => [
                'TRACE' => $data['trace'],
                'TITLE' => $data['title'],
                'UTM_CAMPAIGN' => $data['utm_campaign'],
                'UTM_CONTENT' => $data['utm_content'],
                'UTM_MEDIUM' => $data['utm_medium'],
                'UTM_SOURCE' => $data['utm_source'],
                'UTM_TERM' => $data['utm_term'],
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
            $arr['fields']['NAME'] = $data['name'];
            
            $arr['fields']['PHONE'] = [
                [
                    'VALUE' => $contact_phone,
                    'VALUE_TYPE' => 'MOBILE'
                ]
            ];
        }
        
        if (preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $data['email'])) {
            $arr['fields']['EMAIL'] = [
                [
                    'VALUE' => $data['email'],
                    'VALUE_TYPE' => 'MAILING'
                ]
            ];
        }
        
//        $arr['fields']['TRACE'] = '{"url":"https://bk-invent.ru/","ref":"https://www.yandex.ru/clck/jsredir?from=yandex.ru;suggest;browser&text=","device":{"isMobile":false},"tags":{"ts":1579501926,"list":{},"gclid":null},"client":{"gaId":"209629315.1579004207","yaId":"1564470991512487271"},"pages":{"list":[["https://bk-invent.ru/individualnoe-proektirovanie-domov-i-zdanij?sas",1579002149,"Индивидуальное проектирование домов и зд"],["https://bk-invent.ru/soglasovannyj-proekt-na-vodu-i-kanalizaciyu",1579004407,"Согласованный проект на воду и канализац"],["https://bk-invent.ru/gazifikatsiya",1579498827,"Газификация жилых и нежилых объектов про"],["https://bk-invent.ru/?utm_source=&b24_tracker_checking_origin=https%3A%2F%2Fbkinvent.bitrix24.ru",1579501926,"ГРУППА КОМПАНИЙ «БК ИНВЕНТ»"],["https://bk-invent.ru/",1579598267,"ГРУППА КОМПАНИЙ «БК ИНВЕНТ»"]]},"gid":null,"previous":{"list":[]}}';
        
        return $this->request->post('crm.lead.add.json', $arr);
        
        //return '....';
    }
    
}