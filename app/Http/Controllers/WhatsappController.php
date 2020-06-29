<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Curl\Curl;

class WhatsappController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        
        $curl = new Curl(env('WHATSAPP_URL'));
        $token = '?token=' . env('WHATSAPP_TOKEN');

        $status = $curl->get('status' . $token);

        // dd($status);

        //accountStatus
        //qrCode

        if (isset($status->accountStatus) && $status->accountStatus == 'loading') {
            $curl->get('takeover' . $token);

            $status = $curl->get('status' . $token);
        }

        return view('whatsapp.home', ['status' => $status]);
    }
}
