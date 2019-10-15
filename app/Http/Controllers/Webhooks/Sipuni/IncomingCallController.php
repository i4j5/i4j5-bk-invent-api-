<?php

namespace App\Http\Controllers\Webhooks\Sipuni;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SalesapAPI;

/**
 * WebHook
 * Sipuni
 * Обработка входящих звонков
 */
class IncomingCallController extends Controller
{
    protected $crm;
    
    public function __construct() {
        $this->crm = SalesapAPI::getInstance();
    }

    public function handle(Request $request)
    {        
        $data = $request->all();
        if(isset($data['fromnum']))
        {
            return response()->json($this->crm->sipuni($data['fromnum']));
        }
        
        return response()->json([ 
            'name' => '',
            'choince' => '0',
            'number' => '',
            'timeout' => '0',
        ]);
        
    }
    
}