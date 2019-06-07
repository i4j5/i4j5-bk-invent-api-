<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Phone;

class AmoController extends Controller
{

    private $amocrm;
    private $phone;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
        $this->phone = Phone::getInstance();
    }

    /**
     * Создание заявки с сайта
     * POST
     * @param Request $request
     * @return void
     */
    public function createLeadFromForm(Request $request)
    {
        $lead_name = $request->input('order');
        $contact_name = $request->input('name');
        $contact_phone = $request->input('phone');

        $arrPhone = $this->phone->fix($contact_phone);

        $contact_phone = $arrPhone['phone'];
	
        $contact_email = $request->input('email');
        $utm_medium = $request->input('utm_medium');
        $utm_source = $request->input('utm_source');
        $utm_campaign = $request->input('utm_campaign');
        $utm_term = $request->input('utm_term');
        $utm_content = $request->input('utm_content');
        $url = $request->input('url');
        $utm = $request->input('utm');
        $roistat = $request->input('roistat');
		$comment = $request->input('comment');

        ///////////////

        $unsorted = $this->amocrm->unsorted;
        $unsorted['source'] = 'bk-invent.ru';
        $unsorted['source_uid'] = null;

        //Данные заявки (зависят от категории)
        $unsorted['source_data'] = [
            'data' => [
                'name' => [
                    'type' => 'text',
                    'element_type' => '1',
                    'name' => 'Имя',
                    'value' => $contact_name,
                ],
                'phone' => [
                    'type' => 'text',
                    'element_type' => '1',
                    'name' => 'Телефон',
                    'value' => $contact_phone,
                ],
                'email' => [
                    'type' => 'text',
                    'element_type' => '1',
                    'name' => 'E-mail',
                    'value' => $contact_email,
                ],
            ],
            'form_id' => 1,
            'form_type' => 1,
            'origin' => [
                'ip' => $_SERVER['REMOTE_ADDR']
            ],
            'date' => time(),
            'from' => 'Заявка с сайта'
        ];

        // Сделка которая будет создана после одобрения заявки.
        $lead = $this->amocrm->lead;
        $lead['name'] = $lead_name;
        $lead['tags'] = ['Заявка с сайта'];
        
        // Системные
        $lead->addCustomField(232407, $utm_medium);
        $lead->addCustomField(232409, $utm_source);
        $lead->addCustomField(232411, '-');
        $lead->addCustomField(232413, $utm_campaign);
        $lead->addCustomField(232415, $utm_term);
        $lead->addCustomField(232417, $utm_content);
        $lead->addCustomField(232419, '-');
        $lead->addCustomField(232421, $url);
        $lead->addCustomField(232423, $utm);
        $lead->addCustomField(226175, 487647);
        $lead->addCustomField(240623, $roistat);
      
        // Создание контакта
        $contact = $this->amocrm->contact;
        
		$note = $this->amocrm->note;
		$note['element_type'] = \AmoCRM\Models\Note::TYPE_CONTACT;
		$note['note_type'] = \AmoCRM\Models\Note::COMMON;
		
		$comment = $comment . " \n 
		Страница захвата: $url \n
		Ключевое слово: $utm_term \n
		Промокод: $roistat \n
		";
		
        // Примечания, которые появятся в сделке если телефон имеется в базе
        if($arrPhone['double']) {
            $note = $this->amocrm->note;
            $note['element_type'] = \AmoCRM\Models\Note::TYPE_CONTACT;
            $note['note_type'] = \AmoCRM\Models\Note::COMMON;
            $comment = "\n====================\n Возможно это дубль!!! \n====================\n". $comment;
        }
		
		$note['text'] = $comment;
		$lead['notes'] = $note;

        // Заполнение контакта 
        $contact['name'] = $contact_name;
        $contact->addCustomField('95354', [
            [$contact_phone, 'MOB'],
        ]);
        $contact->addCustomField('95356', [
            [$contact_email, 'PRIV'],
        ]);
                
        // Присоединение контакт к неразобранному
        $unsorted->addDataContact($contact);

        // Присоединение сделки к неразобранному
        $unsorted->addDataLead($lead);

        // Добавление неразобранной заявки с типом FORMS
        $unsortedId = $unsorted->apiAddForms();
        echo 'ok';
    }

    /**
     * Исправление ошибок в контактах
     * GET
     * @param Request $request
     * @return void
     */
    public function fixAllContacts(Request $request)
    {   
        set_time_limit(0);

        $run = true;
        for($limit_offset = 0; $run; $limit_offset++) 
        {

            $data = $this->amocrm->contact->apiList([
                'limit_rows' => 500,
                'limit_offset' => $limit_offset * 500,
                'type' => 'all'
            ]);
			
            foreach ( $data as $contact )
            {
                $phones = [];
                $i++; 

                if(isset($contact['custom_fields'])) 
                {
                    
                    foreach ( $contact['custom_fields'] as $field )
                    {
                        if (isset($field['code']) && $field['code'] == 'PHONE') {
                            
                            foreach ( $field['values'] as $item )
                            {
                                $phone = $item['value'];
                                $enum = $item['enum'];

                                
                                if ($phone)
                                {
                                    $res = $this->fixPhone($phone, $enum);
                                    $phones[] = $res;
                                }
                                
                            }  
                        }
                    }
                }

                                
                if(count($phones) && isset($contact['id']) && $contact['id'])
                {
                    if($contact["type"] == "company")
                    {
                        $updateContact = $this->amocrm->company;
                    } else {
                        $updateContact = $this->amocrm->contact;
                    }
                    $updateContact->addCustomField('95354', $phones);
                    $updateContact->apiUpdate((int) $contact['id'], 'now');
                        
                }
           
            }

            if (count($data) < 500) $run = false;
        }

        echo 'ok';
    }
}
