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
        $lead_name = $request->input('order');
        
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
        
        if ($data['direction'] == 'incoming')
        {
            $roistat = $data['custom_49119'];
            // Получить данные из роймтат 
            
            $contact_phone = $data['src_phone_number'];
            
            $response = $this->crm->searchConcat($contact_phone);

            $contact = null;
            $responsibleID = null;
            
            if ($response) {
//                $contact = $response[0];
//                $responsible = $this->crm->curl->get($contact->relationships->responsible->links->related);
//                if ($responsible->data)
//                {
//                    $responsibleID = $responsible->data->id;
//                }
            } else {
                //Отвечено - 123164
                //Неотвечено - 123165    
                if ($data['status_id'] == 123164) 
                {       
                    $dst = $this->crm->curl->get('https://app.salesap.ru/api/v1/telephony-calls/' . $data['id'] . '/dst-phone');
                   
                    if ($dst->data) {
                        $user = $this->crm->curl->get($dst->data->relationships->user->links->related);
                        if ($user) $responsibleID = $user->data->id;
                    }
                }
                
                $contact = $this->crm->addConcat($contact_phone, $contact_phone, '', $responsibleID);
            }
              
            if ($contact) {
               $this->crm->addОrder("Входящий звонок - $roistat", $contact->id, $responsibleID, '', '', $roistat);
            }
        } elseif ($data['direction'] == 'outgoing') {
            
            $contact_phone = $data['dst_phone_number'];
            
            $response = $this->crm->searchConcat($contact_phone);
            
            if (!$response) {
                $src = $this->crm->curl->get('https://app.salesap.ru/api/v1/telephony-calls/' . $data['id'] . '/src-phone');
                
                $additional = null;
                $responsibleID = null;
                
                if ($src->data) {
                    $user = $this->crm->curl->get($src->data->relationships->user->links->related);
                    if ($user)
                        $responsibleID = $user->data->id;
                } else {
                   $additional = $data['src_phone_number'];
                }
                
                $contact = $this->crm->addConcat($contact_phone, $contact_phone, '', $responsibleID);
                
                if ($additional) {
                    $this->crm->editConcat($contact->id, [
                        'customs' => [
                            'custom-49127' => [$additional]
                        ]
                    ]);
                }
            }
            
        }

        return 'ok';
    }

}
