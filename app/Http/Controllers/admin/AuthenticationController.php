<?php

namespace App\Http\Controllers\admin;

use App\models\ProjectSettings;
use Illuminate\Http\Response as Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use App\Http\Controllers\admin\NavigationsController;
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
use Session;
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
	public function __construct(CommonController $commonController, SendotpController $sendOtp, NavigationsController $nav)
	{

		$this->statuscode =	Config::get('constants.statuscode');
		$this->commonController = $commonController;
		$this->sendOtp = $sendOtp;
		$this->nav = $nav;
	}

	/**
	 * login request for admin
	 *
	 * @return \Illuminate\Http\Response
	 */
    public function showLoginFormAdmin(Request $request)
    {
        $data['title'] = 'Admin Login | HSCC';
        return view('admin.auth.Login', compact('data'));
    }

    public function sendsmswp(Request $request)
    {

    	$sendernumber = DB::select("SELECT * FROM `smsdetails` WHERE `status` = 1");
    	$data['title'] = 'Bulk SMS | HSCC';
    	$data['sendernumber'] = $sendernumber;
        return view('admin.sendsms', compact('data'));
    }

    public function sendbulksmswp(Request $request)
    {

		$fromnumber = $request['fromnumber'];
		$tonumber = $request['send_numbers'];
		$w3review = $request['w3review'];

		$sendernumber = DB::select("SELECT * FROM `smsdetails` WHERE `status` = 1 and `id` = '".$fromnumber."'");
		


		$params=array(
		'token' => $sendernumber[0]->token,
		'to' => $tonumber,
		'body' => $w3review
		);
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.ultramsg.com/".$sendernumber[0]->instance."/messages/chat",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_SSL_VERIFYHOST => 0,
		  CURLOPT_SSL_VERIFYPEER => 0,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => http_build_query($params),
		  CURLOPT_HTTPHEADER => array(
		    "content-type: application/x-www-form-urlencoded"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		return redirect()->back()->with('success', 'SMS Send successful!');
    }

    public function login(Request $request)
	{

		//dd(bcrypt($request->Input('password')));

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
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
			}


			$arrWhere = [];
			$arrWhere[] = ['user_id', $arrInput['user_id']];
			$userDataLoginOTP =	UserModel::select('bcrypt_password','type','id')
				->where($arrWhere)
				->whereIn('type', ['Admin', 'sub-admin'])
				->first();

			$idloginotp = $userDataLoginOTP->id;
			


			$projectSettingnew = ProjectSettingModel::first();
			if ($projectSettingnew->admin_login_status_on_off == 'on') {
				$otpCheck = Otp::select('otpexpire', 'entry_time', 'otp_id', 'otp')->where('id', '=', $idloginotp)->where('otp_status', '=', 0)->orderBy('otp_id', 'desc')->first();



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
			$userData =	UserModel::select('bcrypt_password','type','id')
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


			$intCode 	= Response::HTTP_OK;
			$strMessage	= "Login successful.";
			$strStatus 	= Response::$statusTexts[$intCode];
			//print_r($strStatus); die;
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
				// $arrOutputData['admin_type'] = $userData->type;
			}
			$arrOutputData['admin_type'] = $userData->type;
			$request->merge(['authid' => $userData->id]);
			$path = $this->nav->getNavigations($request);
			$navurl = $path->getData();
			$arrOutputData['path'] = $navurl->data[0]->childmenu[0]->path;
            $credentials = array('user_id' => $request->input('user_id'), 'password' => $request->input('password'));
            // dd($arrOutputData);
            if (Auth::attempt($credentials)) {
                return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
            }
            else{
                $strMessage    = "The user credentials were incorrect";
                toastr()->error($strMessage);
                return redirect()->back();
            }
			//dd($arrOutputData);
		} catch (Exception $e) {
			$arrOutputData = [];
			dd($e);
			$strMessage = "The user credentials were incorrect";
			$intCode 	= Response::HTTP_UNAUTHORIZED;
			$strStatus	= Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		}
	}

	public function subAdmin(){
		return view('admin.create-sub-admin');
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
			'user_id'   => 	'required|min:4',
			'password'  => 	'required|min:6',
			'fullname'	=>	'required',
			'email'		=>	'required',
			'mobile'	=>	'required',
			'type'		=>	'required',
		);
		$messages = array(
			'password.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *'
		);

		//check validations for input parameter
		$validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $request->flash(); // store the input data in the session
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                toastr()->error($err);
                return back();
                } 
                // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		} else {

			$isUserExistWithEmail = $this->commonController->getLoggedUserData(['email' => $arrInput['email']]);
			$isUserExistWithUserId = $this->commonController->getLoggedUserData(['user_id' => $arrInput['user_id']]);

			//check user exsited with email
			/*if(!empty($isUserExistWithEmail)){
      			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'],'Subadmin already existed with this email','');
      		}*/
			//check user exsited with user id
			if (!empty($isUserExistWithUserId)) {
				toastr()->error('Already existed user with this User ID');
				return back();
				// return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'already existed user with this User ID', '');
			}

			//if(empty($isUserExistWithEmail) && empty($isUserExistWithUserId)) {
			if (empty($isUserExistWithUserId)) {

				if($arrInput['type'] == "Admin")
				{
					$arrInsert = [
						'fullname' 	=> $arrInput['fullname'],
						'user_id' 	=> $arrInput['user_id'],
						'bcrypt_password'   => bcrypt($arrInput['password']),
						'password'          => encrypt($arrInput['password']),
						'mobile' 	=> $arrInput['mobile'],
						'email' 	=> $arrInput['email'],
						'type' 		=> $arrInput['type'],
						'admin_access' => '0',
						'facebook_link' 		=> '',
						'twitter_link' 		=> '',
						'linkedin_link' 		=> '',
						'instagram_link' 		=> '',
						'structure_id' 		=> '0',
						'status' 		=> 'Active',
						'remember_token' => md5(uniqid(rand(), true)),
						'entry_time' => now(),
					];
				}
				else{
					$arrInsert = [
						'fullname' 	=> $arrInput['fullname'],
						'user_id' 	=> $arrInput['user_id'],
						'bcrypt_password'   => bcrypt($arrInput['password']),
						'password'          => encrypt($arrInput['password']),
						'mobile' 	=> $arrInput['mobile'],
						'email' 	=> $arrInput['email'],
						'type' 		=> $arrInput['type'],
						'facebook_link' 		=> '',
						'twitter_link' 		=> '',
						'linkedin_link' 		=> '',
						'instagram_link' 		=> '',
						'structure_id' 		=> '0',
						'status' 		=> 'Active',
						'remember_token' => md5(uniqid(rand(), true)),
						'entry_time' => now(),
					];
				}
				//add sudadmin
				$storeSubadmin = User::insertGetId($arrInsert);
				
				if (!empty($storeSubadmin)) {


					if($arrInput['type'] == "Admin")
					{
						DB::table('tbl_ps_admin_rights')->insert([
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 19],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 79],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 111],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 140],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 141],
							['user_id' => $storeSubadmin, 'parent_id' => 23, 'navigation_id' => 24],
							['user_id' => $storeSubadmin, 'parent_id' => 23, 'navigation_id' => 86],
							['user_id' => $storeSubadmin, 'parent_id' => 23, 'navigation_id' => 126],
							['user_id' => $storeSubadmin, 'parent_id' => 23, 'navigation_id' => 165],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 29],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 31],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 32],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 33],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 54],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 57],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 72],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 91],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 95],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 145],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 158],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 159],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 166],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 180],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 190],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 191],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 202],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 81],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 82],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 146],
							['user_id' => $storeSubadmin, 'parent_id' => 117, 'navigation_id' => 118],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 119],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 120],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 154],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 155],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 160],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 164],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 192],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 193],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 194],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 195],
							['user_id' => $storeSubadmin, 'parent_id' => 132, 'navigation_id' => 133],
							['user_id' => $storeSubadmin, 'parent_id' => 132, 'navigation_id' => 208],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 143],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 144],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 150],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 177],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 178],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 179],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 205],
							['user_id' => $storeSubadmin, 'parent_id' => 142, 'navigation_id' => 206],
							['user_id' => $storeSubadmin, 'parent_id' => 147, 'navigation_id' => 148],
							['user_id' => $storeSubadmin, 'parent_id' => 147, 'navigation_id' => 149],
							['user_id' => $storeSubadmin, 'parent_id' => 147, 'navigation_id' => 156],
							['user_id' => $storeSubadmin, 'parent_id' => 147, 'navigation_id' => 157],
							['user_id' => $storeSubadmin, 'parent_id' => 151, 'navigation_id' => 152],
							['user_id' => $storeSubadmin, 'parent_id' => 151, 'navigation_id' => 153],
							['user_id' => $storeSubadmin, 'parent_id' => 151, 'navigation_id' => 203],
							['user_id' => $storeSubadmin, 'parent_id' => 151, 'navigation_id' => 204],
							['user_id' => $storeSubadmin, 'parent_id' => 161, 'navigation_id' => 162],
							['user_id' => $storeSubadmin, 'parent_id' => 161, 'navigation_id' => 163],
							['user_id' => $storeSubadmin, 'parent_id' => 167, 'navigation_id' => 168],
							['user_id' => $storeSubadmin, 'parent_id' => 167, 'navigation_id' => 169],
							['user_id' => $storeSubadmin, 'parent_id' => 172, 'navigation_id' => 173],
							['user_id' => $storeSubadmin, 'parent_id' => 172, 'navigation_id' => 174],
							['user_id' => $storeSubadmin, 'parent_id' => 172, 'navigation_id' => 175],
							['user_id' => $storeSubadmin, 'parent_id' => 172, 'navigation_id' => 176],
							['user_id' => $storeSubadmin, 'parent_id' => 170, 'navigation_id' => 181],
							['user_id' => $storeSubadmin, 'parent_id' => 171, 'navigation_id' => 182],
							['user_id' => $storeSubadmin, 'parent_id' => 183, 'navigation_id' => 184],
							['user_id' => $storeSubadmin, 'parent_id' => 183, 'navigation_id' => 185],
							['user_id' => $storeSubadmin, 'parent_id' => 183, 'navigation_id' => 186],
							['user_id' => $storeSubadmin, 'parent_id' => 183, 'navigation_id' => 187],
							['user_id' => $storeSubadmin, 'parent_id' => 183, 'navigation_id' => 197],
							['user_id' => $storeSubadmin, 'parent_id' => 183, 'navigation_id' => 198],
							['user_id' => $storeSubadmin, 'parent_id' => 183, 'navigation_id' => 199],
							['user_id' => $storeSubadmin, 'parent_id' => 188, 'navigation_id' => 189],
							['user_id' => $storeSubadmin, 'parent_id' => 200, 'navigation_id' => 201],
							['user_id' => $storeSubadmin, 'parent_id' => 200, 'navigation_id' => 207],
							['user_id' => $storeSubadmin, 'parent_id' => 200, 'navigation_id' => 238],
							['user_id' => $storeSubadmin, 'parent_id' => 200, 'navigation_id' => 239],
							['user_id' => $storeSubadmin, 'parent_id' => 200, 'navigation_id' => 240],
							['user_id' => $storeSubadmin, 'parent_id' => 214, 'navigation_id' => 215],
							['user_id' => $storeSubadmin, 'parent_id' => 216, 'navigation_id' => 217],
							['user_id' => $storeSubadmin, 'parent_id' => 218, 'navigation_id' => 219],
							['user_id' => $storeSubadmin, 'parent_id' => 218, 'navigation_id' => 220],
							['user_id' => $storeSubadmin, 'parent_id' => 218, 'navigation_id' => 221],
							['user_id' => $storeSubadmin, 'parent_id' => 218, 'navigation_id' => 222],
							['user_id' => $storeSubadmin, 'parent_id' => 225, 'navigation_id' => 226],
							['user_id' => $storeSubadmin, 'parent_id' => 225, 'navigation_id' => 227],
							['user_id' => $storeSubadmin, 'parent_id' => 215, 'navigation_id' => 229],
							['user_id' => $storeSubadmin, 'parent_id' => 232, 'navigation_id' => 233],
							['user_id' => $storeSubadmin, 'parent_id' => 232, 'navigation_id' => 234],
							['user_id' => $storeSubadmin, 'parent_id' => 235, 'navigation_id' => 236],
							['user_id' => $storeSubadmin, 'parent_id' => 235, 'navigation_id' => 237],
							['user_id' => $storeSubadmin, 'parent_id' => 241, 'navigation_id' => 242],
							['user_id' => $storeSubadmin, 'parent_id' => 241, 'navigation_id' => 243],
							['user_id' => $storeSubadmin, 'parent_id' => 247, 'navigation_id' => 248],
							['user_id' => $storeSubadmin, 'parent_id' => 247, 'navigation_id' => 249],
							['user_id' => $storeSubadmin, 'parent_id' => 251, 'navigation_id' => 252],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 258],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 259],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 260],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 261],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 262],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 263],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 264],
							['user_id' => $storeSubadmin, 'parent_id' => 257, 'navigation_id' => 265],
							['user_id' => $storeSubadmin, 'parent_id' => 266, 'navigation_id' => 267],
							['user_id' => $storeSubadmin, 'parent_id' => 266, 'navigation_id' => 269],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 270],
							['user_id' => $storeSubadmin, 'parent_id' => 87, 'navigation_id' => 271],
							['user_id' => $storeSubadmin, 'parent_id' => 114, 'navigation_id' => 112],
							['user_id' => $storeSubadmin, 'parent_id' => 114, 'navigation_id' => 113],
							['user_id' => $storeSubadmin, 'parent_id' => 114, 'navigation_id' => 272],
							['user_id' => $storeSubadmin, 'parent_id' => 114, 'navigation_id' => 273],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 275],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 276],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 277],
							['user_id' => $storeSubadmin, 'parent_id' => 277, 'navigation_id' => 278],
							['user_id' => $storeSubadmin, 'parent_id' => 277, 'navigation_id' => 279],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 280],
							['user_id' => $storeSubadmin, 'parent_id' => 18, 'navigation_id' => 281],
							['user_id' => $storeSubadmin, 'parent_id' => 282, 'navigation_id' => 283],
							['user_id' => $storeSubadmin, 'parent_id' => 282, 'navigation_id' => 284],
							['user_id' => $storeSubadmin, 'parent_id' => 286, 'navigation_id' => 287],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 288],
							['user_id' => $storeSubadmin, 'parent_id' => 289, 'navigation_id' => 290],
							['user_id' => $storeSubadmin, 'parent_id' => 289, 'navigation_id' => 291],
							['user_id' => $storeSubadmin, 'parent_id' => 289, 'navigation_id' => 292],
							['user_id' => $storeSubadmin, 'parent_id' => 1, 'navigation_id' => 3],
							['user_id' => $storeSubadmin, 'parent_id' => 285, 'navigation_id' => 293],
							['user_id' => $storeSubadmin, 'parent_id' => 295, 'navigation_id' => 296],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 297],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 298],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 299],
							['user_id' => $storeSubadmin, 'parent_id' => 289, 'navigation_id' => 300],
							['user_id' => $storeSubadmin, 'parent_id' => 289, 'navigation_id' => 301],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 302],
							['user_id' => $storeSubadmin, 'parent_id' => 80, 'navigation_id' => 303],
							['user_id' => $storeSubadmin, 'parent_id' => 132, 'navigation_id' => 304],
							['user_id' => $storeSubadmin, 'parent_id' => 132, 'navigation_id' => 305],
							['user_id' => $storeSubadmin, 'parent_id' => 28, 'navigation_id' => 306],
						]);			
						
					}


					toastr()->success('Subadmin created successfully');
					return back();
					// return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Subadmin created successfully', '');
				} else {
					toastr()->error('Error in creating subadmin');
					return back();
					// return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error in creating subadmin', '');
				}
			} else {
				toastr()->error('Already existed User with this user id');
				return back();
				// return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], ' already existed User with this user id', '');
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
            if (empty(Auth::user())) {
                toastr()->error('Something went wrong');
                return redirect()->back();
            } else {

                $request->session()->invalidate();

                $request->session()->regenerateToken();

                Auth::logout();

                toastr()->success('Logout Successfully');

                return redirect('/4P8Sr5Xf83lq/login');
            }
        } catch (Exception $e) {
            toastr()->error('Something went wrong');
            return redirect()->back();
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
            //changed column to bcrypt password :: Imran
            $credentials = array('user_id' => $request->input('user_id'), 'password' => $arrInput['password']);
            //dd(Auth::attempt($credentials));
            //changed auth call & added extra else :: Imran
            if (Auth::attempt($credentials)) {
                $strMessage	= "Login successful.";
                toastr()->success($strMessage);
                return redirect()->intended('/dashboard');
            }
            else{
                $strMessage    = "The user credentials were incorrect";
                toastr()->error($strMessage);
                return redirect()->back();
            }
//			$http = new Client();
//	        $response = $http->post($baseUrl.'/oauth/token', [
//				'form_params' => [
//				    'grant_type' 	=> 'password',
//				    'client_id' => "6",
//                    'client_secret' => "e4s9XtT5RlyXqNAdZYK5xgnSs6xh5NX76NviQ2TH",
//				   /* 'client_id' 	=> env('CLIENT_ID'),
//				    'client_secret' => env('CLIENT_SECRETE'),*/
//				    'username' 		=> $arrInput['user_id'],
//				    'password' 		=> $arrInput['password'],
//				    'scope' 		=> '*',
//				    //'code'			=>	$request->code
//				],
//			]);

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

    public function UserloginNew(Request $request)
    {

        $arrOutputData                     = [];
        $arrOutputData['mailverification'] = $arrOutputData['google2faauth'] = $arrOutputData['mailotp'] = $arrOutputData['mobileverification'] = $arrOutputData['otpmode'] = 'FALSE';

        try {

            $arrInput        = $request->all();
            $projectSettings = ProjectSettings::where('status', 1)
                ->select('login_status', 'login_msg')->first();
            if ($projectSettings->login_status == "off") {
                $intCode    = Response::HTTP_UNAUTHORIZED;
                $strStatus  = Response::$statusTexts[$intCode];
                $strMessage = $projectSettings->login_msg;
                toastr()->error($strMessage);
                return redirect()->back();
            }
            $validator = Validator::make($arrInput, [
                'user_id'  => 'required',
                'password' => 'required',
            ]);
            // check for validation
            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            $userData = User::select('bcrypt_password', 'password','login_allow_status')->where([['user_id', '=', $arrInput['user_id']], ['type', '=', '']])->first();
            if (empty($userData)) {
                $intCode    = Response::HTTP_UNAUTHORIZED;
                $strStatus  = Response::$statusTexts[$intCode];
                $strMessage = 'Invalid User ID';
                toastr()->error($strMessage);
                return redirect()->back();
            } else {
                if ($userData->login_allow_status=='0') {
                    $intCode    = Response::HTTP_UNAUTHORIZED;
                    $strStatus  = Response::$statusTexts[$intCode];
                    $strMessage = 'Login not allowed for this User ID';
                    toastr()->error($strMessage);
                    return redirect()->back();
                } else {
                    $flag       = 0;
                    $master_password_status = ProjectSettingModel::where('status', 1)->select('master_pass_status')->first();
                    if ($master_password_status->master_pass_status == 'on') {
                        $master_pwd = MasterpwdModel::where([['password', '=', md5($arrInput['password'])]])->first();
                        if (!empty($master_pwd)) {
                            $arrInput['password'] = decrypt($userData->password);
                            $flag                 = 1;
                        }
                    }


                    //changed column to bcrypt password :: Imran
                    if (!Hash::check($request->Input('password'), $userData->bcrypt_password) && $flag == 0) {
                        $strMessage = 'Invalid password';
                        toastr()->error($strMessage);
                        return redirect()->back();
                    }
                    // check user status
                    $arrWhere       = [['user_id', $arrInput['user_id']], ['status', 'Active']];

                    //changed column to bcrypt password :: Imran
                    $userDataActive = User::select('password')->where($arrWhere)->first();
                    if (empty($userDataActive)) {
                        $intCode    = Response::HTTP_UNAUTHORIZED;
                        $strStatus  = Response::$statusTexts[$intCode];
                        $strMessage = 'User is inactive,Please contact to admin';
                        toastr()->error($strMessage);
                        return redirect()->back();
                    }
                }

            }
            //changed column to bcrypt password :: Imran
            $credentials = array('user_id' => $request->input('user_id'), 'password' => $arrInput['password']);
            //dd(Auth::attempt($credentials));
            //changed auth call & added extra else :: Imran
            if (Auth::attempt($credentials)) {
                $strMessage	= "Login successful.";
                toastr()->success($strMessage);
                return redirect()->intended('/dashboard');
            }
            else{
                $strMessage    = "The user credentials were incorrect";
                toastr()->error($strMessage);
                return redirect()->back();
            }
        } catch (Exception $e) {
            $strMessage    = "The user credentials were incorrect";
            toastr()->error($strMessage);
            return redirect()->back();
        }
    }


    // Common function for impersonate
    public function loginUser(Request $request, $id)
    {
        $currentId = Auth::user()->id;
        if (Auth::user()->type == 'Admin') {
            Session::put('admin_id', $currentId);
        }
        Auth::loginUsingId($id);
        return redirect('/dashboard');
    }

}
