<?php

namespace App\Http\Middleware;

use Closure;

class APILog
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        \App\Models\ApiLog::write($request, $response->content(), $response->getStatusCode());
        
        return $response;
    }
}
