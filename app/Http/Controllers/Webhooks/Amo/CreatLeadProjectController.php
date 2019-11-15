<?php

namespace App\Http\Controllers\Webhooks\Amo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use \Curl\Curl;

/**
 * WebHook
 * amoCRM
 * Создание проекта в asana
 */
class CreatLeadProjectController extends Controller
{
    private $amocrm;
    private $curl;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
        
        $this->curl = new Curl();
        $this->curl->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $this->curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    public function handle(Request $request)
    {        
        
        $lead_id = $request->input('lead_id');
        $template_id = $request->input('template_id');
        
        if (!$template_id || !$lead_id) {
            return 'error';
        }
        
        $lead = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ])[0];
        
        
        $description  = '';
        
        foreach ($lead['custom_fields'] as $field) {
            if ((int) $field['id'] == 235541) {
                if ($field['values'][0]['value'] != '') {
                    return 'ok';
                }
            }
            
            if ((int) $field['id'] == 235561) {
                if ($field['values'][0]['value'] != '') {
                   $description = $description . '
 География работ (адрес): ' . $field['values'][0]['value'];
                }
            }
            
            if ((int) $field['id'] == 235403) {
                if ($field['values'][0]['value'] != '') {
                   $description = $description . '
 Информация по проекту: ' . $field['values'][0]['value'];
                }
            }
            
            if ((int) $field['id'] == 235511) {
                if ($field['values'][0]['value'] != '') {
                   $description = $description . '
 Папка клиента: ' . $field['values'][0]['value'];
                }
            }
//            if ((int) $field['id'] == 235513) {
//                if ($field['values'][0]['value'] != '') {
//                   $description = $description . '
// Папка менеджера: ' . $field['values'][0]['value'];
//                }
//            }
//            if ((int) $field['id'] == 235515) {
//                if ($field['values'][0]['value'] != '') {
//                   $description = $description . '
// Исследование объекта: ' . $field['values'][0]['value'];
//                }
//            }
//            if ((int) $field['id'] == 235527) {
//                if ($field['values'][0]['value'] != '') {
//                   $description = $description . '
// Исходные документы: ' . $field['values'][0]['value'];
//                }
//            }
        }
        
        
        
        $contacts = $this->amocrm->links->apiList([
           'from' => 'leads',
           'from_id' => $lead_id,
           'to' => 'contacts'
        ]);   
        
                    
        $description = $description . '
 ';
        
        foreach ($contacts as $contact) {
            $contact = $this->amocrm->contact->apiList([
                'id' => $contact['to_id'],
                'limit_rows' => 1,
            ])[0];
                    
             
            $description = $description . '
 ' . $contact['name'] . '
 ';

            foreach ($contact['custom_fields'] as $field) {
                if (isset($field['code']) && $field['code'] == 'PHONE') {
                    foreach ($field['values'] as $item) {
                        if ($item['value'])
                            $description = $description . '' . $item['value'] . ' ';
                    }
                }

                if (isset($field['code']) && $field['code'] == 'EMAIL') {
                    
                    foreach ($field['values'] as $item) {;
                        if ($item['value'])
                            $description = $description . '' . $item['value'] . ' ';
                    }
                }
            }

        }
        
        $data = [
            'name' => $lead['name'],
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
        
        $template = $this->curl->get("https://app.asana.com/api/1.0/projects/$template_id");
        
        //var_dump($template->data->color);
        //exit;
        
        $res = $this->curl->post("https://app.asana.com/api/1.0/projects/$template_id/duplicate", $data);
        
        $gid = $res->data->new_project->gid;
        
        $link = 'https://app.asana.com/0/' . $gid;
        
        $this->curl->put("https://app.asana.com/api/1.0/projects/$gid", [
            'notes' => $description,
            'color' => $template->data->color
        ]);
        
        // Заносим данные в CRM
        $lead = $this->amocrm->lead;
        $lead->addCustomField(235541, $link);
        $lead->apiUpdate((int) $lead_id, 'now');
        
        return 'ok';
    }
}