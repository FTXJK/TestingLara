<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;//hmm
include_once __DIR__ .  ' /../Bootstrap.php';
use Log;
use View;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function  showProfile() 
	{
	    Log::info("Showing Profile view");	
		return view('profile');
	} 
}
