<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiLog;
use DB;

class ToolsController extends Controller
{
    public function webhooksLog(Request $request)
    {     
        $_url = $request->query('url');
        $_request = $request->query('request');
        $_response = $request->query('response');
        // dd($search);

        if ($_url || $_request || $_response ) {
            $db = DB::table('api-logs');

            if ($_url) $db->where('url', 'LIKE', '%' . $_url . '%');
            if ($_request) $db->orWhere('request', 'LIKE', '%' . $_request . '%');
            if ($_response) $db->orWhere('response', 'LIKE', '%' . $_response . '%');

            $res = $db->get();

            $ids = [];
            foreach($res as $item)
            {
                array_push($ids, $item->id);
            }

            // dd($ids);
    
            $logs = ApiLog::whereIn('id', $ids)->paginate(50);
        } else {
            $logs = ApiLog::orderBy('created_at', 'desc')->paginate(10);
        }

        return view('tools/api/log')
                ->with(compact('logs'))
                ->with(compact('_request'))
                ->with(compact('_response'))
                ->with(compact('_url'));
    }
}
