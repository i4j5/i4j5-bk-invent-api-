<?php

namespace App\Http\Controllers\Webhooks\Amo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Phone;

/**
 * WebHook
 * amoCRM
 * При переходе на этап НЕОБРАБОТАННЫЙ ЛИД
 */
class RawLeadController extends Controller
{

    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
    }

    public function handle(Request $request)
    {        
        $lead_id = (int) $request->input('leads')['status'][0]['id'];     

        $data = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ])[0];
        

        $contact_id = $data['main_contact_id'];

        $data = $this->amocrm->contact->apiList([
            'id' => $contact_id,
            'limit_rows' => 1,
        ])[0];

        $contact = $this->amocrm->contact;

        $phones = [];
        foreach ( $data['custom_fields'] as $field )
        {
            if ($field['code'] == 'PHONE') {

                foreach ( $field['values'] as $item )
                {
                    $phone = $item['value'];
                    $enum = $item['enum'];

                    $res = Phone::getInstance()->fix($phone, $enum);
                    $phones[] = [$res['phone'], $res['enum']];
                }  
            }
        }

       $contact->addCustomField('95354', $phones);
       $contact->apiUpdate((int) $contact_id, 'now');
    }

}
