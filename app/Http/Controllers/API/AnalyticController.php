<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use \Curl\Curl;
use App\Models\Visit;
use App\Models\Number;
use App\Models\Call;
use App\AmoCRM;
use Irazasyed\LaravelGAMP\Facades\GAMP;

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
            'roistat' => '',
            
            'first_visit' => 0
        ];
        
        $request->json('utm')['utm_source'] ? $data['utm_source'] = $request->json('utm')['utm_source'] : false;
        $request->json('utm')['utm_medium'] ? $data['utm_medium'] = $request->json('utm')['utm_medium'] : false;
        $request->json('utm')['utm_campaign'] ? $data['utm_campaign'] = $request->json('utm')['utm_campaign']  : false;
        $request->json('utm')['utm_content'] ? $data['utm_content'] = $request->json('utm')['utm_content'] : false;
        $request->json('utm')['utm_term'] ? $data['utm_term'] = $request->json('utm')['utm_term'] : false;
        
        $request->json('google_client_id') ? $data['google_client_id'] = $request->json('google_client_id') : false;
        $request->json('metrika_client_id') ? $data['metrika_client_id'] = $request->json('metrika_client_id') : false;
        
        $request->json('landing_page') ? $data['landing_page'] = $request->json('landing_page') : false;
        $request->json('referrer') ? $data['referrer'] = $request->json('referrer') : false;
        $request->json('trace') ? $data['trace'] = $request->json('trace') : false;

        $request->json('roistat') ? $data['roistat'] = $request->json('roistat') : false;
        
        $request->json('first_visit') ? $data['first_visit'] = $request->json('first_visit') : false;
        

        $visit = Visit::create($data);
        
        if ($data['first_visit'] == 0) {
            $visit->first_visit = $visit->id;
            $visit->save();
        } 

        $phone = false;

        if ($data['referrer'] || $data['utm_source']) {
            // Получаем телефон
            $phone = $this->reservationNumber($visit->id);
        }
        
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
        $trace = '';
        $roistat = '';

        $request->json('trace') ? $trace = $request->json('trace') : false;
        $request->json('roistat') ? $roistat = $request->json('roistat') : false;
        
        //if (!$visit_id && !$trace) return ['error' => ''];
        
        $visit = Visit::find($visit_id);

        if (!$visit) return ['error' => ''];
        
        $visit->trace = $trace;
        
        if ($roistat) {
            $visit->roistat = $roistat;
        }
        
        if (!$visit->google_client_id) 
            $visit->google_client_id = $request->json('google_client_id');
        if (!$visit->metrika_client_id) 
            $visit->metrika_client_id = $request->json('metrika_client_id');
        
        $visit->save();
        
        $phone = false;

        if ($visit->referrer || $visit->utm_source) {
            $phone = $this->reservationNumber($visit->id);
        }
        
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
    
    public function createCall(Request $request) 
    {
        if (isset($_GET['zd_echo'])) exit($_GET['zd_echo']);
        
        if ($request->event != 'NOTIFY_START') return '!NOTIFY_START';
        
        $caller = $request->caller_id; //номер звонящего
        $callee = $request->called_did; //номер, на который позвонили
        
        $number = Number::where('number', $callee)->first();
        
        // TODO Проверка сесии
        
        $visit_id = $number->visit_id;
        
        $data = [
            'phone' => $caller,
            'title' => "Новая сделка по звонку с $caller",
            'sudo' => true,
        ];
      
        if ($number->type == 2) {
            $data['utm_source'] = $number->source;
            $data['roistat'] = $number->roistat;
            $data['tags'] = [$number->roistat];
        } else {
            $visit = Visit::find($visit_id);

            if ($visit) {
                $data = array_merge($data, [
                    'google_client_id' => $visit->google_client_id,
                    'metrika_client_id' => $visit->metrika_client_id,
                    'landing_page' => $visit->landing_page, 
                    'referrer' => $visit->referrer,
                    'utm_medium' => $visit->utm_medium, 
                    'utm_source' =>  $visit->utm_source, 
                    'utm_campaign' => $visit->utm_campaign, 
                    'utm_term' => $visit->utm_term, 
                    'utm_content' => $visit->utm_content,
                    'trace' => $visit->trace,
                    'visit' => $visit_id,
                    'roistat' => $visit->roistat,
                    'tags' => ['Звонок с сайта'],
                ]);
            }
        }
        
        Call::create([
           'caller' => $caller,
           'callee' => $callee,
           'visit_id' => $visit_id,
        ]);

        AmoCRM::getInstance()->addLead($data);

        //TODO: Отправка цели в яндекс метрику

        if (isset($data['google_client_id']) && $data['google_client_id']) {
            return $this->googleAalytics([
                'client_id' => $data['google_client_id'],
                'event-сategory' => 'call',
                'event-action' => 'tracking',
                // 'utm_medium' => $data['utm_medium'], 
                // 'utm_sourse' =>  $data['utm_sourse'], 
                // 'utm_campaign' => $data['utm_campaign'], 
                // 'utm_term' => $data['utm_term'], 
                // 'utm_content' => $data['utm_content'],
                // 'price' => 111
            ]);
        }

        return 'ok';
    }

    private function googleAalytics($params)
    {

        if (!isset($params['client_id']) || !$params['client_id']) return 'error';

        $default_data = [
            'v' => '1',
            't' => 'event',
            'ni' => '1',
            'ds' => 'api',
            'tid' => 'UA-124050216-1',
            'ec' => '',
            'ea' => '',
            'z' => '',
            'cid' => '',
            'cd4' => '',
        ];

        $price = 0;

        $utm = [];
        // $params['utm_sourse'] ? $utm['cs'] = $params['utm_sourse']: false; //utm_sourse
        // $params['utm_medium']? $utm['cm'] = $params['utm_medium']: false; //utm_medium
        // $params['utm_campaign'] ? $utm['cn'] = $params['utm_campaign']: false; //utm_campaign
        // $params['utm_content'] ? $utm['cc'] = $params['utm_content']: false; //utm_content
        // $params['utm_term'] ? $utm['ck'] = $params['ckutm_term']: false; //utm_term

        isset($params['price']) ? $price = $params['price']: false;

        $data = array_merge($default_data, $utm, [
            'cid' => $params['client_id'],
            'cd4' => $params['client_id'],
            'ec' => $params['event-сategory'],
            'ea' => $params['event-action'],
        ]);

        // $z = $data['cid'] . $data['ec'] . $data['ea'] . $price;
        // foreach ($utm as $key => $value) {
        //     $z = $z . $value;
        // }

        $data['z'] = md5($data['cid'] . $data['ec'] . $data['ea'] . $price . date('dmY'));

        if ($price) $data['cm3'] = $price;

        $curl = new Curl();
        $curl->setUserAgent('user_agent_string');
        $curl->setHeader('Content-type', 'application/x-www-form-urlencoded');

        $curl->post('https://google-analytics.com/collect', $data);

        return $data;
    }

    public function crm(Request $request) 
    {

        $lead_id = isset($request->input('leads')['add'][0]['id']) ? (int) $request->input('leads')['add'][0]['id'] : (int) $request->input('leads')['status'][0]['id'];

        $deal = AmoCRM::getInstance()->getLead($lead_id);

        $google_client_id = false;
        $price = (int) $deal['price'];

        foreach ( $deal['custom_fields'] as $field )
        {
            if ( isset($field['name']) ) {

                if ($field['name'] == 'google_client_id') {
                    $google_client_id = $field['values'][0]['value'];
                } 

                if($field['name'] == 'Себестоимость') {
                    // Привести к числу
                    $price = $price - ((int) $field['values'][0]['value']);
                }

            }
        }

        $data = [
            'client_id' => '',
            'event-сategory' => 'CRM',
            'event-action' => '',
        ];

        if ($google_client_id) {
            $data['client_id'] = $google_client_id;
        } else {
            return 'error';
        }

        switch ($request->event) {
            //Провальная Cделка
            case 'FailureDeal':
                $data['event-action'] = 'FailureDeal';
                break;

            //Удачная Сделка
            case 'SuccessDeal':
                $data['event-action'] = 'SuccessDeal';
                $data['price'] = $price;
                break;

            //Проведена квалификация
            case 'Qualified':
                $data['event-action'] = 'Qualified';
                break;
            
            //Передан в производство
            case 'Production':
                $data['event-action'] = 'Production';
                break;

            default:
                return 'error';
        }

       return $this->googleAalytics($data);
    }

}


