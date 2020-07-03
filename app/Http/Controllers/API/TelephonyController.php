<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use App\Models\User;

class TelephonyController extends Controller
{
    protected $amo;

    public function __construct()
    {
        $this->amo = \App\AmoAPI::getInstance();
    }
    
    public function record(Request $request)
    {
        
        // 1 – оставил сообщение
        // 2 – перезвонить позже
        // 3 – нет на месте
        // 4 – разговор состоялся
        // 5 – неверный номер
        // 6 – Не дозвонился
        // 7 – номер занят.

        $direction = ($request->input('direction') == 'in') ? 'inbound' : 'outbound';
        $virtual_phone = $request->input('virtual_phone_number'); // Виртуальный
        $extension_phone = $request->input('extension_phone_number'); // Добавочный
        $contact_phone = $request->input('contact_phone_number'); // Номер клиента
        $call_result = $request->input('employee_full_name'); // Менеджер
        $duration = $request->input('file_duration'); // Длительность звонка 
        $link = $request->input('file_link'); // Сыылка на файл
        $uniq = $request->input('communication_id'); // ID Звонка 

        //"notification_timestamp": 1593688834,
        $start_time = $request->input('start_time');

        $data = [
            'direction' => $duration,
            'source' => 'example_integration', // ?
            'phone' => $contact_phone,
            'link' => $link,
            'duration' => $duration,
            'call_result' => $call_result,
            'call_status' => 4, 
            'uniq' => $uniq,
            'created_at' => strtotime($start_time)
        ];


        $user = User::where('extension_phone', $extension_phone)->first();
        $responsible_user_id = $user->amo_user_id;
        $data['responsible_user_id'] = $responsible_user_id;

        $call_data = [];
        $call_data[] = $data;

        $res = $amo->request('api/v4/calls', 'post', $call_data);

        // Проверка  $res
    }

    public function lost(Request $request)
    {
        $direction = ($request->input('direction') == 'in') ? 'inbound' : 'outbound';
        $virtual_phone = $request->input('virtual_phone_number'); // Виртуальный
        $extension_phone = $request->input('extension_phone_number'); // Добавочный
        $contact_phone = $request->input('contact_phone_number'); // Номер клиента
        $call_result = $request->input('employee_full_name'); // Менеджер
        $uniq = $request->input('communication_id'); // ID Звонка
        $start_time = $request->input('start_time');

        $data = [
            'direction' => $duration,
            'source' => 'example_integration', // ?
            'phone' => $contact_phone,
            'duration' => 0,
            'call_result' => $call_result,
            'call_status' => 6, 
            'uniq' => $uniq,
            'created_at' => strtotime($start_time)
        ];

        $user = User::where('extension_phone', $extension_phone)->first();
        $responsible_user_id = $user->amo_user_id;
        $data['responsible_user_id'] = $responsible_user_id;

        $call_data = [];
        $call_data[] = $data;

        $res = $amo->request('api/v4/calls', 'post', $call_data);
    }

    private function searchInCRM($phone)
    {
    }

    private function addToUnsorted($data)
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
            'service_code' => 'private.bk-invent.ru',
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

        $res = $amo->request('api/v4/leads/unsorted/sip', 'post', $unsorted_data);
        return $res ;
    }

    private function addContact($phone)
    {
    }
}
