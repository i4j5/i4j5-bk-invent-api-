<?php

namespace App\Http\Controllers\Webhooks\Salesap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SalesapAPI;
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

    public function __construct()
    {
        $this->crm = SalesapAPI::getInstance();
        
        $this->curl = new Curl();
        $this->curl->setHeader('Authorization', 'Bearer ' . env('ASANA_KEY'));
        $this->curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');
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
        if ($deal['data']['custom_48203'] != null) return 'ok';
        
        if ($deal['data']['custom_49670'] == null) return 'error';
        
        //Получать данные
        // crm  => asana 
        $tpl = [
            
        ];
        
        $tplID = $tpl[ $deal['data']['custom_49670'] ];
        
        $data = [
            'name' => $deal['data']['name'],
            'include' => [
                'task_notes',
                'task_subtasks',
                'task_projects',
                'task_assignee',
                'task_attachments',
                'notes',
            ],
            'team' => '1571928436701'
        ];
        
        //...
        
        $this->curl->post("https://app.asana.com/api/1.0/projects/$tplID/duplicate", $data);
        
        $link = '';
        
        // Заносим данные в CRM
        $this->crm->editDeal($deal['data']['id'], ['customs' => ['custom-48207' => $link]]);
        
        return 'ok';
    }
}