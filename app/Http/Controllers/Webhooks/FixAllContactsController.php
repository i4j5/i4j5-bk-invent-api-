<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Phone;

/**
 * WebHook
 * amoCRM
 * Исправление ошибок в контактах
 */
class FixAllContactsController extends Controller
{

    private $amocrm;
    private $phone;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
        $this->phone = Phone::getInstance();
    }

    public function handle(Request $request)
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
                                    $res = $this->phone->fix($phone, $enum);
                                    $phones[] = [$res['phone'], $res['enum']];
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
    }

}
