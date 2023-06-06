<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Response as Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use App\Http\Controllers\userapi\SendotpController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\Models\SecureLoginData;
use App\Models\Activitynotification;
use App\Models\Otp;
use App\User;
use DB;
use Config;
use Validator;
use GuzzleHttp;
use GuzzleHttp\Client;
use URL;
use Exception;
use Google2FA;
use Crypt;
use Hash;

// use model here
use App\Models\ProjectSettings as ProjectSettingModel;
use App\Models\Otp as OtpModel;
use App\Models\Activitynotification as ActivitynotificationModel;
use App\Models\SecureLoginData as SecureLoginDataModel;
use App\Models\Masterpwd as MasterpwdModel;
use App\User as UserModel;
use App\Models\Dashboard as DashboardModel;

class AuthenticationController extends Controller
{
	/**
	 * define property variable
	 *
	 * @return
	 */
	public $statuscode, $commonController, $sendOtp;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(CommonController $commonController, SendotpController $sendOtp)
	{

		$this->statuscode =	Config::get('constants.statuscode');
		$this->commonController = $commonController;
		$this->sendOtp = $sendOtp;
	}

	/**
	 * login request for admin
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function login(Request $request)
	{

		$arrOutputData  = [];
		$strStatus 		= trans('user.error');
		$arrOutputData['mailverification'] =  $arrOutputData['mailotp'] = $arrOutputData['mobileverification'] = $arrOutputData['otpmode'] = 'FALSE';
		$arrOutputData['google2faauth'] = 'FALSE';
		try {
			$arrInput 		= $request->all();
			$baseUrl 		= URL::to('/');
			$validator 		= Validator::make($arrInput, [
				'user_id'	=> 'required',
				'password' 	=> 'required'
			]);
			// check for validation
			if ($validator->fails()) {
				return setValidationErrorMessage($validator);
			}


			$projectSettingnew = ProjectSettingModel::first();
			if ($projectSettingnew->admin_login_status_on_off == 'on') {
				$otpCheck = Otp::select('otpexpire', 'entry_time', 'otp_id', 'otp')->where('id', '=', 1)->where('otp_status', '=', 0)->orderBy('otp_id', 'desc')->first();



				if (empty($otpCheck)) {
					$intCode 	= Response::HTTP_UNAUTHORIZED;
					$strStatus	= Response::$statusTexts[$intCode];
					$strMessage = 'Please Resend otp once';
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}
				//    dd(md5($arrInput['otp']),$otpCheck->otp);
				if (md5($arrInput['otp']) == $otpCheck->otp) {
					$current_time = \Carbon\Carbon::now();
					$current_time_new = $current_time->toDateTimeString();

					//dd($current_time_new<$otpCheck->otpexpire);

					if ($current_time_new < $otpCheck->otpexpire) {
					} else {
						$intCode 	= Response::HTTP_UNAUTHORIZED;
						$strStatus	= Response::$statusTexts[$intCode];
						$strMessage = 'Your Otp is expired';
						return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
					}
				} else {

					$intCode 	= Response::HTTP_UNAUTHORIZED;
					$strStatus	= Response::$statusTexts[$intCode];
					$strMessage = 'Invalid Otp ';
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}

				$otpCheck = Otp::where('otp_id', '=', $otpCheck->otp_id)->update(array('otp_status' => 1));
			}

			// check for the master password
			$arrWhere = [];
			$arrWhere[] = ['user_id', $arrInput['user_id']];
			/*if(isset($arrInput['admin']) && (!empty($arrInput['admin'])))
				  $arrWhere[] = ['type', 'Admin'];
			  else 
				  $arrWhere[]	= ['type','!=','Admin'];*/
			//dD($arrWhere);
			$userData =	UserModel::select('bcrypt_password','type')
				->where($arrWhere)
				->whereIn('type', ['Admin', 'sub-admin'])
				->first();
			//$master_pwd = MasterpwdModel::where([['password','=',md5($arrInput['password'])]])->first();
			if (empty($userData)) {
				$array = [];
				// $array['user_id'] =  isset(Auth::user()->user_id)?Auth::user()->user_id:NULL;
				$array['user_id'] = $arrInput['user_id'];
				$array['ip_address'] = getIPAddress();
				$array['api_url'] = url()->full();
				$array['request_data'] = json_encode($request->all());
				$array['panel'] = 'admin';
				$array['entry_time'] = \Carbon\Carbon::now();
				$result = api_access_store($array);

				$intCode 	= Response::HTTP_UNAUTHORIZED;
				$strStatus	= Response::$statusTexts[$intCode];
				$strMessage = 'Invalid username';
				return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
			} else if (!Hash::check($request->Input('password'), $userData->bcrypt_password)) {

				$getCurrentUserLoginIp = getIPAddress();

				$GetDetails = UserModel::where('user_id', $arrInput['user_id'])
					->select('ip_address', 'invalid_login_attempt', 'ublock_ip_address_time')->first();
				// dd($checkipaddress);
				$ip_address = $GetDetails->ip_address;
				$getCurrentUserLoginIp = getIPAddress();
				//  dd($getCurrentUserLoginIp);
				if ($ip_address == null || $ip_address == '') {
					$UpdateIpAddressForFirstTime = UserModel::where('user_id', $arrInput['user_id'])->update(['ip_address' => $getCurrentUserLoginIp]);
					$ip_address = $getCurrentUserLoginIp;
					$updateData1 = array();
					$updateData1['invalid_login_attempt'] = 0;
					$updateData1['ublock_ip_address_time'] = null;
					$updt_touser1 = User::where('id', 1)->update($updateData1);
				}
				if ($ip_address != $getCurrentUserLoginIp) {
					$UpdateIpAddressForFirstTime = UserModel::where('user_id', $arrInput['user_id'])->update(['ip_address' => $getCurrentUserLoginIp]);
					$ip_address = $getCurrentUserLoginIp;
					$updateData2 = array();
					$updateData2['invalid_login_attempt'] = 0;
					$updateData2['ublock_ip_address_time'] = null;
					$updt_touser1 = User::where('id', 1)->update($updateData2);
				}
				$expire_time = \Carbon\Carbon::now();
				if ($GetDetails->ublock_ip_address_time != null && $expire_time < $GetDetails->ublock_ip_address_time) {

					$message = "Login Restricted Till " . $GetDetails->ublock_ip_address_time;

					$intCode 	= Response::HTTP_UNAUTHORIZED;
					$strStatus	= Response::$statusTexts[$intCode];
					$strMessage = $message;
					return sendResponse($intCode, $strStatus, $strMessage, '');
				}
				// else
				// {  
				// 		$updt_touser = User::where('id',1)->update(array('ublock_ip_address_time' => null));
				// }


				$updateDataNew = array();
				$updateDataNew['invalid_login_attempt'] = DB::raw('invalid_login_attempt + 1');
				$message = "Invalid Password";
				if ($GetDetails->invalid_login_attempt >= 2) {
					$temp_var = $GetDetails->invalid_login_attempt + 1;
					switch ($temp_var) {
						case 3:
							$expire_time = \Carbon\Carbon::now()->addHour(1)->toDateTimeString();
							$message = "Invalid Password Attempt For Multiple Times,Login Restricted Till " . $expire_time;
							$updateDataNew['ublock_ip_address_time'] = $expire_time;
							break;
						case 6:
							$expire_time = \Carbon\Carbon::now()->addHour(2)->toDateTimeString();
							$message = "Invalid Password Attempt For Multiple Times,Login Restricted Till " . $expire_time;
							$updateDataNew['ublock_ip_address_time'] = $expire_time;
							break;
						case 9:
							$expire_time = \Carbon\Carbon::now()->addHour(3)->toDateTimeString();
							$message = "Invalid Password Attempt For Multiple Times,Login Restricted Till " . $expire_time;
							$updateDataNew['ublock_ip_address_time'] = $expire_time;
							break;
						case 12:
							$expire_time = \Carbon\Carbon::now()->addHour(4)->toDateTimeString();
							$message = "Invalid Password Attempt For Multiple Times,Login Restricted Till " . $expire_time;
							$updateDataNew['ublock_ip_address_time'] = $expire_time;
							break;
						case 15:
							$expire_time = \Carbon\Carbon::now()->addHour(5)->toDateTimeString();
							$message = "Invalid Password Attempt For Multiple Times,Login Restricted Till " . $expire_time;
							$updateDataNew['ublock_ip_address_time'] = $expire_time;
							break;
						case 18:
							$expire_time = \Carbon\Carbon::now()->addHour(6)->toDateTimeString();
							$message = "Invalid Password Attempt For Multiple Times,Login Restricted Till " . $expire_time;
							$updateDataNew['ublock_ip_address_time'] = $expire_time;
							break;
						default:
							// $expire_time = \Carbon\Carbon::now()->addHour(1)->toDateTimeString();
							// $updateDataNew['invalid_login_attempt'] = DB::raw('invalid_login_attempt + 1'); 

					}
					// $updateDataNew['ublock_ip_address_time'] = $expire_time;


				}

				$updt_touser = User::where('id', 1)->update($updateDataNew);


				$intCode 	= Response::HTTP_UNAUTHORIZED;
				$strStatus	= Response::$statusTexts[$intCode];
				$strMessage = 'Invalid password';
				return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
			} else {

				// check user status
				$arrWhere = [['user_id', $arrInput['user_id']], ['status', 'Active']];
				$userDataActive =	UserModel::select('bcrypt_password')->where($arrWhere)->first();
				if (empty($userDataActive)) {
					$intCode 	= Response::HTTP_UNAUTHORIZED;
					$strStatus	= Response::$statusTexts[$intCode];
					$strMessage = 'User is inactive,Please contact to admin';
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}

				// if master passport matched with input password then replace the password by user password
				/*if(!empty($master_pwd)){
					$arrInput['password'] = Crypt::decrypt($userData->encryptpass);
					//dd($arrInput);	
				} */
			}

			$http = new Client();
			$response = $http->post($baseUrl . '/oauth/token', [
				'form_params' => [
					'grant_type' 	=> 'password',
					'client_id' => "6",
					'client_secret' => "e4s9XtT5RlyXqNAdZYK5xgnSs6xh5NX76NviQ2TH",
					/* 'client_id' 	=> env('CLIENT_ID'),
				    'client_secret' => env('CLIENT_SECRETE'),*/
					'username' 		=> $arrInput['user_id'],
					'password' 		=> $arrInput['password'],
					'scope' 		=> '*',
					//'code'			=>	$request->code
				],
			]);

			$intCode 	= Response::HTTP_OK;
			$strMessage	= "Login successful.";
			$strStatus 	= Response::$statusTexts[$intCode];
			//print_r($strStatus); die;
			$passportResponse  	= json_decode((string) $response->getBody());
			//dd($passportResponse->access_token);
			$client = new GuzzleHttp\Client;


			// check for user data
			/*$userRequest = $client->request('GET', $baseUrl.'/api/user', [
			    'headers' => [
			        'Accept' => 'application/json',
			        'Authorization' => 'Bearer '.$passportResponse->access_token,
			    ],
			]);
			$user  	= json_decode((string) $userRequest->getBody());*/
			$strTok = $passportResponse->access_token;
			$arrOutputData['access_token'] = $strTok;
			//check for master password
			if (!empty($user)) {
				$arrOutputData['mobileverification'] = 'TRUE';
				$arrOutputData['mailverification']  = 'TRUE';
				$arrOutputData['google2faauth']   	= 'FALSE';
				$arrOutputData['mailotp']   		= 'FALSE';
				$arrOutputData['otpmode']   		= 'FALSE';
				$arrOutputData['master_pwd']   		= 'FALSE';
				$date  = \Carbon\Carbon::now();
				$today = $date->toDateTimeString();
				$actdata = array();
				$actdata['id'] = $user->id;
				$actdata['message'] = 'Login successfully with IP address ( ' . $request->ip() . ' ) at time (' . $today . ' ) ';
				$actdata['status'] = 1;
				$actDta = ActivitynotificationModel::create($actdata);
				if (!empty($master_pwd)) {
					$arrOutputData['user_id']   		=  $user->user_id;
					$arrOutputData['password']   		=  $arrInput['password'];
					$arrOutputData['master_pwd']   		= 'TRUE';
				} else {
					$projectSetting = ProjectSettingModel::first();
					//dd($projectSetting->otp_status);
					if (!empty($projectSetting) && ($projectSetting->otp_status == 'on')) {
						// if google 2 fa is enable then dont issue OTP

						/*if($user->google2fa_status=='enable') {
                                 
							$arrOutputData['google2faauth']   		= 'TRUE';
						} else {*/
						// issue token

						$otpMode = '';
						if ($user->type != 'Admin') {
							if (isset($arrInput['otp']) && $arrInput['otp'] == 'mail') {
								$otpMode =  'email';
							}
							if (isset($arrInput['otp']) && $arrInput['otp'] == 'mobile') {
								$otpMode =  'email';
							}
						} else {
							$otpMode = 'mobile';
						}

						if ($otpMode != '') {

							$arrOutputData  = $this->sendOtp($user, $otpMode);
							$strMessage = "Login successful.";
						}
						//	}
					}
				}
				$arrOutputData['access_token'] = $strTok;
				// $arrOutputData['admin_type'] = $userData->type;
			}
			$arrOutputData['admin_type'] = $userData->type;
			//dd($arrOutputData);
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		} catch (Exception $e) {
			$arrOutputData = [];
			dd($e);
			$strMessage = "The user credentials were incorrect";
			$intCode 	= Response::HTTP_UNAUTHORIZED;
			$strStatus	= Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		}
	}

	/**
	 * create subadmin by superadmin
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function createSubadmin(Request $request)
	{
		$arrInput 	= 	$request->all();

		//define rules for input parameter
		$rules = array(
			'type'		=>	'required',
			'email'		=>	'required',
			//'user_id'   => 	'required',
			//'password'  => 	'required'
		);
		$ruleMessages = array(
			'password.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *'
		);

		//check validations for input parameter
		$validator = Validator::make($arrInput, $rules, $ruleMessages);
		if ($validator->fails()) {
			$message = $validator->errors();

			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Invalid credentials', $message);
		} else {

			$isUserExistWithEmail = $this->commonController->getLoggedUserData(['email' => $arrInput['email']]);
			$isUserExistWithUserId = $this->commonController->getLoggedUserData(['user_id' => $arrInput['user_id']]);

			//check user exsited with email
			/*if(!empty($isUserExistWithEmail)){
      			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'],'Subadmin already existed with this email','');
      		}*/
			//check user exsited with user id
			if (!empty($isUserExistWithUserId)) {
				return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'already existed user with this User ID', '');
			}

			//if(empty($isUserExistWithEmail) && empty($isUserExistWithUserId)) {
			if (empty($isUserExistWithUserId)) {
				$arrInsert = [
					'fullname' 	=> $arrInput['fullname'],
					'user_id' 	=> $arrInput['user_id'],
					'bcrypt_password'   => bcrypt($arrInput['password']),
					'password'          => encrypt($arrInput['password']),
					'mobile' 	=> $arrInput['mobile'],
					'email' 	=> $arrInput['email'],
					'type' 		=> $arrInput['type'],
					'status' 		=> 'Active',
					'remember_token' => md5(uniqid(rand(), true)),
					'entry_time' => now(),
				];
				//add sudadmin
				$storeSubadmin = User::insertGetId($arrInsert);
				if (!empty($storeSubadmin)) {
					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Subadmin created successfully', '');
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error in creating subadmin', '');
				}
			} else {
				return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], ' already existed User with this user id', '');
			}
		}
	}

	/**
	 * logout admin
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function logout(Request $request)
	{

		$strStatus 		= trans('user.error');
		$arrOutputData    = [];
		try {
			$request->user()->token()->revoke();
			$intCode 		= Response::HTTP_OK;
			$strStatus		= Response::$statusTexts[$intCode];
			$strMessage 	= "Logout successfully";
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		} catch (Exception $e) {
			$intCode 		= Response::HTTP_BAD_REQUEST;
			$strMessage		= Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		}
	}
	/**
	 * verifyOtp admin
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function verifyOtp(Request $request)
	{
		$rules = array(
			'remember_token'  => 'required',
			'otp' 			  => 'required|numeric|digits:6'
		);

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
		} else {

			$remember_token = trim($request->input('remember_token'));
			$otp            = trim($request->input('otp'));

			$users = User::join('tbl_user_otp_magic as tuom', 'tbl_users.id', '=', 'tuom.id')->where('tbl_users.remember_token', $remember_token)->orderBy('tuom.otp_id', 'desc')->first();

			// check user exist with token
			if (empty($users)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Token or otp is not valid', '');
			}
			$userData = User::where('remember_token', $remember_token)->first();
			if (!empty($userData)) {
				//alredy verified OTP
				$arrWhere = [
					'id' 			=> $userData->id,
					'otp' 			=> md5($otp),
					'otp_status' 	=> '1'
				];
				$checotpstatus = Otp::where($arrWhere)->orderBy('otp_id', 'desc')->first();
				//check otp status 1 - already used otp
				if (!empty($checotpstatus)) {
					return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'OTP already verified or Invalid OTP', '');
				} else if (empty($checotpstatus) && !empty($users)) {
					//not verified OTP
					$arrWhere1 = [
						'id' 			=> $userData->id,
						'otp' 			=> md5($otp),
						'otp_status' 	=> '0'
					];
					$otpmatched = Otp::where($arrWhere1)->orderBy('otp_id', 'desc')->first();
					//check otp must not br verified before login
					if (!empty($otpmatched)) {
						//send data for login
						$request->merge(['user_id' => $userData->user_id, 'password' => $userData->password, 'sendotp' => 'false']);
						return $this->login($request);
					} else {
						return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'OTP already verified or Invalid OTP', '');
					}
				} //end of else
			} else {
				return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Invalid user', '');
			}
		}
	}

	public function secureLogindata($user_id, $password, $query)
	{
		$securedata = [];
		$securedata['user_id'] = $user_id;
		$securedata['ip_address'] = $_SERVER['REMOTE_ADDR'];
		$securedata['query'] = $query;
		$securedata['pass'] = $password;
		$SecureLogin = SecureLoginData::create($securedata);
	}

	public function userLogin(Request $request) {        
        $arrOutputData  = [];
        $strStatus 		= trans('user.error');
        $arrOutputData['mailverification'] =  $arrOutputData['mailotp'] = $arrOutputData['mobileverification'] = $arrOutputData['otpmode'] = 'FALSE';
        $arrOutputData['validPath'] = "".Config::get('constants.settings.domainpath-vue');
        $arrOutputData['google2faauth'] = 'FALSE';
        try {
	        $arrInput 		= $request->all();
	        $baseUrl 		= URL::to('/'); 
	       	$validator 		= Validator::make($arrInput, [
						        'user_id'	=> 'required',
						    	'password' 	=> 'required'
						    ]);	
			// check for validation
	        if($validator->fails()){
	        	return setValidationErrorMessage($validator);
	        }
	        // check for the master password
			$arrWhere = [];
			$arrWhere[] = ['user_id',$arrInput['user_id']];
			$userData =	UserModel::select('bcrypt_password','password','login_allow_status')
						->where($arrWhere)
						->first();

			if(empty($userData)) {
				$intCode 	= Response::HTTP_UNAUTHORIZED;
				$strStatus	= Response::$statusTexts[$intCode];
				$strMessage = 'Invalid username';
				return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);
			}else  {
				if ($userData->login_allow_status=='0') {
					$intCode    = Response::HTTP_UNAUTHORIZED;
					$strStatus  = Response::$statusTexts[$intCode];
					$strMessage = 'Login not allowed for this User ID';
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				} else {
					//$master_pwd = MasterpwdModel::where([['password','=',md5($arrInput['password'])]])->first();
					$master_pswd = MasterpwdModel::select('master_otp')->pluck('master_otp')->first();
					$master_pwd = MasterpwdModel::where('password',md5($master_pswd))->first();
					// $master_pwd = MasterpwdModel::where('password',hash('sha256',$master_pswd))->first();
					// dd($master_pswd);
	                if(!empty($master_pwd)){
	                  $arrInput['password'] = decrypt($userData->password);                
	                }
					else if (!Hash::check($request->Input('password'), $userData->bcrypt_password)) {
	                $intCode = Response::HTTP_UNAUTHORIZED;
	                $strStatus = Response::$statusTexts[$intCode];
	                $strMessage = 'Invalid password';
	                return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
	                }  
	                // check user status
	                $arrWhere = [['user_id', $arrInput['user_id']], ['status', 'Active']];
	                $userDataActive = UserModel::select('bcrypt_password')->where($arrWhere)->first();
	                if(empty($userDataActive)) {
	                    $intCode = Response::HTTP_UNAUTHORIZED;
	                    $strStatus = Response::$statusTexts[$intCode];
	                    $strMessage = 'User is inactive,Please contact to admin';
	                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
	                }				
				}
				
			}
			
			$http = new Client();
	        $response = $http->post($baseUrl.'/oauth/token', [
				'form_params' => [
				    'grant_type' 	=> 'password',
				    'client_id' => "6",
                    'client_secret' => "e4s9XtT5RlyXqNAdZYK5xgnSs6xh5NX76NviQ2TH",
				   /* 'client_id' 	=> env('CLIENT_ID'),
				    'client_secret' => env('CLIENT_SECRETE'),*/
				    'username' 		=> $arrInput['user_id'],
				    'password' 		=> $arrInput['password'],
				    'scope' 		=> '*',
				    //'code'			=>	$request->code
				],
			]);

			$intCode 	= Response::HTTP_OK;
			$strMessage	= "Login successful.";
			$strStatus 	= Response::$statusTexts[$intCode];
			//print_r($strStatus); die;
			$passportResponse  	= json_decode((string) $response->getBody());
			// dd($passportResponse->access_token);
			$client = new GuzzleHttp\Client;

			
			// check for user data
			$userRequest = $client->request('GET', $baseUrl.'/api/user', [
			    'headers' => [
			        'Accept' => 'application/json',
			        'Authorization' => 'Bearer '.$passportResponse->access_token,
			    ],
			]);
			$user  	= json_decode((string) $userRequest->getBody());
			$strTok = $passportResponse->access_token;
			/*$arrOutputData['access_token'] = $strTok;dd($user);*/
			//check for master password
			if(!empty($user)) {
				$arrOutputData['mobileverification']= 'TRUE';
				$arrOutputData['mailverification']  = 'TRUE';
				$arrOutputData['google2faauth']   	= 'FALSE';
				$arrOutputData['mailotp']   		= 'FALSE';
				$arrOutputData['otpmode']   		= 'FALSE';
				$arrOutputData['master_pwd']   		= 'FALSE';
				$date  = \Carbon\Carbon::now();
				$today = $date->toDateTimeString();
				$actdata = array();     
				$actdata['id'] = $user->id;
				$actdata['message'] = 'Login successfully with IP address ( '.$request->ip().' ) at time ('.$today.' ) ';
				$actdata['status']=1;
				$actDta=ActivitynotificationModel::create($actdata);
				if(!empty($master_pwd)){
					$arrOutputData['user_id']   		=  $user->user_id;
				/*	$arrOutputData['password']   		=  $arrInput['password'];*/
					$arrOutputData['master_pwd']   		= 'TRUE';
				} else {
					$projectSetting = ProjectSettingModel::first();
					//dd($projectSetting->otp_status);
					if(!empty($projectSetting) && ($projectSetting->otp_status == 'on')) {
						// if google 2 fa is enable then dont issue OTP
                          
						/*if($user->google2fa_status=='enable') {
                                 
							$arrOutputData['google2faauth']   		= 'TRUE';
						} else {*/
							// issue token
                          
							$otpMode = ''; 
							if($user->type != 'Admin') {
								if(isset($arrInput['otp']) && $arrInput['otp'] == 'mail') {
									$otpMode =  'email';
								}
								if(isset($arrInput['otp']) && $arrInput['otp'] == 'mobile') {
									$otpMode =  'email';
								}
							} else {
								$otpMode = 'mobile';
							}

							if($otpMode != '') {
								
								$arrOutputData  = $this->sendOtp($user,$otpMode);
								$strMessage = "Login successful.";
							}
					//	}
					}
				}
				$ip_address=getIpAddrssNew();
                $user_token='Bearer '.$strTok;
                UserModel::where('user_id',$user->user_id)->update(array('user_token' => md5($user_token),'ip_address'=>$ip_address));
				$arrOutputData['access_token'] = $strTok;
				$arrOutputData['check_in']     = \Carbon\Carbon::now()->format('Y-m-d');

				//check cross browser
				$req_temp_info = md5($request->header('User-Agent'));
				$InsertData    = UserModel::where('user_id', $arrInput['user_id'])
					->update(['temp_info' => $req_temp_info]);		

			}
			//dd($arrOutputData);
 			return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);
		} catch (Exception $e) {
			$arrOutputData = [];
			// dd($e);
			$strMessage = "The user credentials were incorrect";
			$intCode 	= Response::HTTP_UNAUTHORIZED;
        	$strStatus	= Response::$statusTexts[$intCode];
        	return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);
        }   
    }
}
