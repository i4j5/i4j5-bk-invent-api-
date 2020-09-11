<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
// use Symfony\Component\HttpFoundation\Response;

class AmoCRMController extends Controller
{
    
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
    }

    //   
    public function deal(Request $request, $event)
    {

        $deal_id = null;

        if ($event == 'delete') {
            $deal_id = $request->input('leads')['delete'][0]['id'];
        }

        if ($event == 'closing') {
            $deal_id = $request->input('leads')['status'][0]['id'];
        }

        if (!$deal_id) return 'error';

        $asana = new Curl();
        $asana->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $asana->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        $asana_webhooks = $asana->get('https://app.asana.com/api/1.0/webhooks?workspace=' . env('ASANA_WORKSPACE_ID'))->data;

        foreach ($asana_webhooks as $webhook) {
            
            $arr = explode('/', $webhook->target);
            $i = ((int) count($arr)) - 2;

            if ($arr[$i] == $deal_id) {
                $asana->delete("https://app.asana.com/api/1.0/webhooks/$webhook->gid");
            }

        }

        return 'ok';
    }
    
    // Папка  
    public function createDealFolders(Request $request)
    { 
        $deal_id = $request->input('id');

        if (!$deal_id) return 'error';
        
        $data = $this->amocrm->lead->apiList([
            'id' => $deal_id ,
            'limit_rows' => 1,
        ])[0];

        foreach ($data['custom_fields'] as $field) {
            if ((int) $field['id'] == 75429) {
                if ($field['values'][0]['value'] != '') {
                    return 'ok';
                }
            }
        }
         
        $deal = $this->amocrm->lead;

        $client = new \Google_Client();
        $client->setAuthConfig(storage_path(env('GOOGLE_API_KEY')));
        $client->addScope(\Google_Service_Drive::DRIVE);
        $service = new \Google_Service_Drive($client);
      

        $file = new \Google_Service_Drive_DriveFile([
            'parents' => ['16_u3j93RtbO-eCvpQS9Dw_OGAa3X_Bw5'],
            'name' => $data['name'],
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        // ПАПКА СДЕЛКИ
        $dealFolder = $service->files->create($file);
        $deal->addCustomField(75429, "https://drive.google.com/open?id=$dealFolder->id");
      
      
        // ПАПКА МЕНЕДЖЕРА
        $file->setName('1.1 ПАПКА МЕНЕДЖЕРА');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);
        $deal->addCustomField(75431, "https://drive.google.com/open?id=$folder->id");

        $tz = new \Google_Service_Drive_DriveFile([
            'parents' => [$folder->id],
            'name' => 'ТЗ.doc',
            'mimeType' => 'application/vnd.google-apps.document'
        ]);
      
        $fileTZ = $service->files->create($tz);
       	$deal->addCustomField(284979, "https://drive.google.com/open?id=$fileTZ->id");

        // ИСХОДНЫЕ ДОКУМЕНТЫ
        $file->setName('1.3 ИСХОДНЫЕ ДОКУМЕНТЫ');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);
        $deal->addCustomField(75435, "https://drive.google.com/open?id=$folder->id");

        // УСЛУГА
        $file->setName('1.4 УСЛУГА');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);

        // ОБСЛЕДОВАНИЕ ОБЪЕКТА
        $file->setName('1.2 ОБСЛЕДОВАНИЕ ОБЪЕКТА');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);
        $deal->addCustomField(75433, "https://drive.google.com/open?id=$folder->id");
        $file->setParents([$folder->id]);
        
        // ФОТООТЧЁТ
        $file->setName('1.2.1 ФОТООТЧЁТ');
        $folder = $service->files->create($file);
        
        // РЕЗЮМЕ
        $file->setName('1.2.2 РЕЗЮМЕ');
        $folder = $service->files->create($file);

        
        $deal->apiUpdate((int) $deal_id);
        
        return 'ok';
    }
    
    // ASANA
    public function createDealProject(Request $request)
    {   
        $deal_id = $request->input('deal');
        $project_id = $request->input('project');
        $task_id = $request->input('task');
        $section_id = $request->input('section');

        if (!$project_id || !$deal_id) {
            return 'error';
        }

        $amo = \App\AmoAPI::getInstance();

        $deal = $amo->request('/api/v2/leads','get', ['id'=>$deal_id])->_embedded->items[0];

        $description = $deal->name . '
';
        $description = $description . 'https://' . env('AMO_DOMAIN') . '.amocrm.ru/leads/detail/' . $deal->id . '
';

        foreach ($deal->custom_fields as $field) {

            if ((int) $field->id == 75401) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'География работ (адрес): ' . $field->values[0]->value;
                }
            }

            if ((int) $field->id == 75417) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'Информация по проекту: ' . $field->values[0]->value;
                }
            }

            if ((int) $field->id == 75429) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'Папка клиента: ' . $field->values[0]->value;
                }
            }

            if ((int) $field->id == 284979) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'ТЗ: ' . $field->values[0]->value;
                }
            }

            if ((int) $field->id == 290561) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'Дата начала подачи заявки: ' . $field->values[0]->value;
                }
            }

            if ((int) $field->id == 290563) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'Дата окончания подачи заявки: ' . $field->values[0]->value;
                }
            }

            if ((int) $field->id == 290557) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'Номер закупки: ' . $field->values[0]->value;
                }
            }

            if ((int) $field->id == 290559) {
                if ($field->values[0]->value != '') {
                    $description = $description . '
';
                    $description = $description . 'Ссылка на тендер ' . $field->values[0]->value;
                }
            }

        }

        $contacts = [];

        if (isset($deal->contacts->_links)) {
            $contacts = $amo->request($deal->contacts->_links->self->href)->_embedded->items;
        }

        $description = $description . '

';

        foreach ($contacts as $contact )
        {

            $description = $description . $contact->name . '
';

            foreach ($contact->custom_fields as $field )
            {
                if (isset($field->code) && $field->code == 'PHONE') {
                    foreach ( $field->values as $item )
                    {
                        $description = $description . '   ' . $item->value . '
';
                    }  
                }

                if (isset($field->code) && $field->code == 'EMAIL') {
                    foreach ( $field->values as $item )
                    {
                        $description = $description . '   ' . $item->value . '
';
                    }  
                }
            }
            $description = $description . '
';
        }

        $asana = new Curl();
        
        $asana->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $asana->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        $link = '';

        $new_project = [
            'gid' => 0,
            'responsible' => $deal->responsible_user_id,
            'type' => '',
            'deal_id' => $deal_id,
        ];


        if($task_id) {

            $data = [
                'name' => $deal->name,
                'include' => [
                    'notes',
                    'assignee',
                    'subtasks',
                    'attachments',
                    'tags',
                    'followers',
                    'projects',
                    'dates',
                    'parent',
                ]
            ];

            $res = $asana->post("https://app.asana.com/api/1.0/tasks/$task_id/duplicate", $data);
            
            $gid = $res->data->new_task->gid;
            
            $link = "https://app.asana.com/0/$project_id/$gid";
            
            if ($section_id) {
                $asana->post("https://app.asana.com/api/1.0/sections/$section_id/addTask", [
                    'task' => $gid
                ]);
            }

            $asana->post("https://app.asana.com/api/1.0/tasks/$gid/removeProject", [
                'project' => 1172502221110985
            ]);

            $asana->put("https://app.asana.com/api/1.0/tasks/$gid", [
                'notes' => $description,
                'due_on' => date('Y-m-d')
            ]);

            $new_project['gid'] = $gid;
            $new_project['type'] = 'task';

        } else {

            $data = [
                'name' => $deal->name,
                'include' => [
                    'task_notes',
                    'task_subtasks',
                    'task_projects',
                    'task_assignee',
                    'task_attachments',
                    'notes',
                ],
                'team' => '882014108971315'
            ];

            $template = $asana->get("https://app.asana.com/api/1.0/projects/$project_id");
        
            $res = $asana->post("https://app.asana.com/api/1.0/projects/$project_id/duplicate", $data);
            
            $gid = $res->data->new_project->gid;
            
            $link = 'https://app.asana.com/0/' . $gid;

            $new_project['gid'] = $gid;
            $new_project['type'] = 'project';
            
            $asana->put("https://app.asana.com/api/1.0/projects/$gid", [
                'notes' => $description,
                'color' => $template->data->color,
            ]);

        }

        
        $update_data = [
            'update' => []
        ];

        $update_data['update'][] = [
            'id' => $deal_id,
            'updated_at' => time(),
            'custom_fields' => [
                [
                    'id' => 75437,
                    'values' => [
                        ['value' => $link]
                    ]
                ]
            ] 
        ];

        $amo->request('/api/v2/leads', 'post', $update_data);
        
        return $new_project;
    }

    public function asanaWebhook(Request $request, $deal_id, $project_id)
    {
        $secret = $request->header('X-Hook-Secret');;
        $events = $request->input('events') ? $request->input('events') : [];

        // return $events;
        
        // $amo = \App\AmoAPI::getInstance();
        // $res = $amo->request('/api/v4/leads', 'get', ['id'=>$deal_id]);
        // $deal = $res->_embedded->leads[0];

        if ($secret) {
            if (!$deal_id) return response('No Content', 204)->header('X-Hook-Secret', $secret);
            return response('OK', 200)->header('X-Hook-Secret', $secret);
        } 

        $asana = new Curl();
        $asana->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $asana->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        $amo = \App\AmoAPI::getInstance();

        foreach ($events as $event) 
        {

            if ($event['action'] == 'sync_error') {
                $icq = new Curl();
                $icq->get('https://api.icq.net/bot/v1/messages/sendText', [
                    'token' => env('ICQ_TOKEN'),
                    'chatId' => env('ICQ_CHAT_ID'),
                    'text' => 'ASANA: ' . $event['message'],
                ]);
                
                continue;
            }

            $user_name = '';
            if (
                isset($event['user']) &&
                isset($event['user']['gid']) 
            ) {
                $user_name = $asana->get('https://app.asana.com/api/1.0/users/' . $event['user']['gid'])->data->name;
                // usleep(10);
            }

            $text = "ASANA: Пользователь $user_name";

            $change = isset($event['change']) ? $event['change'] : null;

            //$event['resource']['resource_type'] == 'attachment' прикрепил файл...

            // Перенос задачи в секцию 
            if (
                $event['action'] == 'added' && 
                $event['resource']['resource_type'] == 'task' && 
                $event['parent']['resource_type'] == 'section'
            ) {

                $task = $asana->get('https://app.asana.com/api/1.0/tasks/' . $event['resource']['gid']);
                $text = "$text перенёс задачу «{$task->data->name}»";

                $section = $asana->get('https://app.asana.com/api/1.0/sections/' . $event['parent']['gid']);

                $text = "$text в секцию «{$section->data->name}»";

                // $data_notes = [
                //     'add' => []
                // ];

                // $data_notes['add'][] = [
                //     'element_id' => $deal_id,
                //     'element_type' => 2,
                //     'note_type' => 4,
                //     'created_at' => time(),
                //     'text' => $text,
                // ];

                // $amo->request('/api/v2/notes', 'post', $data_notes);

                $data_notes = [];
                $data_notes[] = [
                    'note_type' => 'invoice_paid',
                    'params' => [
                        'text' => $text,
                        'service' => 'ASANA',
                        'icon_url' => 'https://bk-invent.ru/images/asana.png',
                    ]
                ];
                $amo->request("/api/v4/leads/$deal_id/notes", 'post', $data_notes);
            }

            // Комментарий добавлен 
            if ($event['action'] == 'added' && $event['resource']['resource_type'] == 'story' && $event['resource']['resource_subtype'] == 'comment_added') {

                $text = "$text добавил комментарий";

                $resource = $asana->get('https://app.asana.com/api/1.0/stories/' . $event['resource']['gid']);

                if ($resource->data->type == 'comment') {
                    $text = $text . ' "' . $resource->data->text . '"';
                }

                if ($event['parent']['resource_type'] == 'task') {
                    $task = $asana->get('https://app.asana.com/api/1.0/tasks/' . $event['parent']['gid']);

                    $text = "$text к задаче «{$task->data->name}»";
                }

                // $data_notes = [
                //     'add' => []
                // ];

                // $data_notes['add'][] = [
                //     'element_id' => $deal_id,
                //     'element_type' => 2,
                //     'note_type' => 4,
                //     'created_at' => time(),
                //     'text' => $text,
                // ];

                // $amo->request('/api/v2/notes', 'post', $data_notes);

                $data_notes = [];
                $data_notes[] = [
                    'note_type' => 'invoice_paid',
                    'params' => [
                        'text' => $text,
                        'service' => 'ASANA',
                        'icon_url' => 'https://bk-invent.ru/images/asana.png',
                    ]
                ];
                $amo->request("/api/v4/leads/$deal_id/notes", 'post', $data_notes);
            }

            // Задача закрыта
            if (isset($change['field'])) {
                if ($change['field'] == 'completed' && $event['resource']['resource_type'] == 'task') {

                    $resource = $asana->get('https://app.asana.com/api/1.0/tasks/' . $event['resource']['gid']);
    
                    $text = "$text закрыл задачу «{$resource->data->name}»";

                    // $data_notes = [
                    //     'add' => []
                    // ];

                    // $data_notes['add'][] = [
                    //     'element_id' => $deal_id,
                    //     'element_type' => 2,
                    //     'note_type' => 4,
                    //     'created_at' => time(),
                    //     'text' => $text,
                    // ];

                    // $amo->request('/api/v2/notes', 'post', $data_notes);

                    $data_notes = [];
                    $data_notes[] = [
                        'note_type' => 'invoice_paid',
                        'params' => [
                            'text' => $text,
                            'service' => 'ASANA',
                            'icon_url' => 'https://bk-invent.ru/images/asana.png',
                        ]
                    ];
                    $amo->request("/api/v4/leads/$deal_id/notes", 'post', $data_notes);
                }
            }

            usleep(50);

        }

        return 'ok';
    }

    public function updateDealProject(Request $request)
    {
        
        set_time_limit(0);

        $users = [];
        foreach (User::all() as $user) {
            $users[$user->email] = $user->asana_user_id;
        }

        $gid = $request->input('gid');
        $type = $request->input('type');
        $amo_user_id = $request->input('responsible');
        $deal_id = $request->input('deal_id');

        $asana = new Curl();
        $asana->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $asana->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        sleep(4);

        $asana_user_id = 0;
        $description = '';
        $tasks = [];

        if ($amo_user_id) {
            $user = User::where('amo_user_id', $amo_user_id)->first();

            if($user) {
                $asana_user_id = $user->asana_user_id;
            }
        }

        if ($type == 'project') {
            $project = $asana->get("https://app.asana.com/api/1.0/projects/$gid");

            $description = $project->data->notes;

            $tasks = $asana->get("https://app.asana.com/api/1.0/projects/$gid/tasks")->data;

        } else if ($type == 'task') {
            $project = $asana->get("https://app.asana.com/api/1.0/tasks/$gid");
            
            $description = $project->data->notes;

            $tasks = $asana->get("https://app.asana.com/api/1.0/tasks/$gid/subtasks")->data;
        }

        foreach ( $tasks as $task )
        {
            $rename = false;

            $data = [];

            $name = $task->name;

            $name = str_replace("%date%", "", $name, $count);
            if ($count > 0) {
                $data['due_on'] = date('Y-m-d');
                $rename = true;
            }

            // $name = str_replace("%date+1day%", "", $name, $count);
            // if ($count > 0) {
            //     $data['due_on'] = date("Y-m-d", microtime(true)+(60*60*24));
            //     $rename = true;
            // }

            $name = str_replace("%crm%", "", $name, $count);
            if ($count > 0) {
                if($asana_user_id) $data['assignee'] = $asana_user_id;
                $rename = true;
            }

            preg_match_all("/[-a-z0-9!#$&'*_`{|}~]+[-a-z0-9!#$%&'*_`{|}~\.=?]*@[a-zA-Z0-9_-]+[a-zA-Z0-9\._-]+/i", $name, $result);
            $result = $result[0];
            if (count($result)) {
                foreach ($result as $email) {
                    $name = str_replace("%$email%", "", $name, $count);
                    if ($count > 0) {
                        if($users[$email]) $data['assignee'] = $users[$email];
                        $rename = true;
                    }
                }
            }

            $name = str_replace("%description%", "", $name, $count);
            if ($count > 0) {
                $data['notes'] = $description;
                $rename = true;
            }

            if($rename) {
                $data['name'] = $name;
                $asana->put("https://app.asana.com/api/1.0/tasks/$task->gid", $data);
                usleep(50);
            } 
        }

        //TODO: проверить есть ли вебхук с $deal_id
        // Подписать на события
        $asana->post('https://app.asana.com/api/1.0/webhooks', [
            'target' => "https://private.bk-invent.ru/api/webhook/asana/$deal_id/$gid",
            'resource' => $gid,
        ]);

        return 'ok';
    }

    public function rawLead(Request $request)
    {        
         
        $lead_id = isset($request->input('leads')['add'][0]['id']) ? (int) $request->input('leads')['add'][0]['id'] : (int) $request->input('leads')['status'][0]['id'];
        
        $amo = \App\AmoAPI::getInstance();

        $deal = $amo->request('/api/v2/leads','get', ['id'=>$lead_id])->_embedded->items[0];

        $responsible = 0;

        $events = $amo->request('/api/v2/events','get', [
            'filter[entity_id]'=> $deal->main_contact->id,
            'filter[entity]' => 'contact'
        ])->_embedded->events;

        $incoming_calls = [];
        foreach ($events as $event) 
        {
            if ($event->type == 'incoming_call') {
                $incoming_calls[] = [
                    'id' => $event->value_after[0]->note->id,
                    'created_at' => $event->created_at
                ];
            }
        }

        usort($incoming_calls, function($a, $b) {
            return ($a['created_at'] > $b['created_at']);
        });

        foreach ($incoming_calls as $item) 
        {
            $note = $amo->request('/api/v2/notes','get', [
                'id'=>$item['id'],
            ])->_embedded->items[0];

            if (isset($note->params)) {
                if ($note->params->call_status == 4) {

                    $responsible = $note->responsible_user_id;

                    break; 
                }
            }
        }

        if ($responsible) {
            $deal_data = [
                'update' => []
            ];
            $deal_data['update'][] = [
                'id' => $lead_id,
                'updated_at' => time(),
                'responsible_user_id' => $responsible
            ];
    
            $amo->request('/api/v2/leads', 'post', $deal_data);

            $contact_data = [
                'update' => []
            ];
            $contact_data['update'][] = [
                'id' => $deal->main_contact->id,
                'updated_at' => time(),
                'responsible_user_id' => $responsible
            ];
    
            $amo->request('/api/v2/contacts', 'post', $contact_data);
        }
       
        return 'ok';
    }

    public function unsorted(Request $request) {

        if (!$request->phone) return ['error' => ''];

        $res = \App\AmoCRM::getInstance()->searchConcat($request->phone);

        if ($res) {
            return [
                'data' => $res
            ];
        }

        return ['error' => ''];

    }

    public function dd(Request $request)
    {
        $amo = \App\AmoAPI::getInstance();



        // $url = 'https://app.uiscom.ru/system/media/talk/1197843161/13f79da9cdad2dfaee5ef282712164a4/';
        // $contents = file_get_contents($url);
        // $name = '1.mp3';
        // Storage::put('public/mp3/' .  $name, $contents);;
        
        
        // $contents = Storage::get('public/m/1.mp3');
        // $mime = Storage::mimeType('public/m/1.mp3');
        // $response = Response::make($contents, 200);
        // $response->header('Content-Type', $mime);
        // // return $response;
        // return Storage::download('public/m/1.mp3', '1.mp3');
        // $url = Storage::url('file.jpg');
        // dd($url);


        $amo = \App\AmoAPI::getInstance();
    
        $data = [];

        $data[] = [
            "duration" => 1,
            "source" => "example_integration",
            "phone" => "79896231790",
            "link" => "https://bk-invent.ru/mlp/sn",
            "direction" => "outbound",
            "call_result"=> "Успешный разговор",
            "call_status" => 4
        ];


        // api/v4/contact/2211023/notes/{id}

        $res = $amo->request('api/v4/calls', 'post', $data);
        

        dd($res);

        // foreach ($calls->_embedded->events as $event) 
        // {

        // }

        return 'dd';
    }
}
