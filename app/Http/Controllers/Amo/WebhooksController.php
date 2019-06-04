<?php

namespace App\Http\Controllers\Amo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;

class WebhooksController extends Controller
{

    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
    }

    private function fixPhone($contact_phone, $enum = 214336)
    {
        $contact_phone = str_replace(array('+', '(', ')', ' ', '-', '_', '*'), '', $contact_phone);
        
        if(strlen($contact_phone) >= 11) {
            if($contact_phone[0] == 8) {
                $contact_phone[0] = 7;	
            }
        }

        if(strlen($contact_phone) == 10) {
            $contact_phone = '7' . $contact_phone;	
        }

        if ($enum == 214340) $enum = 214336;

        return [$contact_phone, $enum];

    }

    public function rawLead(Request $request)
    {        
        $lead_id = $request->input('id') ? (int) $request->input('id') : (int) $request->input('leads')['status'][0]['id'];     
        //$lead_id = '16864475';

        $data = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ], '-100 DAYS')[0];

        $contact_id = $data['main_contact_id'];

        $data = $this->amocrm->contact->apiList([
            'id' => $contact_id,
            'limit_rows' => 1,
        ], '-100 DAYS')[0];
        
        //dd($data);

        $contact = $this->amocrm->contact;
        $phones = [];
        foreach ( $data['custom_fields'] as $field )
        {
            if ($field['code'] == 'PHONE') {

                foreach ( $field['values'] as $item )
                {
                    $phone = $item['value'];
                    $enum = $item['enum'];

                    $res = $this->fixPhone($phone, $enum);
                    $phones[] = $res;
                }

                
            }
        }

        $contact->addCustomField('95354', $phones);
        
        $contact->apiUpdate((int) $contact_id, 'now');

    }

}
