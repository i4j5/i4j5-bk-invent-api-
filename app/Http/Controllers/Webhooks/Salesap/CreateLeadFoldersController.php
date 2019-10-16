<?php

namespace App\Http\Controllers\Webhooks\Salesap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SalesapAPI;

/**
 * WebHook
 * SalesapCRM
 * Создание папки сделки на Google Drive
 */
class CreateLeadFoldersController extends Controller
{
    private $service;
    private $crm;

    public function __construct()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(storage_path( env('GOOGLE_API_KEY') ));
        $client->addScope(\Google_Service_Drive::DRIVE);
        $this->service = new \Google_Service_Drive($client);

        $this->crm = SalesapAPI::getInstance();
    }

    public function handle(Request $request)
    {        
        $deal = $request->all();    
        
        if (isset($deal['type']) && $deal['type'] == 'Deal')
        {
            return $this->create($deal);
        }
        
        return 'error';
    }

    private function create($deal = [])
    {
        if($deal['data']['custom_48203'] != null) return 'ok';
        
        $data = [];
        
        $file = new \Google_Service_Drive_DriveFile([
            'parents' => ['16_u3j93RtbO-eCvpQS9Dw_OGAa3X_Bw5'],
            'name' => $deal['data']['name'],
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);
        
        // ПАПКА СДЕЛКИ
        $dealFolder = $this->service->files->create($file);
        $data['custom-48203'] = "https://drive.google.com/open?id=$dealFolder->id";
        
        // ПАПКА МЕНЕДЖЕРА
        $file->setName('1.1 ПАПКА МЕНЕДЖЕРА');
        $file->setParents([$dealFolder->id]);
        $folder = $this->service->files->create($file);
        $data['custom-48204'] = "https://drive.google.com/open?id=$folder->id";

        // ИСХОДНЫЕ ДОКУМЕНТЫ
        $file->setName('1.3 ИСХОДНЫЕ ДОКУМЕНТЫ');
        $file->setParents([$dealFolder->id]);
        $folder = $this->service->files->create($file);
        $data['custom-48206'] = "https://drive.google.com/open?id=$folder->id";

        // УСЛУГА
        $file->setName('1.4 УСЛУГА');
        $file->setParents([$dealFolder->id]);
        $folder = $this->service->files->create($file);

        // ОБСЛЕДОВАНИЕ ОБЪЕКТА
        $file->setName('1.2 ОБСЛЕДОВАНИЕ ОБЪЕКТА');
        $file->setParents([$dealFolder->id]);
        $folder = $this->service->files->create($file);
        $data['custom-48205'] = "https://drive.google.com/open?id=$folder->id";
        $file->setParents([$folder->id]);
        // ФОТО_АДРЕС
        $file->setName('1.2.1 ФОТО_АДРЕС');
        $folder = $this->service->files->create($file);
        // РЕЗЮМЕ_АДРЕС
        $file->setName('1.2.2 РЕЗЮМЕ_АДРЕС');
        $folder = $this->service->files->create($file);
        
        // Заносим данные в CRM
        $this->crm->editDeal($deal['data']['id'], ['customs' => $data]);
        
        return 'ok';
    }
}