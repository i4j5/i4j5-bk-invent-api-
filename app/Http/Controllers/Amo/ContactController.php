<?php

namespace App\Http\Controllers\Amo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;

class ContactController extends Controller
{

    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
        set_time_limit(0);
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

    public function fixAllPhones(Request $request)
    {   
        $i = 0;     
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
                $contact = $this->amocrm->contact;
                $phones = [];

                if(isset($data['custom_fields'])) 
                {
                    
                    foreach ( $data['custom_fields'] as $field )
                    {
                        if ($field['code'] == 'PHONE') {
                            
                            $i++; 
                            foreach ( $field['values'] as $item )
                            {
                                $phone = $item['value'];
                                $enum = $item['enum'];
    
                                $res = $this->fixPhone($phone, $enum);
                                $phones[] = $res;
                            }  
                        }
                    }
                }

                $contact->addCustomField('95354', $phones);
                
                if(isset($data['id']) && $data['id']) 
                    $contact->apiUpdate((int) $data['id'], 'now');
            }

            if (count($data) < 500) $run = false;
        }

        echo "Было обработано $i контактов";

    }

}
