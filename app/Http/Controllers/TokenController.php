<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Log;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
define('STATE_FILE', 'tmp' . DIRECTORY_SEPARATOR . 'StateData.json');

use AmoCRM\Client\AmoCRMApiClient;

class TokenController extends Controller
{
    public function processToken(Request $request)
	{
		Log::info("This is token controller'");		
		//not needed probably, session_status differs anyway
		/*
		if (session_status()  !== PHP_SESSION_ACTIVE) { 
			session_start(); 
		}*/
		$sessionState = Session::get('oauth2state'); //$_SESSION['oauth2state']
		
		if (isset($_GET['state'])) {
			Log::info("tHIS IS GET SATE" .  $_GET['state']);
		}
		Log::info("session is " . Session::get('oauth2state')); //session()->get('oauth2state')

		
		if (Storage::disk('local')->exists(STATE_FILE)) {           	 
            if (empty(Session::get('oauth2state')))   $sessionState =  json_decode(Storage::get(STATE_FILE));
		    //Log::info("State is in STATE FILE reading " . $sessionState);	
		} 
					
		if (isset($_GET['referer'])) {
			$this->apiClient->setAccountBaseDomain($_GET['referer']);
		}
		
		if (!isset($_GET['code'])) {
			$state = bin2hex(random_bytes(16));
			//$_SESSION['oauth2state'] = $state;
			
			//$request->session()->put('oauth2state', $state); //session(['oauth2state', $state  ]); //
			Session::put(['oauth2state' => $state]);
			Session::save();//session()->save();
			Log::info("THIS IS OAUTH2 STATE before redirect" .  Session::get('oauth2state'));//Session::get('oauth2state')); 
			//Log::info("THIS IS OAUTH2 STATE before redirect" .  Session::get('oauth2state'));//Session::get('oauth2state')); 
			//dd(session());
			
			if (Storage::disk('local')->exists(STATE_FILE)) {
    		    Storage::put(STATE_FILE, json_encode($state)); //rewrite
			} else {
			    Storage::put(STATE_FILE, json_encode($state)); //create
			}	
			
			
			if (isset($_GET['button'])) {
				Log::info("setting button for code");
				echo $this->$apiClient->getOAuthClient()->getOAuthButton(
					[
						'title' => 'Установить интеграцию',
						'compact' => true,
						'class_name' => 'className',
						'color' => 'default',
						'error_callback' => 'handleOauthError',
						'state' => $state,
					]
				);
				die;
			} else {
				Log::info("redirecting for code");
				$authorizationUrl = $this->apiClient->getOAuthClient()->getAuthorizeUrl([
					'state' => $state,
					'mode' => 'post_message',
				]);
				Log::info("THIS IS OAUTH2 STATE before redirect" .  Session::get('oauth2state'));//session()->get('oauth2state'));//
				return redirect()->away($authorizationUrl)->with('oauth2state', $state);
				/*
				header('Location: ' . $authorizationUrl);
				die;
				*/
			}
		}  elseif (empty($_GET['state']) || empty($sessionState) || ($_GET['state'] !== $sessionState)) { 
	     	dd(session());
			//Session::forget('oauth2state');
			//unset($_SESSION['oauth2state']);
			exit('Invalid state');
		}
		
		Log::info("Got code from Amo ");
		//GETTING TOKEN BY Code and SAVING IT
		try {
			$accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

			if (!$accessToken->hasExpired()) {
				$this->saveToken([
					'accessToken' => $accessToken->getToken(),
					'refreshToken' => $accessToken->getRefreshToken(),
					'expires' => $accessToken->getExpires(),
					'baseDomain' => $this->apiClient->getAccountBaseDomain(),
				]);
				Log::info("token has been saved");
			}
		} catch (Exception $e) {
			die((string)$e);
		}
		
		$ownerDetails = $this->apiClient->getOAuthClient()->getResourceOwner($accessToken);
		Log::info("got token from redirect with owner name " . $ownerDetails->getName() );	
		
		return redirect()->route('contact');   //redirect to ContactController!!!!
	}

}
