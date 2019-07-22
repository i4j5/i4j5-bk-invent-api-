<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Models\AmoContact;
use App\Models\AmoContactValue;
use DB;

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

        $search = $request->query('search');

        if($search) 
        {

            $res = DB::table('amo-contacts')
                    ->join('amo-contact-values', 'amo-contacts.id', '=', 'amo-contact-values.contact_id')
                    ->select('*')
                    ->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('value', 'LIKE', '%' . $search . '%')
                    ->get();

            $ids = [];
            foreach($res as $item)
            {
                array_push($ids, $item->contact_id);
            }

            $contacts = AmoContact::whereIn('id', $ids)->paginate(48);

        } else {
            $contacts = AmoContact::paginate(48);
        }
        
        return view('phonebook/main')->with('contacts', $contacts)->with('search', $search);
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


