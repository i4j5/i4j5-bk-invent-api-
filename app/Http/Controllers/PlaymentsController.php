<?php

namespace App\Http\Controllers;

use App\Models\Playment;
use Illuminate\Http\Request;
use \Curl\Curl;

class PlaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $playments;

        if ($search) {
            $playments = Playment::where('deal_id', 'LIKE', $search)
                            ->orWhere('order_id', 'LIKE', $search)
                            ->orWhere('id', 'LIKE', $search)
                            ->orWhere('phone', 'LIKE', '%' . $search . '%')
                            ->orWhere('email', 'LIKE', '%' . $search . '%')
                            ->orWhere('fio', 'LIKE', '%' . $search . '%')
                            ->orWhere('description', 'LIKE', '%' . $search . '%')
                            ->paginate(25);
        } else {
            $playments = Playment::paginate(15);
        }

       
        return view('playments.index')
                ->with(compact('playments'))
                ->with(compact('search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('playments.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'sum' => 'required|min:1',
            'description' => 'required',
            'email' => 'sometimes|nullable|email',
            'phone' => 'sometimes|nullable|numeric|min:10',
            'date' => 'required',
        ]);

        $curl = new Curl();
        $curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        $sum = $request->sum * 100;
        $email = $request->email;
        $phone = $request->phone;
        $description = $request->description;

        $playment = Playment::create([
            'amount' => $sum ,
            'order_id' => '',
            'description' => $description,
            'status' => 0,
            'payment_url' => '', 
            'phone'=> $request->phone ? $request->phone : '', 
            'email' => $request->email ? $request->email : '',  
            'fio' => $request->fio ? $request->fio : '', 
            'date' => $request->date, 
            'deal_id' => $request->deal_id ? $request->deal_id : 0,
        ]);

        $playment->id;

        $vars = [];
		$vars['userName'] = env('SBERBANK_NAME');
		$vars['password'] = env('SBERBANK_PASSWORD');
        $vars['orderNumber'] = $playment->id;
        $vars['amount'] = $sum;
        $vars['returnUrl'] = env('PAGE_PAY_SUCCESS');
        $vars['failUrl'] = env('PAGE_PAY_ERROR');
        $vars['description'] =  $playment->description;
        $vars['expirationDate'] = date('Y-m-d\TH:m:s', strtotime($playment->date));
    
        
        // $vars['clientId'] = ''; //Номер (идентификатор) клиента в системе магазина. 
        if ($email) $vars['email'] = $email;
        if ($phone) $vars['phone'] = $phone;  //79998887766
    
        $res = $curl->post(env('SBERBANK_URL') . 'payment/rest/register.do', $vars);
        
        $res = json_decode($res);

        $order_id = false;

        if (isset($res->orderId)) $order_id = $res->orderId;

        if ($order_id) {
            $playment->order_id = $order_id;
            $playment->payment_url = $res->formUrl;
            $playment->save();
            return redirect()->to('playments/' . $playment->id );
        } else {
            $playment->delete();
            $request->session()->flash('message', $res->errorMessage);
            return redirect()->to('playments');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Playment  $playment
     * @return \Illuminate\Http\Response
     */
    public function show(Playment $playment)
    {



        $status = [
            0 => 'заказ зарегистрирован, но не оплачен',
            1 => 'предавторизованная сумма удержана (для двухстадийных платежей)',
            2 => 'проведена полная авторизация суммы заказа',
            3 => 'авторизация отменена',
            4 => 'по транзакции была проведена операция возврата',
            5 => 'инициирована авторизация через сервер контроля доступа банка-эмитента',
            6 => 'авторизация отклонена',
        ];

        $curl = new Curl();
        $curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        $vars = [];
		$vars['userName'] = env('SBERBANK_NAME');
		$vars['password'] = env('SBERBANK_PASSWORD');
        $vars['orderId'] = $playment->order_id;
    
        $res = $curl->post('https://3dsec.sberbank.ru/payment/rest/getOrderStatusExtended.do', $vars);
        $res = json_decode($res);
        // dd($res);

        

        // $res->amount $playment->amount

        // $res->date

        return view('playments.show', compact('playment'));
    }
}


