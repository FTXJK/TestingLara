<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Route;

use Closure;
use Illuminate\Http\Request;
use Log;

class CheckJSON
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
		Log::info('CheckJSON middleware');
		/*
	     force | validate
		 $data = request()->json()->all();
         dd($data['type']);
		*/
        return $next($request);
    }
}
