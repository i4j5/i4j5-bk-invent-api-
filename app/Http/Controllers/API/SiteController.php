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
            'trace' => '', //История переходов
           
            'comment' => '',
        ];
        
        $request->order ? $data['title'] = $request->order : false;
        $request->comment ? $data['comment'] = $request->comment : false;
        $request->url ? $data['landing_page'] = $request->url : false;
        $request->referrer ? $data['referrer'] = $request->referrer : false;
        
        //$request->trace ? $data['trace'] = $request->trace : false;
        
        $request->phone ? $data['phone'] =  $this->phone->fix($request->phone)['phone'] : false;
        $request->name ? $data['name'] = $request->name : false;
        $request->email ? $data['email'] = $request->email : false;

        $request->google_client_id ? $data['google_client_id'] = $request->google_client_id : false;
        $request->metrika_client_id ? $data['metrika_client_id'] = $request->metrika_client_id : false;
        
        //$request->visit ? $data['visit'] = $request->visit : false;
        
        $request->utm_source ? $data['utm_source'] = $request->utm_source : false;
        $request->utm_medium ? $data['utm_medium'] = $request->utm_medium : false;
        $request->utm_campaign ? $data['utm_campaign'] = $request->utm_campaign : false;
        $request->utm_content ? $data['utm_content'] = $request->utm_content : false;
        $request->utm_term ? $data['utm_term'] = $request->utm_term : false;
        
        // Получаем контакт
        $contact = $this->phone->contactSearch($data['phone']); // !!!!!!!!!!!!!

        $lead = $this->amocrm->lead;
        $lead['name'] = $data['title'];
        $lead['tags'] = ['Заявка с сайта'];

        $lead->addCustomField(75455, $data['utm_source']);
        $lead->addCustomField(75457, $data['utm_medium']);
        $lead->addCustomField(75461, $data['utm_campaign']);
        $lead->addCustomField(75459, $data['utm_content']);
        $lead->addCustomField(75453, $data['utm_term']);
        $lead->addCustomField(75467, $data['google_client_id']);
        $lead->addCustomField(75469, $data['metrika_client_id']);
        $lead->addCustomField(75451, $data['landing_page']);
        $lead->addCustomField(75465, $data['referrer']);

        $note = $this->amocrm->note;
        $note['element_type'] = \AmoCRM\Models\Note::TYPE_CONTACT;
        $note['note_type'] = \AmoCRM\Models\Note::COMMON;

        $data['comment'] = $data['comment'] . " \n
        ====================\n
        {$data['title']} \n
        ====================\n
        Имя: {$data['name']} \n
        Телефон: {$data['phone']} \n
        E-mail: {$data['email']} \n
        ====================\n
        Страница захвата: {$data['landing_page']} \n
        Ключевое слово: {$data['utm_term']} \n
        Реферальная ссылка: {$data['referrer']} \n
        ";

        $note['text'] = $data['comment'];

        if (!$contact) {
            $lead['notes'] = $note;

            $unsorted = $this->amocrm->unsorted;
            $unsorted['source'] = 'bk-invent.ru';
            $unsorted['source_uid'] = null;

            $unsorted['source_data'] = [
                'data' => [
                    'name' => [
                        'type' => 'text',
                        'element_type' => '1',
                        'name' => 'Имя',
                        'value' => $data['name'],
                    ],
                    'phone' => [
                        'type' => 'text',
                        'element_type' => '1',
                        'name' => 'Телефон',
                        'value' => $data['phone'],
                    ],
                    'email' => [
                        'type' => 'text',
                        'element_type' => '1',
                        'name' => 'E-mail',
                        'value' => $data['email'],
                    ]
                ],
                'form_id' => 1,
                'form_type' => 1,
                'origin' => [
                    'ip' => $_SERVER['REMOTE_ADDR']
                ],
                'date' => time(),
                'from' => 'Заявка с сайта'
            ];

            // Заполнение контакта 
            $contact = $this->amocrm->contact;
            $contact['name'] = $data['name'];
            $contact->addCustomField('75087', [
                [$data['phone'], 'MOB'],
            ]);
            $contact->addCustomField('75089', [
                [$data['email'], 'WORK'],
            ]);

            // Присоединение контакт к неразобранному
            $unsorted->addDataContact($contact);

            // Присоединение сделки к неразобранному
            $unsorted->addDataLead($lead);

            // Добавление неразобранной заявки с типом FORMS
            $unsortedId = $unsorted->apiAddForms();
        } else {
            if(isset($contact['responsible_user_id'])) {
                $lead['responsible_user_id'] = $contact['responsible_user_id'];
            }

            $lead_id = $lead->apiAdd();

            //Добавить коментарий
            $note['element_id'] = $contact['id'];
            $note->apiAdd();

            // Добавить задачу
            $task = $this->amocrm->task;
            $task['element_id'] = $lead_id;
            $task['element_type'] = 2;
            $task['task_type'] = 1;
            $task['text'] = "@A Связаться с клиентом.";
            $task['responsible_user_id'] = $contact['responsible_user_id'];
            $task['complete_till'] = '+20 minutes';
            $task->apiAdd();

            $link = $this->amocrm->links;
            $link['from'] = 'leads';
            $link['from_id'] = $lead_id;
            $link['to'] = 'contacts';
            $link['to_id'] = $contact['id'];
            $link->apiLink();
        }

        // Lead::create([
        //     'deal_id' => 0, 
        //     'visitor_id' => $visitor_id, 
        //     'session_id' =>$session_id, 
        //     'hit_id' => $hit_id,
        //     'name' => $contact_name, 
        //     'phone' =>  $contact_phone, 
        //     'email' => $contact_email, 
        //     'title' => $lead_name, 
        //     'comment' => $comment,
        //     'url' => $url, 
        //     'utm_medium' => $utm_medium, 
        //     'utm_source' =>  $utm_source, 
        //     'utm_campaign' => $utm_campaign, 
        //     'utm_term' => $utm_term, 
        //     'utm_content' => $utm_content,
        //     'hash_id' => md5($visitor_id . $session_id),
        // ]);
        
        // $server = $consultant_server_url . 'api/add_offline_message/';
        
        // $data = [
        //     'site_key' => $site_key,
        //     'visitor_id' => $visitor_id,
        //     'hit_id' => $hit_id,
        //     'session_id' => $session_id, 
        //     'name' => $contact_name,
        //     'phone' => $contact_phone,
        //     'text' => $comment,
        //     'is_sale' => false, 
        //     //'sale_cost' => 10000
        // ];
        
        // if (preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $contact_email)) {
        //     $data['email'] = $contact_email;
        // }
        
        // $options = [
        //     'http' => [
        //         'header' => "Content-type: application/x-www-form-urlencoded; charset=UTF-8",
        //         'method' => "POST",
        //         'content' => http_build_query($data)
        //     ]
        // ];
        
        // $context = stream_context_create($options);
        // $result = file_get_contents($server, false, $context);
        // $resultArray = json_decode($result, true);

        // if ($result === false or $resultArray['success'] === false) {
        //     // Ошибка...
        // }

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
