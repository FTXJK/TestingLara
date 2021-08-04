<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use Log;
use View;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use League\OAuth2\Client\Token\AccessToken;
//use League\OAuth2\Client\Token\AccessTokenInterface; 
use Illuminate\Support\Facades\Storage;
define('FORM_FILE', 'tmp' . DIRECTORY_SEPARATOR . 'FormData.json');
define('TOKEN_FILE', 'tmp' . DIRECTORY_SEPARATOR . 'token_info.json');

use AmoCRM\Models\ContactModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TaskModel;
use AmoCRM\Models\AccountModel;
use AmoCRM\Models\UserModel;
use AmoCRM\Models\Customers\CustomerModel;
//use AmoCRM\Models\Customers\Segments\SegmentModel;

//use AmoCRM\Collections\Customers\Segments\SegmentsCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\UsersCollection;
use AmoCRM\Collections\TasksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Collections\ContactsCollection;

use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;

//FIELDS VALUES 
use AmoCRM\Collections\CustomFieldsValuesCollection; //column FieldsValuesCollection

//TEXT FIELDS
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;   //uncomment
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;  
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel; 
//NUMERIC FIELDS
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;   //uncomment
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;  
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;  
//RADIOBUTTON FIELDS ?
use AmoCRM\Models\CustomFieldsValues\RadiobuttonCustomFieldValuesModel;   //uncomment
use AmoCRM\Models\CustomFieldsValues\ValueCollections\RadiobuttonCustomFieldValueCollection;  
use AmoCRM\Models\CustomFieldsValues\ValueModels\RadiobuttonCustomFieldValueModel; 
//DATE FIELDS ?
use AmoCRM\Models\CustomFieldsValues\DateCustomFieldValuesModel;   //uncomment
use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;  
use AmoCRM\Models\CustomFieldsValues\ValueModels\DateCustomFieldValueModel; 
//TEXTAREA FIELDS  ?
use AmoCRM\Models\CustomFieldsValues\TextareaCustomFieldValuesModel;   //uncomment
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextareaCustomFieldValueCollection;  
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextareaCustomFieldValueModel; 

//FIELDS CREATION
use AmoCRM\Collections\CustomFields\CustomFieldsCollection;
use AmoCRM\Models\CustomFields\CustomFieldModel;

use AmoCRM\Models\CustomFields\TextCustomFieldModel;
use AmoCRM\Models\CustomFields\NumericCustomFieldModel;
use AmoCRM\Models\CustomFields\RadiobuttonCustomFieldModel;
use AmoCRM\Models\CustomFields\DateCustomFieldModel;
use AmoCRM\Models\CustomFields\TextareaCustomFieldModel;

use AmoCRM\Models\CustomFields\EnumModel;
use AmoCRM\Collections\CustomFields\CustomFieldEnumsCollection;

//NOTES
use AmoCRM\Collections\NotesCollection;

//use AmoCRM\Models\NoteType\ServiceMessageNote;
use AmoCRM\Models\NoteType\CommonNote;

class ContactController extends Controller
{
    public function addContact (Request $request) 
	{ 
	    ///RETRIEVE FORM DATA
		$data  = json_decode(Storage::get(FORM_FILE), true);		
		//Log::info("This is data " .  $data['name']);
		//dd($data);
		Log::info("Contact Controller and also data " . $data['name']);	
		//return redirect()->route('profile'); //for testing
		
	    $apiClient  = $this->getApiClient();
	    $accessToken = $this->getToken();
         
		$apiClient->setAccessToken($accessToken)
				->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
				->onAccessTokenRefresh(
					function (AccessTokenInterface $accessToken, string $baseDomain) { //saveToken
						$this->saveToken(
							[
								'accessToken' => $accessToken->getToken(),
								'refreshToken' => $accessToken->getRefreshToken(),
								'expires' => $accessToken->getExpires(),
								'baseDomain' => $baseDomain,
							]
						);
					}
				);	
	    
		//CHECK IF EXIST BY PHONE 	AND GET CONTACT
	    $contactExists = false;
		$contact;
	    $phoneData = $data['phone'];
		 
        $contactsCollection = $apiClient->contacts()->get();
		
	    foreach ($contactsCollection as $cont) {
	        $contFields = $cont->getCustomFieldsValues();
		    if ($contFields != null) {
	            $contPhoneField = $contFields->getBy('fieldCode', 'PHONE');					
			    if (!empty($contPhoneFields)) {
				    $phoneNumber  = $contPhoneField->toArray();
				    $phoneNumber = $phoneNumber['values'][0]['value'];//should check all valueCollection
			
				    if ($phoneData == $phoneNumber) {
					    $contactExists = true;
					    $contact = $cont;//setting existing contact
					    break;
					}
			    }
		    }
		}
	    $contactExists = $contactExists == true ? 1 : 0;
	    Log::info("Checked Exist By PHone " . $contactExists);
		 
		
		if ($contactExists == false) {
			//SET CONTACT
			$contact = new ContactModel();
			//name surname age sex phone email  notes and date  
			$contact->setName($data['name']);//hmm
			$contact->setFirstName($data["name"]);
			$contact->setLastName($data['surname']);	
		
			//ADDING CONTACT
			$this->addContactTo($apiClient, $contact);
			Log::info("Contact has been set");
				
			 //SETTING FIELDS 
	        $this->setFields($apiClient);
		    Log::info("Fields have been set to contact");
			 
		    //SETTING FIELDS VALUES
		    $this->setFieldsValues($apiClient, $contact, $data); 
		    Log::info("Fileds values have been saet to contact");
			 
			 //UPDATING CONTACT   //after setting fields
			 $this->updateContact($apiClient, $contact);
			 Log::info("Contact CustomField has been updated");
		}  else {
			//GETTING CONTACT   we set contact above
			/*
			$contactsCollection = $apiClient->contacts()->get();
			//$contactsCollection = $contactsCollection->toArray();
			$contact = $contactsCollection->last();//$contactsCollection[0];
			//var_dump($contact);
			//die;
			Log::info("got contact from collectionn " . $contact->getName() . " and id: " . $contact->getId());	
			*/
	    }
			
		//CONTACT/CHECK STATUS OF LEAD AND CREATE CUSTOMER     //WON_STATUS 142   
		$leadExists = false;
		 
	    //$leadsCollection = $apiClient->leads()->get();
	    $leadsCollection = $contact->getLeads();
	    if (is_iterable($leadsCollection)) {
		    foreach ($leadsCollection as $lead) {
                if ($lead->getStatusId() == 142) {  			 //check lead by id? 
		        Log::info("WOWOWOWOWOOWN STATUS LEAD");
				$leadExists = true;
			    }
			}
		 }
	    Log::info("Leads checked");
	     
		if($leadExists == false) {
			
			$leadsService = $apiClient->leads();
			// NEXT PAGE ERROR X(
			/*try {
		        $leadsCollection = $leadsService->get();
				$leadsCollection = $leadsService->nextPage($leadsCollection);  //if no content service?
			} catch (AmoCRMApiException $e) {
				Log::error("Leads Service cauth Error");
				die;
			}*/
			
			//GET ACCAUNT
			//try {
			//    $account = $apiClient->account()->getCurrent(AccountModel::getAvailableWith());//$account->toArray();
			//} catch (AmoCRMApiException $e) {
			//    printError($e);
			//}
				
			//GET USERS 
			$usersService = $apiClient->users();
			try {
		        $usersCollection = $usersService->get();
			} catch (AmoCRMApiException $e) {
			    printError($e);
			    die;
			}
			
			//GET RANDOM USER 	 
			Log::info("USERS COLLECTION IS " . var_dump($usersCollection));
			$usersCollection = $usersCollection->toArray();
			$randomUser = $usersCollection[rand(0, count($usersCollection) - 1)]; 
			//$str = join(',', $randomUser);  //////////////////////////////////////////////////////////////
			Log::info("USER is COLLECTION IS " );
			
			//SET LEAD USING RANDOM USER
			$lead = new LeadModel();
			$lead->setName(' PizzaTrade');
			$lead->setPrice(337);
			$lead->setResponsibleUserId($randomUser['id']); //get random user in accaunt //$userId
			//$lead->setContacts(); //contacts
			//$lead->setClosedAt(); // date of closing
			//$lead->setClosestTaskAt();
			
			$leadsCollection = new LeadsCollection();
			$leadsCollection->add($lead);   //service->get()
			
			//ADD LEAD
			try {
				$leadsCollection = $leadsService->add($leadsCollection);
			} catch (AmoCRMApiException $e) {
				printError($e);
				die;
			}
			//UPDATE IF NEEDED
			/*
			try {
				$apiClient->leads()->update($leadsCollection);
			} catch (AmoCRMApiException $e) {
				printError($e);
				die;
			}*/
		
			Log::info("Lead has been set");

			//lLINK CONTACT TO LEAD
			$links = new LinksCollection();
			$links->add($contact);
			try {
				$apiClient->leads()->link($lead, $links);
			} catch (AmoCRMApiException $e) {
				//printError($e);
				Log::error("Error of link" . $e);
				die;		   
			}
			
			Log::info("Contact and lead have been tied");       
			
			//SET TASK   
			 $tasksCollection = new TasksCollection();
			 $task = new TaskModel();
			 $task->setTaskTypeId(TaskModel::TASK_TYPE_ID_FOLLOW_UP)
					 ->setResponsibleUserId($randomUser['id']) //  random 
					 ->setDuration(13213)
					 ->setCompleteTill(mktime(10, 0, 0, 10, 3, 2022))
					 ->setText('Pizza Task')                                                     
					 ->setEntityType(EntityTypesInterface::LEADS);
			$tasksCollection->add($task);
			 
			$taskService = $apiClient->tasks();
			//ADD TASK 
			try {
			    $tasksCollection = $taskService->add($tasksCollection);
			} catch (AmoCRMApiException $e) {
				Log::error("Task Service error " . $e);
				die;			
			}
			Log::info("Contact Controller task added");
		 
			//SET NOTES IF NEEDED
			
		    if ($data['notes'] != null) {
		        $notesCollection = new NotesCollection();
			    $serviceMessageNote = new CommonNote(); //CommonNote ? 
			    $serviceMessageNote->setText("Some data notes")
			                                     ->setEntityId($lead->getId())  //setting
												 ->setCreatedBy(0);
												  
				$notesCollection->add($serviceMessageNote);
				
				try {
				   $leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);
				   $notesCollection = $leadNotesService->add($notesCollection);		  
				} catch (AmoCRMApiException $e) {
				   Log::error("leadNotesService add error " . $e);
				   die; 		   
				 }
				 
				 Log::info("Note uploa successful");
			}		
		}
		
	    //SET CUSTOMER  AFTER CHECKING 
		$contactsService = $apiClient->contacts();
	    $customerService = $apiClient->customers();
	    $customer = new CustomerModel();
	    $customer->setName($contact->getName());
		 
	    try {
			$customer = $customerService->addOne($customer);
	    } catch (AmoCRMApiException $e) {
			Log::error("Customer service error " . $e);
			die;				
		 }
	    Log::info("Customer has been added " . $customer->getName());
		
	    //LINK CUSTOMER  TO CONTACT 
	    $links = new LinksCollection();
	    $links->add($contact); 
		 
	    try {
   	        $customerService->link($customer, $links);
		} catch (AmoCRMApiException $e)  {
	        Log::error('Customer Linking error ' .$e);
		    die;
		}				 
		Log:;info("Customer has been linked to contact");
		 
		//SAVE CUSTOMER
		try {
		    $customerCollection = $customerService->get();
		    $customerCollection->add($customer);
		    $customerService->update($customerCollection); 
		} catch (AmoCRMApiException $e) {
			Log::error("Customer Service update error " . $e);
			die;
	    }
		Log::info("Custjmer has been saved to contact");
			 
	
	    return $this->returnView($data);
    	//return redirect()->route('profile');
	}
	
	public function getApiClient() 
	{
      $clientId = '';
	  $clientSecret = '';
	  $redirectUrl = '';
	  
	  $apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUrl);
	  return $apiClient;
	}
	
	function getToken()
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
	
	function saveToken($accessToken)
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
	
	public function returnView($data) 
	{
	   	$view =  View::make('profile')->with('namePr',  $data['name'])  
	                                          ->with('surnamePr', $data['surname'])
											  ->with('agePr', $data['age'])
											  ->with('phonePr', $data['phone'])
											  ->with('emailPr', $data['email'])
											  ->with('datePr', $data['date'])
											  ->with('notesPr', $data['notes']);
        return $view;
	}
	
	//API FUNCTIONS
	//SETTING UNIQUE FIELDS CODES
	public function setFields($apiClient) 
	{
	
	    //textEmail numericPhone  radiobuttonSex  numericAge   dateDate  textareaNotes   \\\name surname
        $customFieldsService = $apiClient->customFields(EntityTypesInterface::CONTACTS);// ->get();
	   
	    //CHECK DUPLICATES
	    $info = [
		  'sex' => false,
		  'age' => false,
		  'date' => false,
		  'notes' => false
	    ];
	  
	    $customFieldsCollection = $customFieldsService->get(); 
	    foreach ($customFieldsCollection as $ele) {
		    $name = $ele->getName();
		   
		    switch($name) {
			    case('Возраст:'): $info['age'] = true;break;
			    case('Пол:'): $info['sex'] = true;break;
			    case('Создан:'): $info['date'] = true;break;
			    case('Пожелания:'): $info['notes'] = true;break;
			    default: break;
		    }
	   }
	   Log::info("Duplicates checked in setting fields");
	   //PHONE AND EMAIL already exist as are FirstName and SecondName 
	   $customFieldsCollection = new CustomFieldsCollection();
	   
	   // numeric age
	    if ($info['age'] != true) {
		    $cf = new NumericCustomFieldModel();
		    $cf  
				->setName('Возраст:')
				->setCode('AGE') 
				->setSort(10);
				
			$customFieldsCollection->add($cf);
	    }
		
		//radiobutton sex 
		if ($info['sex'] != true) {
		   $cf = new RadiobuttonCustomFieldModel();
		   $cf
				->setName('Пол:')
				->setCode('SEX')
				->setEnums(
				     (new CustomFieldEnumsCollection())
					      ->add(
						    (new EnumModel())
						            ->setValue('М:')
									->setSort(10)
						  )
				          ->add(
						      (new EnumModel())
						               ->setValue('Ж:')
									   ->setSort(20)
		                   )
				
				);
			$customFieldsCollection->add($cf);
		}
	   
	   //date date 
	   if ($info['date'] != true) {
		   $cf = new DateCustomFieldModel();
		   $cf 
				->setName('Создан:')
				->setCode('DATE');
		   $customFieldsCollection->add($cf);
	   }
	   
	   //textarea  notes
	   if ( $info['notes'] != true) {
		   $cf = new TextareaCustomFieldModel(); 
		   $cf 
				->setName('Пожелания:')
                ->setCode('NOTES');				
		   $customFieldsCollection->add($cf);
	   }
	   //Do not send empty collection
	   if ($customFieldsCollection->isEmpty() == false) {
		    try {
			   $customFieldsCollection = $customFieldsService->add($customFieldsCollection);
			   
		    } catch (AmoCRMApiException $e) {
		       Log::error("Contact controller setting field codes " . $e);
			   die;
		    }
	    }
	}
	
	//SETTING VALUES 
	public function setFieldsValues($apiClient, $contact, $data) 
	{
		 
	    $valuesCollection = $contact->getCustomFieldsValues();
	    if(empty($valuesCollection)) $valuesCollection = new CustomFieldsValuesCollection(); //CHECK IF THEY ARE EXIST FIRST
	
        //SET EMAIL TEXTFIELD		
	    $textCustomFieldValuesModel = new TextCustomFieldValuesModel();
	    //$textCustomFieldValuesModel->setFieldName('MAIL'); //setFieldCode("")
	    //$textCustomFieldValuesModel->setFieldId(45409);//(45409);//$contact->getId());
	    $textCustomFieldValuesModel->setFieldCode('EMAIL');											                       				 
        $valueCollection = new TextCustomFieldValueCollection();
	    $valueCollection->add(
						(new TextCustomFieldValueModel())
							//->setEnum('WORK')
							->setValue($data['email'] != null ? $data['email'] : "EmailExample")
					);
		/*
	    $valueCollection->add(
		                (new TextCustomFieldValueModel())
							//->setEnum('WORK')
							->setValue('asdasdasd')
					);
			*/		 
		$textCustomFieldValuesModel->setValues($valueCollection);
		$valuesCollection->add($textCustomFieldValuesModel); //add email to FieldsValues
		
		//SET PHONE numericCustomField  
		$numericCustomFieldValuesModel = new NumericCustomFieldValuesModel();
		$numericCustomFieldValuesModel->setFieldCode('PHONE');
		$valueCollection = new NumericCustomFieldValueCollection();
		$valueCollection->add(
		        (new NumericCustomFieldValueModel())
				   ->setValue($data['phone'] != null ? $data['phone'] : 88005353535) 
		        );		
		$numericCustomFieldValuesModel->setValues($valueCollection);
		$valuesCollection->add($numericCustomFieldValuesModel);
		
		//Age - NumericCustomFieldValue
		$numericCustomFieldValuesModel = new NumericCustomFieldValuesModel();
		$numericCustomFieldValuesModel->setFieldCode('AGE');
		$valueCollection = new NumericCustomFieldValueCollection();
		$valueCollection->add(
		        (new NumericCustomFieldValueModel())
				   ->setValue($data['age'] != null ? $data['age'] : 9999)
		        );
		$numericCustomFieldValuesModel->setValues($valueCollection);		
		$valuesCollection->add($numericCustomFieldValuesModel); 
		
		//Sex  - RadiobuttonCusotmField ?    
		/*
		$radiobuttonCustomFieldValuesModel = new RadiobuttonCustomFieldValuesModel();
		$radiobuttonCustomFieldValuesModel->setFieldCode("SEX");
		$valueCollection = new RadiobuttonCustomFieldValueCollection(); //set enum values set enum valjues /which value
		$valueCollection->add(
		                (new RadiobuttonCustomFieldValueModel()) //from aarray
						   ->setEnumId(1)
				         /*->add(  
						 (new EnumModel())
										->setValue(1)
										->setSort(10)
							  ) 
						 );
			/*
			(new CustomFieldEnumsCollection())
					      ->add(
						    (new EnumModel())
						            ->setValue('М:')
									->setSort(10)
						  )
				          ->add(
						      (new EnumModel())
						               ->setValue('Ж:')
									   ->setSort(20)
	                      }
			
		$radiobuttonCustomFieldValuesModel->setValues($valueCollection);
		$valuesCollection->add($radiobuttonCustomFieldValuesModel);
		*/
	    ///DateCustom or DateTimeCustom 
        $dateCustomFieldValuesModel = new DateCustomFieldValuesModel();
	    $dateCustomFieldValuesModel->setFieldCode("DATE");
		$valueCollection = new DateCustomFieldValueCollection();
		$valueCollection->add(
		          (new DateCustomFieldValueModel())
				     ->setValue($data['date'] != null ? $data['date'] : time())
		          );
		$dateCustomFieldValuesModel->setValues($valueCollection);
		$valuesCollection->add($dateCustomFieldValuesModel); 
		
		//Notes 
		$textareaCustomFieldValuesModel = new TextareaCustomFieldValuesModel();
		$textareaCustomFieldValuesModel->setFieldCode("NOTES");
		$valueCollection = new TextareaCustomFieldValueCollection();
		$valueCollection->add(
		         (new TextareaCustomFieldValueModel())
				   ->setValue($data['notes'] != null ? $data['notes'] : 'nothing' )
		         );
		$textareaCustomFieldValuesModel->setValues($valueCollection);
		$valuesCollection->add($textareaCustomFieldValuesModel); 
	

		$contact->setCustomFieldsValues($valuesCollection);	
	}
	
	public function updateContact($apiClient, $contact) 
	{
	    try {
		    $contacts = $apiClient->contacts(); //ContactService
		    $contacts->updateOne($contact);
		} catch (AmoCRMApiException $e) {
		    Log::error("Updating contact error" . $e);
			var_dump($contacts->getLastRequestInfo());
			//Log::error("Updating contact error" . $e);
            die; 			
	    }
	}
	
	public function addContactTo($apiClient, $contact) 
	{
	    try {
		    $contactModel = $apiClient->contacts()->addOne($contact);
		} catch (AmoCRMApiException $e) {
	        Log::error("Adding contact error" . $e);
	        var_dump($apiClient->contacts()->getLastRequestInfo());
            die;	
		}		   
		
    }
	
	public function addCustomerTo($apiClient, $contact) {}
	public function addLeadTo($apiClient, $contact) {}
    	
}