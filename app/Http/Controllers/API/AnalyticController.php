<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use App\Models\Visit;
use App\Models\Number;
use App\Models\Call;

class AnalyticController extends Controller
{
//    public $b24;

    public function __construct()
    {
//        $this->$b24 = new Curl(env('BTRIX24_URL'));
    }
    
    public function createVisit(Request $request)
    {
        $utm_medium = $request->input('utm_medium') ? $request->input('utm_medium') : '';
        $utm_source = $request->input('utm_sourse') ? $request->input('utm_source') : '';
        $utm_campaign = $request->input('utm_campaign')? $request->input('utm_campaign') : '';
        $utm_term = $request->input('utm_term') ? $request->input('utm_term') : ' ';
        $utm_content = $request->input('utm_content') ? $request->input('utm_content') : '';
        
        $google_client_id  = $request->input('google_client_id');
        $metrika_client_id = $request->input('metrika_client_id');
        
        $landing_page = $request->input('landing_page');
        $referrer= $request->input('referrer');
        $trace= $request->input('trace');
        
        
        $first_visit = $request->first_visit ? $request->first_visit : 0;
        
        
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
        
        if (!$first_visit) {
            $visit->first_visit = $visit->id;
            $visit->save();
        }
        
//        dd($visit->id);
        
        return $visit->id;
    }
    
    public function updateTrace(Request $request)
    {
        $visit_id = $request->visit;
        $trace = $request->input('trace');
        
        $visit = Visit::find($visit_id);
        
        $visit->trace = $trace;
        
        $visit->save();
        
        return 'ok';
    }
    
    public function reservationNumber(Request $request)
    {
        $visit_id = $request->visit;
        
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
            'phone' => $caller
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
        
        //
        // TODO Созданеие лида. 
        //
        
        return 'ok';
    }

}
