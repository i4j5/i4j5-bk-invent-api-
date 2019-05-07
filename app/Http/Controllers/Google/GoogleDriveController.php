<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use Illuminate\Http\Request;

class GoogleDriveController extends Controller
{
    private $service;
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $client = new \Google_Client();
        $client->setAuthConfig(storage_path( env('GOOGLE_API_KEY') ));
        $client->addScope(\Google_Service_Drive::DRIVE);
        $this->service = new \Google_Service_Drive($client);

        $this->amocrm = $amocrm;
       
    }

    public function amoWebhook(Request $request)
    {        
        $lead_id = $request->input('id') ? (int) $request->input('id') : (int) $request->input('leads')['status'][0]['id'];     
        
        if ($lead_id) {
            $this->createLeadFolders($lead_id);
        }
    }

    private function createLeadFolders($id = null)
    {
        $lead_id = $id;

        $data = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ], '-100 DAYS')[0];

        $lead_name = $data['name'];

        foreach ( $data['custom_fields'] as $field )
        {
            if ((int) $field['id'] == 223913) {
                if ($field['values'][0]['value'] != '') {
                    return;
                } 
            }
        }
        
        $lead = $this->amocrm->lead;

        $file = new \Google_Service_Drive_DriveFile([
            'parents' => ['16_u3j93RtbO-eCvpQS9Dw_OGAa3X_Bw5'],
            'name' => $lead_name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);
        
        // ПАПКА СДЕЛКИ
        $leadFolder = $this->service->files->create($file);
        $lead->addCustomField(223913, "https://drive.google.com/open?id=$leadFolder->id");
        
        // ПАПКА МЕНЕДЖЕРА
        $file->setName('1.1 ПАПКА МЕНЕДЖЕРА');
        $file->setParents([$leadFolder->id]);
        $folder = $this->service->files->create($file);
        $lead->addCustomField(226185, "https://drive.google.com/open?id=$folder->id");

        // ИСХОДНЫЕ ДОКУМЕНТЫ
        $file->setName('1.3 ИСХОДНЫЕ ДОКУМЕНТЫ');
        $file->setParents([$leadFolder->id]);
        $folder = $this->service->files->create($file);
        $lead->addCustomField(236989, "https://drive.google.com/open?id=$folder->id");

        // УСЛУГА
        $file->setName('1.4 УСЛУГА');
        $file->setParents([$leadFolder->id]);
        $folder = $this->service->files->create($file);

        // ОБСЛЕДОВАНИЕ ОБЪЕКТА
        $file->setName('1.2 ОБСЛЕДОВАНИЕ ОБЪЕКТА');
        $file->setParents([$leadFolder->id]);
        $folder = $this->service->files->create($file);
        $lead->addCustomField(234517, "https://drive.google.com/open?id=$folder->id");
        $file->setParents([$folder->id]);
        // ФОТО_АДРЕС
        $file->setName('1.2.1 ФОТО_АДРЕС');
        $folder = $this->service->files->create($file);
        // РЕЗЮМЕ_АДРЕС
        $file->setName('1.2.2 РЕЗЮМЕ_АДРЕС');
        $folder = $this->service->files->create($file);
        
        // Заносим данные в amoCRM
        $lead->apiUpdate((int) $lead_id, 'now');
    }
}
