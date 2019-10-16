<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{

    protected $table = 'api-logs';

    protected $fillable = [
        'method', 'url', 'request', 'response', 'code'
    ];
    
    public static function write($request, $response = '', $code = 200)
    {
        self::create([
            'method' => $request->method(),
            'url' => $request->url(),
            'request' => count($request->all()) ? json_encode($request->all(), JSON_UNESCAPED_UNICODE) : '', 
            'response' => $response,
            'code' => $code,
        ]);
    }
}