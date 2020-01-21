<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use App\Models\Visit;
use App\Models\Number;
use App\Models\Call;
use App\Bitrix24;

class AnalyticController extends Controller
{

    public function __construct()
    {
        
    }
    
    public function createVisit(Request $request)
    {
        
        $data = [
            'google_client_id' => '',
            'metrika_client_id' => '',
            
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_content' => '',
            'utm_term' => '',
            
            'landing_page' => '',
            'referrer' => '',
            'trace' => '',
            
            'first_visit' => 0
        ];
        
        $request->utm_source ? $data['utm_source'] = $request->utm_source : false;
        $request->utm_medium ? $data['utm_medium'] = $request->utm_medium : false;
        $request->utm_campaign ? $data['utm_campaign'] = $request->utm_campaign : false;
        $request->utm_content ? $data['utm_content'] = $request->utm_content : false;
        $request->utm_term ? $data['utm_term'] = $request->utm_term : false;
        
        $request->google_client_id ? $data['google_client_id'] = $request->google_client_id : false;
        $request->metrika_client_id ? $data['metrika_client_id'] = $request->metrika_client_id : false;
        
        $request->landing_page ? $data['landing_page'] = $request->landing_page : false;
        $request->referrer ? $data['referrer'] = $request->referrer : false;
        $request->trace ? $data['trace'] = $request->trace : false;
        
        $request->first_visit ? $data['first_visit'] = $request->first_visit : false;
        
        
        $visit = Visit::create([
            'first_visit' => $first_visit, 
            'google_client_id' => $google_client_id,
            'metrika_client_id' => $metrika_client_id,
            'landing_page' => $landing_page, 
            'referrer' => $referrer,
            'utm_medium' => $utm_medium, 
            'utm_sourse' =>  $utm_source, 
            'utm_campaign' => $utm_campaign, 
            'utm_term' => $utm_term, 
            'utm_content' => $utm_content,
            'trace' => $trace,
        ]);
        
        if ($data['first_visit'] == 0) {
            $visit->first_visit = $visit->id;
            $visit->save();
        } 
        
        return [
            'visit' => $visit->id,
            'first_visit' => $visit->first_visit,
        ];
    }
    
    public function updateTrace(Request $request)
    {
        $visit_id = $request->visit;
        $trace = $request->visit;
        
        if(!$visit_id && !$trace) return 'ok';
        
        $visit = Visit::find($visit_id);
        
        $visit->trace = $visit;
        
        $visit->save();
        
        return 'ok';
    }
    
    public function reservationNumber(Request $request)
    {
        $visit_id = $request->visit;
        
        //Проверка visit по id
        
        $now = date('Y-m-d H:i:s', time());
        
        $number = Number::where('visit_id', $visit_id)->first();
        
        if(!$number) {
            $number = Number::where([['reservation_at', '<', $now], ['type', '=' ,1]])->first();
        }
        
        if($number){
            
            $reservation_at = date('Y-m-d H:i:s', time() + (15 * 60));
            
            $number->reservation_at = $reservation_at;
            $number->visit_id = $visit_id;
            $number->save();

            return [
                'number' => $number->number,
                'ttl' => $reservation_at,
            ];
        }
        
        return [
            'error' => ''
        ];
    }
    
    public function createCall(Request $request) {
        
        if ($request->event != 'NOTIFY_START') return 'ok';
        
        $caller = $request->caller_id; //номер звонящего
        $callee = $request->called_did; //номер, на который позвонили
        
        $number = Number::where('number', $callee)->first();
        
        $visit_id = $number->visit_id;
        
        $data = [
            'phone' => $caller,
            'title' => 'Входящий звонок',
            'source' => 'CALL',
        ];
        
        if ($number->type = 2) {
            $data['utm_sourse'] = $number->sourse;
        } else {
            
            $visit = Visit::find($visit_id);
            
            $data = array_merge($data, [
                'google_client_id' => $visit->google_client_id,
                'metrika_client_id' => $visit->metrika_client_id,
                'landing_page' => $visit->landing_page, 
                'referrer' => $visit->referrer,
                'utm_medium' => $visit->utm_medium, 
                'utm_sourse' =>  $visit->utm_source, 
                'utm_campaign' => $visit->utm_campaign, 
                'utm_term' => $visit->utm_term, 
                'utm_content' => $visit->utm_content,
                'trace' => $visit->trace,
            ]);
        }
        
        Visit::create([
            'caller' => $caller,
            'callee' => $callee,
            'visit_id' => $visit_id,
        ]);
        
        Bitrix24::getInstance()->addLead($data);
        
        return 'ok';
    }

}
