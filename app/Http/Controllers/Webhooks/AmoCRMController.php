<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use Dotzero\LaravelAmoCrm\AmoCrmManager;


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
    public function сreatDealProject(Request $request)
    {        
        
        $deal_id = $request->input('deal_id');
        $template_id = $request->input('template_id');
        
        if (!$template_id || !$deal_id) {
            return 'error';
        }
        
        $deal = $this->amocrm->lead->apiList([
            'id' => $deal_id ,
            'limit_rows' => 1,
        ])[0];

        foreach ($deal['custom_fields'] as $field) {
            if ((int) $field['id'] == 75437) {
                if ($field['values'][0]['value'] != '') {
                    return 'ok';
                }
            }
        }

        $description  = '';

        foreach ($deal['custom_fields'] as $field) {

            if ((int) $field['id'] == 75401) {
                if ($field['values'][0]['value'] != '') {
                    $description = $description . '
';
                    $description = $description . 'География работ (адрес): ' . $field['values'][0]['value'];
                }
            }

            if ((int) $field['id'] == 75417) {
                if ($field['values'][0]['value'] != '') {
                    $description = $description . '
';
                    $description = $description . 'Информация по проекту: ' . $field['values'][0]['value'];
                }
            }

            if ((int) $field['id'] == 75429) {
                if ($field['values'][0]['value'] != '') {
                    $description = $description . '
';
                    $description = $description . 'Папка клиента: ' . $field['values'][0]['value'];
                }
            }
        }
        

        if(isset($deal['main_contact_id'])) {

            $contact = $this->amocrm->contact->apiList([
                'id' => $deal['main_contact_id'],
                'limit_rows' => 1,
                'type' => 'contact' 
            ])[0];

            $description = $description . '
';

            $description = $description . $contact['name'];

            foreach ( $contact['custom_fields'] as $field )
            {
                if (isset($field['code']) && $field['code'] == 'PHONE') {
                    foreach ( $field['values'] as $item )
                    {

                        $description = $description . ' ' . $item['value'] . ' ';
                    }  
                }

                if (isset($field['code']) && $field['code'] == 'EMAIL') {
                    foreach ( $field['values'] as $item )
                    {

                        $description = $description . ' ' . $item['value'] . ' ';
                    }  
                }
            }

        }

      
        $data = [
            'name' => $deal['name'],
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
        
        
        $asana = new Curl();
        
        $asana->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $asana->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        $template = $asana->get("https://app.asana.com/api/1.0/projects/$template_id");
        
        $res = $asana->post("https://app.asana.com/api/1.0/projects/$template_id/duplicate", $data);
        
        $gid = $res->data->new_project->gid;
        
        $link = 'https://app.asana.com/0/' . $gid;
        
        $a = $asana->put("https://app.asana.com/api/1.0/projects/$gid", [
            'notes' => $description,
            'color' => $template->data->color,
        ]);
        
        // Заносим данные в CRM
        $this->amocrm->lead
            ->addCustomField(75437, $link)
            ->apiUpdate((int) $deal_id);
        
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
        }e


       //$contact->addCustomField('95354', $dataPhones);
       //$contact->apiUpdate((int) $contact_id, 'now');
       
       return 'ok' ;
    }



}
