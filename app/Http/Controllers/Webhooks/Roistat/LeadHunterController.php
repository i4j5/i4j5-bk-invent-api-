<?php

namespace App\Http\Controllers\Webhooks\Roistat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Phone;

/**
 * WebHook
 * Roistat
 * Ловец Лидов
 */
class LeadHunterController extends Controller
{

    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
    }

    public function handle(Request $request)
    {        

        $contact_name = $request->input('name');
        $contact_phone = $request->input('phone');

        $lead_name = "Пойманный лид: $contact_name, $contact_phone";

        $arrPhone = Phone::getInstance()->fix($contact_phone);

        $contact_phone = $arrPhone['phone'];

        $utm_medium = $request->input('utm_medium');
        $utm_source = $request->input('utm_source');
        $utm_campaign = $request->input('utm_campaign');
        $utm_term = $request->input('utm_term');
        $utm_content = $request->input('utm_content');
        $url = $request->input('landing_page');
        $utm = '';
        $roistat = $request->input('visit_id');
        $comment = '';
        $referrer = $request->input('referrer');

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
                ]
            ],
            'form_id' => 1,
            'form_type' => 1,
            'origin' => [
                'ip' => $_SERVER['REMOTE_ADDR']
            ],
            'date' => time(),
            'from' => 'Пойманный лид'
        ];

        // Сделка которая будет создана после одобрения заявки.
        $lead = $this->amocrm->lead;
        $lead['name'] = $lead_name;
        $lead['tags'] = ['Заявка с сайта'];
        
        // Системные
        $lead->addCustomField(232407, $utm_medium);
        $lead->addCustomField(232409, $utm_source);
        //$lead->addCustomField(232411, '');
        $lead->addCustomField(232413, $utm_campaign);
        $lead->addCustomField(232415, $utm_term);
        $lead->addCustomField(232417, $utm_content);
        $lead->addCustomField(232419, $referrer);
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
        // if($arrPhone['double']) {
        //     $note = $this->amocrm->note;
        //     $note['element_type'] = \AmoCRM\Models\Note::TYPE_CONTACT;
        //     $note['note_type'] = \AmoCRM\Models\Note::COMMON;
        //     $comment = "\n====================\n Возможно это дубль!!! \n====================\n". $comment;
        // }
		
		$note['text'] = $comment;
		$lead['notes'] = $note;

        // Заполнение контакта 
        $contact['name'] = $contact_name;
        $contact->addCustomField('95354', [
            [$contact_phone, 'MOB'],
        ]);
                
        // Присоединение контакт к неразобранному
        $unsorted->addDataContact($contact);

        // Присоединение сделки к неразобранному
        $unsorted->addDataLead($lead);

        // Добавление неразобранной заявки с типом FORMS
        $unsortedId = $unsorted->apiAddForms();
    }

}
