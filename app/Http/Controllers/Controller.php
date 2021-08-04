<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Log;
Log::info("THIS IS BOOTSTRAP EXECUTION");

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;

use Illuminate\Support\Facades\Storage;
define('TOKEN_FILE', 'tmp' . DIRECTORY_SEPARATOR . 'token_info.json');

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
		
	// extends BaseController then $this->apiClient
		
	protected $apiClient;
		 
	public function __construct()
	{				
		$this->apiClient = $this->getApiClient();
	    //add error printer	
	}				

    public function getApiClient() 
	{
        $clientId = 'd2d57b68-f3d9-46e4-87eb-8bc02e0f7be4';
	    $clientSecret = 'bm0DrhY0RNZbaTutb7IExjvk8o5myQoYfmU8EH67PE4NvLvtkvgjDwtrRImCdhhc';
	    $redirectUrl = 'http://1bf120cf7a95.ngrok.io';
	  
	    $apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUrl);
	    return $apiClient;
	}
	
	public function getToken()
	{
	    if(!Storage::disk('local')->exists(TOKEN_FILE)) {
			exit('Access token file not found');
		}
	
		//$accessToken = json_decode(file_get_contents(TOKEN_FILE), true);
        $accessToken = json_decode(Storage::get(TOKEN_FILE), true);//reutrn array

		if (
			isset($accessToken)
			&& isset($accessToken['accessToken'])
			&& isset($accessToken['refreshToken'])
			&& isset($accessToken['expires'])
			&& isset($accessToken['baseDomain'])
		) {
			return new AccessToken([
				'access_token' => $accessToken['accessToken'],
				'refresh_token' => $accessToken['refreshToken'],
				'expires' => $accessToken['expires'],
				'baseDomain' => $accessToken['baseDomain'],
			]);
		} else {
			exit('Invalid access token ' . var_export($accessToken, true));
		}
	}
	
	public function saveToken($accessToken)
	{
		if (
			isset($accessToken)
			&& isset($accessToken['accessToken'])
			&& isset($accessToken['refreshToken'])
			&& isset($accessToken['expires'])
			&& isset($accessToken['baseDomain'])
		) {
			$data = [
				'accessToken' => $accessToken['accessToken'],
				'expires' => $accessToken['expires'],
				'refreshToken' => $accessToken['refreshToken'],
				'baseDomain' => $accessToken['baseDomain'],
			];

			//file_put_contents(TOKEN_FILE, json_encode($data));                    
			if (Storage::disk('local')->exists(TOKEN_FILE)) {
		        Storage::put(TOKEN_FILE, json_encode($data)); //rewrites
		    } else {
		       Storage::put(TOKEN_FILE, json_encode($data));
	    	}		
		} else {
			exit('Invalid access token ' . var_export($accessToken, true));
		}
	}
	
}
