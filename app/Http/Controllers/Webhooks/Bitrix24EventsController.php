<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;


class Bitrix24EventsController extends Controller
{
    public $bitrix24;
    
    public function __construct()
    {
        $this->bitrix24 = new Curl(env('BTRIX24_URL'));
    }

    public function onCrmDealUpdate(Request $request)
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

}
