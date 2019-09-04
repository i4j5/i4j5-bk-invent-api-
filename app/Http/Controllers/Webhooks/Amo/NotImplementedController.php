<?php

namespace App\Http\Controllers\Webhooks\Amo;

use App\Http\Controllers\Controller;
use Dotzero\LaravelAmoCrm\AmoCrmManager;
use Illuminate\Http\Request;

/**
 * WebHook
 * amoCRM
 * При переход на этап ЗАКРЫТО И НЕ РЕАЛИЗОВАНО
 */
class NotImplementedController extends Controller
{
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->amocrm = $amocrm;
    }

    public function handle(Request $request)
    {        
        $lead_id = (int) $request->input('leads')['status'][0]['id'];     
        
        $data = $this->amocrm->lead->apiList([
            'id' => $lead_id,
            'limit_rows' => 1,
        ])[0];
        
        if($data['loss_reason_id'] == 1751311 || $data['loss_reason_id'] == 3341590) 
        {
            $lead = $this->amocrm->lead;
            $lead->addCustomField(241507, true);
            $lead->apiUpdate($lead_id, 'now');
        }

    }
}