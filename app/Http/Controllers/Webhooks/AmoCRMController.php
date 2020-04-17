<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use App\Models\User;


class AmoCRMController extends Controller
{
    
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
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

        // dd($data);
         
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
        
        // ФОТО_АДРЕС
        $file->setName('1.2.1 ФОТО_АДРЕС');
        $folder = $service->files->create($file);
        
        // РЕЗЮМЕ_АДРЕС
        $file->setName('1.2.2 РЕЗЮМЕ_АДРЕС');
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

        }

        $contacts = $amo->request($deal->contacts->_links->self->href)->_embedded->items;

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
            'type' => ''
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
            
            if($section_id) {
                $asana->post("https://app.asana.com/api/1.0/sections/$section_id/addTask", [
                    'task' => $gid
                ]);
            }

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

    public function updateDealProject(Request $request)
    {
        set_time_limit(0);
        sleep(10);

        $gid = $request->input('gid');
        $type = $request->input('type');
        $amo_user_id = $request->input('responsible');

        $asana_user_id = 0;
        $description = '';
        $tasks = [];

        if ($amo_user_id) {
            $user = User::where('amo_user_id', $amo_user_id)->first();

            if($user) {
                $asana_user_id = $user->asana_user_id;
            }
        }

        $asana = new Curl();
        $asana->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $asana->setHeader('Content-Type', 'application/x-www-form-urlencoded');

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

        return 'ok';

    }

    public function rawLead(Request $request)
    {        
        $lead_id = isset($request->input('leads')['add'][0]['id']) ? (int) $request->input('leads')['add'][0]['id'] : (int) $request->input('leads')['status'][0]['id'];

        $data = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ])[0];

        $tags = [];
        foreach ( $data['tags'] as $tag )
        {
            array_push($tags, $tag['name']);
        }
        
        $contact_id = $data['main_contact_id'];

        $data = $this->amocrm->contact->apiList([
            'id' => $contact_id,
            'limit_rows' => 1,
        ])[0];

        $contact = $this->amocrm->contact;

        $phones = [];
        $dataPhones = [];
        foreach ( $data['custom_fields'] as $field )
        {
            if (isset($field['code']) && $field['code'] == 'PHONE') {

                foreach ( $field['values'] as $item )
                {
                    $phone = $item['value'];
                    $enum = $item['enum'];

                    // $res = Phone::getInstance()->fix($phone, $enum);

                    // \App\AmoCRM::getInstance()->addLead($data);

                    // $dataPhones[] = [$res['phone'], $res['enum']];
                    $phones[] = $phone;
                }  
            }
        }

        $double = false;

        foreach ( $phones as $phone ) {

            $items = $this->amocrm->contact->apiList([
                'query' => $phone,
                'type' => 'contact',
            ]);

            //if($double) return;
            
            foreach ( $items as $item )
            {
                
                if ($item['id'] == $contact_id) break;
                
                foreach ( $item['custom_fields'] as $field )
                {
                    if (isset($field['code']) && $field['code'] == 'PHONE') {
                        foreach ( $field['values'] as $el )
                        {
                            if (in_array($el['value'], $phones)) {
                                $double = true;
                                break(4);
                            }
                        }  
                    }
                }
            }
           
        }

        if($double) {
            // Добавить тек к сделке 
            $lead = $this->amocrm->lead;
            array_push($tags, 'Дубль');
            $lead['tags'] = $tags;
            $lead->apiUpdate((int) $lead_id, 'now');
            return 'Дубль - ' . $lead_id;
        }


       //$contact->addCustomField('95354', $dataPhones);
       //$contact->apiUpdate((int) $contact_id, 'now');
       
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
}
