<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use Illuminate\Support\Facades\Mail;

class SiteController extends Controller
{
    public $curl;

    public function __construct()
    {
        $this->curl = new Curl(env('BTRIX24_URL'));
    }

    /**
     * Создание заявки с сайта
     * POST
     * @param Request $request
     * @return void
     */
    public function createLeadFromForm(Request $request)
    {
        $lead_name = $request->input('order');
        $contact_phone = $request->input('phone');
        $contact_name = $request->input('name') ? $request->input('name') : $contact_phone;
	
        $contact_email = $request->input('email');
        $utm_medium = $request->input('utm_medium');
        $utm_source = $request->input('utm_source');
        $utm_campaign = $request->input('utm_campaign');
        $utm_term = $request->input('utm_term');
        $utm_content = $request->input('utm_content');
        $url = $request->input('url');
        $comment = $request->input('comment');
        
        $contact_phone = str_replace(['+', '(', ')', ' ', '-', '_', '*', '–'], '', $contact_phone);
        
        if (strlen($contact_phone) >= 11) {
            if ($contact_phone[0] == 8) {
                $contact_phone[0] = 7;
            }
        }

        if (strlen($contact_phone) == 10) {
            $contact_phone = '7' . $contact_phone;
        }

        $comment =  $comment . 
            "<br>
            <b>$lead_name</b> <br>
            Имя: $contact_name <br>
            Телефон: $contact_phone <br>
            E-mail: $contact_email <br>
            Страница захвата: $url <br> 
            Ключевое слово: $utm_term <br>";
        
        $concact_id = null;
        
        if (strlen($contact_phone) >= '5') {
            
            $res = $this->curl->post('crm.contact.list.json', [
                'filter' => [
                    'PHONE' => $contact_phone
                ],
                'select' => ['ID']
            ])->result;
 
            if ($res) $concact_id = $res[0]->ID;
        }
        
        $data = [
            'fields' => [
                'TITLE' => $lead_name,
                'UTM_CAMPAIGN' => $utm_campaign,
                'UTM_CONTENT' => $utm_content,
                'UTM_MEDIUM' => $utm_medium,
                'UTM_SOURCE' => $utm_source,
                'UTM_TERM' => $utm_term,
                'COMMENTS' => $comment,
                'ASSIGNED_BY_ID' => 9
            ]
        ];

        if ($concact_id) {
            $data['fields']['CONTACT_ID'] = $concact_id;
        } else {
            $data['fields']['NAME'] = $contact_name;
            
            $data['fields']['PHONE'] = [
                [
                    'VALUE' => $contact_phone,
                    'VALUE_TYPE' => 'MOBILE'
                ]
            ];
        }
        
        if (preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $contact_email)) {
            $data['fields']['EMAIL'] = [
                [
                    'VALUE' => $contact_email,
                    'VALUE_TYPE' => 'MAILING'
                ]
            ];
        }
        
        $this->curl->post('crm.lead.add.json', $data);

        return 'ok';
    }
    
    public function createReview(Request $request)
    {
        $fio = $request->input('fio');
        $text = $request->input('text');
        $email = $request->input('email');
        
        $file = $request->file('file');
        
        $data = [];
        $data['fio'] = $fio;
        $data['text'] = $text;
        $data['email'] = $email;
                   
        $path =[];
        
        if ($file) {
            $file->move(storage_path('app/tmp/') , $file->getClientOriginalName());
            $path[] = storage_path('app/tmp/' . $file->getClientOriginalName());
        }
        
        Mail::send('email.review', $data, function ($message) use ($path) {
            $message->to('it@bkinvent.net')->from('support@bk-invent.ru', 'БК Инвент')->subject('Отзыв c сайта');
            
            $size = sizeOf($path);
       
            for($i=0; $i<$size; $i++){
                $message->attach($path[$i]);
            }
        });
        
        return 'ok';
    }
}
