<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\File;

class TelephonyController extends Controller
{
    protected $amo;

    public function __construct()
    {
        $this->amo = \App\AmoAPI::getInstance();
    }
    
    public function record(Request $request)
    {
    
        $direction = ($request->input('direction') == 'in') ? 'inbound' : 'outbound';
        
        $virtual_phone = $request->input('virtual_phone_number'); // Виртуальный
        $extension_phone = $request->input('extension_phone_number'); // Добавочный
        $contact_phone = $request->input('contact_phone_number'); // Номер клиента
        $call_result = $request->input('employee_full_name'); // Менеджер
        $duration = $request->input('file_duration'); // Длительность звонка 
        $link = $request->input('file_link'); // Сcылка на файл
        $uniq = $request->input('communication_id'); // ID Звонка 

        $start_time = $request->input('start_time'); 

        $filename = str_replace([':', ' '], ['-', '_'], $start_time);

        $from = '';
        $to = '';

        if ($direction == 'inbound') {
            $from = $extension_phone;
            $to = $contact_phone;
        } else {
            $from = $contact_phone;
            $to = $extension_phone;
        }

        $filename = $filename . "_from_$from" . "_to_$to";

        $link = $this->savetTalk($link, $filename);

        $data = [
            'direction' => $direction,
            'source' => 'Telephony',
            'phone' => $contact_phone,
            'link' => $link,
            'duration' => $duration,
            'call_result' => $call_result,
            'call_status' => 4, 
            'uniq' => $uniq,
            'created_at' => strtotime($start_time)
        ];

        $user = User::where('extension_phone_number', $extension_phone)->first();
        
        if ($user) $data['responsible_user_id'] = $user->amo_user_id;

        $res = $this->addCall($data);

        if ($res->errors) {
            if ($direction == 'inbound') {
                return $this->addUnsorted($data); 
            } elseif ($direction == 'outbound') {
                $this->addContact($data['phone']);
                return $this->addCall($data);
            }
        }

        return $res;
    }

    public function lost(Request $request)
    {

        // 2 – перезвонить позже
        // 3 – нет на месте
        // 6 – Не дозвонился
        // 7 – номер занят.


        $employee_ids = $request->input('employee_ids');
        $employee_full_names = $request->input('employee_full_names');

        $direction = ($request->input('direction') == 'in') ? 'inbound' : 'outbound';

        $virtual_phone = $request->input('virtual_phone_number');
        $contact_phone = $request->input('contact_phone_number');

        $call_result = $request->input('lost_reason');
        $uniq = $request->input('communication_id');
        $start_time = $request->input('start_time');

        $data = [
            'direction' => $direction,
            'source' => 'Telephony',
            'phone' => $contact_phone,
            'link' => '',
            'duration' => 0,
            'call_result' => $call_result,
            'call_status' => 6, 
            'uniq' => $uniq,
            'created_at' => strtotime($start_time)
        ];

        $responsible_user_id = 0;
        
        // TODO Поиск контакта и получение id отвестренного
        // $contact = $this->amo->request('api/v4/contacts', 'get', [
        //     'filter[custom_fields_values][75087][]' => $contact_phone
        // ]);

        // $company = $this->amo->request('api/v4/companies', 'get', [
        //     'filter[custom_fields_values][75087][]' => $contact_phone
        // ]);

        if ($responsible_user_id) $data['responsible_user_id'] = $responsible_user_id;

        $res = $this->addCall($data); 

        if ($res->errors) {
            if ($direction == 'inbound') {
                return $this->addUnsorted($data); 
            } elseif ($direction == 'outbound') {
                $this->addContact($data['phone']);
                return $this->addCall($data);
            }
        }

        return $res;
    }

    private function addCall($data)
    {
        $res = $this->amo->request('api/v4/calls', 'post', [
            '0' => $data
        ]);
        return $res;
    }

    private function savetTalk($url, $filename)
    {
        $content = file_get_contents($url);
        Storage::put("media/talk/$filename.mp3", $content);
        return url("media/talk/$filename");
    }

    private function addContact($phone)
    {
        $this->amo->request('api/v4/contacts', 'post', [
            '0' => [
                'name' => $phone,
                'custom_fields_values' => [
                    '0' => [
                        'field_id' => 75087,
                        'values' => [
                            '0' => [
                                'value' => $phone,
                                'enum_code' => 'MOB'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    private function addUnsorted($data)
    {
        $contacts = [];
        $contacts[] = [
            'name' => $data['phone'],
            'custom_fields_values' => [
                '0' => [
                    'field_id' => 75087,
                    'values' => [
                        '0' => [
                            'value' => $data['phone'],
                            'enum_code' => 'MOB'
                        ]
                    ]
                ]
            ]
        ];

        $leads = [];
        $leads[] = [
            'name' => 'Новая сделка по звонку с ' . $data['phone'],
        ];

        $metadata = [
            'from' => $data['phone'],
            'phone' => $data['phone'],
            'called_at' => $data['created_at'],
            'duration' => $data['duration'],
            'link' => $data['link'],
            'service_code' => 'Telephony',
            'is_call_event_needed' => true,
        ];

        $unsorted_data = [
            'source_uid' => $data['uniq'],
            'source_name' => 'Telephony',
            'created_at' => $data['created_at'],
            '_embedded' => [
                'leads' => $leads,
                'contacts' => $contacts
            ],
            'metadata' => $metadata,
        ];

        $res = $this->amo->request('api/v4/leads/unsorted/sip', 'post', $unsorted_data);
        return $res ;
    }

}
