<?php

namespace App\Http\Controllers\userapi;

use Illuminate\Http\Response as Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\User;
use App\Models\Dashboard;
use App\Models\ProjectSettings;
use App\Models\Template;
use App\Models\Withdrawbydate;
use App\Models\verifyOtpStatus;
use App\Models\WhiteListIpAddress;
use App\Models\UserWithdrwalSetting;
use App\Models\Topup;
use App\Models\TodayDetails;




use App\Traits\CurrencyValidation;

use Config;
use Auth;
use Illuminate\Support\Carbon;

class SendotpController extends Controller
{
	use CurrencyValidation;

	protected $projects;
	public function __construct(Request $request)
	{
		$this->linkexpire   = Config::get('constants.settings.linkexpire');
		$this->statuscode   = Config::get('constants.statuscode');
		$this->authKey      = Config::get('constants.settings.authKey');
		$this->senderId     = Config::get('constants.settings.senderId');
		$this->OTP_interval = Config::get('constants.settings.OTP_interval');
		$this->sms_username = Config::get('constants.settings.sms_username');
		$this->sms_pwd      = Config::get('constants.settings.sms_pwd');
		$this->sms_route    = Config::get('constants.settings.sms_route');
		$this->emptyArray   = (object) array();

		$this->middleware(function ($request, $next) {
			$this->projects = Auth::user()->temp_info;

			return $next($request);
		});

		$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info,  $this->projects);
		if ($result == false) {
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
		}
	}

	public function sendotponmail($users, $username, $type = null)
	{

		$checotpstatus = Otp::where([['id', '=', $users->id],])->orderBy('entry_time', 'desc')->first();
		//dd($checotpstatus);
		if (!empty($checotpstatus)) {
			$entry_time   = $checotpstatus->entry_time;
			$out_time     = $checotpstatus->out_time;
			$checkmin     = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($entry_time)));
			$current_time = date('Y-m-d H:i:s');
		}

		/* if(!empty($checotpstatus) && $entry_time!='' && strtotime($checkmin)>=strtotime($current_time) && $checotpstatus->otp_status!='1'){
		$updateData=array();
		$updateData['otp_status']=0;

		$updateOtpSta=Otp::where('id', $users->id)->update($updateData);

		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'OTP already sent to your mail id', $this->emptyArray);

		}else{ */
		$temp_data        = Template::where('title', '=', 'Otp')->first();
		$project_set_data = ProjectSettings::select('icon_image', 'domain_name')->first();
		//dd($project_set_data);

		$otpExpireMit = Config::get('constants.settings.otpExpireMit');

		$mytime_new = \Carbon\Carbon::now();
		$expire_time = \Carbon\Carbon::now()->addMinutes($otpExpireMit)->toDateTimeString();
		$current_time_new = $mytime_new->toDateTimeString();

		$pagename    = "emails.otpsend";
		$subject     = $temp_data->subject;
		$content     = $temp_data->content;
		$domain_name = $project_set_data->domain_name;
		// $subject = "OTP sent successfully";
		$random = rand(100000, 999999);
		/*$random = 123456;*/
		$data   = array('pagename' => $pagename, 'otp' => $random, 'username' => $users->user_id, 'content' => $content, 'domain_name' => $domain_name);
		//dd($data, $username, $subject);
		$mail = sendMail($data, $username, $subject);
		//dd($mail);
		$insertotp               = array();
		$insertotp['id']         = $users->id;
		$insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
		// $insertotp['otp']        = md5($random);
		$insertotp['otp']        = hash('sha256', $random);
		$insertotp['otp_status'] = 0;
		$insertotp['type']       = 'email';
		$insertotp['otpexpire'] = $expire_time;
		$insertotp['entry_time'] = $current_time_new;
		$sendotp                 = Otp::create($insertotp);

		$arrData = array();
		// $arrData['id']   = $users->id;
		$arrData['remember_token'] = $users->remember_token;

		$arrData['mailverification']   = 'TRUE';
		$arrData['google2faauth']      = 'FALSE';
		$arrData['mailotp']            = 'TRUE';
		$arrData['mobileverification'] = 'TRUE';
		$arrData['otpmode']            = 'FALSE';
		//$mask_mobile = maskmobilenumber($users->mobile);
		$mask_email       = maskEmail($users->email);
		$arrData['email'] = $mask_email;
		//$arrData['mobile'] = $mask_mobile;

		if ($type == null) {
			return $random;
		}

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', $random);

		return $sendotp;

		//}  // end of users
	}

	//=========================================================
	public function sendotponmobile($users, $username)
	{

		$checotpstatus = Otp::where([['id', '=', $users->id],])->orderBy('entry_time', 'desc')->first();

		if (!empty($checotpstatus)) {
			$entry_time   = $checotpstatus->entry_time;
			$out_time     = $checotpstatus->out_time;
			$checkmin     = date('Y-m-d H:i:s', strtotime($this->OTP_interval, strtotime($entry_time)));
			$current_time = date('Y-m-d H:i:s');
		}

		if (false/* !empty($checotpstatus) && $entry_time!='' && strtotime($checkmin)>=strtotime($current_time) && $checotpstatus->otp_status!='1' */) {
			$updateData               = array();
			$updateData['otp_status'] = 0;

			$updateOtpSta = Otp::where('id', $users->id)->update($updateData);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'OTP already sent to your mobile no', $this->emptyArray);
		} else {

			$random = rand(100000, 999999);

			$numbers  = urlencode($users->mobile);
			$username = urlencode($this->sms_username);
			$pass     = urlencode($this->sms_pwd);
			$route    = urlencode($this->sms_route);
			$senderid = urlencode($this->senderId);
			$OTP      = $random;
			$msg      = '' . $OTP . ' is your verification code ';
			$message  = urlencode($msg);

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL            => "http://173.45.76.227/send.aspx?username=" . $username . "&pass=" . $pass . "&route=" . $route . "&senderid=" . $senderid . "&numbers=" . $numbers . "&message=" . $message,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING       => "",
				CURLOPT_MAXREDIRS      => 10,
				CURLOPT_TIMEOUT        => 30,
				CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST  => "GET",
				CURLOPT_POSTFIELDS     => "",
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => 0,
			));

			$response = curl_exec($curl);
			$err      = curl_error($curl);

			curl_close($curl);

			if ($err) {
				// echo "cURL Error #:" . $err;
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
			} else {
				//echo $response;
				$insertotp               = array();
				$insertotp['id']         = $users->id;
				$insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
				$insertotp['otp']        = md5($random);
				$insertotp['otp_status'] = 0;
				$insertotp['type']       = 'mobile';
				$sendotp                 = Otp::create($insertotp);

				$arrData = array();
				// $arrData['id']   = $users->id;
				$arrData['remember_token']     = $users->remember_token;
				$arrData['mailverification']   = 'TRUE';
				$arrData['google2faauth']      = 'FALSE';
				$arrData['mailotp']            = 'TRUE';
				$arrData['mobileverification'] = 'TRUE';
				$arrData['otpmode']            = 'FALSE';
				$mask_mobile                   = maskmobilenumber($users->mobile);
				$mask_email                    = maskEmail($users->email);
				$arrData['email']              = $mask_email;
				$arrData['mobile']             = $mask_mobile;
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your mobile no ', $arrData);

				return $sendotp;
			}
		} // end of users
	}






	public function sendotpEditUserProfile_2(Request $request)
	{

		$user = Auth::User();
		//dd($request->type);
		if ($request->type == "Withdrawal") {
			$id   = Auth::user()->id;
			$dash = Dashboard::where('id', $id)->select('working_wallet', 'working_wallet_withdraw')
				->first();
			$working_wallet_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

			$projectSettings = ProjectSettings::select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->where('status', 1)->first();
			$day             = \Carbon\Carbon::now()->format('D');
			$date_day        = \Carbon\Carbon::now()->format('d');
			$hrs             = \Carbon\Carbon::now()->format('H');
			$hrs             = (int) $hrs;
			$days            = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");

			$withdrawSetting = Withdrawbydate::select('first_day', 'second_day', 'third_day', 'status')->first();
			/* if(!empty($withdrawSetting) && $withdrawSetting->status == "on"){
			if($date_day == $withdrawSetting->first_day){
			// dd('first');
			//}else if($date_day == $withdrawSetting->second_day){
			// dd('second');

			//}else if($date_day == $withdrawSetting->third_day){
			// dd('third');

			//}else{
			//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of Month', '');
			//}

			}*/
			if ($projectSettings->withdraw_status == "off") {
				$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals next Sunday.';
				if ($projectSettings->withdraw_off_msg != '' && $projectSettings->withdraw_off_msg != NULL) {
					$msg = $projectSettings->withdraw_off_msg;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			}

			if ($request->working_wallet > $working_wallet_balance) {
				return sendresponse(
					$this->statuscode[404]['code'],
					$this->statuscode[404]['status'],
					'Insufficient Balance',
					''
				);
			}
			// if($day != $projectSettings->withdraw_day)
			// {
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// } elseif($hrs < $projectSettings->withdraw_start_time){
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// }
		}
		if ($request->type == "balance_transfer") {
			$bal = Dashboard::selectRaw('round(working_wallet-working_wallet_withdraw,2) as balance')->where('id', $user->id)->pluck('balance')->first();
			if ($bal < 20) {
				return sendresponse(
					$this->statuscode[404]['code'],
					$this->statuscode[404]['status'],
					'Your Wallet is having less than 20$. Login from ID which have 20$ and then Try',
					''
				);
				// $arrStatus  = Response::HTTP_NOT_FOUND;
				// $arrCode    = Response::$statusTexts[$arrStatus];
				// $arrMessage = 'Your Wallet is having less than 20$. Login from ID which have 20$ and then Try';
				//return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			/*$projectSettings = ProjectSettings::select('withdraw_day','withdraw_start_time')->where('status', 1)->first();
		$day = \Carbon\Carbon::now()->format('D');
		$hrs = \Carbon\Carbon::now()->format('H');
		$hrs = (int) $hrs;
		$days = array('Mon'=>"Monday",'Tue'=>"Tuesday",'Wed'=>"Wednesday",'Thu'=>"Thursday",'Fri'=>"Friday",'Sat'=>"Saturday",'Sun'=>"Sunday");
		if($day != $projectSettings->withdraw_day)
		{
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can add request only on '.$days[$projectSettings->withdraw_day].' after '.$projectSettings->withdraw_start_time.' AM', '');
		} elseif($hrs < $projectSettings->withdraw_start_time){
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can add request only on '.$days[$projectSettings->withdraw_day].' after '.$projectSettings->withdraw_start_time.' AM', '');
		}*/
		}

		$username = $user->fullname;
		$mail     = $user->email;

		//$mobileResponse = $this->sendotponmobile($user,$username);
		$emailResponse = $this->sendotponmail($user, $mail);
		//dd($emailResponse);
		$whatsappMsg = "Your OTP is -: " . $emailResponse;

		$countrycode = getCountryCode($user->country);

		$mobile = $user->mobile;

		//sendSMS($mobile, $whatsappMsg);
		//sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
	}

	public function SendOtpForEditProfile(Request $request)
	{
		if (!empty($request->btc)) {
			$checkAddress =  $this->checkcurrencyvalidaion('BTC', $request->input('btc'));

			if ($checkAddress != '') {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}

		if (!empty($request->bnb_address)) {
			$checkAddress =  $this->checkcurrencyvalidaion('bnb_address', $request->input('bnb_address'));

			if ($checkAddress != '') {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}

		if (!empty($request->ethereum)) {
			$checkAddress =  $this->checkcurrencyvalidaion('ethereum', $request->input('ethereum'));

			if ($checkAddress != '') {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}

		if (!empty($request->trn_address)) {
			$checkAddress =  $this->checkcurrencyvalidaion('trn_address', $request->input('trn_address'));

			if ($checkAddress != '') {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}

		$user = Auth::User();

		$result = SendOtpForAll($user);

		if ($result) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
		}
	}

	public function sendotpEditUserProfile(Request $request)
	{
		// check user is from same browser or not

		// $intCode = Response::HTTP_NOT_FOUND;
		// $strStatus = Response::$statusTexts[$intCode];
		// $strMessage = 'Your gateway to a strong financial future is going to open soon!';
		// return sendResponse($intCode, $strStatus, $strMessage, array());

		// dd("stop");

		$user = Auth::User();
		$id = Auth::User()->id;
		//dd($request->type);
		if ($request->type == "Withdrawal") {
			$status = User::select('withdraw_block_by_admin')->where('id', $id)->first();
			if ($status['withdraw_block_by_admin'] === 1) {
				$msg = 'Thank You for Requesting But Your Withdraw Block By Admin.';
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			}
			// $status = verifyOtpStatus::select('withdraw_update_status')->where('statusID',1)->first();
			// if($status['withdraw_update_status'] === 0)
			// {
			//     $msg = 'Your Withdraw Update Status With OTP is Disable';
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$msg, ''); 
			// }        
			$projectSettings = ProjectSettings::select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->where('status', 1)->first();
			$day = \Carbon\Carbon::now()->format('D');
			$date_day = \Carbon\Carbon::now()->format('d');
			$hrs = \Carbon\Carbon::now()->format('H');
			$hrs = (int) $hrs;
			$days = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");

			$withdrawSetting = Withdrawbydate::select('first_day', 'second_day', 'third_day', 'status')->first();
			/* if(!empty($withdrawSetting) && $withdrawSetting->status == "on"){
                if($date_day == $withdrawSetting->first_day){
                    // dd('first');
                //}else if($date_day == $withdrawSetting->second_day){
                    // dd('second');

                //}else if($date_day == $withdrawSetting->third_day){
                    // dd('third');

                //}else{
                    //return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of Month', ''); 
                //}
                  
            }*/
			if ($projectSettings->withdraw_status == "off") {
				$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals next Sunday.';
				if ($projectSettings->withdraw_off_msg != '' && $projectSettings->withdraw_off_msg != NULL) {
					$msg = $projectSettings->withdraw_off_msg;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			}
			// if($day != $projectSettings->withdraw_day)
			// {
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, ''); 
			// } elseif($hrs < $projectSettings->withdraw_start_time){
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, ''); 
			// }
		}

		if ($request->type == "transfer") {
			$status = User::select('transfer_block_by_admin')->where('id', $id)->first();
			if ($status['transfer_block_by_admin'] === 1) {
				$msg = 'Thank You for Requesting But Your transfer Block By Admin.';
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			}
			// $status = verifyOtpStatus::select('transfer_update_status')->where('statusID',1)->first();
			// if($status['transfer_update_status'] === 0)
			// {
			//     $msg = 'Your Transfer Update Status With OTP is Disable';
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$msg, ''); 
			// }   
			$bal = Dashboard::selectRaw('round(working_wallet-working_wallet_withdraw,2) as balance')->where('id', $user->id)->pluck('balance')->first();
			if ($bal < 20) {
				return sendresponse(
					$this->statuscode[404]['code'],
					$this->statuscode[404]['status'],
					'Your Wallet is having less than 20$. Login from ID which have 20$ and then Try',
					''
				);
				// $arrStatus   = Response::HTTP_NOT_FOUND;
				// $arrCode     = Response::$statusTexts[$arrStatus];
				// $arrMessage  = 'Your Wallet is having less than 20$. Login from ID which have 20$ and then Try';
				// return sendResponse($arrStatus, $arrCode, $arrMessage, ''); 
			}
			/*$projectSettings = ProjectSettings::select('withdraw_day','withdraw_start_time')->where('status', 1)->first();
            $day = \Carbon\Carbon::now()->format('D');          
            $hrs = \Carbon\Carbon::now()->format('H');
            $hrs = (int) $hrs;
            $days = array('Mon'=>"Monday",'Tue'=>"Tuesday",'Wed'=>"Wednesday",'Thu'=>"Thursday",'Fri'=>"Friday",'Sat'=>"Saturday",'Sun'=>"Sunday");
            if($day != $projectSettings->withdraw_day)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can add request only on '.$days[$projectSettings->withdraw_day].' after '.$projectSettings->withdraw_start_time.' AM', ''); 
            } elseif($hrs < $projectSettings->withdraw_start_time){ 
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can add request only on '.$days[$projectSettings->withdraw_day].' after '.$projectSettings->withdraw_start_time.' AM', ''); 
            }*/
		}

		$username = $user->fullname;
		$mail = $user->email;

		//$mobileResponse = $this->sendotponmobile($user,$username);      
		$emailResponse = $this->sendotponmail($user, $mail);
		//dd($emailResponse);
		$whatsappMsg = "Your OTP is -: " . $emailResponse;

		$countrycode = getCountryCode($user->country);

		$mobile = $user->mobile;

		//sendSMS($mobile, $whatsappMsg);
		sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
	}

	public function send3XmailNotification(Request $request)
	{
		try {
			$capping_percentage=$request->capping_percentage;
			$mail_status=$request->mail_status;
			// dd('mail_status',$mail_status,'$capping_percentage', $capping_percentage);
			$user = Auth::User();
			$id = Auth::User()->id;
			$project_set_data = ProjectSettings::select('icon_image', 'domain_name')->first();
			$username = $user->fullname;
			$mail = $user->email;

			$mobile = $user->mobile;

			$pagename    = "emails.send3xmail";
			// dd($user->cap_mail_status);
			$content='';
			// $subject     = '3x capping reminder';
			$subject     = '10x capping reminder';
			if ($capping_percentage >= 80 && $capping_percentage < 100) {
				/*$content     = 'Congratulations! We are happy to see you grow. You are about to reach your 3x capping of your account. Kindly, top up again to increase your 3x capping and continue earning.';*/
				$content     = 'Congratulations! We are happy to see you grow. You are about to reach your 10x capping of your account. Kindly, top up again to increase your 10x capping and continue earning.';
			} else if($capping_percentage == 100){
				/*$content     = 'Congratulations! We are happy to see you grow. You have reached 3x capping of your account.Kindly, top up again to increase your 3x capping and continue earning.';*/
				$content     = 'Congratulations! We are happy to see you grow. You have reached 10x capping of your account.Kindly, top up again to increase your 10x capping and continue earning.';
			}
			
			$domain_name = $project_set_data->domain_name;
			// $subject = "OTP sent successfully";
			/*$random = 123456;*/
			$data   = array('pagename' => $pagename, 'username' => $user->user_id, 'content' => $content, 'domain_name' => $domain_name);

			$UserUpdateData=array();
			// dd('$mail_status',$mail_status,'$user->cap_mail_status',$user->cap_mail_status);
			if ($mail_status == 1 && $user->cap_mail_status == 0) {


				$mail = sendMail($data, $mail, $subject);
				
				$UserUpdateData['cap_mail_status'] = 1;
				$updateData = User::where('id', $id)->update($UserUpdateData);
				/*return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '3X capping reminder notification sent ', '');*/
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '10X capping reminder notification sent ', '');
			} else if($mail_status == 1 && $user->cap_mail_status == 1){
				
				$UserUpdateData['cap_mail_status'] = 1;
				$updateData = User::where('id', $id)->update($UserUpdateData);
				/*return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'No 3X capping reminder notification sent ', '');*/
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'No 10X capping reminder notification sent ', '');
			}else{

				$UserUpdateData['cap_mail_status'] = 0;
				$updateData = User::where('id', $id)->update($UserUpdateData);
				/*return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '3X capping below 80%', '');*/
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '10X capping below 80%', '');
			}
			
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
		

		
	}
	public function SendOtpForSelfTopup(Request $request)
	{
		// dd($request);

		/* $intCode = Response::HTTP_NOT_FOUND;
		$strStatus = Response::$statusTexts[$intCode];
		$strMessage = 'Your gateway to a strong financial future is going to open soon!';
		return sendResponse($intCode, $strStatus, $strMessage, array());

		dd("stop"); */

		$rules = array(
			'product_id'       => 'required',
			'hash_unit'        => 'required|numeric|min:1',
			'user_id'          => 'required',
			'transcation_type' => 'required',
			/*'masterfranchise_user_id' => 'required',*/
		);
		$validator = checkvalidation($request->all(), $rules, '');
		if (!empty($validator)) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
		}

		// check user is from same browser or not
		$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info, Auth::user()
			->temp_info);
		if ($result == false) {
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
		}

		/*$auth_user = User::select('tbl_dashboard.top_up_wallet','tbl_dashboard.top_up_wallet_withdraw','tbl_dashboard.total_withdraw','tbl_users.id')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();*/
		$users = User::select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.virtual_parent_id', 'tbl_users.ref_user_id', 'tbl_dashboard.total_investment', 'tbl_dashboard.active_investment', 'tbl_users.position')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.user_id', '=', $request->user_id], ['tbl_users.status', '=', 'Active']])->first();
		// dd($users);
		if (empty($users)) {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This User ID is Blocked', $this->emptyArray);
		}

		$check_record = whiteListIpAddress($type = 1, Auth::user()->id);
		// dd($check_record);
		$ip_Address = getIpAddrss();
		$check_user_hits = WhiteListIpAddress::select('id', 'topup_status', 'topup_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
		// dd($check_user_hits);
		if (!empty($check_user_hits)) {
			if ($check_user_hits->topup_status == 1) {
				$today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
				if ($check_user_hits->topup_expire >= $today) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block for 1 hour!', $this->emptyArray);
				}
			}
		}


		// if ($request->transcation_type != 2 && $request->transaction_type != 1) {

		// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
		// }

		$checktopup = Topup::where([['id', $users->id]])->count();
		// dd($request->transcation_type);
		// if ($checktopup > 0) {
		// 	 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one topup allowed for one user', '');
		// }

		/* to check user downline or not */
		$id = $users->id;
		$users_id = Auth::User()->id;
		$userId = $to_user_id = $users_id;
		// dd($to_user_id);
		if ($to_user_id != $id) {
			$todaydetailsexist = TodayDetails::where('to_user_id', $to_user_id)->where('from_user_id', $id)->get();
			// dd($todaydetailsexist);
			if (count($todaydetailsexist) == 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User is not Downline User', '');
			}
		}
		$projectSettings = ProjectSettings::where('status', 1)
			->select('topup_status', 'topup_msg')->first();
		if ($projectSettings->topup_status == "off") {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = $projectSettings->topup_msg;;
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
		// check user is from same browser or not

		$user = Auth::User();
		$id = Auth::User()->id;
		//dd($request->type);

		$result = SendOtpForAll($user);

		if ($result) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
		}
	}
	public function SendOtpForChangePassword(Request $request)
	{
		$messsages = array(
			'new_pwd.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *',
		);
		//|regex:/^[a-zA-Z](?=.*\d)(?=.*[a-zA-Z])[0-9A-Za-z!@#$%]{6,50}$/
		$rules = array(
			'current_pwd' => 'required|',
			'new_pwd'     => [
				'string',
				'min:8', // must be at least 10 characters in length
				'max:15',
				'regex:/[a-z]/', // must contain at least one lowercase letter
				'regex:/[A-Z]/', // must contain at least one uppercase letter
				'regex:/[0-9]/', // must contain at least one digit
				'regex:/[@$!%*#?&]/', // must contain a special character',
			],
			'conf_pwd' => 'required|same:new_pwd',
		);

		$validator = checkvalidation($request->all(), $rules, $messsages);
		$result = isPasswordValid($request->new_pwd);

		if ($result['status'] == false) {
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = $result['message'];
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

		// if (!empty($validator)) {
		// 	$arrStatus  = Response::HTTP_NOT_FOUND;
		// 	$arrCode    = Response::$statusTexts[$arrStatus];
		// 	$arrMessage = $validator;
		// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		// }
		// check user is from same browser or not

		$user = Auth::User();
		$id = Auth::User()->id;
		//dd($request->type);

		// $user = Auth::User();

		$result = SendOtpForAll($user);

		if ($result) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
		}
	}
	public function SendOtpForWithdraw(Request $request)
	{
		// check user is from same browser or not

		/* $intCode   = Response::HTTP_NOT_FOUND;
		$strStatus = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, 'Your gateway to a strong financial future is going to open soon!', '');

		dd("stop"); */

		$user = Auth::User();
		$id = Auth::User()->id;
		//dd($request->Currency_type);

		$message = array('');
		$rules   = array(
			// 'working_wallet' => 'required|numeric|min:20',
			'Currency_type'  => 'required',
		);
		$messages = array(
			// 'working_wallet.required' => 'Please enter amount',
			'Currency_type.required'  => 'Please select currency',
			// 'working_wallet.numeric'  => 'Please enter valid amount',
			// 'working_wallet.min'      => 'Amount must be minimum 20',
			// 'working_wallet.digit'	  => 'You can enter maximum 8 digit'
		);
		$validator = checkvalidation($request->all(), $rules, $messages);
		if (!empty($validator)) {
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = $validator;
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

		if(Auth::User()->topup_status == 0)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Not having any Topup", ''); 
            }

        if(Auth::User()->capping_withdrawal_status == "Inactive")
        {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Not having Active Topup", ''); 
        }
        

		$check_record = whiteListIpAddress($type = 2, Auth::user()->id);
		$ip_Address = getIpAddrss();
		$check_user_hits = WhiteListIpAddress::select('id', 'withdraw_status', 'withdraw_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
		if (!empty($check_user_hits)) {
			if ($check_user_hits->withdraw_status == 1) {
				$today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
				if ($check_user_hits->withdraw_expire >= $today) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block!', $this->emptyArray);
				}
			}
		}

		//check cross browser (check_user_authentication_browser)
		$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info, Auth::user()->temp_info);
		if ($result == false) {
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
		}

		$projectSettings = ProjectSettings::where('status', 1)
			->select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->first();

		$day             = \Carbon\Carbon::now()->format('D');
		$date_day        = \Carbon\Carbon::now()->format('d');
		$hrs             = \Carbon\Carbon::now()->format('H');
		$hrs             = (int) $hrs;
		$days            = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");
		$withdrawSetting = Withdrawbydate::select('first_day', 'second_day', 'third_day', 'status')->first();
		if (!empty($withdrawSetting) && $withdrawSetting->status == "Active") {
			if ($date_day == $withdrawSetting->first_day) {
				// dd('first');
			} else if ($date_day == $withdrawSetting->second_day) {
				// dd('second');

			} else if ($date_day == $withdrawSetting->third_day) {
				// dd('third');

			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on ' . $withdrawSetting->first_day . ',' . $withdrawSetting->second_day . ',' . $withdrawSetting->third_day . ' of month', '');
			}
		}
		if($day != $projectSettings->withdraw_day){
			$msg = 'You can place withdrawals only on '.$days[$projectSettings->withdraw_day];
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
		}

		$user_id = Auth::user()->id;
		$arrInput = $request->all();
		$currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
		if (empty($currency_address)) {
			$intCode      = Response::HTTP_NOT_FOUND;
			$strStatus    = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
		}
		// else{
		//     if(!empty($currency_address->block_user_date_time)){
		//         $today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
		//         if($currency_address->block_user_date_time >= $today){
		//             $intCode      = Response::HTTP_NOT_FOUND;
		//             $strStatus    = Response::$statusTexts[$intCode];
		//             return sendResponse($intCode,$strStatus,'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)','');
		//         }
		//     }
		// }

		// if($day != $projectSettings->withdraw_day)
		// {
		//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
		// } elseif($hrs < $projectSettings->withdraw_start_time){
		//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
		// }
		$id       = Auth::user()->id;
		$user_id  = $id;
		$arrInput = $request->all();
		if ($arrInput['Currency_type'] == "BTC") {
			// dd($user_id);
			// $currency_address = User::where('id', $user_id)->pluck('btc_address')->first();
			$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'BTC')->pluck('currency_address')->first();
			if (empty($currency_address)) {
				$intCode   = Response::HTTP_NOT_FOUND;
				$strStatus = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update BTC address to withdraw amount', '');
			}
		}
		if ($arrInput['Currency_type'] == "TRX") {
			// $currency_address = User::where('id', $user_id)->pluck('trn_address')->first();
			$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'TRX')->pluck('currency_address')->first();

			if (empty($currency_address)) {
				$intCode   = Response::HTTP_NOT_FOUND;
				$strStatus = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update Tron address to withdraw amount', '');
			}
		}
		if ($arrInput['Currency_type'] == "ETH") {
			// $currency_address = User::where('id', $user_id)->pluck('ethereum')->first();
			$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'ETH')->pluck('currency_address')->first();

			if (empty($currency_address)) {
				$intCode   = Response::HTTP_NOT_FOUND;
				$strStatus = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update ETH address to withdraw amount', '');
			}
		}
		if ($arrInput['Currency_type'] == "BNB.ERC20") {
			$currency_address = User::where('id', $user_id)->pluck('bnb_address')->first();

			if (empty($currency_address)) {
				$intCode   = Response::HTTP_NOT_FOUND;
				$strStatus = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update BNB address to withdraw amount', '');
			}
		}
		// $user = Auth::User();
		$userData=User::where('id',$user_id)->first();
		if ($userData->google2fa_status=='disable') {
			$result = SendOtpForAll($user);
			if ($result) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
			}
		}else{
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
		}





		// $username = $user->fullname;
		// $mail = $user->email;

		// //$mobileResponse = $this->sendotponmobile($user,$username);      
		// $emailResponse = $this->sendotponmail($user,$mail);
		// //dd($emailResponse);
		// $whatsappMsg = "Your OTP is -: " . $emailResponse ;

		// $countrycode = getCountryCode($user->country);

		// $mobile = $user->mobile;

		// //sendSMS($mobile, $whatsappMsg);
		// //sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

		// return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
	}

	public function sendOtpForTransfer(Request $request)
	{
		// check user is from same browser or not

		$user = Auth::User();
		$id = Auth::User()->id;
		$rules = array(
			'amount' => 'required|numeric|min:20',
			/*'to_user_id' => 'required',*/
		);
		$validator = checkvalidation($request->all(), $rules, '');
		if (!empty($validator)) {
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = $validator;
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

		$check_record = whiteListIpAddress($type = 3, Auth::user()->id);

		$ip_Address = getIpAddrss();
		$check_user_hits = WhiteListIpAddress::select('id', 'transfer_status', 'transfer_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
		if (!empty($check_user_hits)) {
			if ($check_user_hits->transfer_status == 1) {
				$today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
				if ($check_user_hits->transfer_expire >= $today) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block!', $this->emptyArray);
				}
			}
		}
		// $user = Auth::User();

		$result = SendOtpForAll($user);

		if ($result) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
		}

		// $username = $user->fullname;
		// $mail = $user->email;

		// //$mobileResponse = $this->sendotponmobile($user,$username);      
		// $emailResponse = $this->sendotponmail($user,$mail);
		// //dd($emailResponse);
		// $whatsappMsg = "Your OTP is -: " . $emailResponse ;

		// $countrycode = getCountryCode($user->country);

		// $mobile = $user->mobile;

		// //sendSMS($mobile, $whatsappMsg);
		// //sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

		// return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
	}
}
