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
        
        $request->json('utm')['utm_source']? $data['utm_source'] = $request->json('utm')['utm_source'] : false;
        $request->json('utm')['utm_medium']? $data['utm_medium'] = $request->json('utm')['utm_medium'] : false;
        $request->json('utm')['utm_campaign'] ? $data['utm_campaign'] = $request->json('utm')['utm_campaign']  : false;
        $request->json('utm')['utm_content'] ? $data['utm_content'] = $request->json('utm')['utm_content'] : false;
        $request->json('utm')['utm_term'] ? $data['utm_term'] = $request->json('utm')['utm_term'] : false;
        
        $request->json('google_client_id') ? $data['google_client_id'] = $request->json('google_client_id') : false;
        $request->json('metrika_client_id') ? $data['metrika_client_id'] = $request->json('metrika_client_id') : false;
        
        $request->json('landing_page') ? $data['landing_page'] = $request->json('landing_page') : false;
        $request->json('referrer') ? $data['referrer'] = $request->json('referrer') : false;
        $request->json('trace') ? $data['trace'] = $request->json('trace') : false;
        
        $request->json('first_visit') ? $data['first_visit'] = $request->json('first_visit') : false;
        

        $visit = Visit::create($data);
        
        if ($data['first_visit'] == 0) {
            $visit->first_visit = $visit->id;
            $visit->save();
        } 
        
        // Получаем телефон
        $phone = $this->reservationNumber($visit->id);
        
        return [
            'data' => [
                'visit' => $visit->id,
                'first_visit' => $visit->first_visit,
                'phone' => $phone
            ]
        ];
    }
    
    public function updateVisit(Request $request)
    {
        $visit_id = $request->json('visit');
        $trace = $request->json('trace');
        
        //if (!$visit_id && !$trace) return ['error' => ''];
        
        $visit = Visit::find($visit_id);

        if (!$visit) return ['error' => ''];
        
        $visit->trace = $trace;
        
        $visit->save();
        
        $phone = $this->reservationNumber($visit->id);
        
        return [
            'data' => [
                'visit' => $visit->id,
                'first_visit' => $visit->first_visit,
                'phone' => $phone
            ]
        ];
    }
    
    private function reservationNumber($visit_id)
    {
        if (!$visit_id) {
            return false;
        }
        
        $now = date('Y-m-d H:i:s', time());
        
        $number = Number::where('visit_id', $visit_id)->first();
        
        if (!$number) {
            $number = Number::where([['reservation_at', '<', $now], ['type', '=', 1]])->first();
        }


        
        if ($number) {
            $reservation_at = date('Y-m-d H:i:s', time() + (15 * 60));
            
            $number->reservation_at = $reservation_at;
            $number->visit_id = $visit_id;
            $number->save();

            return [
                'number' => $number->number,
                'ttl' => $reservation_at,
            ];
        }
        
        return false;
    }
    
    public function createCall(Request $request) {
        
        if ($request->event != 'NOTIFY_START') return 'ok';
        
        $caller = $request->caller_id; //номер звонящего
        $callee = $request->called_did; //номер, на который позвонили
        
        //78631112233
        
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
        
        // Отправка цели в google analytics
        // Отправка цели в яндекс метрику
        
        Bitrix24::getInstance()->addLead($data);
        
        return 'ok';
    }

}
