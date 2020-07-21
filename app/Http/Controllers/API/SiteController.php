<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use Illuminate\Support\Facades\Mail;
use App\Models\Lead;
// use App\Bitrix24;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Phone;

class SiteController extends Controller
{
    private $amocrm;
    private $phone;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
        $this->phone = Phone::getInstance();
    }

    public function e(AmoCrmManager $amocrm)
    {
        set_time_limit(0);

        $amo = \App\AmoAPI::getInstance();
        $regexp = '/\d+__/';

        $data = [];
        $run = true;
        for($limit_offset = 0; $run; $limit_offset++) 
        {
            sleep(1);
            $res = $amo->request('/api/v2/contacts','get', ['limit_rows' => 500, 'limit_offset' => ($limit_offset * 500)])->_embedded->items;

            // dd($res->_embedded->items);

            $data = array_merge($data, $res);

            if (count($res) < 500) $run = false;
        }

        // dd($data);

        foreach ($data as $key => $contact) {
            $result = preg_match($regexp, $contact->name, $match);
            if($result) {

                $name = str_replace($match[0], '', $contact->name, $count);

                $update_data = [];
                $update_data['update'][] = [
                    'id' => $contact->id,
                    'updated_at' => time(),
                    'name' => $name
                ];
        
                $amo->request('/api/v2/contacts', 'post', $update_data);

                //dd($match, $contact->id, $contact->name, $name);
            }
        }

        
        // $result = preg_match($regexp, $text, $match);
        // var_dump(
        //     $result,
        //     $match
        // );

        // $update_data = [
        //     'update' => []
        // ];

       

        dd($data);
    }

    /**
     * Создание заявки с сайта
     * POST
     * @param Request $request
     * @return void
     */
    public function createLeadFromForm(Request $request)
    {
        $data = [
            'title' => 'LEAD',
            'name' => '',
            'phone' => '',
            'email' => '',
            'google_client_id' => '',
            'metrika_client_id' => '',
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_content' => '',
            'utm_term' => '',
            'landing_page' => '',
            'referrer' => '',
            'trace' => '',
            'comment' => '',
            'visit' => '',
            'tags' => ['Заявка с сайта'],
            'page_view_tracker' => '',
            'ip' => $request->ip()
        ];
        
        $request->order ? $data['title'] = $request->order : false;
        $request->comment ? $data['comment'] = $request->comment : false;
        $request->url ? $data['landing_page'] = $request->url : false;
        $request->referrer ? $data['referrer'] = $request->referrer : false;
        
        $request->visit ? $data['visit'] = $request->visit : false;
        $request->roistat ? $data['roistat'] = $request->roistat : false;
        
        $request->phone ? $data['phone'] =  $this->phone->fix($request->phone)['phone'] : false;
        $request->name ? $data['name'] = $request->name : false;
        $request->email ? $data['email'] = $request->email : false;

        $request->google_client_id ? $data['google_client_id'] = $request->google_client_id : false;
        $request->metrika_client_id ? $data['metrika_client_id'] = $request->metrika_client_id : false;;
        
        $request->utm_source ? $data['utm_source'] = $request->utm_source : false;
        $request->utm_medium ? $data['utm_medium'] = $request->utm_medium : false;
        $request->utm_campaign ? $data['utm_campaign'] = $request->utm_campaign : false;
        $request->utm_content ? $data['utm_content'] = $request->utm_content : false;
        $request->utm_term ? $data['utm_term'] = $request->utm_term : false;

        $request->page_view_tracker ? $data['page_view_tracker'] = $request->page_view_tracker : false;

        // \App\AmoCRM::getInstance()->addLead($data);
        dd( \App\AmoAPI::getInstance()->addLead($data) );

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
