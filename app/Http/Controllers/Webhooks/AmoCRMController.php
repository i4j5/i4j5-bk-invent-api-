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
        //if( isset($request->input('id')) ) return false;
        
        $deal_id = $request->input('id');
        
        $deal = $this->amocrm->lead->apiList([
            'id' => $deal_id ,
            'limit_rows' => 1,
        ])[0];

        foreach ($deal['custom_fields'] as $field) {
            if ((int) $field['id'] == 75429) {
                if ($field['values'][0]['value'] != '') {
                    return 'ok';
                }
            }
        }
         
        ///////
        $client = new \Google_Client();
        $client->setAuthConfig(storage_path(env('GOOGLE_API_KEY')));
        $client->addScope(\Google_Service_Drive::DRIVE);
        $service = new \Google_Service_Drive($client);
        
        $file = new \Google_Service_Drive_DriveFile([
            'parents' => ['16_u3j93RtbO-eCvpQS9Dw_OGAa3X_Bw5'],
            'name' => $deal['name'],
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
        
        $deal->apiAdd();
        
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
 География работ (адрес): ' . $field['values'][0]['value'];
                }
            }

            if ((int) $field['id'] == 75417) {
                if ($field['values'][0]['value'] != '') {
                    $description = $description . '
 Информация по проекту: ' . $field['values'][0]['value'];
                }
            }

            if ((int) $field['id'] == 75429) {
                if ($field['values'][0]['value'] != '') {
                    $description = $description . '
 Папка клиента: ' . $field['values'][0]['value'];
                }
            }

            $description = $description . '
            ';   

        }

              
        
//         $contacts = $this->bitrix24->post('crm.deal.contact.items.get.json', [
//             'id' => $deal_id
//         ])->result;

//         foreach ($contacts as $contact) {
//             $res = $this->bitrix24->post('crm.contact.get.json', [
//                 'id' => $contact->CONTACT_ID
//             ])->result;
            
//             $description = $description . '
//  ' . "$res->LAST_NAME $res->NAME $res->SECOND_NAME". '
//  ';            
//             if (isset($res->PHONE)) {
//                 foreach ($res->PHONE as $phone) {
//                     $description = $description . '' . $phone->VALUE . ' ';
//                 }
//             }
            
//             if (isset($res->EMAIL)) {
//                 foreach ($res->EMAIL as $email) {
//                     $description = $description . '' . $email->VALUE . ' ';
//                 }
//             }
//         }
      
        $data = [
            'name' => $deal['name'],
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
        $deal->addCustomField(75437, $link)->apiAdd();
        
        return 'ok';
    }

}
