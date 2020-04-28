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

    private $groups = [
        'Клиент',
        'Подрядчик',
        'Поставщик',
        'Партнер',
        'Конкурент',
        'Сотрудник',
        'Спам'
    ];

    public function __construct(AmoCrmManager $amocrm)
    {

        $this->amocrm = $amocrm;
    }

    public function create()
    {
        return view('phonebook.create')->with('groups', $this->groups);;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
            'type' => 'required',
            'group' => 'required',
            'email.*' => 'sometimes|nullable|email',
            'phone.*' => 'sometimes|nullable|numeric|min:10',

        ]);

        // dd($request);

        $keys = [
            'email'       => ($request->type == 'company') ? 75089 : 75089,
            'phone'       => ($request->type == 'company') ? 75087 : 75087,
            'group'       => ($request->type == 'company') ? 287299 : 287297,
            'description' => ($request->type == 'company') ? 292825 : 292823,
        ];
        
        $data = [
            'add' => []
        ];

        $custom_fields = [
            [
                'id' => $keys['group'],
                'values' => [
                    [
                        'value' => $request->group,
                    ]
                ]
            ],
            [
                'id' => $keys['description'],
                'values' => [
                    [
                        'value' => $request->description,
                    ]
                ]
            ]
        ];

        $values = [];

        foreach ($request->email as $value) {

            $custom_fields[] = [
                'id' => $keys['email'],
                'values' => [
                    [
                        'value' => $value,
                        'enum' => 'WORK'
                    ]
                ]
            ];

            if ($value) {
                $values[] = new AmoContactValue([
                    'type' => 'EMAIL',
                    'value' => $value,
                ]);
            }
        }

        foreach ($request->phone as $value) {

            $custom_fields[] = [
                'id' => $keys['phone'],
                'values' => [
                    [
                        'value' => $value,
                        'enum' => 'WORK'
                    ]
                ]
            ];

            if ($value) {
                $values[] = new AmoContactValue([
                    'type' => 'PHONE',
                    'value' => $value,
                ]);
            }
        }

        $data['add'][] = [
            'name' => $request->name,
            'tags' => \Auth::user()->email,
            'created_at' => time(),
            'custom_fields' => $custom_fields
        ];

        $amo = \App\AmoAPI::getInstance();

        $link = ($request->type == 'company') ? '/api/v2/companies' : '/api/v2/contacts';

        $res = $amo->request($link, 'post', $data);
        
        $id = $res->_embedded->items[0]->id;

        $contact = new AmoContact([
            'name' => $request->name,
            'type' => $request->type,
            'amo_id' => $id,
            'description' => ($request->description ? $request->description : ' '),
            'group' => $request->group,
        ]);

        $contact->save();
        $contact->values()->saveMany($values);

        return redirect()->to('phonebook');
    }

    public function index(Request $request)
    {

        $search = $request->query('search');
        $group = $request->query('group');

        if ($search || $group) {

            if ($group) {
                $res = DB::table('amo-contacts')
                    ->join('amo-contact-values', 'amo-contacts.id', '=', 'amo-contact-values.contact_id')
                    ->select('*')
                    ->where([
                        ['name', 'LIKE', '%' . $search . '%'],
                        ['group', $group]
                    ])
                    ->orWhere([
                        ['value', 'LIKE', '%' . $search . '%'],
                        ['group', $group]
                    ])
                    ->orWhere([
                        ['description', 'LIKE', '%' . $search . '%'],
                        ['group', $group]
                    ])
                    ->get();
            } else {
                $res = DB::table('amo-contacts')
                    ->join('amo-contact-values', 'amo-contacts.id', '=', 'amo-contact-values.contact_id')
                    ->select('*')
                    ->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('value', 'LIKE', '%' . $search . '%')
                    ->orWhere('description', 'LIKE', '%' . $search . '%')
                    ->get();
            }

            

            $ids = [];
            foreach($res as $item)
            {
                array_push($ids, $item->contact_id);
            }

            $contacts = AmoContact::whereIn('id', $ids)->paginate(9);

        } else {
            $contacts = AmoContact::paginate(9);
        }
        
        
        return view('phonebook/main')
                ->with('contacts', $contacts)
                ->with('search', $search)
                ->with('groups', $this->groups)
                ->with('group', $group);
    }

    public function xml()
    {
        //$this->getContacts();
        
        $contacts = AmoContact::all();
        
        return view('phonebook.xml')->with('contacts', $contacts);
    }

    public function update()
    {
        $this->getContacts();
        return redirect()->to('phonebook');
    }

    private function getContacts() 
    {
        $amo = \App\AmoAPI::getInstance();


        $contacts = [];
        $run = true;
        for($limit_offset = 0; $run; $limit_offset++) 
        {
            $res = $amo->request('/api/v2/contacts','get', ['limit_rows' => 500, 'limit_offset' => ($limit_offset * 500)])->_embedded->items;

            $contacts = array_merge($contacts, $res);

            if (count($res) < 500) $run = false;
        }

        $companies = [];
        $run = true;
        for($limit_offset = 0; $run; $limit_offset++) 
        {
            $res = $amo->request('/api/v2/companies','get', ['limit_rows' => 500, 'limit_offset' => ($limit_offset * 500)])->_embedded->items;

            $companies = array_merge($companies, $res);

            if (count($res) < 500) $run = false;
        }

        $all = [];
        foreach ($contacts as $contact)
        {
            //->company

            $values = [];
            $group = '';
            $description = '';

            if (isset($contact->custom_fields)) {
                foreach($contact->custom_fields as $field)
                {
                    if (isset($field->code)) 
                    {
                        if ($field->code == 'PHONE')  {
                            foreach ( $field->values as $item )
                            {
                                $values[] = [
                                    'contact_id' => $contact->id,
                                    'value' => $item->value,
                                    'type' => 'PHONE',
                                ];
                            }
                        } else if ($field->code == 'EMAIL') {
                            foreach ( $field->values as $item )
                            {
                                $values[] = [
                                    'contact_id' => $contact->id,
                                    'value' => $item->value,
                                    'type' => 'EMAIL',
                                ];
                            }
                        }
                    }

                    if ($field->id == 287297) {
                        $group = $field->values[0]->value;
                    }

                    if ($field->id == 292823) {
                        $description = $field->values[0]->value;
                    }
                }
            }

            $all[] = [
                'name' => $contact->name,
                'id' => $contact->id,
                'type' => 'contact',
                'group' => $group,
                'description' => $description,
                'values' => $values,
            ];
        }

        foreach ($companies as $company)
        {

            $values = [];
            $group = '';
            $description = '';

            if (isset($company->custom_fields)) {
                foreach($company->custom_fields as $field)
                {
                    if (isset($field->code)) 
                    {
                        if ($field->code == 'PHONE')  {
                            foreach ( $field->values as $item )
                            {
                                $values[] = [
                                    'contact_id' => $company->id,
                                    'value' => $item->value,
                                    'type' => 'PHONE',
                                ];
                            }
                        } else if ($field->code == 'EMAIL') {
                            foreach ( $field->values as $item )
                            {
                                $values[] = [
                                    'contact_id' => $company->id,
                                    'value' => $item->value,
                                    'type' => 'EMAIL',
                                ];
                            }
                        }
                    }

                    if ($field->id == 287299) {
                        $group = $field->values[0]->value;
                    }

                    if ($field->id == 292825) {
                        $description = $field->values[0]->value;
                    }
                }
            }

            $all[] = [
                'name' => $company->name,
                'id' => $company->id,
                'type' => 'company',
                'group' => $group,
                'description' => $description,
                'values' => $values,
            ];
        }
        
        AmoContact::truncate();
        AmoContactValue::truncate();

        foreach ($all as $item) {

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
                'description' => $item['description'],
                'group' => $item['group'],
            ]);

            $contact->save();

            $contact->values()->saveMany($_values);
            
        }
    }

}


