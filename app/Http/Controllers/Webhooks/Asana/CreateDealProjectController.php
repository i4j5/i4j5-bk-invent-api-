<?php

namespace App\Http\Controllers\Webhooks\Salesap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use \Curl\Curl;

/**
 * WebHook
 * SalesapCRM
 * Создание проекта  в asana
 */
class CreateDealProjectController extends Controller
{
    private $crm;
    private $curl;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->crm = $amocrm;
        
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
        
        // Проверка есть ли лид

        foreach ($lead['custom_fields'] as $field) {
            if ((int) $field['id'] == 235541) {
                if ($field['values'][0]['value'] != '') {
                    return 'ok';
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
            ],
            'team' => '882014108971315'
        ];
        
        //...
        
        $res = $this->curl->post("https://app.asana.com/api/1.0/projects/$template_id/duplicate", $data);
        
        dd($res);
        
        $link = '';
        
        // Заносим данные в CRM
        $lead = $this->amocrm->lead;
        $lead->addCustomField(235541, $link);
        //$lead->apiUpdate((int) $lead_id, 'now');
        
        return 'ok';
    }
}