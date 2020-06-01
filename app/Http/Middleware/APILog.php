<?php

namespace App\Http\Middleware;

use Closure;
use \Illuminate\Http\Response;

class APILog
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if($response->getStatusCode() != 500)
        {
            $response_class_name = get_class($response);

            if ($response_class_name == 'Illuminate\Http\Response') { 
                \App\Models\ApiLog::write($request, $response->content(), $response->getStatusCode());
            } else if($response_class_name == 'Symfony\Component\HttpFoundation\BinaryFileResponse') {
                \App\Models\ApiLog::write($request, 'FILE', $response->getStatusCode());
                //$response = new Response('', 200);
            }
        }
        return $response;
    }
}
