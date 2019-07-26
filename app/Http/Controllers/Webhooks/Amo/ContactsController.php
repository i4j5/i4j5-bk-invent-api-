<?php

namespace App\Http\Controllers\Webhooks\Amo;

use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use Illuminate\Http\Request;

/**
 * WebHook
 * amoCRM
 * Синхранизация контактов
 */
class ContactsController extends Controller
{
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
    }

    public function handle(Request $request)
    {        
        $arr = $request->input('contacts'); 

        dd($arr);
        
        $event = null;

        if(isset($arr['add']))
        {
            $event = 'add';
            $contact = $arr['add'][0];
        } else if(isset($arr['update']))
        {
            $event = 'update';
            $contact = $arr['update'][0];
        } else {
            return;
        }

        $values = [];

        if(isset($contact['custom_fields'])) 
        {
            foreach( $contact['custom_fields'] as $field )
            {

                if (isset($field['code'])) 
                {
                    if($field['code'] == 'PHONE') 
                    {
                        foreach ( $field['values'] as $item )
                        {
                            $values[] = [
                                'contact_id' => $contact['id'],
                                'value' => $item['value'],
                                'type' => 'PHONE',
                            ];
                        }
                    } else if ($field['code'] == 'EMAIL') 
                    {
                        foreach ( $field['values'] as $item )
                        {
                            if($item['value'] != "-" && $item['value'] != "—" && $item['value'] != '@') 
                            {
                                $values[] = [
                                    'contact_id' => $contact['id'],
                                    'value' => $item['value'],
                                    'type' => 'EMAIL',
                                ];
                            }
                        }
                    } 
                } 
            }
        }


        if ($event === 'add') {

            $newContact = new AmoContact([
                'name' => $contact['name'],
                'type' => $contact['type'],
                'amo_id' => $contact['id'],
            ]);

            $newContact->save();

            $newContact->values()->saveMany($values);
            
        } else if ($event === 'update')
        {
            $oldContact = AmoContact::where('amo_id', $contact['id']);
            
            foreach($oldContact->values as $value)
            {
                AmoContactValue::find($value->id)->delete();
            }

            $oldContact->name = $contact['name'];
            // $newContact->save();
            $newContact->values()->saveMany($values);

        }

    }
    
}