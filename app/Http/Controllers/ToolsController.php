<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiLog;

class ToolsController extends Controller
{
    public function webhooksLog(Request $request)
    {     
        $logs = ApiLog::orderBy('created_at', 'desc')->paginate(10);
        return view('tools/api/log')->with(compact('logs'));
    }
}
