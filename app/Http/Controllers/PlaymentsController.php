<?php

namespace App\Http\Controllers;

use \Curl\Curl;
use App\Models\Playment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            $playments = Playment::orderBy('created_at', 'desc')->paginate(15);
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

        $validator = Validator::make($request->all(), [
            'sum' => 'required|min:1',
            'description' => 'required|max:24',
            'email' => 'sometimes|nullable|email',
            'phone' => 'sometimes|nullable|numeric|min:10',
            'date' => 'required',
        ]);

        $validator->validate();

        $curl = new Curl();
        $curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        $sum = $request->sum * 100;
        $email = $request->email;
        $phone = $request->phone;
        $description = $request->description;

        $playment = Playment::create([
            'amount' => $sum,
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
            $validator->errors()->add('sber', $res->errorMessage);
            return redirect('playments/create')
                        ->withErrors($validator)
                        ->withInput();
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
        $curl = new Curl();
        $curl->setHeader('Content-Type', 'application/x-www-form-urlencoded');

        $vars = [];
		$vars['userName'] = env('SBERBANK_NAME');
		$vars['password'] = env('SBERBANK_PASSWORD');
        $vars['orderId'] = $playment->order_id;
    
        $sber = $curl->post(env('SBERBANK_URL') . 'payment/rest/getOrderStatusExtended.do', $vars);
        $sber = json_decode($sber);

        return view('playments.show', compact(['playment', 'sber']));
    }
}


