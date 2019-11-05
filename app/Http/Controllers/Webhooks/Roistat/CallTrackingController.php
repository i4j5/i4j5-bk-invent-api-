<?php

namespace App\Http\Controllers\Webhooks\Roistat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\SalesapAPI;

/**
 * WebHook
 * Roistat
 * Коллтрекинг
 */
class CallTrackingController extends Controller
{

    protected $crm;
    
    public function __construct() {
        $this->crm = SalesapAPI::getInstance();
    }

    public function handle(Request $request)
    {        
        $lead_name = 'Входящий звонок';
        
        $contact_phone = $request->input('caller');
        
        $utm = [
            'utm_medium' => $request->input('utm_medium'),
            'utm_source' => $request->input('utm_source'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_term' => $request->input('utm_term'),
            'utm_content' => $request->input('utm_content'),
        ];
        
        $url = $request->input('landing_page');
        $roistat = $request->input('visit_id');
        $referrer = $request->input('referrer');

        $responsibleID = null;
        
        //Возможно сделать задержку 
        //sleep(180);
           
        $response = $this->crm->searchConcat($contact_phone);
        
        if ($response) {
            $contact = $response[0];
            $responsible = $this->crm->curl->get($contact->relationships->responsible->links->related);
            if ($responsible->data) {
                $responsibleID = $responsible->data->id;
            }
        } else {
            $contact = $this->crm->addConcat($contact_phone, $contact_name);
        }
        
        $this->crm->addОrder($lead_name, $contact->id, $responsibleID, $url, '', $roistat, $utm);

        return 'ok';
    }

}
