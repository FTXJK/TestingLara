<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Route;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;
use View;

use Illuminate\Support\Facades\Storage;
define('FORM_FILE', 'tmp' . DIRECTORY_SEPARATOR . 'FormData.json'); 


class ValidateForm
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
	public $data;
	public $notify = '';
	public $chkVld = true;

    public function handle(Request $request, Closure $next)
    {
		Log::info('ValidateForm middleware');
		$data = $request->all();
		$this->data = $data;;
		
		$this->validateName($data['name']); 
		$this->validateName($data['surname']);
		$this->validatePhone($data['phone']);
		$this->validateEmail($data['email']);		
		 
   		$profileDate = date('Y-m-d H:i:s');//Carbon\Carbon::now()->toDateTimeString();
		//$request->merge(['date' => $profileDate]); //also $request->request->add(['variable' => 'value']);
		$this->data['date'] = $profileDate;		


		if ($this->chkVld == false) {
			Log::info("Validation is wrong");
			return new response($this->returnView($this->data, $this->notify));  //BAD PRACTICE, BETTER REDIRECT TO CONTROLLER
		   //return redirect()->route('profile');
		} else {
		    If (Storage::disk('local')->exists(FORM_FILE)) {
		       Storage::put(FORM_FILE, json_encode($this->data)); //rewrites
			} else {
		       Storage::put(FORM_FILE, json_encode($this->data));
			}		
			 
		    return $next($request);
		} 
    }
	 
	//Name should be longer than 5 letters. No more than 15 letters; Example:	
	public function validateName($name) 
	{ 
		if (preg_match("/^[a-zA-Z0-9]{5,15}$/ ", $name) != 1)  {
			Log::info('Name validation fails');
			
			$this->data['name'] = '';
			$this->data['surname'] = '';
			$this->notify = "Name or Surname incorrect";
			$this->chkVld = false;
			}
			
		return true;
	}
	
	//Phone should consist of 10 digits
	public function validatePhone($phone) 
	{
	    if (preg_match("/^[0-9]{10}$/", $phone) != 1)  {
	        Log::error("Password validation fails");
		  
	        $this->data['phone'] = '';
		    $this->notify = "Phone incorrect";
		    $this->chkVld = false;
	   }
	   
	   return true;
	}
	
	//Email example://^[a-zA-IZ](5, 15)  \@ [a-z]+ \. [a-z]
	public function validateEmail($email) 
	{
		//if(filter_var($email, FILTER_VALIDATE_EMAIL) !== true) {
		if (preg_match('/^[a-zA-Z0-9]{5,15}\@[a-z]+\.[a-z]+$/', $email) != 1) {
	       Log::error("Email ivalidation fails");
			
		   $this->data['email'] = '';
		   $this->notify = "Email incorrect";
		   $this->chkVld = false;
		}
		
		return true;
	}
	
	public function returnView($data, $notify = '') 
	{
	   	$view = View::make('profile')->with('name',  $data['name'])  
	                                          ->with('surname', $data['surname'])
											  ->with('age', $data['age'])
											  ->with('phone', $data['phone'])
											  ->with('email', $data['email'])
											  ->with('date', $data['date'])
											  ->with('notes', $data['notes'])
											  ->with('notifyError', $notify);
											  
        return $view;
	}
		
}
