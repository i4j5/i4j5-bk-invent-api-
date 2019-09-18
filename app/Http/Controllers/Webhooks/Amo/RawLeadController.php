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
        $lead_id = isset($request->input('leads')['add'][0]['id']) ? (int) $request->input('leads')['add'][0]['id'] : (int) $request->input('leads')['status'][0]['id'];

        $data = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ])[0];

        $tags = [];
        foreach ( $data['tags'] as $tag )
        {
            array_push($tags, $tag['name']);
        }
        
        $contact_id = $data['main_contact_id'];

        $data = $this->amocrm->contact->apiList([
            'id' => $contact_id,
            'limit_rows' => 1,
        ])[0];

        $contact = $this->amocrm->contact;

        $phones = [];
        $dataPhones = [];
        foreach ( $data['custom_fields'] as $field )
        {
            if (isset($field['code']) && $field['code'] == 'PHONE') {

                foreach ( $field['values'] as $item )
                {
                    $phone = $item['value'];
                    $enum = $item['enum'];

                    $res = Phone::getInstance()->fix($phone, $enum);
                    $dataPhones[] = [$res['phone'], $res['enum']];
                    $phones[] = $res['phone'];
                }  
            }
        }

        $double = false;

        foreach ( $phones as $phone ) {

            $items = $this->amocrm->contact->apiList([
                'query' => $phone,
                'type' => 'contact',
            ]);

            //if($double) return;
            
            foreach ( $items as $item )
            {
                if($item['id'] == $contact_id) break;
                
                foreach ( $item['custom_fields'] as $field )
                {
                    if(isset($field['code']) && $field['code'] == 'PHONE') {
                        foreach ( $field['values'] as $el )
                        {
                            if (in_array($el['value'], $phones)) 
                            {
                                $double = true;
                                break(4);
                            }
                        }  
                    }
                }
            }
           
        }

        if($double) {
            // Добавить тек к сделке 
            $lead = $this->amocrm->lead;
            array_push($tags, 'Дубль');
            $lead['tags'] = $tags;
            $lead->apiUpdate((int) $lead_id, 'now');
        }


       $contact->addCustomField('95354', $dataPhones);
       $contact->apiUpdate((int) $contact_id, 'now');
       
       return 'ok';
    }

}
