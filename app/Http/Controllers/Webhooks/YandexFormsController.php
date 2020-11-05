<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
// use \Curl\Curl;
// use App\Models\User;
// use Illuminate\Support\Facades\Storage;
// use Illuminate\Support\Facades\File;


class YandexFormsController extends Controller
{

    public function __construct()
    {

    }

    public function balans(Request $request)
    {        
        $text = $request->input('params')['data'];
        // $text = "1\n2";
        $amo = \App\AmoAPI::getInstance();

        $leads = [];
        $leads[] = [
            'name' => 'БАЛАНС',
            'visitor_uid' => uniqid(),
        ];

        $metadata = [
            'ip' => '8.8.8.8',
            'form_id' => 'bk-invent.ru',
            'form_name' => 'БАЛАНС',
            'form_sent_at' => time(),
            'form_page' => 'https://bk-invent.ru/form/balans',
            // 'referer' => '', 
        ];

        $unsorted_data = [];
        $unsorted_data[] = [
            'source_uid' => uniqid(),
            'source_name' => 'bk-invent.ru',
            'created_at' => time(),
            '_embedded' => ['leads' => $leads],
            'metadata' => $metadata,
        ];
        $res = $amo->request('api/v4/leads/unsorted/forms', 'post', $unsorted_data);

        $lead_id = $res->_embedded->unsorted[0]->_embedded->leads[0]->id;

        $data_notes = [];
        // $data_notes[] = [
        //     'note_type' => 'invoice_paid',
        //     'params' => [
        //         'text' => $text,
        //         'service' => 'Яндекс.Формы',
        //         'icon_url' => 'https://bk-invent.ru/images/yandex_forms.png',
        //     ]
        // ];

        $data_notes[] = [
            'note_type' => 'common',
            'params' => [
                'text' => $text
            ]
        ];

        $amo->request("/api/v4/leads/$lead_id/notes", 'post', $data_notes);

        return 'ok';
    }
}
