<?php

namespace App\Http\Controllers\Amo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;

class UnsortedController extends Controller
{

    private $amo;

    public function __construct(AmoCrmManager $amocrm)
    {
        try {
            $this->amo = $amocrm;
    
        } catch (\Exception $e) {
            abort(400, $e->getMessage());
        }
    }

    public function add(Request $request)
    {
        $lead_name = $request->input('order');
        $contact_name = $request->input('name');
        $contact_phone = $request->input('phone');

        $contact_phone = str_replace(array('+', '(', ')', ' ', '-', '_', '*'), '', $contact_phone);

        if(strlen($contact_phone) >= 11) {
            if($contact_phone[0] == 8) {
                $contact_phone[0] = 7;	
            }
        }

        if(strlen($contact_phone) == 10) {
            $contact_phone = '7' . $contact_phone;	
        }
	
        $contact_email = $request->input('email');
        $utm_medium = $request->input('utm_medium');
        $utm_source = $request->input('utm_source');
        $utm_campaign = $request->input('utm_campaign');
        $utm_term = $request->input('utm_term');
        $utm_content = $request->input('utm_content');
        $url = $request->input('url');
        $utm = $request->input('utm');
        $roistat = $request->input('roistat');


        ///////////////


        $unsorted = $this->amo->unsorted;
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
        $lead = $this->amo->lead;
        $lead['name'] = $lead_name;
        // $lead['price'] = 3000;
        $lead['tags'] = ['LP'];
        
        // Системные
        $lead->addCustomField(232407, $utm_medium);
        $lead->addCustomField(232409, $utm_source);
        $lead->addCustomField(232411, '-');
        $lead->addCustomField(232413, $utm_campaign);
        $lead->addCustomField(232415, $utm_term);
        $lead->addCustomField(232417, $utm_content);
        $lead->addCustomField(232419, '-');
        $lead->addCustomField(232421, $url);
        $lead->addCustomField(233419, ''); //ya_id
        $lead->addCustomField(232423, $utm);
        $lead->addCustomField(226175, 487647);
        $lead->addCustomField(240623, $roistat);
      

        // Создание контакта
        $contact = $this->amo->contact;

        $query_contact = $this->amo->contact->apiList([
            'query' => $contact_phone,
            'limit_rows' => 1,
        ]);
        
        // Примечания, которые появятся в сделке если телефон имеется в базе
        if($query_contact) {
            $note = $this->amo->note;
            $note['element_type'] = \AmoCRM\Models\Note::TYPE_CONTACT;
            $note['note_type'] = \AmoCRM\Models\Note::COMMON;
            $note['text'] = "Номер $contact_phone присутствует в базе!!!";
            $lead['notes'] = $note;
        }

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

        //exit;
        // Добавление неразобранной заявки с типом FORMS
        $unsortedId = $unsorted->apiAddForms();
        print_r($unsortedId);
    }
}
