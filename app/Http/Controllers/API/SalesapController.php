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
     * @return void
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
        
        if ($response) {
            $contact = $response[0];
            $responsible = $this->crm->curl->get($contact->relationships->responsible->links->related);
            if ($responsible->data) {
                $responsibleID = $responsible->data->id;
            }
        } else {
            $contact = $this->crm->addConcat($contact_phone, $contact_name, $contact_email);
        }
        
        $this->crm->addОrder($lead_name, $contact->id, $responsibleID, $url, $comment, $roistat, $utm);

        return 'ok';
    }

}
