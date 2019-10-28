<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SalesapAPI;

class SalesapController extends Controller
{
    
    protected $crm;
    
    public function __construct() {
        $this->crm = SalesapAPI::getInstance();
    }
    
    /**
     * Создание заявки с сайта
     * POST
     * @param Request $request
     * @return string
     */
    public function createLeadFromForm(Request $request) {
        $lead_name = 'Заявка с сайта. ' . $request->input('order');
        
        $contact_phone = $request->input('phone');
        $contact_name = $request->input('name') ? $request->input('name') : $contact_phone;
        $contact_email = $request->input('email');
        
        $utm = [
            'utm_medium' => $request->input('utm_medium'),
            'utm_source' => $request->input('utm_source'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_term' => $request->input('utm_term'),
            'utm_content' => $request->input('utm_content'),
        ];
        
        $url = $request->input('url');
        
        //$utm = $request->input('utm');
        
        $roistat = $request->input('roistat');
        $comment = $request->input('comment');
        
        $comment = 
                    "Имя: $contact_name |
                    Телефон: $contact_phone |
                    E-mail: $contact_email | "
                    . $comment;
     
        $responsibleID = null;
           
        $response = $this->crm->searchConcat($contact_phone);
        
        if ($response) 
        {
            $contact = $response[0];
            $responsible = $this->crm->curl->get($contact->relationships->responsible->links->related);
            if ($responsible->data)
            {
                $responsibleID = $responsible->data->id;
            }
        } else {
            $contact = $this->crm->addConcat($contact_phone, $contact_name, $contact_email);
        }
        
        $this->crm->addОrder($lead_name, $contact->id, $responsibleID, $url, $comment, $roistat, $utm);
        //return $this->crm->addОrder($lead_name, 0, 0, $url, $comment, $roistat, $utm);

        return 'ok';
    }
    
    /**
     * Обработка входящих звонков
     * POST
     * @param Request $request
     * @return string
     */
    public function incomingСall(Request $request) 
    {
        $data = $request->input('data');
        
        if ($data) $json = json_decode($data, true);
        
        if ($json['direction'] == 'incoming')
        {
            $roistat = $json['roistat_visit'];
            
            
            // Получить данные из ройстата!!!
            
            $contact_phone = $json['src_phone_number'];
            
            $response = $this->crm->searchConcat($contact_phone);

            $contact = null;
            $responsibleID = null;
            
            if ($response && (int)$roistat) 
            {
                $contact = $response[0];
                $responsible = $this->crm->curl->get($contact->relationships->responsible->links->related);
                if ($responsible->data)
                {
                    $responsibleID = $responsible->data->id;
                }
            } else {
                $contact = $this->crm->addConcat($contact_phone);
                
                //Проверка статуса звонка 
                
                    // Узнать кто ответил.
                    // Поставить отвественного
            }
            
            if ($contact)
            {
                $comment = '';

                $this->crm->addОrder(
                    'Звонок от ' .$contact_phone, 
                    $contact->id, 
                    $responsibleID, 
                    '',
                    $comment, 
                    $roistat
                );
            }
        }

        return 'ok';
    }

}
