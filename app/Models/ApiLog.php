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

        $_request = '';

        if ( count($request->all()) ) {
            $_request = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
        } else if( count($request->json()->all()) ) {
            $_request = json_encode($request->json()->all(), JSON_UNESCAPED_UNICODE);
        }

        self::create([
            'method' => $request->method(),
            'url' => $request->url(),
            'request' => $_request, 
            'response' => $response,
            'code' => $code,
        ]);
    }
}