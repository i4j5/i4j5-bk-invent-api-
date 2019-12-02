<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;


class Bitrix24EventsController extends Controller
{
    public $bitrix24;
    
    public function __construct()
    {
        $this->bitrix24 = new Curl(env('BTRIX24_URL'));
    }

    public function onCrmDealUpdate(Request $request)
    {        
        isset($request->input('data')) ? $data = $request->input('data') : $data = [];
        
        isset($request->input('event')) ? $event = $request->input('event') : $event = null;
        
        $event = 'ONCRMDEALUPDATE';
        
        if ($event == 'ONCRMDEALUPDATE') {
            $id = $data['FIELDS']['ID'];
            
            $responsible = $this->bitrix24->post('crm.deal.get.json', [
                'id' => $id
            ])->result->ASSIGNED_BY_ID;
            
            $contacts = $this->bitrix24->post('crm.deal.contact.items.get.json', [
                'id' => $id
            ])->result;
            
            foreach ( $contacts as $contact )
            {
                $this->bitrix24->post('crm.contact.update.json', [
                    'id' => $contact->CONTACT_ID,
                    'fields' => [
                        'ASSIGNED_BY_ID' => $responsible
                    ]
                ]);
            }
        }
    }

}
