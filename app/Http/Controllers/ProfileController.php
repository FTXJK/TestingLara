<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;//hmm
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