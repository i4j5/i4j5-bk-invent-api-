<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use Illuminate\Support\Facades\Mail;
use App\Models\Lead;
use App\Bitrix24;

class SiteController extends Controller
{
    public $curl;

    public function __construct()
    {
        $this->curl = new Curl(env('BTRIX24_URL'));
    }

    public function e()
    {
        set_time_limit(0);

        $ids = [];
        $next = 0;

        $run = true;

        $n = 0;

        while ($run) {
            sleep(1);
            $data = $this->curl->post('crm.contact.list',['start' => $next]); //result

            if(isset($data->result)) {

                foreach ($data->result as $contact) {
                    $n++;

                    if(explode('__', $contact->LAST_NAME . ' ')[0] != $contact->ID) {

                        sleep(10);
                        
                        $this->curl->get("crm.contact.update.json?id={$contact->ID}&fields[LAST_NAME]={$contact->ID}__ {$contact->LAST_NAME}");

                        //echo "$n crm.contact.update.json?id={$contact->ID}&fields[LAST_NAME]={$contact->ID}__ {$contact->LAST_NAME}  <br>";

                    }

                    // echo "$n  {$contact->ID} {$contact->LAST_NAME}  <br>";
                }

            } else {
                dd($data);
            }

            if (!isset($data->next)){
                $run = false;
            } else {
                $next = $data->next;
            }

        }
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
        $contact_phone = $request->input('phone') ? $request->input('phone') : '-';
        $contact_name = $request->input('name') ? $request->input('name') : $contact_phone;
	
        $contact_email = $request->input('email') ? $request->input('email') : '-';
        
        $utm_medium = $request->input('utm_medium') ? $request->input('utm_medium') : ' ';
        $utm_source = $request->input('utm_source') ? $request->input('utm_source') : ' ';
        $utm_campaign = $request->input('utm_campaign')? $request->input('utm_campaign') : ' ';
        $utm_term = $request->input('utm_term') ? $request->input('utm_term') : ' ';
        $utm_content = $request->input('utm_content') ? $request->input('utm_content') : ' ';
        $url = $request->input('url');
        $comment = $request->input('comment');
        
        $site_key = $request->input('site_key');
        $visitor_id = $request->input('visitor_id');
        $hit_id = $request->input('hit_id');
        $session_id = $request->input('session_id');
        $consultant_server_url = $request->input('consultant_server_url');
        
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
            "\n
            <b>$lead_name</b> \n
            Имя: $contact_name \n
            Телефон: $contact_phone \n
            E-mail: $contact_email \n
            Страница захвата: $url \n 
            Ключевое слово: $utm_term \n";

        Lead::create([
            'deal_id' => 0, 
            'visitor_id' => $visitor_id, 
            'session_id' =>$session_id, 
            'hit_id' => $hit_id,
            'name' => $contact_name, 
            'phone' =>  $contact_phone, 
            'email' => $contact_email, 
            'title' => $lead_name, 
            'comment' => $comment,
            'url' => $url, 
            'utm_medium' => $utm_medium, 
            'utm_source' =>  $utm_source, 
            'utm_campaign' => $utm_campaign, 
            'utm_term' => $utm_term, 
            'utm_content' => $utm_content,
            'hash_id' => md5($visitor_id . $session_id),
        ]);
        
        $server = $consultant_server_url . 'api/add_offline_message/';
        
        $data = [
            'site_key' => $site_key,
            'visitor_id' => $visitor_id,
            'hit_id' => $hit_id,
            'session_id' => $session_id, 
            'name' => $contact_name,
            'phone' => $contact_phone,
            'text' => $comment,
            'is_sale' => false, 
            //'sale_cost' => 10000
        ];
        
        if (preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $contact_email)) {
            $data['email'] = $contact_email;
        }
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded; charset=UTF-8",
                'method' => "POST",
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($server, false, $context);
        $resultArray = json_decode($result, true);

        if ($result === false or $resultArray['success'] === false) {
            // Ошибка...
        }

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
    
    public function createQuestion(Request $request)
    {
        $fio = $request->input('fio');
        $text = $request->input('text');
        $email = $request->input('email');
        
        $data = [];
        $data['fio'] = $fio;
        $data['text'] = $text;
        $data['email'] = $email;
                   
        
        Mail::send('email.question', $data, function ($message) {
            $message->to('it@bkinvent.net')->from('support@bk-invent.ru', 'БК Инвент')->subject('Вопрос');
            
        });
        
        return 'ok';
    }
}
