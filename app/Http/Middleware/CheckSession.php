<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Log;
use Session;

class CheckSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
		
	    Log::info("This is CHECKSESSION data OAUTH2: " .  $request->session()->get('oauth2state'));
	    //dd(session());
        return $next($request);
    }
}
