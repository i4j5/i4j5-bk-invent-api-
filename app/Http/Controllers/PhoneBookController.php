<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dotzero\LaravelAmoCrm\AmoCrmManager;

class PhoneBookController extends Controller
{
    private $amocrm;

    public function __construct(AmoCrmManager $amocrm)
    {
        $this->middleware('auth');
        $this->amocrm = $amocrm;
        
    }

    public function index()
    {
        $data = [];
        $run = true;
        for($limit_offset = 0; $run; $limit_offset++) 
        {
            $res = $this->amocrm->contact->apiList([
                'query' => '',
                'limit_rows' => 500,
                'limit_offset' => $limit_offset * 500,
                'type' => 'all'
            ]);

            $data = array_merge($data, $res);

            if (count($res) < 500) $run = false;
        }

        return view('phonebook/main')->with('data', $data);
    }

    public function search()
    {

    }
}
