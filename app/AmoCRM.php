<?php

namespace App;

use \Curl\Curl;
use \Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Phone;

class AmoCRM 
{
    private $amocrm;
    protected static $_instance;

    public function __construct()
    {
        $this->amocrm = \Dotzero\LaravelAmoCrm\Facades\AmoCrm::getClient();
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

    public function searchConcat($phone)
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
                if (isset($field['code']) && $field['code'] == 'PHONE') {
                    foreach ( $field['values'] as $item )
                    {
                        if ($phone == $item['value']) {
                            $res = $contact;
                            break(3);
                        }
                    }  
                }
            }
        }

        return $res;
    }

    public function addLead($params)
    {
        $default_data = [
            'title' => 'LEAD',
            'name' => '',
            'phone' => '',
            'email' => '',
            'google_client_id' => '',
            'metrika_client_id' => '',
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_content' => '',
            'utm_term' => '',
            'landing_page' => '',
            'referrer' => '',
            'trace' => '',
            'tags'=> [],
            'comment' => '',
            'visit' => '',
        ];

        $data = array_merge($default_data, $params);

        // #PHONE
        $data['phone']= str_replace(['+', '(', ')', ' ', '-', '_', '*', '–'], '', $data['phone']);
        
        if (strlen($data['phone']) >= 11) {
            if ($data['phone'][0] == 8) {
                $data['phone'][0] = 7;
            }
        } else if (strlen($data['phone']) == 10) {
            $data['phone'] = '7' . $data['phone'];
        }

        // #КОНТАКТ
        $contact = $this->searchConcat($data['phone']);

        // #СДЕЛКА
        $lead = $this->amocrm->lead;
        $lead['name'] = $data['title'];
        $lead['tags'] = $data['tags'];

        $lead->addCustomField(75455, $data['utm_source']);
        $lead->addCustomField(75457, $data['utm_medium']);
        $lead->addCustomField(75461, $data['utm_campaign']);
        $lead->addCustomField(75459, $data['utm_content']);
        $lead->addCustomField(75453, $data['utm_term']);
        $lead->addCustomField(75467, $data['google_client_id']);
        $lead->addCustomField(75469, $data['metrika_client_id']);
        $lead->addCustomField(75451, $data['landing_page']);
        $lead->addCustomField(75465, $data['referrer']);

        $note = $this->amocrm->note;
        $note['element_type'] = \AmoCRM\Models\Note::TYPE_CONTACT;
        $note['note_type'] = \AmoCRM\Models\Note::COMMON;

        $data['comment'] = $data['comment'] . " \n
        ====================\n
        {$data['title']} \n
        ====================\n
        Имя: {$data['name']} \n
        Телефон: {$data['phone']} \n
        E-mail: {$data['email']} \n
        ====================\n
        Страница захвата: {$data['landing_page']} \n
        Ключевое слово: {$data['utm_term']} \n
        Реферальная ссылка: {$data['referrer']} \n
        ";

        $note['text'] = $data['comment'];

        if (!$contact) {
            $lead['notes'] = $note;

            $unsorted = $this->amocrm->unsorted;
            $unsorted['source'] = 'bk-invent.ru';
            $unsorted['source_uid'] = null;

            $unsorted['source_data'] = [
                'data' => [
                    'name' => [
                        'type' => 'text',
                        'element_type' => '1',
                        'name' => 'Имя',
                        'value' => $data['name'],
                    ],
                    'phone' => [
                        'type' => 'text',
                        'element_type' => '1',
                        'name' => 'Телефон',
                        'value' => $data['phone'],
                    ],
                    'email' => [
                        'type' => 'text',
                        'element_type' => '1',
                        'name' => 'E-mail',
                        'value' => $data['email'],
                    ]
                ],
                'form_id' => 1,
                'form_type' => 1,
                'origin' => [
                    'ip' => $_SERVER['REMOTE_ADDR']
                ],
                'date' => time(),
                'from' => 'Заявка с сайта'
            ];

            // Заполнение контакта 
            $contact = $this->amocrm->contact;
            $contact['name'] = $data['name'];
            $contact->addCustomField('75087', [
                [$data['phone'], 'MOB'],
            ]);
            $contact->addCustomField('75089', [
                [$data['email'], 'WORK'],
            ]);

            // Присоединение контакт к неразобранному
            $unsorted->addDataContact($contact);

            // Присоединение сделки к неразобранному
            $unsorted->addDataLead($lead);

            // Добавление неразобранной заявки с типом FORMS
            $unsortedId = $unsorted->apiAddForms();
        } else {
            if(isset($contact['responsible_user_id'])) {
                $lead['responsible_user_id'] = $contact['responsible_user_id'];
            }

            $lead_id = $lead->apiAdd();

            //Добавить коментарий
            $note['element_id'] = $contact['id'];
            $note->apiAdd();

            // Добавить задачу
            $task = $this->amocrm->task;
            $task['element_id'] = $lead_id;
            $task['element_type'] = 2;
            $task['task_type'] = 1;
            $task['text'] = "@A Связаться с клиентом.";
            $task['responsible_user_id'] = $contact['responsible_user_id'];
            $task['complete_till'] = '+20 minutes';
            $task->apiAdd();

            $link = $this->amocrm->links;
            $link['from'] = 'leads';
            $link['from_id'] = $lead_id;
            $link['to'] = 'contacts';
            $link['to_id'] = $contact['id'];
            $link->apiLink();
        }

        //return $lead_id;

    }
}
