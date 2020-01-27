<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use App\Models\Lead;


class Bitrix24Controller extends Controller
{
    public $bitrix24;
    
    public function __construct()
    {
        $this->bitrix24 = new Curl(env('BTRIX24_URL'));
    }

    public function createDealMainResponsible(Request $request)
    {        
        $id = $request->input('id');
        
        $responsible = $this->bitrix24->post('crm.deal.get.json', [
            'id' => $id
        ])->result->ASSIGNED_BY_ID;

        $contacts = $this->bitrix24->post('crm.deal.contact.items.get.json', [
            'id' => $id
        ])->result;

        foreach ( $contacts as $contact )
        {
            $this->bitrix24->post('crm.contact.update.json', [
                'id' => $contact->CONTACT_ID,
                'fields' => [
                    'ASSIGNED_BY_ID' => $responsible
                ]
            ]);
        }
    }
    
    // Папка  
    public function createDealFolders(Request $request)
    { 
        //if( isset($request->input('id')) ) return false;
        
        $id = $request->input('id');
        
        $deal = $this->bitrix24->post('crm.deal.get.json', [
            'id' => $id
        ])->result;
        
        //dd($deal);
        
        if($deal->UF_CRM_AMO_235511 != '') return 'ok';
        
        $data = [
            'id' => $id
        ];
        
        $deal = $this->bitrix24->post('crm.deal.get.json', [
            'id' => $id
        ])->result;
         
        ///////
        $client = new \Google_Client();
        $client->setAuthConfig(storage_path(env('GOOGLE_API_KEY')));
        $client->addScope(\Google_Service_Drive::DRIVE);
        $service = new \Google_Service_Drive($client);
        
        $file = new \Google_Service_Drive_DriveFile([
            'parents' => ['16_u3j93RtbO-eCvpQS9Dw_OGAa3X_Bw5'],
            'name' => $deal->TITLE,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);

        // ПАПКА СДЕЛКИ
        $dealFolder = $service->files->create($file);
        $data['fields']['UF_CRM_AMO_235511'] = "https://drive.google.com/open?id=$dealFolder->id";

        // ПАПКА МЕНЕДЖЕРА
        $file->setName('1.1 ПАПКА МЕНЕДЖЕРА');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);
        $data['fields']['UF_CRM_AMO_235513'] = "https://drive.google.com/open?id=$folder->id";

        // ИСХОДНЫЕ ДОКУМЕНТЫ
        $file->setName('1.3 ИСХОДНЫЕ ДОКУМЕНТЫ');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);
        $data['fields']['UF_CRM_AMO_235527'] = "https://drive.google.com/open?id=$folder->id";

        // УСЛУГА
        $file->setName('1.4 УСЛУГА');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);

        // ОБСЛЕДОВАНИЕ ОБЪЕКТА
        $file->setName('1.2 ОБСЛЕДОВАНИЕ ОБЪЕКТА');
        $file->setParents([$dealFolder->id]);
        $folder = $service->files->create($file);
        $data['fields']['UF_CRM_AMO_235515'] = "https://drive.google.com/open?id=$folder->id";
        $file->setParents([$folder->id]);
        // ФОТО_АДРЕС
        $file->setName('1.2.1 ФОТО_АДРЕС');
        $folder = $service->files->create($file);
        // РЕЗЮМЕ_АДРЕС
        $file->setName('1.2.2 РЕЗЮМЕ_АДРЕС');
        $folder = $service->files->create($file);
        
        
        $this->bitrix24->post('crm.deal.update.json', $data)->result;
        
        return 'ok';
    }
    
    // ASANA
    public function сreatDealProject(Request $request)
    {        
        
        $deal_id = $request->input('deal_id');
        $template_id = $request->input('template_id');
        
        if (!$template_id || !$deal_id) {
            return 'error';
        }
        
        $deal = $this->bitrix24->post('crm.deal.get.json', [
            'id' => $deal_id
        ])->result;
        
       // dd($deal);
        
        if($deal->UF_CRM_AMO_235541 != '') return 'ok';
        
        $description  = '';
        
        if($deal->UF_CRM_AMO_235561 != '') {
            $description = $description . '
 География работ (адрес): ' . $deal->UF_CRM_AMO_235561;            
        }
        
        if($deal->UF_CRM_AMO_235561 != '') {
            $description = $description . '
 Информация по проекту: ' . $deal->UF_CRM_AMO_235403;            
        }

        if($deal->UF_CRM_AMO_235561 != '') {
            $description = $description . '
 Папка клиента: ' . $deal->UF_CRM_AMO_235511;            
        }        
        
        $description = $description . '
 ';        
        
        $contacts = $this->bitrix24->post('crm.deal.contact.items.get.json', [
            'id' => $deal_id
        ])->result;

        foreach ($contacts as $contact) {
            $res = $this->bitrix24->post('crm.contact.get.json', [
                'id' => $contact->CONTACT_ID
            ])->result;
            
            $description = $description . '
 ' . "$res->LAST_NAME $res->NAME $res->SECOND_NAME". '
 ';            
            if (isset($res->PHONE)) {
                foreach ($res->PHONE as $phone) {
                    $description = $description . '' . $phone->VALUE . ' ';
                }
            }
            
            if (isset($res->EMAIL)) {
                foreach ($res->EMAIL as $email) {
                    $description = $description . '' . $email->VALUE . ' ';
                }
            }
        }
      
        $data = [
            'name' => $deal->TITLE,
            'include' => [
                'task_notes',
                'task_subtasks',
                'task_projects',
                'task_assignee',
                'task_attachments',
                'notes',
                //'color',
            ],
            'team' => '882014108971315'
        ];
        
        $asana = new Curl();
        
        $asana->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $asana->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        $template = $asana->get("https://app.asana.com/api/1.0/projects/$template_id");
        
        $res = $asana->post("https://app.asana.com/api/1.0/projects/$template_id/duplicate", $data);
        
        
        $gid = $res->data->new_project->gid;
        
        $link = 'https://app.asana.com/0/' . $gid;
        
        $asana->put("https://app.asana.com/api/1.0/projects/$gid", [
            'notes' => $description,
            'color' => $template->data->color
        ]);
        
        // Заносим данные в CRM
        $this->bitrix24->post('crm.deal.update.json', [
            'id' => $deal_id,
            'fields' => [
               'UF_CRM_AMO_235541' => $link
            ]
        ])->result;
        
        return 'ok';
    }
    
    public function complementDeal(Request $request)
    {        
        $id = $request->get('id');
        
        $deal = $this->bitrix24->post('crm.deal.get.json', [
            'id' => $id
        ])->result;
        
        $visitor_id = $deal->UF_CRM_1576676929;
        $hit_id = $deal->UF_CRM_1576677010;
        $session_id = $deal->UF_CRM_1576677025;
        
        $hash_id = md5($visitor_id . $session_id);
        
        $leads = Lead::where('hash_id', $hash_id)->get();
        
        
        $comments = ''; 
        
        foreach ($leads as $lead) {
            if ($lead->deal_id == 0) {
                $comments = $comments . '<hr>' . $lead->comment;
                 
                $lead->deal_id = $id;
                $lead->save();
            } 
        }
        
        if ($comments != '') {
            
            $this->bitrix24->post('crm.deal.update.json', [
                'id' => $id,
                'fields' => [
                   'COMMENTS' => $deal->COMMENTS . $comments
                ]
            ]);
        }
        
        return 'ok';
    }
    

}
