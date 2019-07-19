<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Models\AmoContact;
use App\Models\AmoContactValue;

class PhoneBookController extends Controller
{
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->middleware('auth');
        $this->amocrm = $amocrm;
        
    }

    public function index(Request $request)
    {

        $search = $request->input('search');

        //$contacts = AmoContact::all();

        if($search) 
        {
            $contacts = AmoContact::where('name', 'LIKE', '%' . $search . '%')->paginate(999999);
            $values = AmoContactValue::where('value', 'LIKE', '%' . $search . '%')
                            // ->orwhere('value', 'LIKE', '%' . $search . '%')
                            ->paginate(999999);
            
            $_contacts = $contacts->items();
            foreach($values as $value)
            {
                array_push($_contact, $value->contact());
            }

            dd($contacts->items(), $_contacts);
        } else {
            $contacts = AmoContact::paginate(99);
        }
        

        // $contacts->withPath('custom/url');
        // $contacts->values();

        return view('phonebook/main')->with('contacts', $contacts)->with('search', $search);
    }

    public function all()
    {
        $contacts = AmoContact::paginate(999999);

        return view('phonebook/main')->with('contacts', $contacts)->with('search', '');
    }

    public function update()
    {
        $data = [];
        $run = true;
        for($limit_offset = 0; $run; $limit_offset++) 
        {
            $res = $this->amocrm->contact->apiList([
                'query' => '',
                'limit_rows' => 500,
                'limit_offset' => $limit_offset * 500,
                'type' => 'all'
            ]);

            $data = array_merge($data, $res);

            if (count($res) < 500) $run = false;
        }

        $items = [];

        foreach ( $data as $contact )
        {
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
                        } else if ($field['code'] == 'EMAIL') {
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

            $items[] = [
                'name' => $contact['name'],
                'id' => $contact['id'],
                'type' => $contact['type'],
                'values' => $values,
            ];
        }
        
        AmoContact::truncate();
        AmoContactValue::truncate();

        foreach ($items as $item) {

            $_values = [];

            foreach ($item['values'] as $value) {
                $_values[] = new AmoContactValue([
                    'type' => $value['type'],
                    'value' => $value['value'],
                ]);
            }

            $contact = new AmoContact([
                'name' => $item['name'],
                'type' => $item['type'],
                'amo_id' => $item['id'],
            ]);

            $contact->save();

            $contact->values()->saveMany($_values);
            
        }

        return redirect()->to('phonebook');
    }

}


