<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\SettingsController;
use App\Http\Controllers\userapi\Google2FAController;
use App\Models\Activitynotification;
use App\Models\UserCurrAddrHistory;

use App\Models\AppVersion;
use App\Models\AppVersionLog;
use App\Models\Dashboard;
use App\Models\KYC;
use App\Models\Otp;
use App\Models\ProjectSettings;
use App\Models\Rank;
use App\Models\RegTempInfo;
use App\Models\Resetpassword;
use App\Models\SecureLoginData;
use App\Models\supermatching;
use App\Models\SupperMatchingIncome;
use App\Models\TodayDetails;
use App\Models\UserWithdrwalSetting;
use App\Models\UserUpdateProfileCount;
use App\Models\WhiteListIpAddress;
use App\Models\MarketTool;

use App\Models\verifyOtpStatus;
use App\Traits\AddressValid;
use App\Traits\Users;
use App\Traits\CurrencyValidation;

use App\User;
use Carbon\Carbon;
// use Config;
use DB;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Validator;

class UserController extends Controller {

	use Users, AddressValid,CurrencyValidation;
	public function __construct(Google2FAController $google2facontroller) {
		$this->linkexpire = Config::get('constants.linkexpire');
		$date             = \Carbon\Carbon::now();
		$this->today      = $date->toDateTimeString();
		$this->statuscode = Config::get('constants.statuscode');
		$this->google2facontroller = $google2facontroller;
	}
	/**
	 * Registered user
	 *
	 * @return \Illuminate\Http\Response
	 */


	public function DiableAdress()
	{
        try
        {
			$getuser_id = Auth::user()->id;

            $result=DB::table('tbl_users')
            ->select('address_change_status')
            ->where('id', $getuser_id)
            ->first();

            if(!empty($result))
            {
                $arrData = $result;
                $arrStatus   = Response::HTTP_OK;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Address Change Data Found'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);
            }

        }
        catch(Exception $e){
              dd($e);      
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }   
	}

public function downlineuserauto($ref,$uid){
	return $this->levelView($ref,$uid,1);
}
	public function register(Request $request) {

		/*$intCode = Response::HTTP_NOT_FOUND;
		$strStatus = Response::$statusTexts[$intCode];
		$strMessage = 'Registrations are stopped till 12th September';
		return sendResponse($intCode, $strStatus, $strMessage, array());*/
		$arrInput  = $request->all();
      // if ($arrInput['type'] == 'email') {
      //   $arrRules  = array('email_otp' => 'required|min:6|max:6', 'email' => 'required');
      // } else {
      //   $strMessage = 'Something went wrong!!';
      //   $intCode    = Response::HTTP_NOT_FOUND;
      //   $strStatus  = Response::$statusTexts[$intCode];
      //   $arrOutputData['status'] = $arrInput['type'];
      //   return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
      // }

      // if ($arrInput['type'] == 'email') {
      //   $where = array('otp' => hash('sha256',$arrInput['email_otp']), 'email' => $arrInput['email'], 'status' => '0');
      //   $strMessage     = "Email Otp Verified";
      // }
      // $check_details = RegTempInfo::where($where)->orderBy('id', 'desc')->first();

      // // check otp status 1 - already used otp
      // if (empty($check_details)) {
      //   $strMessage = 'Invalid Otp';
      //   $intCode    = Response::HTTP_BAD_REQUEST;
      //   $strStatus  = Response::$statusTexts[$intCode];
      //   $arrOutputData['status'] = $arrInput['type'];
      //   return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
      // }
      // if ($check_details->status == 1) {
      //   $strMessage     = trans('user.otpverified');
      //   $intCode        = Response::HTTP_BAD_REQUEST;
      //   $strStatus      = Response::$statusTexts[$intCode];
      //   $arrOutputData['status'] = $arrInput['type'];
      //   return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
      // }
      // $otpId = $check_details->id;
      // $updateData = array();
      // $updateData['status'] = 1; //1 -verify otp
      // $updateData['out_time']=date('Y-m-d H:i:s');
      // $updateOtpSta =  RegTempInfo::where('id', $otpId)->update($updateData);
		$adm_id = User::select('id')->where('type', '=', 'Admin')->get();
		$ref_id = User::select('id')->where('user_id', '=', $request->ref_user_id)->get();
		if ($adm_id == $ref_id) {
			$refCount = User::where('ref_user_id', '=', $adm_id[0]->id)->count();
			if ($refCount >= 1) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cannot register with this Referral ID!';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		}
		// if($request->Input('email') == 'BashirWaibi00@gmail.com'){
		// 	$arrStatus  = Response::HTTP_NOT_FOUND;
		// 		$arrCode    = Response::$statusTexts[$arrStatus];
		// 		$arrMessage = 'Bas Kar Pagle Abbbbbbbbb!';
		// 		return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		// }
		

		try {

			$projectSettings = ProjectSettings::where('status', 1)
				->select('registration_status', 'char_fromate', 'user_id_Int_fromat', 'registration_msg')	->first();

			//dd($projectSettings->user_id_Int_fromat);
			// dd($projectSettings);
			$int  = $projectSettings->user_id_Int_fromat;
			$char = $projectSettings->char_fromate;
			//   $radUserId = substr(number_format(time() * rand(), 0, '', ''), 0, $int);
			//   $userId = $request->request->add(['user_id' => $char . $radUserId]);

			$arrValidation = User::registrationValidationRules();
			$validator     = checkvalidation($request->all(), $arrValidation['arrRules'], $arrValidation['arrMessage']);

			$result=isPasswordValid($request->password);
			
					if($result['status']==false)
					{
						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = $result['message'];
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

			if ($projectSettings->registration_status == "off") {
				$intCode    = Response::HTTP_UNAUTHORIZED;
				$strStatus  = Response::$statusTexts[$intCode];
				$strMessage = $projectSettings->registration_msg;
				return sendResponse($intCode, $strStatus, $strMessage, '');
			}

			if (!empty($validator)) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			//---end to check wether give address is valid or not---//
			/*a:$radUserId = substr(number_format(time() * rand(), 0, '', ''), 0, '10');
			$checkifrandnoExist = User::where('unique_user_id', $radUserId)->first();
			if (!empty($checkifrandnoExist)) {
			goto a;
			}*/

			// $request->request->add(['user_id' => $radUserId ]);
			// $request->request->add(['user_id' => $request->Input('email')]);

			$getuser = $this->checkSpecificUserData(['user_id' => $request->Input('user_id'), 'status' => 'Active']);
 
			if (empty($getuser)) {

				//   if ($request->input('password') == $request->input('password_confirmation')) {

				$refUserExist = User::select('user_id')->where([['user_id', '=', $request->Input('ref_user_id')], ['status', '=', 'Active']])->count();

				if ($refUserExist > 0) {
					$registation_plan = ProjectSettings::where([['status', '=', 1]])->pluck('registation_plan')->first();
				
					// if binary plan is on t
					//echo $registation_plan;
					if ($registation_plan == 'binary' && $request->Input('position') != 0) {
						return $this->binaryPlan($request);
					} else if ($registation_plan == 'level') {
						// if level plan on
						return $this->levelPlan($request);
					} else {
						$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Something went wrong,Please try again';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Sponser not exist';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
				/* } else {

			$arrStatus   = Response::HTTP_NOT_FOUND;
			$arrCode     = Response::$statusTexts[$arrStatus];
			$arrMessage  = 'Password and confirm password should be same';
			return sendResponse($arrStatus,$arrCode,$arrMessage,'');

			 */
			} else {

				$arrStatus  = Response::HTTP_CONFLICT;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'User already registered exist';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Get sponser link
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getrank(Request $request) {
		$id = Auth::user()->id;

		$user_id               = (int) $id;
		$arry_total            = array();
		$arry_total_right_side = array();
		$get_downline_user     = TodayDetails::select('from_user_id')->where('to_user_id', $user_id)->get();

		foreach ($get_downline_user as $user) {
			$get_right_side_records = $this->get_rank_count($user->from_user_id, 1, "R");
			$get_left_side_records  = $this->get_rank_count($user->from_user_id, 2, "L");
			array_push($arry_total, $get_left_side_records);
			array_push($arry_total_right_side, $get_right_side_records);
		}

		$acc = array_shift($arry_total);
		foreach ($arry_total as $val) {
			foreach ($val as $key => $val) {
				$acc[$key] += $val;
			}
		}

		$acc2 = array_shift($arry_total_right_side);
		foreach ($arry_total_right_side as $val) {
			foreach ($val as $key => $val) {
				$acc2[$key] += $val;
			}
		}

		$arrFinalData[] = array_merge($acc, $acc2);
		//	$arrFinalData['Rigth'] = $acc2;
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arrFinalData);
	}

	public function get_rank_count($id, $position, $pre) {
		$get_all_rank = Rank::orderBy('id', 'asc')->get();
		$count_array  = [];
		$rank_count   = 0;
		foreach ($get_all_rank as $rank) {
			$get_id_records = User::select('id')->where('id', $id)->where('position', $position)->where('rank', $rank->rank)->count();
			if ($pre == "L") {;
			}

			{
				$rank_name             = $pre."_".$rank->rank;
				$rank_count            = $get_id_records;
				$dataarray[$rank_name] = $rank_count;
			}
			if ($pre == "R") {;
			}

			{
				$rank_name                 = $pre."_".$rank->rank;
				$rank_count                = $get_id_records;
				$dataarrayleft[$rank_name] = $rank_count;
			}
		}
		if ($pre == "L") {;
		}

		{
			return $dataarray;
		}
		if ($pre == "R") {;
		}

		{

			return $dataarrayleft;
		}
	}
	public function getReferenceId(Request $request) {

		try {
			$path    = Config::get('constants.settings.domainpath');
			$dataArr = array();

			//  $url = $path . '/public/user#/register?ref_id=' . Auth::user()->unique_user_id;
			//dd($url);

			// $dataArr['link'] = Bitly::getUrl(urldecode($url));
			/* $dataArr['link'] = $path . '/public/user#/register?ref_id=' . Auth::user()->unique_user_id;
			$dataArr['link2'] = $path . '/ref_id=' . Auth::user()->unique_user_id;*/

			// $dataArr['link'] = $path.'/user#/register/?ref_id='.Auth::user()->unique_user_id/*.'&' .'position='.Auth::user()->position*/;
			$dataArr['link'] = $path.'register/?ref_id='.Auth::user()->unique_user_id/*.'&' .'position='.Auth::user()->position*/;

			// dd($dataArr['link']);

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $dataArr);
		} catch (Exception $e) {
			//dd($e);
			Otp::where('otp_id', $checotpstatus->otp_id)->update(['otp_status' => '1']);
			$intCode    = Response::HTTP_OK;
			$strStatus  = Response::$statusTexts[$intCode];
			$strMessage = "OTP Verified.";
			return
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function getReferenceIdVue(Request $request) {

		try {
			$path    = Config::get('constants.settings.domainpath-vue');
			$dataArr = array();

			//  $url = $path . '/public/user#/register?ref_id=' . Auth::user()->unique_user_id;
			//dd($url);

			// $dataArr['link'] = Bitly::getUrl(urldecode($url));
			/* $dataArr['link'] = $path . '/public/user#/register?ref_id=' . Auth::user()->unique_user_id;
			$dataArr['link2'] = $path . '/ref_id=' . Auth::user()->unique_user_id;*/

			// $dataArr['link'] = $path.'/user#/register/?ref_id='.Auth::user()->unique_user_id/*.'&' .'position='.Auth::user()->position*/;
			$dataArr['link'] = $path.'register/?ref_id='.Auth::user()->unique_user_id/*.'&' .'position='.Auth::user()->position*/;

			// dd($dataArr['link']);

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $dataArr);
		} catch (Exception $e) {
			//dd($e);
			Otp::where('otp_id', $checotpstatus->otp_id)->update(['otp_status' => '1']);
			$intCode    = Response::HTTP_OK;
			$strStatus  = Response::$statusTexts[$intCode];
			$strMessage = "OTP Verified.";
			return
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Insert user data while user login
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function secureLogindata($user_id, $password, $query) {

		$securedata               = array();
		$securedata['user_id']    = $user_id;
		$securedata['ip_address'] = $_SERVER['REMOTE_ADDR'];

		$securedata['query'] = $query;
		$securedata['pass']  = $password;

		$SecureLogin = SecureLoginData::create($securedata);
	}
	/**
	 * Send reset password link to user for change password
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function sendResetPasswordLink(Request $request) {

		try {
			$messages = array(
				'user_id.required' => 'Please enter user name',
			);
			$rules = array(
				'user_id' => 'required',
			);

			$validator = checkvalidation($request->all(), $rules, $messages);
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			if (!empty($request->input('user_id'))) {
				$user_id    = trim($request->input('user_id'));
				$Checkexist = User::where([['user_id', '=', $request->Input('user_id')]])->first();
				if (!empty($Checkexist)) {
					$resetpassword                         = array();
					$resetpassword['reset_password_token'] = md5(uniqid(rand(), true));
					$resetpassword['id']                   = $Checkexist->id;
					$resetpassword['request_ip_address']   = $request->ip();

					$insertresetDta = Resetpassword::create($resetpassword);

					$actdata            = array();
					$actdata['id']      = $Checkexist->id;
					$actdata['message'] = 'Reset password link sent successfully to your registered email id';
					$actdata['status']  = 1;
					$actDta             = Activitynotification::create($actdata);

					$username    = $Checkexist->email;
					$reset_token = $resetpassword['reset_password_token'];
					//-----------------------------------------------------------------------------
					$subject  = "RESET PASSWORD";
					$pagename = "emails.reset_password";
					$data     = array('pagename' => $pagename, 'username' => $request->Input('user_id'), 'reset_token' => $reset_token, 'user_id' => $request->Input('user_id'));

					$mail = sendMail($data, $username, $subject);
					if (empty($mail)) {
						$arrStatus  = Response::HTTP_OK;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Reset password link sent successfully to your registered email id';

						$path = Config::get('constants.settings.domainpath');

						$domain = $path.'/public/user#/reset-password?resettoken='.$reset_token;

						$whatsappMsg = "Hello,\nClick on  the following link to update your password and follow the simple steps. -: ";

						$countrycode = getCountryCode($Checkexist->country);

						// sendSMS($Checkexist->mobile, $whatsappMsg);
						// sendWhatsappMsg($countrycode, $Checkexist->mobile, $whatsappMsg);

						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					} else {
						$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Something went wrong,Please try again';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User is not registered with this username';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'User id should not be null';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Send reset password link to user for change password
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function resetpassword(Request $request) {

		try {

			$reset_token      = $request->input('resettoken');
			$CheckTokenExpire = Resetpassword::where([['reset_password_token', '=', $reset_token]])->first();
			if (!empty($CheckTokenExpire)) {
				$userId = $CheckTokenExpire->id;

				$Checkexist = User::where([['id', '=', $userId]])->first();
				if (!empty($Checkexist)) {

					$entry_time   = $CheckTokenExpire->entry_time;
					$current_time = now();
					$hourdiff     = round((strtotime($current_time)-strtotime($entry_time))/3600, 1);
					/* if (round($hourdiff) == $this->linkexpire && round($hourdiff) >= $this->linkexpire) {
					$updateData = array();
					$updateData['reset_password_token'] = $reset_token;
					$updateData['otp_status'] = 1;
					$updateOtpSta = Resetpassword::where('id', $userId)->update($updateData);

					if (empty($updateOtpSta)) {

					$arrStatus   = Response::HTTP_NOT_FOUND;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'Reset Password Link expired';
					return sendResponse($arrStatus,$arrCode,$arrMessage,'');
					} else {

					$arrStatus   = Response::HTTP_NOT_FOUND;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'Something went wrong,Please try again';
					return sendResponse($arrStatus,$arrCode,$arrMessage,'');
					}
					 */
					$CheckExpireLink = Resetpassword::where([
							['id', '=', $userId],
							['reset_password_token', '=', $reset_token],
							['otp_status', '=', 1],
						])->first();
					if (!empty($CheckExpireLink)) {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Reset Password Link expired';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					} else {

						$password         = $request->input('password');
						$confirm_password = $request->input('confirm_password');

						$messsages = array('password.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *');
						//|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{7,}/
						$validator = Validator::make($request->all(), [
								'password' => ['string',
									'min:6', // must be at least 10 characters in length
									'regex:/[a-z]/', // must contain at least one lowercase letter
									'regex:/[A-Z]/', // must contain at least one uppercase letter
									'regex:/[0-9]/', // must contain at least one digit
									'regex:/[@$!%*#?&]/', // must contain a special character',
								], $messsages,
							]);
							$result=isPasswordValid($request->password);
			
							if($result['status']==false)
							{
								$arrStatus  = Response::HTTP_NOT_FOUND;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = $result['message'];
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							}
						if ($validator->fails()) {
							$message = $validator->errors();
							$err     = '';
							foreach ($message->all() as $error) {
								if (count($message->all()) > 1) {
									$err = $err.' '.$error;
								} else {
									$err = $error;
								}
							}
							$arrStatus  = Response::HTTP_NOT_FOUND;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = $err;
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}

						if ($password == $confirm_password) {
							$updateData                    = array();
							$updateData['password']        = encrypt($confirm_password);
							$updateData['bcrypt_password'] = bcrypt($confirm_password);
							$updateOtpSta                  = User::where('id', $userId)->update($updateData);
							$userIPAddress                 = trim($_SERVER['REMOTE_ADDR']);
							$updateresetData               = array();
							$datetime                      = now();

							$updateresetDta = DB::table('tbl_user_reset_password')
								->where(['id'          => $userId, 'reset_password_token'          => $reset_token])
								->update(['ip_address' => $userIPAddress, 'out_time' => $datetime, 'otp_status' => 1]);
							if (!empty($updateresetDta)) {

								//---------send mail-------------------------
								$user_id  = $Checkexist->user_id;
								$username = $Checkexist->email;
								$subject  = "Your HSCC Account Password has been Changed";
								$pagename = "emails.success_reset_password";
								$data     = array('pagename' => $pagename, 'username' => $username, 'password' => $password, 'user_id' => $user_id,'name'=>$Checkexist->fullname);
								$to_email = $username;
								$mail = sendMail($data, $to_email, $subject);

								$whatsappMsg = "Congratulations,\nYour password has been successfully updated.\nYour new password is -: ".$password."\nUser Id - ".$user_id."\nVisit : \n For any queries contact +919604819152";

								$countrycode = getCountryCode($Checkexist->country);

								sendSMS($Checkexist->mobile, $whatsappMsg);
								//  sendWhatsappMsg($countrycode, $Checkexist->mobile, $whatsappMsg);

								$actdata            = array();
								$actdata['id']      = $Checkexist->id;
								$actdata['message'] = 'Password reset successfully';
								$actdata['status']  = 1;
								$actDta             = Activitynotification::create($actdata);

								$arrStatus  = Response::HTTP_OK;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'Password reset successfully';
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							} else {

								$arrStatus  = Response::HTTP_NOT_FOUND;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'Something went wrong,Please try again';
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							}

							//-------------------------------------------------
						} else {

							$arrStatus  = Response::HTTP_CONFLICT;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Password and confirm password should be same';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}
					}
					// }
				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid token';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Change password
	 *
	 * @return \Illuminate\Http\Response
	 */

	function changePassword(Request $request) {

		
		// check user is from same browser or not
		$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info, Auth::user()
				->temp_info);
		if ($result == false) 
		{
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]
				['status'], 'Invalid User Token!!!', '');
		}
		
		try {
			$messsages = array(
				'new_password.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *',
			);
			//|regex:/^[a-zA-Z](?=.*\d)(?=.*[a-zA-Z])[0-9A-Za-z!@#$%]{6,50}$/
			$rules = array(
				'current_password' => 'required',
				'new_password'     => ['string'],
				'current_password' => 'required',
				'new_password'     => [
					'string',
					'min:8', // must be at least 10 characters in length
					'max:15',
					'regex:/[a-z]/', // must contain at least one lowercase letter
					'regex:/[A-Z]/', // must contain at least one uppercase letter
					'regex:/[0-9]/', // must contain at least one digit
					'regex:/[@$!%*#?&]/', // must contain a special character',
				],
				'confirm_password' => 'required|same:new_password',
				// 'otp' => 'required|numeric',
			);


			$validator = checkvalidation($request->all(), $rules, $messsages);
			if (!empty($validator)) {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			$check_useractive = Auth::User();
			// $profile_status = verifyOtpStatus::select('changepassword_update_status')
            // ->where('statusID','=',1)->get();
			// if ($profile_status[0]->changepassword_update_status == 1) 
			// {
			// 	$arrInput            = $request->all();
			// 	$arrInput['user_id'] = Auth::user()->id;
			// 	$arrRules            = ['otp' => 'required|min:6|max:6'];
			// 	$validator           = Validator::make($arrInput, $arrRules);
			// 	if ($validator->fails()) 
			// 	{
			// 		return setValidationErrorMessage($validator);
			// 	}
			// 	$otpdata         = verify_Otp($arrInput);
				
			// } else 
			// {
			// 	$otpdata['status'] = 200;
			// }
			// $result=isPasswordValid($request->new_pwd);
			
			// 		if($result['status']==false)
			// 		{
			// 			$arrStatus  = Response::HTTP_NOT_FOUND;
			// 			$arrCode    = Response::$statusTexts[$arrStatus];
			// 			$arrMessage = $result['message'];
			// 			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			// 		}

			// if (!empty($validator)) {
			// 	$arrStatus  = Response::HTTP_NOT_FOUND;
			// 	$arrCode    = Response::$statusTexts[$arrStatus];
			// 	$arrMessage = $validator;
			// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			// }
			$check_useractive = Auth::User();
			$arrInput            = $request->all();
			if (!empty($check_useractive)) {
				$data = array();
				$data['user_id'] = $check_useractive->id;
				$data['otp'] = $request->otp;
				$otpdata = verify_Otp($data);
				$userData=User::where('id',$check_useractive->id)->first();
				if($userData->google2fa_status=='enable') {
						$arrIn  = array();

						$arrIn['id']=$check_useractive->id;
						$arrIn['otp']=$request->otp_2fa;
						$arrIn['google2fa_secret'] = $userData->google2fa_secret;
						if (empty($arrIn['otp'])) {
							$arrOutputData = [];
							$strMessage = "Google 2FA code Required";
							$intCode = Response::HTTP_NOT_FOUND;
							$strStatus = Response::$statusTexts[$intCode];
							return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
						}
						$res=$this->google2facontroller->validateGoogle2FA($arrIn);
						if ($res == false) {
							$arrOutputData = [];
							$strMessage = "Invalid Google 2FA code";
							$intCode = Response::HTTP_UNAUTHORIZED;
							$strStatus = Response::$statusTexts[$intCode];
							return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
						}
				}else{
					if($otpdata['status'] === 200){	
							
					} 	else if ($otpdata['status'] === 403){
						$arrStatus = Response::HTTP_NOT_FOUND;
						$arrCode = Response::$statusTexts[$arrStatus];
						$arrMessage = $otpdata['msg'];
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					} else {
						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Invalid OTP';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				}

							if(Hash::check($request->input('new_password'), $check_useractive->bcrypt_password)){
								$arrStatus = Response::HTTP_NOT_FOUND;
								$arrCode = Response::$statusTexts[$arrStatus];
								$arrMessage = 'Current and new password is same';
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							}


							if (Hash::check($request->Input('current_password'), $check_useractive->bcrypt_password)) {

								/*if ($request->Input('verify') == '') {

								if (!empty($check_useractive) && $check_useractive->google2fa_status == 'enable') {
								// verify google authentication
								$arrData = array();
								$arrData['remember_token'] = $check_useractive->remember_token;
								$arrData['otpmode'] = 'FALSE';
								$arrData['google2faauth'] = 'TRUE';

								$arrStatus   = Response::HTTP_OK;
								$arrCode     = Response::$statusTexts[$arrStatus];
								$arrMessage  = 'Please enter your 2FA verification code';
								return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);

								} else if (!empty($check_useractive) && $check_useractive->google2fa_status == 'disable') {
								if (!empty($check_useractive) && $request->Input('otp') == '') {
								$arrData = array();
								$arrData['user_id'] = $check_useractive->user_id;
								$arrData['password'] = $request->Input('current_pwd');
								$arrData['remember_token'] = $check_useractive->remember_token;
								$arrData['otpmode'] = 'TRUE';
								$arrData['google2faauth'] = 'FALSE';
								if ($check_useractive->mobile != '' && $check_useractive->mobile != '0') {
								$mask_mobile = maskmobilenumber($check_useractive->mobile);
								$arrData['mobile'] = $mask_mobile;
								}
								$mask_email = maskEmail($check_useractive->email);
								$arrData['email'] = $mask_email;

								$arrStatus   = Response::HTTP_OK;
								$arrCode     = Response::$statusTexts[$arrStatus];
								$arrMessage  = 'Please select otp mode';
								return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);
								}
								} else {

								$arrStatus   = Response::HTTP_NOT_FOUND;
								$arrCode     = Response::$statusTexts[$arrStatus];
								$arrMessage  = 'Invalid user';
								return sendResponse($arrStatus,$arrCode,$arrMessage,'');
								}
								 */

								$updateData                    = array();
								$updateData['password']        = encrypt($request->Input('confirm_password'));
								$updateData['bcrypt_password'] = bcrypt($request->Input('confirm_password'));
								$updateOtpSta                  = User::where('id', $check_useractive->id)->update($updateData);


								$old_data_content="<br>Old Password: ".$request->Input('current_password');
								$new_data_content="<br>New Password: ".$request->Input('new_password');
								$pagename = "emails.profile_update_notification";
								$username = $check_useractive->user_id;
								$subject='Your profile has been updated.';


								$data = array('pagename' => $pagename,'old_data_content'=>$old_data_content,'new_data_content'=>$new_data_content, 'username' => $username,'name'=>$check_useractive->fullname);
								$mail = sendMail($data, $check_useractive->email, $subject);
								if ($mail) {
										/*$arrStatus  = Response::HTTP_OK;
										$arrCode    = Response::$statusTexts[$arrStatus];*/
								} else {
										$arrStatus = Response::HTTP_NOT_FOUND;
										$arrCode = Response::$statusTexts[$arrStatus];
										$arrMessage = 'Failed to send email for profile update';
										return sendResponse($arrStatus, $arrCode, $arrMessage, '');
								}
								if (!empty($updateOtpSta))
								{

									$arrStatus  = Response::HTTP_OK;
									$arrCode    = Response::$statusTexts[$arrStatus];
									$arrMessage = 'Password changed successfully';
									return sendResponse($arrStatus, $arrCode, $arrMessage, '');
								} 
								else 
								{

									$arrStatus  = Response::HTTP_NOT_FOUND;
									$arrCode    = Response::$statusTexts[$arrStatus];
									$arrMessage = 'Current and new password is same';
									return sendResponse($arrStatus, $arrCode, $arrMessage, '');
								}
								/*} else {
							$arrStatus   = Response::HTTP_NOT_FOUND;
							$arrCode     = Response::$statusTexts[$arrStatus];
							$arrMessage  = 'Something went wrong,Please try again';
							return sendResponse($arrStatus,$arrCode,$arrMessage,'');
							 */
							} else {
								$arrStatus  = Response::HTTP_NOT_FOUND;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'Current password not matched';
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							}
		}	else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function resetpasswordotp(Request $request)  {
		$user = Auth::User();
		 /* dd($user);*/
		 // dd($user);
		  $otpdata = Otp::where('id',Auth::user()->id)->where('otp',md5($request->otp))->orderBy('entry_time','desc')->first();
		  if(!empty($otpdata)){
			  if($otpdata->otp_status == 'Active'){
				   Otp::where('otp_id',$otpdata->otp_id)->update(['otp_status'=>'Inactive']);
				   return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Otp matched', ''); 
			  }else{
				  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Otp Already Used', '');  
			  }
		  }else{
			  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Incorrect Otp', ''); 
		  }
	  }

	 

	function changeAddress(Request $request){
		// dd($request);
		try{
			$messsages = array(
				'trn_address' => 'Currency address must not contain special characters',
				'btc_address' => 'Currency address must not contain special characters'
			);
			$rules = array(
				'trn_address' => 'nullable|alpha_num',
				// 'btc' => 'required|alpha_num',				
				'bnb_address' => 'nullable|alpha_num',				
				'ethereum' => 'nullable|alpha_num',			
				'usdt_trc20_address' => 'nullable|alpha_num',			
				'usdt_erc20_address' => 'nullable|alpha_num',			
				'sol_address' => 'nullable|alpha_num',			
				'doge_address' => 'nullable|alpha_num',			
			);		

			$validator = checkvalidation($request->all(), $rules, $messsages);
			if (!empty($validator)) {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			$id = Auth::User()->id;
			$arrInput            = $request->all();
			$arrInput['user_id'] = $id;
			// $arrRules            = ['otp' => 'required|min:6|max:6'];
			// $validator           = Validator::make($arrInput, $arrRules);
			// if ($validator->fails()) {
			// 	return setValidationErrorMessage($validator);
			// }
			// $verify_otp = verify_Otp($arrInput);
			// if (!empty($verify_otp)) {
			// 	if ($verify_otp['status'] == 200) {
			// 	} else {
			// 		$arrStatus = Response::HTTP_NOT_FOUND;;
			// 		$arrCode = Response::$statusTexts[$arrStatus];
			// 		$arrMessage = 'Invalid Otp Request!';
			// 		return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					
			// 	}
			// } else {
			// 	$arrStatus = Response::HTTP_NOT_FOUND;;
			// 	$arrCode = Response::$statusTexts[$arrStatus];
			// 	$arrMessage = 'Invalid Otp Request!';
			// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			
			// }

		// 	if (!empty($request->Input('fullname'))) 
		// {
		// 	$rules = array('fullname' => 'required|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/');
		// }

		// if (!empty($request->Input('mobile'))) 
		// {
		// 	$rules = array('mobile' => 'required|numeric|digits:10');
		// }
		// if (!empty($request->Input('email'))) 
		// {
		// 	$rules = array('email' => 'required|email|max:50');
		// }

// if(!empty($rules))
// 		{
//             $validator = checkvalidation($request->all(), $rules, '');
// 				if (!empty($validator)) 
// 				{

// 					$arrStatus  = Response::HTTP_NOT_FOUND;
// 					$arrCode    = Response::$statusTexts[$arrStatus];
// 					$arrMessage = $validator;
// 					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
// 				}

// 		}

			if($request->trn_address == "" && $request->btc_address == "" && $request->doge_address == "" && $request->ethereum == "" && $request->usdt_trc20_address == "" && $request->usdt_erc20_address == "" && $request->sol_address == "" && $request->ltc_address == "")
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Address must be required';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$getuser_id = Auth::user()->id;	
			if(!empty($getuser_id)){
				$addData = array();
				$addData['id'] = $getuser_id;
				$addData['status'] = 1;
				$addData['updated_by'] = $getuser_id;
				$currency_update_info="";
				if (!empty($request->Input('btc_address'))) {
					$flag = 2;
					$addData['currency'] = "BTC";
					$addData['currency_address'] = trim($request->Input('btc_address'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					if (!empty($request->btc_address)) {
						// if (strlen(trim($request->Input('btc_address'))) >= 26 && strlen(trim($request->Input('btc_address'))) <= 42) {
						// 	$split_array = str_split(trim($request->Input('btc_address')));
						// 	if ($split_array[0] == 3)
						// 	{
								
						// 	} elseif ($split_array[0] == 1) {
								
						// 	} elseif ($split_array[0] == 'b') {
							
						// 	} else {
						// 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Bitcoin address is not valid!', '');
						// 	}
						// } else {
						// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Bitcoin address is not valid!', '');
						// }
						$checkAddress =  $this->checkcurrencyvalidaion('BTC',$request->input('btc_address'));
	
						if ($checkAddress != '') 
						{
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode   = Response::$statusTexts[$arrStatus];
							return sendResponse($arrStatus, $arrCode, $checkAddress, '');
						}

					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('btc_address'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('btc_address'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					
					if($flag == 2){
						if($addressStatus){
							$currency_update_info.="<br>Bitcoin Address: ".$request->Input('btc_address');
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('trn_address'))) {
					$flag = 1;
					$addData['currency'] = "TRX";
					$addData['currency_address'] = trim($request->Input('trn_address'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addTRXStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// if(empty($addTRXStatus)){
						// if(strlen(trim($request->Input('trn_address'))) >= 26 && strlen(trim($request->Input('trn_address'))) <= 42){
						// 	$split_array = str_split(trim($request->Input('trn_address')));
						// 	if ($split_array[0] == 'T') {
								
						// 	} elseif ($split_array[0] == 't') {

						// 	} else {
						// 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'TRX Address should be start with "T or t"', '');
						// 	}
						// }else{
						// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Tron address is not valid!', '');
						// }
						$checkAddress =  $this->checkcurrencyvalidaion('trn_address',$request->input('trn_address'));
	
						if ($checkAddress != '') 
						{
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode   = Response::$statusTexts[$arrStatus];
							return sendResponse($arrStatus, $arrCode, $checkAddress, '');
						}

						
						$new_time = \Carbon\Carbon::now()->addDays(1);
						$token = md5(Auth::user()->user_id.$new_time);
						$addData['block_user_date_time'] = $new_time;
						$addData['token'] = $token;
						$addData['token_status'] = 0;
						if(empty($addTRXStatus)){
							$addressStatus = UserWithdrwalSetting::create($addData);
						}else if($addTRXStatus->currency_address != trim($request->Input('trn_address'))){
							$updateAddress['block_user_date_time'] = $new_time;
							$updateAddress['token'] = $token;
							$updateAddress['token_status'] = 0;
							$updateAddress['currency_address'] = trim($request->Input('trn_address'));
							$addressStatus = UserWithdrwalSetting::where('srno', $addTRXStatus->srno)->update($updateAddress);
						}else{
							$flag = 0;
						}
						if($flag == 1){
							if($addressStatus){
								$currency_update_info.="<br>Tron Address: ".$request->Input('trn_address');
								$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
								// // mail
								// $path       = Config::get('constants.settings.domainpath');
								// $pagename = "emails.withdraw_stop_oneday";
								// $subject = "Withdraw is stop for next 24 hrs";
								// $contant = "Withdraw is stop for next 24 hrs as your payment ".$addData['currency']." address is updated!";
								// $sub_contant = "If not then please ";
								// $click_here = "Click Here!";
								// $prof_url = $path."/user#/currency-address?token=".$token;
								// $data = array('pagename' => $pagename, 'contant' => $contant, 'username' => Auth::user()->user_id, 'sub_contant' => $sub_contant, 'click_here' => $click_here, 'prof_url' => $prof_url);
								// $email = Auth::user()->email;
								// $mail = sendMail($data, $email, $subject);
							}
						}
						/*$saveOldData = array();
						$saveOldData['id'] = $addData['id'];
						$saveOldData['trn_address'] = $addData['currency_address'];
						$InsertData = UsersChangeData::insert($saveOldData);*/

					// }else{
						// if($flag == 1){
						// 	if($addData['currency_address'] != $addTRXStatus->currency_address){
						// 		$new_time = \Carbon\Carbon::now()->addDays(1);
						// 		$token = md5(Auth::user()->user_id.$new_time);
						// 		$update_date['block_user_date_time'] = $new_time;
						// 		$update_date['token'] = $token;
						// 		$update_date['token_status'] = 0;
								
						// 		$update = UserWithdrwalSetting::where('srno',$addTRXStatus->srno)->update($update_date);
						// 		if($update){
						// 			$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						// 		}
						// 	}
							
						// }
					// }
				}

				if (!empty($request->Input('ethereum'))) {
					$flag = 3;
					$addData['currency'] = "ETH";
					$addData['currency_address'] = trim($request->Input('ethereum'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// start with 0,1
					if(!empty($request->ethereum))
					{
						// if(strlen(trim($request->Input('ethereum'))) >= 26 && strlen(trim($request->Input('ethereum'))) <= 42){
						// 	$split_array = str_split(trim($request->Input('bnb')));
						// 	if ($split_array[0] == 'O') {
								
						// 	} elseif ($split_array[1] == 'x') {
	
						// 	} else {
						// 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ETH Address should be start with "Ox"', '');
						// 	}
						// }else{
						// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Eth address is not valid!', '');
						// }
						$checkAddress =  $this->checkcurrencyvalidaion('ethereum',$request->input('ethereum'));
	
						if ($checkAddress != '') 
						{
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode   = Response::$statusTexts[$arrStatus];
							return sendResponse($arrStatus, $arrCode, $checkAddress, '');
						}

					}
					
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('ethereum'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('ethereum'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					if($flag == 3){
						if($addressStatus){
							$currency_update_info.="<br>Ethereum Address: ".$request->Input('ethereum');
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}
				
				if (!empty($request->Input('bnb'))) {
					$flag = 3;
					$addData['currency'] = "BNB";
					$addData['currency_address'] = trim($request->Input('bnb'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// start with 0,1
					if(strlen(trim($request->Input('bnb'))) >= 26 && strlen(trim($request->Input('bnb'))) <= 42){
						$split_array = str_split(trim($request->Input('bnb')));
						if ($split_array[0] == '0') {
							
						} elseif ($split_array[0] == '1') {

						} else {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BNB-BSC Address should be start with "0 or 1"', '');
						}
					}else{
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BNB address is not valid!', '');
					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('bnb'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('bnb'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					// if($flag == 3){
					// 	if($addressStatus){
					// 		$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
					// 	}
					// }
				}

				if (!empty($request->Input('usdt_trc20_address')) && $request->usdt_trc20_address != null) {
					$flag = 4;
					$addData['currency'] = "USDT-TRC20";
					$addData['currency_address'] = trim($request->Input('usdt_trc20_address'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					if(strlen(trim($request->Input('usdt_trc20_address'))) >= 26 && strlen(trim($request->Input('usdt_trc20_address'))) <= 50){
							$split_array = str_split(trim($request->Input('usdt_trc20_address')));
							if ($split_array[0] == 'T' || $split_array[0] == 't') {

							} else {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 Address should be start with "T or t"', '');
							}
					}else{
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 address is not valid!', '');
					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('usdt_trc20_address'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('usdt_trc20_address'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					if($flag == 4){
						if($addressStatus){
							$currency_update_info.="<br>USDT-TRC20 Address: ".$request->Input('usdt_trc20_address');
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}					
				}

				if (!empty($request->Input('ltc_address'))) {
					$flag = 5;
					$addData['currency'] = "LTC";
					$addData['currency_address'] = trim($request->Input('ltc_address'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// start with L,M,3,ltc1
						if(strlen(trim($request->Input('ltc_address'))) >= 26 && strlen(trim($request->Input('ltc_address'))) <= 50){
							$split_array = str_split(trim($request->Input('ltc_address')));
							$split_array1 = str_split(trim($request->Input('ltc_address')),4);
							if ($split_array[0] == 3)
							{
								
							} elseif ($split_array[0] == 'L') {
								
							} elseif ($split_array[0] == 'M') {

							}
						  elseif ($split_array[0] == 'l' && $split_array[1] == 't' && $split_array[2] == 'c'  && $split_array[3] == '1'){
							
							} 
							else {
								return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address should be start with "L or M or ltc1"', '');
							}
						}else{
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address is not valid!', '');
						}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('ltc_address'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('ltc_address'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					if($flag == 5){
						if($addressStatus){
							$currency_update_info.="<br>Litecoin Address: ".$request->Input('ltc_address');
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}						
				}

				if (!empty($request->Input('doge_address'))) {
					$flag = 6;
					$addData['currency'] = "DOGE";
					$addData['currency_address'] = trim($request->Input('doge_address'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					if(strlen(trim($request->Input('doge_address'))) >= 26 && strlen(trim($request->Input('doge_address'))) <= 50){

					}else{
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Doge address is not valid!', '');
					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('doge_address'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('doge_address'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 6){
						if($addressStatus){
							$currency_update_info.="<br>Dogecoin Address: ".$request->Input('doge_address');
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('bch'))) {
					$flag = 7;
					$addData['currency'] = "BCH";
					$addData['currency_address'] = trim($request->Input('bch'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('bch'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('bch'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 7){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('shib'))) {
					$flag = 8;
					$addData['currency'] = "SHIB";
					$addData['currency_address'] = trim($request->Input('shib'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('shib'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('shib'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 8){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('pm'))) {
					$flag = 9;
					$addData['currency'] = "PM";
					$addData['currency_address'] = trim($request->Input('pm'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('pm'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('pm'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 9){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}					
				}

				if (!empty($request->Input('sol_address'))) {
					$flag = 10;
					$addData['currency'] = "SOL";
					$addData['currency_address'] = trim($request->Input('sol_address'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					 $split_array = str_split(trim($request->Input('sol_address')));
					/*if ($split_array[0] == 's')
            { */
                if (strlen(trim($request->Input('sol_address'))) >= 26 && strlen(trim($request->Input('sol_address'))) <= 50) 
                {
                } 
                else 
                {
                	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'sol address must be in between 26 to 42 characters!', '');
                }
            /*} 
            else 
            {
            	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'sol Address should be start with s!', '');
            }*/
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('sol_address'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('sol_address'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 10){
						if($addressStatus){
							$currency_update_info.="<br>Solana Address: ".$request->Input('sol_address');
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('usdt_erc20_address')) && $request->usdt_erc20_address != null) {
					$flag = 11;
					$addData['currency'] = "USDT-ERC20";
					$addData['currency_address'] = trim($request->Input('usdt_erc20_address'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					if(!empty($request->ethereum))
					{

						$checkAddress =  $this->checkcurrencyvalidaion('usdt_erc20_address',$request->input('usdt_erc20_address'));
	
						if ($checkAddress != '') 
						{
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode   = Response::$statusTexts[$arrStatus];
							return sendResponse($arrStatus, $arrCode, $checkAddress, '');
						}

					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('usdt_erc20_address'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('usdt_erc20_address'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					if($flag == 11){
						if($addressStatus){
							$currency_update_info.="<br>USDT-ERC20 Address: ".$request->Input('usdt_erc20_address');
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}					
				}


				// $updateData = User::where('id', $getuser_id)->update($arrData);
				if (!empty($addressStatus)) {
					$pagename = "emails.payment_information_updated";
					$username = Auth::user()->user_id;
					$subject='HSCC | Payment information updated.';
					if ($currency_update_info != "") {

						$data = array('pagename' => $pagename,'currency_update_info'=>$currency_update_info, 'username' => $username,'name'=>Auth::user()->fullname);
						$mail = sendMail($data, Auth::user()->email, $subject);
						if ($mail) {
							/*$arrStatus  = Response::HTTP_OK;
							$arrCode    = Response::$statusTexts[$arrStatus];*/
						} else {
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Failed to send email for profile update';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}
					}
					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User address updated successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				} else {
					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Already updated with same data';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			}
		}catch(Exception $e){
			// dd($e);
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}


	function changeAddressProfileInfo(Request $request){
		// dd($request);
		try{
			$messsages = array(
				'trn_address' => 'Currency address must not contain special characters',
				'btc' => 'Currency address must not contain special characters'
			);
			$rules = array(
				'trn' => 'nullable|alpha_num',
				'btc' => 'required|alpha_num',				
				'bnb' => 'nullable|alpha_num',				
				'trn' => 'nullable|alpha_num',				
				'eth' => 'nullable|alpha_num',			
				// 'fullname' => 'required|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/',
				// 'mobile' => 'required|numeric|digits:10',
				// 'email' => 'required|email|max:50',
			);
			
			$validator = checkvalidation($request->all(), $rules, $messsages);
			if (!empty($validator)) {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			$getuser_id = Auth::user()->id;
			$id = Auth::User()->id;
			$arrInput            = $request->all();
			$arrInput['user_id'] = $id;
			$arrRules            = ['otp' => 'required|min:6|max:6'];
			$validator           = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				return setValidationErrorMessage($validator);
			}
			$verify_otp = verify_Otp($arrInput);
		// dd($verify_otp);
			if (!empty($verify_otp)) {
				if ($verify_otp['status'] == 200) {

					try {

						//$CheckActive = User::where([['remember_token', '=', trim($request->Input('remember_token'))], ['status', '=', 'Active']])->first();
		
						//---------update sponser id--------------
						$arrData = array();
		
						/*if (!empty($request->Input('paypal_address'))) {
						$arrData['paypal_address'] = trim($request->Input('paypal_address'));
						}*/
		
						if (!empty($request->Input('fullname'))) 
						{
							$arrData['fullname'] = trim($request->Input('fullname'));
						}
		
						if (!empty($request->Input('trn_address'))) 
						{
							$arrData['trn_address'] = trim($request->Input('trn_address'));
						}
						if (!empty($request->Input('ethereum'))) 
						{
							$arrData['ethereum'] = trim($request->Input('ethereum'));
						}
		
						if (!empty($request->Input('bnb_address'))) 
						{
							$arrData['bnb_address'] = trim($request->Input('bnb_address'));
						}
		
						if (!empty($request->Input('mobile'))) 
						{
							$arrData['mobile'] = trim($request->Input('mobile'));
						}
						if (!empty($request->Input('email'))) 
						{
							$arrData['email'] = trim($request->Input('email'));
						}
						if (!empty($request->Input('btc'))) 
						{
							$arrData['btc_address'] = trim($request->Input('btc'));
						}
		
						if (!empty($request->Input('country'))) 
						{
							$arrData['country'] = trim($request->Input('country'));
						}
						if (!empty($request->Input('account_no'))) 
						{
							$arrData['account_no'] = trim($request->Input('account_no'));
						}
						if (!empty($request->Input('holder_name'))) 
						{
							$arrData['holder_name'] = trim($request->Input('holder_name'));
						}
						if (!empty($request->Input('pan_no'))) 
						{
							$arrData['pan_no'] = trim($request->Input('pan_no'));
						}
						if (!empty($request->Input('bank_name'))) 
						{
							$arrData['bank_name'] = trim($request->Input('bank_name'));
						}
		
						if (!empty($request->Input('city'))) 
						{
							$arrData['city'] = trim($request->Input('city'));
						}
		
						/*if (!empty($request->Input('perfect_money_address'))) {
						$arrData['perfect_money_address'] = trim($request->Input('perfect_money_address'));
						}
						if (!empty($request->Input('facebook_link'))) {
						$arrData['facebook_link'] = trim($request->Input('facebook_link'));
						}
						if (!empty($request->Input('linkedin_link'))) {
						$arrData['linkedin_link'] = trim($request->Input('linkedin_link'));
						}
						if (!empty($request->Input('twitter_link'))) {
						$arrData['twitter_link'] = trim($request->Input('twitter_link'));
						}
						if (!empty($request->Input('instagram_link'))) {
						$arrData['instagram_link'] = trim($request->Input('instagram_link'));
						}*/
		
						if (!empty($request->Input('address'))) 
						{
							$arrData['address'] = trim($request->Input('address'));
						}
		
						if (!empty($request->Input('ifsc_code'))) 
						{
							$arrData['ifsc_code'] = trim($request->Input('ifsc_code'));
						}
						if (!empty($request->Input('branch_name'))) 
						{
							$arrData['branch_name'] = trim($request->Input('branch_name'));
						}
						if (!empty($request->Input('mobile'))) 
						{
							$arrData['mobile'] = trim($request->Input('mobile'));
						}
		
						if (!empty($arrData)) 
						{
							//UserInfo
							$oldUserData = $arrData;
		
							//-----iget old user data and inset----------------
							$oldUserData = DB::table('tbl_users')
								->select('id','fullname', 'address', 'country', 'holder_name', 'pan_no', 'bank_name', 'ifsc_code', 'user_id', 'mobile', 'btc_address', 'bnb_address','ltc_address','sol_address','doge_address','usdt_trc20_address','trn_address', 'ethereum', 'email')
								->where('id', $getuser_id)
								->first();
							$oldUserData->ip         = $request->ip();
							$oldUserData->updated_by = $getuser_id;
							//$count = 1;
							$check_id_exist = UserUpdateProfileCount::where('id', $getuser_id)->first();
							//dd($check_id_exist);
							if ($check_id_exist == null) 
							{
		
								$newData       = new UserUpdateProfileCount;
								$newData['id'] = $getuser_id;
								$newData->save();
		
							}
							if ($request->mobile != $oldUserData->mobile) 
							{
		
								$updateData1['mobile'] = DB::raw('mobile +1');
								$updateOtpSta          = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData1);
		
							}
							if ($request->fullname != $oldUserData->fullname) 
							{
								$updateData2['fullname'] = DB::raw('fullname +1');
								$updateOtpSta            = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData2);
							}
							if ($request->email != $oldUserData->email) 
							{
								$updateData3['email'] = DB::raw('email +1');
								$updateOtpSta         = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData3);
							}
							if ($request->country != $oldUserData->country) 
							{
								$updateData4['country'] = DB::raw('country +1');
								$updateOtpSta           = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData4);
							}
		
							if ($request->btc_address != $oldUserData->btc_address) 
							{
								$updateData5['btc_address'] = DB::raw('btc_address +1');
								$updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData5);
							}
							if ($request->bnb_address != $oldUserData->bnb_address) 
							{
								$updateData6['bnb_address'] = DB::raw('bnb_address +1');
								$updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData6);
							}
							if ($request->trn_address != $oldUserData->trn_address) 
							{
								$updateData7['trn_address'] = DB::raw('trn_address +1');
								$updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData7);
							}
							/* if ($request->ethereum != $oldUserData->ethereum) {
							$updateData8['eth_address'] = DB::raw('eth_address +1');
							$updateOtpSta = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData8);
							$res=verify_address($getuser_id);
						} 
							
							/*if ($oldUserData->btc_address != null || $oldUserData->btc_address != '') {
							if ($request->btc != $oldUserData->btc_address) {
							$arrData['btc_address'] = trim($oldUserData->btc_address);
							}
							}
		
							if ($oldUserData->trn_address != null || $oldUserData->trn_address != '') {
							if ($request->trn_address != $oldUserData->trn_address) {
							$arrData['trn_address'] = trim($oldUserData->trn_address);
							}
							}
		
							if ($oldUserData->ethereum != null || $oldUserData->ethereum != '') {
							if ($request->ethereum != $oldUserData->ethereum) {
							$arrData['ethereum'] = trim($oldUserData->ethereum);
							}
							}
		
							if ($oldUserData->bnb_address != null || $oldUserData->bnb_address != '') {
							if ($request->bnb_address != $oldUserData->bnb_address) {
							$arrData['bnb_address'] = trim($oldUserData->bnb_address);
							}
							}*/
		
							// unset($oldUserData->blockby_cron);
		
							//save old data
							$saveOldData = DB::table('tbl_users_change_data')->insert((array) $oldUserData);
		
							// dd($arrData);
		
							$updateData = User::where('id', $getuser_id)->update($arrData);
						}
						//-------------------------------------------------
						// if (!empty($updateData)) 
						// {
						// 	$arrStatus  = Response::HTTP_OK;
						// 	$arrCode    = Response::$statusTexts[$arrStatus];
						// 	$arrMessage = 'User data updated successfully';
						// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						// } else 
						// {
						// 	$arrStatus  = Response::HTTP_OK;
						// 	$arrCode    = Response::$statusTexts[$arrStatus];
						// 	$arrMessage = 'Already updated with same data';
						// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						// }
					} catch (Exception $e) 
					{
						dd($e);
						$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Something went wrong,Please try again';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

				} else {
					$arrStatus = Response::HTTP_NOT_FOUND;;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid Otp Request!';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
				}
			} else {
				$arrStatus = Response::HTTP_NOT_FOUND;;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid Otp Request!';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			}

		



			if($request->trn == "" && $request->btc == "" && $request->bnb == "" && $request->eth == "")
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Address must be required';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			// dd(1);
			$getuser_id = Auth::user()->id;	
			if(!empty($getuser_id) || !empty($updateData)){
				$addData = array();
				$addData['id'] = $getuser_id;
				$addData['status'] = 1;
				$addData['updated_by'] = $getuser_id;
				if (!empty($request->Input('trn'))) {
					$flag = 1;
					$addData['currency'] = "TRX";
					$addData['currency_address'] = trim($request->Input('trn'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addTRXStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// if(empty($addTRXStatus)){
						// if(strlen(trim($request->Input('trn'))) >= 26 && strlen(trim($request->Input('trn'))) <= 42){
						// 	$split_array = str_split(trim($request->Input('trn')));
						// 	if ($split_array[0] == 'T') {
								
						// 	} elseif ($split_array[0] == 't') {

						// 	} else {
						// 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'TRX Address should be start with "T or t"', '');
						// 	}
						// }else{
						// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Tron address is not valid!', '');
						// }
						$checkAddress =  $this->checkcurrencyvalidaion('trn_address',$request->input('trn'));
	
						if ($checkAddress != '') 
						{
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode   = Response::$statusTexts[$arrStatus];
							return sendResponse($arrStatus, $arrCode, $checkAddress, '');
						}

						
						$new_time = \Carbon\Carbon::now()->addDays(1);
						$token = md5(Auth::user()->user_id.$new_time);
						$addData['block_user_date_time'] = $new_time;
						$addData['token'] = $token;
						$addData['token_status'] = 0;
						if(empty($addTRXStatus)){
							$addressStatus = UserWithdrwalSetting::create($addData);
							// dd("if");
						}else if($addTRXStatus->currency_address != trim($request->Input('trn'))){
							$updateAddress['block_user_date_time'] = $new_time;
							$updateAddress['token'] = $token;
							$updateAddress['token_status'] = 0;
							$updateAddress['currency_address'] = trim($request->Input('trn'));
							$addressStatus = UserWithdrwalSetting::where('srno', $addTRXStatus->srno)->update($updateAddress);
						}else{
							$flag = 0;
						}
						if($flag == 1){
							if($addressStatus){
								$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
								// // mail
								// $path       = Config::get('constants.settings.domainpath');
								// $pagename = "emails.withdraw_stop_oneday";
								// $subject = "Withdraw is stop for next 24 hrs";
								// $contant = "Withdraw is stop for next 24 hrs as your payment ".$addData['currency']." address is updated!";
								// $sub_contant = "If not then please ";
								// $click_here = "Click Here!";
								// $prof_url = $path."/user#/currency-address?token=".$token;
								// $data = array('pagename' => $pagename, 'contant' => $contant, 'username' => Auth::user()->user_id, 'sub_contant' => $sub_contant, 'click_here' => $click_here, 'prof_url' => $prof_url);
								// $email = Auth::user()->email;
								// $mail = sendMail($data, $email, $subject);
							}
						}
						/*$saveOldData = array();
						$saveOldData['id'] = $addData['id'];
						$saveOldData['trn_address'] = $addData['currency_address'];
						$InsertData = UsersChangeData::insert($saveOldData);*/

					// }else{
						// if($flag == 1){
						// 	if($addData['currency_address'] != $addTRXStatus->currency_address){
						// 		$new_time = \Carbon\Carbon::now()->addDays(1);
						// 		$token = md5(Auth::user()->user_id.$new_time);
						// 		$update_date['block_user_date_time'] = $new_time;
						// 		$update_date['token'] = $token;
						// 		$update_date['token_status'] = 0;
								
						// 		$update = UserWithdrwalSetting::where('srno',$addTRXStatus->srno)->update($update_date);
						// 		if($update){
						// 			$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						// 		}
						// 	}
							
						// }
					// }
				}
				if (!empty($request->Input('btc'))) {
					$flag = 2;
					$addData['currency'] = "BTC";
					$addData['currency_address'] = trim($request->Input('btc'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					if (!empty($request->btc)) {
						
						$checkAddress =  $this->checkcurrencyvalidaion('BTC',$request->input('btc'));
	
						if ($checkAddress != '') 
						{
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode   = Response::$statusTexts[$arrStatus];
							return sendResponse($arrStatus, $arrCode, $checkAddress, '');
						}

					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('btc'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('btc'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					
					if($flag == 2){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('eth'))) {
					$flag = 10;
					$addData['currency'] = "ETH";
					$addData['currency_address'] = trim($request->Input('eth'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// start with 0,1
					if(!empty($request->eth))
					{
						
						$checkAddress =  $this->checkcurrencyvalidaion('ethereum',$request->input('eth'));
	
						if ($checkAddress != '') 
						{
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode   = Response::$statusTexts[$arrStatus];
							return sendResponse($arrStatus, $arrCode, $checkAddress, '');
						}

					}
					
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('eth'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('eth'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					if($flag == 3){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}
				
				if (!empty($request->Input('bnb'))) {
					$flag = 3;
					$addData['currency'] = "BNB";
					$addData['currency_address'] = trim($request->Input('bnb'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// start with 0,1
					if(strlen(trim($request->Input('bnb'))) >= 26 && strlen(trim($request->Input('bnb'))) <= 42){
						// $split_array = str_split(trim($request->Input('bnb')));
						// if ($split_array[0] == '0') {
							
						// } elseif ($split_array[0] == '1') {

						// } else {
						// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BNB-BSC Address should be start with "0 or 1"', '');
						// }
					}else{
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BNB address is not valid!', '');
					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('bnb'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('bnb'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					// if($flag == 3){
					// 	if($addressStatus){
					// 		$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
					// 	}
					// }
				}

				if (!empty($request->Input('usdt_trc20'))) {
					$flag = 4;
					$addData['currency'] = "USDT-TRC20";
					$addData['currency_address'] = trim($request->Input('usdt_trc20'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					if(strlen(trim($request->Input('usdt_trc20'))) >= 26 && strlen(trim($request->Input('usdt_trc20'))) <= 42){
						$split_array = str_split(trim($request->Input('usdt_trc20')));
						if ($split_array[0] == 'T') {
							
						} elseif ($split_array[0] == 't') {

						} else {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 Address should be start with "T or t"', '');
						}
					}else{
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 address is not valid!', '');
					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('usdt_trc20'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('usdt_trc20'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					if($flag == 4){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}					
				}

				if (!empty($request->Input('ltc'))) {
					$flag = 5;
					$addData['currency'] = "LTC";
					$addData['currency_address'] = trim($request->Input('ltc'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					// start with L,M,3,ltc1
						if(strlen(trim($request->Input('ltc'))) >= 26 && strlen(trim($request->Input('ltc'))) <= 42){
							$split_array = str_split(trim($request->Input('ltc')));
							$split_array1 = str_split(trim($request->Input('ltc')),4);
							if ($split_array[0] == 3)
							{
								
							} elseif ($split_array[0] == 'L') {
								
							} elseif ($split_array[0] == 'M') {

							} elseif ($split_array1[0] == 'ltc1') {
							
							} else {
								return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address should be start with "L or M or ltc1 or 3"', '');
							}
						}else{
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address is not valid!', '');
						}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('ltc'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('ltc'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}

					if($flag == 5){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}						
				}

				if (!empty($request->Input('doge'))) {
					$flag = 6;
					$addData['currency'] = "DOGE";
					$addData['currency_address'] = trim($request->Input('doge'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					if(strlen(trim($request->Input('doge'))) >= 26 && strlen(trim($request->Input('doge'))) <= 42){

					}else{
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Doge address is not valid!', '');
					}
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('doge'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('doge'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 6){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('bch'))) {
					$flag = 7;
					$addData['currency'] = "BCH";
					$addData['currency_address'] = trim($request->Input('bch'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('bch'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('bch'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 7){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('shib'))) {
					$flag = 8;
					$addData['currency'] = "SHIB";
					$addData['currency_address'] = trim($request->Input('shib'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('shib'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('shib'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 8){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}
				}

				if (!empty($request->Input('pm'))) {
					$flag = 9;
					$addData['currency'] = "PM";
					$addData['currency_address'] = trim($request->Input('pm'));
					$addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
					$addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
					$new_time = \Carbon\Carbon::now()->addDays(1);
					$token = md5(Auth::user()->user_id.$new_time);
					$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;
					if(empty($addBTCStatus)){
						$addressStatus = UserWithdrwalSetting::create($addData);
					}else if($addBTCStatus->currency_address != trim($request->Input('pm'))){
						$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;
						$updateAddress['currency_address'] = trim($request->Input('pm'));
						$addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
					}else{
						$flag = 0;
					}
					if($flag == 9){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}					
				}

				// $updateData = User::where('id', $getuser_id)->update($arrData);
				if (!empty($addressStatus)) {
					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User address updated successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				} else {
					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Already updated with same data';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			}
		}catch(Exception $e){
			// dd($e);
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	/**
	 * check user excited or not by passing parameter
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function checkUserExist(Request $request) {
		try {
			$arrInput = $request->user_id;
			//validate the info, create rules for the inputs
			$rules = array(
				'user_id' => 'required',

			);
			// dd($arrInput);
			

			// run the validation rules on the inputs from the form
			$validator = Validator::make($request->all(), $rules);
			// if the validator fails, redirect back to the form
			if ($validator->fails()) {
				$message    = $validator->errors();
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Sponsor ID required';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				//check wether user exist or not by user_id
				$checkUserExist = User::where('user_id', trim($request->user_id))->where('type', '!=', 'Admin')->select('id', 'user_id', 'fullname', 'remember_token')->first();
				
				if (!empty($checkUserExist)) {

					$arrObject['id']             = $checkUserExist->id;
					$arrObject['user_id']        = $checkUserExist->user_id;
					$arrObject['fullname']       = $checkUserExist->fullname;
					$arrObject['remember_token'] = $checkUserExist->remember_token;

					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User available';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrObject);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			}
		} catch (Exception $e) {
			dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function checkUserExistAuth(Request $request) {
		try {
			$arrInput = $request->user_id;
			//validate the info, create rules for the inputs
			$rules = array(
				'user_id' => 'required',

			);
			// dd($arrInput);
			

			// run the validation rules on the inputs from the form
			$validator = Validator::make($request->all(), $rules);
			// if the validator fails, redirect back to the form
			if ($validator->fails()) {
				$message    = $validator->errors();
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Sponsor ID required';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				//check wether user exist or not by user_id
				$checkUserExist = User::where('user_id', trim($request->user_id))->where('type', '!=', 'Admin')->select('id', 'user_id', 'fullname', 'remember_token')->first();
				
				if (!empty($checkUserExist)) {

					$arrObject['id']             = $checkUserExist->id;
					$arrObject['user_id']        = $checkUserExist->user_id;
					$arrObject['curr_user_id']        = Auth::User()->user_id;
					$arrObject['fullname']       = $checkUserExist->fullname;
					$arrObject['remember_token'] = $checkUserExist->remember_token;

					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User available';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrObject);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			}
		} catch (Exception $e) {
			dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * update user profile
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function updateUserData(Request $request) {
		// check user is from same browser or not
		// dd($request);
		$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info, Auth::user()
				->temp_info);
		if ($result == false) 
		{
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]
				['status'], 'Invalid User Token!!!', '');
		}

		//dd($request->profile_status);

		if (!empty($request->Input('fullname'))) 
		{
			$rules = array('fullname' => 'required|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/');
		}

		if (!empty($request->Input('mobile'))) 
		{
			$rules = array('mobile' => 'required|numeric|digits:10');
		}
		if (!empty($request->Input('email'))) 
		{
			$rules = array('email' => 'required|email|max:50');
		}
		/*if (!empty($request->Input('paypal_address'))) {
		$rules = array('paypal_address' => 'required|email|m:100');
		}
		 */
		// $verify_otp = verify_Otp($request->input('otp'));
		//  dd($verify_otp);
		if (!empty($request->input('btc_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('btc_address',$request->input('btc_address'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		if (!empty($request->input('ethereum'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('ethereum',$request->input('ethereum'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		if (!empty($request->input('trn_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('trn_address',$request->input('trn_address'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}

		if (!empty($request->input('bnb_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('bnb_address',$request->input('bnb_address'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		
		if (!empty($request->input('doge_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('doge_address',$request->input('doge_address'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		if (!empty($request->input('ltc_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('ltc_address',$request->input('ltc_address'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		if (!empty($request->input('sol_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('sol_address',$request->input('sol_address'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		if (!empty($request->input('usdt_trc20_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('usdt_trc20_address',$request->input('usdt_trc20_address'));
			
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		if (!empty($request->input('usdt_erc20_address'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('usdt_erc20_address',$request->input('usdt_erc20_address'));
			
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}
		if (!empty($request->input('TRC-20'))) 
		{
			$checkAddress =  $this->checkcurrencyvalidaion('TRC-20',$request->input('TRC-20'));
	
			if ($checkAddress != '') 
			{
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode   = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $checkAddress, '');
			}
		}


		if(!empty($rules))
		{
            $validator = checkvalidation($request->all(), $rules, '');
				if (!empty($validator)) 
				{

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = $validator;
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

		}
		
		$getuser_id = Auth::user()->id;
		// //dd($getuser_id);
		// $otpdata = Otp::select('otp_id','otp_status','otp')
		//     ->where('id', Auth::user()->id)
		// 	->where('otp', md5($request->otp))
		// 	->where('otp_status', '=',0)
		//     ->orderBy('entry_time', 'desc')->first();
		//dd($otpdata['otp_id']);
		$arrInput            = $request->all();
		$profile_status = verifyOtpStatus::select('profile_update_status')
            ->where('statusID','=',1)->get();
		$userData=User::where('id',$getuser_id)->first();
		if ($profile_status[0]->profile_update_status == 1) 
		{
			if($userData->google2fa_status=='disable') {
				$arrInput['user_id'] = Auth::user()->id;
				if($arrInput['type'] != "photo"){
					$arrRules            = ['otp' => 'required|min:6|max:6'];
					$validator           = Validator::make($arrInput, $arrRules);
					if ($validator->fails()) 
					{
						return setValidationErrorMessage($validator);
					}
					$otpdata         = verify_Otp($arrInput);

				}else{
					$otpdata['status'] = 200;
				}
			}else{
				$otpdata['status'] = 200;

			}
			
		} else 
		{
			$otpdata['status'] = 200;
		}
		// if (!empty($otpdata)) {
		if ($arrInput['type'] != "photo") {
			
			if($userData->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$getuser_id;
				$arrIn['otp']=$arrInput['otp_2fa'];
				$arrIn['google2fa_secret'] = $userData->google2fa_secret;
				if (empty($arrIn['otp'])) {
					$arrOutputData = [];
					$strMessage = "Google 2FA code Required";
					$intCode = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}
				$res=$this->google2facontroller->validateGoogle2FA($arrIn);
				if ($res == false) {
					$arrOutputData = [];
					$strMessage = "Invalid Google 2FA code";
					$intCode = Response::HTTP_UNAUTHORIZED;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}
			}
		}

		if ($otpdata['status'] == 200) 
		{
			//	if (true) {
			// Otp::where('otp_id', $otpdata->otp_id)->update(['otp_status' => 1]);
			//Otp::where('otp_id', $otpdata->otp_id)->delete();

			try {

				//$CheckActive = User::where([['remember_token', '=', trim($request->Input('remember_token'))], ['status', '=', 'Active']])->first();

				//---------update sponser id--------------
				$arrData = array();

				/*if (!empty($request->Input('paypal_address'))) {
				$arrData['paypal_address'] = trim($request->Input('paypal_address'));
				}*/

				if (!empty($request->Input('fullname'))) 
				{
					$arrData['fullname'] = trim($request->Input('fullname'));
				}

				if (!empty($request->Input('trn_address'))) 
				{
					$arrData['trn_address'] = trim($request->Input('trn_address'));
				}
				if (!empty($request->Input('ethereum'))) 
				{
					$arrData['ethereum'] = trim($request->Input('ethereum'));
				}
				

				if (!empty($request->Input('bnb_address'))) 
				{
					$arrData['bnb_address'] = trim($request->Input('bnb_address'));
				}

				if (!empty($request->Input('mobile'))) 
				{
					$arrData['mobile'] = trim($request->Input('mobile'));
				}
				if (!empty($request->Input('email'))) 
				{
					$arrData['email'] = trim($request->Input('email'));
				}

				if (!empty($request->Input('btc_address'))) 
				{
					$arrData['btc_address'] = trim($request->Input('btc_address'));
				}
				if (!empty($request->Input('ltc_address'))) 
				{
					$arrData['ltc_address'] = trim($request->Input('ltc_address'));
				}

				if (!empty($request->Input('doge_address'))) 
				{
					$arrData['doge_address'] = trim($request->Input('doge_address'));
				}

				if (!empty($request->Input('sol_address'))) 
				{
					$arrData['sol_address'] = trim($request->Input('sol_address'));
				}
				
				if (!empty($request->Input('usdt_trc20_address')))
				{
					$arrData['usdt_trc20_address'] = trim($request->Input('usdt_trc20_address'));
				}
				if (!empty($request->Input('country'))) 
				{
					$arrData['country'] = trim($request->Input('country'));
				}
				if (!empty($request->Input('account_no'))) 
				{
					$arrData['account_no'] = trim($request->Input('account_no'));
				}
				if (!empty($request->Input('holder_name'))) 
				{
					$arrData['holder_name'] = trim($request->Input('holder_name'));
				}
				if (!empty($request->Input('pan_no'))) 
				{
					$arrData['pan_no'] = trim($request->Input('pan_no'));
				}
				if (!empty($request->Input('bank_name'))) 
				{
					$arrData['bank_name'] = trim($request->Input('bank_name'));
				}

				if (!empty($request->Input('city'))) 
				{
					$arrData['city'] = trim($request->Input('city'));
				}

				/*if (!empty($request->Input('perfect_money_address'))) {
				$arrData['perfect_money_address'] = trim($request->Input('perfect_money_address'));
				}
				if (!empty($request->Input('facebook_link'))) {
				$arrData['facebook_link'] = trim($request->Input('facebook_link'));
				}
				if (!empty($request->Input('linkedin_link'))) {
				$arrData['linkedin_link'] = trim($request->Input('linkedin_link'));
				}
				if (!empty($request->Input('twitter_link'))) {
				$arrData['twitter_link'] = trim($request->Input('twitter_link'));
				}
				if (!empty($request->Input('instagram_link'))) {
				$arrData['instagram_link'] = trim($request->Input('instagram_link'));
				}*/

				if (!empty($request->Input('address'))) 
				{
					$arrData['address'] = trim($request->Input('address'));
				}

				if (!empty($request->Input('ifsc_code'))) 
				{
					$arrData['ifsc_code'] = trim($request->Input('ifsc_code'));
				}
				if (!empty($request->Input('branch_name'))) 
				{
					$arrData['branch_name'] = trim($request->Input('branch_name'));
				}
				if (!empty($request->Input('mobile'))) 
				{
					$arrData['mobile'] = trim($request->Input('mobile'));
				}
				
				if (!empty($arrData)) 
				{
					//UserInfo
					$oldUserData = $arrData;


					//-----iget old user data and inset----------------
					$oldUserData = DB::table('tbl_users')
						->select('id','fullname', 'address', 'country', 'holder_name', 'pan_no', 'bank_name', 'ifsc_code', 'user_id', 'mobile', 'btc_address', 'bnb_address', 'trn_address','ltc_address','sol_address','doge_address','usdt_trc20_address','ethereum', 'email')
						->where('id', $getuser_id)
						->first();

					$oldUserData->ip         = $request->ip();
					$oldUserData->updated_by = $getuser_id;
					//$count = 1;
					$check_id_exist = UserUpdateProfileCount::where('id', $getuser_id)->first();
					//dd($check_id_exist);
					$old_data_content="";
					$new_data_content="";
					if ($check_id_exist == null) 
					{

						$newData       = new UserUpdateProfileCount;
						$newData['id'] = $getuser_id;
						$newData->save();

					}
					if ($request->fullname != $oldUserData->fullname) 
					{
						$updateData2['fullname'] = DB::raw('fullname +1');
						$updateOtpSta            = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData2);
						$old_data_content.="<br>Name: ".$oldUserData->fullname;
						$new_data_content.="<br>Name: ".$request->fullname;
					}
					if ($request->email != $oldUserData->email) 
					{
						$updateData3['email'] = DB::raw('email +1');
						$updateOtpSta         = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData3);
						$old_data_content.="<br>Email: ".$oldUserData->email;
						$new_data_content.="<br>Email: ".$request->email;
					}
					if ($request->mobile != $oldUserData->mobile) 
					{

						$updateData1['mobile'] = DB::raw('mobile +1');
						$updateOtpSta          = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData1);
						$old_data_content.="<br>Mobile: ".$oldUserData->mobile;
						$new_data_content.="<br>Mobile: ".$request->mobile;

					}
					if ($request->country != $oldUserData->country) 
					{
						$updateData4['country'] = DB::raw('country +1');
						$updateOtpSta           = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData4);
					}

					if ($request->btc_address != $oldUserData->btc_address) 
					{
						$updateData5['btc_address'] = DB::raw('btc_address +1');
						$updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData5);
					}
					if ($request->bnb_address != $oldUserData->bnb_address) 
					{
						$updateData6['bnb_address'] = DB::raw('bnb_address +1');
						$updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData6);
					}
					if ($request->trn_address != $oldUserData->trn_address) 
					{
						$updateData7['trn_address'] = DB::raw('trn_address +1');
						$updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData7);
					}

					
					/* if ($request->ethereum != $oldUserData->ethereum) {
					$updateData8['eth_address'] = DB::raw('eth_address +1');
					$updateOtpSta = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData8);
					$res=verify_address($getuser_id);
				} 
					
					/*if ($oldUserData->btc_address != null || $oldUserData->btc_address != '') {
					if ($request->btc != $oldUserData->btc_address) {
					$arrData['btc_address'] = trim($oldUserData->btc_address);
					}
					}

					if ($oldUserData->trn_address != null || $oldUserData->trn_address != '') {
					if ($request->trn_address != $oldUserData->trn_address) {
					$arrData['trn_address'] = trim($oldUserData->trn_address);
					}
					}

					if ($oldUserData->ethereum != null || $oldUserData->ethereum != '') {
					if ($request->ethereum != $oldUserData->ethereum) {
					$arrData['ethereum'] = trim($oldUserData->ethereum);
					}
					}

					if ($oldUserData->bnb_address != null || $oldUserData->bnb_address != '') {
					if ($request->bnb_address != $oldUserData->bnb_address) {
					$arrData['bnb_address'] = trim($oldUserData->bnb_address);
					}
					}*/

					// unset($oldUserData->blockby_cron);

					//save old data
					if($arrInput['type'] == "photo"){
						$rules_img=array(
							'profile_image'=>"required|mimes:jpeg,jpg,png|max:130000"
						);
						/*$img_validator           = Validator::make($arrInput, $rules_img);
						if ($validator->fails()) {
							return setValidationErrorMessage($img_validator);
						}*/
						$validator = checkvalidation($request->all(), $rules_img, '');
						if (!empty($validator)) {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, '');
						}
						$file       = Input::file('profile_image');
						$newUrl = '';
						if($request->hasFile('profile_image')) {
							$url    = Config::get('constants.settings.aws_url');
							 // dd($url);
							$fileName = Storage::disk('s3')->put("user_profile", $file, "public");
							//dd($fileName);
							$newUrl=$url.$fileName;
							//dd($newUrl);
	
							$arrData['user_profile'] = $newUrl ;
						}
					}


					$saveOldData = DB::table('tbl_users_change_data')->insert((array) $oldUserData);

					// dd($arrData);

					$updateData = User::where('id', $getuser_id)->update($arrData);

					$pagename = "emails.profile_update_notification";
					$username = $userData->user_id;
					$subject='Your profile has been updated.';
					if ($new_data_content != "") {

						$data = array('pagename' => $pagename,'old_data_content'=>$old_data_content,'new_data_content'=>$new_data_content, 'username' => $username,'name'=>$request->fullname);
						$mail = sendMail($data, $userData->email, $subject);
						if ($mail) {
							/*$arrStatus  = Response::HTTP_OK;
							$arrCode    = Response::$statusTexts[$arrStatus];*/
						} else {
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Failed to send email for profile update';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}
					}
					
				}
				//-------------------------------------------------
				if (!empty($updateData)) 
				{
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User data updated successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				} else 
				{
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Already updated with same data';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} catch (Exception $e) 
			{
				dd($e);
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Something went wrong,Please try again';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

		} else if ($otpdata['status'] == 403) 
		{
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Opt expired, Resend it..';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
		 else 
		{
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Incorrect OTP Or OTP Already Used';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function updateUserImage(Request $request){
		$rules_img=array(
			'profile_image'=>"required|mimes:jpeg,jpg,png|max:130000"
		);
						/*$img_validator           = Validator::make($arrInput, $rules_img);
						if ($validator->fails()) {
							return setValidationErrorMessage($img_validator);
						}*/
						$validator = checkvalidation($request->all(), $rules_img, '');
						if (!empty($validator)) {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, '');
						}else{
							return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Valid Image', '');
						}
	}

	public function getIpAddress(Request $request) {
		$ip = $request->ip();
		//dd($ip);
		if (!empty($ip)) {
			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Ip Address found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $ip);
		} else {
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Ip Address not found Otp';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function checkAppVersion(Request $request) {

		$arrInput  = $request->all();
		$arrRules  = ['version_code' => 'required', 'device_type' => 'required'];
		$validator = Validator::make($arrInput, $arrRules);
		if ($validator->fails()) {
			return setValidationErrorMessage($validator);
		}
		$version_code = $request->version_code;
		$device_type  = $request->device_type;
		//DB::enableQueryLog();
		$app = AppVersion::where([['version_code', '>', $version_code], ['device_type', $device_type]])->first();
		//dd(DB::getQueryLog());
		if (empty($app)) {
			$intCode    = Response::HTTP_NOT_FOUND;
			$strMessage = "App already updated successfully";
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, array());
		}
		$projectSetting            = ProjectSettings::first();
		$latestApp                 = array();
		$latestApp['title']        = "Update Is Available";
		$latestApp['app_link']     = $projectSetting->app_link;
		$latestApp['version_name'] = $app['version_name'];
		$latestApp['version_code'] = $app['version_code'];
		$latestApp['version_desc'] = $app['version_desc'];
		$latestApp['update_type']  = $app['update_type'];
		if ($latestApp['update_type'] == 'F') {
			$intCode    = Response::HTTP_OK;
			$strMessage = "Update Is Available";
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $latestApp);
		}

		$appupdt = AppVersionLog::where([['version_code', '>', $version_code], ['device_type', $device_type], ['update_type', "F"]])->count('id');
		if ($appupdt > 0) {
			$latestApp['update_type'] = "F";
		}
		$intCode    = Response::HTTP_NOT_FOUND;
		$strMessage = "";
		$strStatus  = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, $strMessage, $latestApp);
	}

	/**
	 * check user excited or not by passing parameter
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getAmount(Request $request) {
		$now = \Carbon\Carbon::now()->toDateString();

		$userId   = Auth::User()->id;
		$allRanks = Rank::get();

		//  $dataarray = array();
		//  foreach ($allRanks as $value) {
		//  $usersleft = TodayDetails::join('tbl_users as tu','tu.id','=','tbl_today_details.from_user_id')
		// 			->where('tbl_today_details.to_user_id','=value',$userId)
		// 			->where('tbl_today_details.position', '=', 1)
		// 			->where('tu.rank', '=',$value->rank)
		// 			->count();
		// 			//$coutdata = $data->where('rank', '=', $value->rank)->count();
		// 			$dataarray[$value->rank] = $usersleft;
		//  }
		// dd($usersleft);
		foreach ($allRanks as $value) {
			$data = TodayDetails::join('tbl_super_matching as tu', 'tu.user_id', '=', 'tbl_today_details.from_user_id')
				->where('tbl_today_details.to_user_id', '=', $userId)
				->where('tbl_today_details.position', '=', 2)
				->select('tbl_today_details.from_user_id', 'tu.rank')	->get();

			$coutdata                = $data->where('rank', '=', $value->rank)->count();
			$dataarray[$value->rank] = $coutdata;

		}

		foreach ($allRanks as $value) {
			$dataleft = TodayDetails::join('tbl_super_matching as tu', 'tu.user_id', '=', 'tbl_today_details.from_user_id')
				->where('tbl_today_details.to_user_id', '=', $userId)
				->where('tbl_today_details.position', '=', 1)
				->select('tbl_today_details.from_user_id', 'tu.rank')	->get();

			$coutdataleft                = $dataleft->where('rank', '=', $value->rank)->count();
			$dataarrayleft[$value->rank] = $coutdataleft;

		}
		$usersleft = TodayDetails::join('tbl_users as tu', 'tu.id', '=', 'tbl_today_details.from_user_id')
			->where('tbl_today_details.to_user_id', '=', $userId)
			->where('tbl_today_details.position', '=', 1)
			->where('rank', '!=', null)
			->count();

		$usersright = TodayDetails::join('tbl_users as tu', 'tu.id', '=', 'tbl_today_details.from_user_id')
			->where('tbl_today_details.to_user_id', '=', $userId)
			->where('tbl_today_details.position', '=', 2)
			->where('rank', '!=', null)
			->count();

		$todayright = TodayDetails::join('tbl_users as tu', 'tu.id', '=', 'tbl_today_details.from_user_id')
			->where('tbl_today_details.to_user_id', '=', $userId)
			->where('tbl_today_details.position', '=', 2)
			->where('rank', '!=', null)
			->where('tbl_today_details.entry_time', 'like', '%'.$now.'%')
			->count();

		$todayleft = TodayDetails::join('tbl_users as tu', 'tu.id', '=', 'tbl_today_details.from_user_id')
			->where('tbl_today_details.to_user_id', '=', $userId)
			->where('tbl_today_details.position', '=', 1)
			->where('rank', '!=', null)
			->where('tbl_today_details.entry_time', 'like', '%'.$now.'%')
			->count();

		$arrData['usersleft']        = $dataarrayleft;
		$arrData['usersright']       = $dataarray;
		$arrData['usersrightcounts'] = $usersright;
		$arrData['usersleftcounts']  = $usersleft;
		$arrData['todayright']       = $todayright;
		$arrData['todayleft']        = $todayleft;
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
	}
	public function supermatchingbonusdata(Request $request) {
		// $now = \Carbon\Carbon::now()->toDateString();
		$day    = \Carbon\Carbon::now()->format('D');
		$userId = Auth::User()->id;

		$user = User::join('tbl_super_matching', 'tbl_super_matching.user_id', '=', 'tbl_users.id')
			->select('tbl_users.id', 'tbl_users.mobile', 'tbl_users.country', 'tbl_users.user_id', 'tbl_users.email', 'tbl_super_matching.pin', 'tbl_super_matching.rank', 'tbl_super_matching.entry_time')
			->where([['tbl_users.status', '=', 'Active'], ['tbl_users.type', '=', ''], ['tbl_users.id', '=', $userId]])
			->where('tbl_super_matching.rank', 'Ace')
			->first();

		$today           = \Carbon\Carbon::now();
		$today_datetime  = $today->toDateTimeString();
		$today_datetime2 = $today->toDateString();

		$id            = $user['id'];
		$pin           = $user['pin'];
		$entry_time    = $user['entry_time'];
		$user_id       = $user['user_id'];
		$rank          = $user['rank'];
		$lastDateExist = SupperMatchingIncome::select('entry_time', 'rank')->where([['id', '=', $id]])->orderBy('entry_time', 'desc')->first();

		$Dailydata = array();
		if (!empty($lastDateExist)) {
			// A
			$entry_time = $lastDateExist->entry_time;

			$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + 7 days'));

			if (strtotime($nextEntrydate) <= strtotime($today)) {
				$packageExist1          = Rank::where([['rank', '=', $lastDateExist->rank]])->limit(1)->first();
				$bonus_percentage       = $packageExist1->bonus_percentage;
				$entry_in_supermatching = supermatching::select('rank', 'pin', 'entry_time')
					->where([['user_id', '=', $id], ['rank', '=', $lastDateExist->rank]])
					->first();
				$pin                     = $entry_in_supermatching->pin;
				$qualify_date_time       = $entry_in_supermatching->entry_time;
				$rank                    = $lastDateExist->rank;
				$entry_in_supermatching1 = supermatching::select('rank', 'pin', 'entry_time')
					->where([['user_id', '=', $id], ['entry_time', '>', $qualify_date_time]])
					->get();

				if (count($entry_in_supermatching1) > 0) {
					foreach ($entry_in_supermatching1 as $key => $value) {
						if (date('Y-m-d', strtotime($entry_in_supermatching1[$key]->entry_time.' + 15 days')) <= $nextEntrydate) {

							$pin  = $entry_in_supermatching1[$key]->pin;
							$rank = $entry_in_supermatching1[$key]->rank;

							$packageExist1    = Rank::where([['rank', '=', $rank]])->limit(1)->first();
							$bonus_percentage = $packageExist1->bonus_percentage;

						}

					}
				}

				$Dailydata['entry_time'] = $nextEntrydate;
				$Dailydata['rank']       = $rank;

			} else {
				$Dailydata['entry_time'] = $nextEntrydate;
				$Dailydata['rank']       = $lastDateExist->rank;
			}

		} else {
			// B
			/*  $entry_time=$entry_time;*/

			$entry_time = date('Y-m-d', strtotime($entry_time));

			$last_entry_in_supermatching = supermatching::select('rank', 'pin')
				->where([['user_id', '=', $id]])
				->where([[DB::raw("(Date(entry_time))"), '=', $entry_time]])
				->orderBy('entry_time', 'desc')
				->orderBy('id', 'desc')
				->first();
			/*dd($last_entry_in_supermatching->rank);*/
			$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + 7 days'));
			//	dd($last_entry_in_supermatching);
			if (strtotime($nextEntrydate) <= strtotime($today)) {
				$packageExist            = Rank::where([['rank', '=', $last_entry_in_supermatching->rank]])->limit(1)->first();
				$supermatching           = $packageExist->bonus_percentage;
				$Dailydata['entry_time'] = $nextEntrydate;
				$Dailydata['rank']       = $last_entry_in_supermatching->rank;

			} else {
				$Dailydata['entry_time'] = $nextEntrydate;
				$Dailydata['rank']       = $last_entry_in_supermatching->rank;
			}
		}

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $Dailydata);
	}
	public function checkUserExistCrossLeg(Request $request) {

		$arrInput = $request->all();
		//validate the info, create rules for the inputs
		$rules = array(
			'user_id' => 'required',
		);
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {

			$checkUserExist = User::select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.remember_token')
				->join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_users.id')
				->where(['tbl_users.user_id' => $arrInput['user_id']])
			// ->where(['tbl_users.user_id' => $arrInput['user_id'], 'ttd.to_user_id' => Auth::user()->id])
				->first();

			if (!empty($checkUserExist)) {
				$arrObject['id']             = $checkUserExist->id;
				$arrObject['user_id']        = $checkUserExist->user_id;
				$arrObject['fullname']       = $checkUserExist->fullname;
				$arrObject['remember_token'] = $checkUserExist->remember_token;

				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'User available';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrObject);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'User not available';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		}
	}

	/**
	 * [Upload photos description]
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function uploadPhotos(Request $request) {
		//dd($request);
        $rules = array(
            'photo' => 'required',
            'name' => 'required'
        );
        $messages = array(
            'photo.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        // $getuser = Auth::user();
        $id = Auth::user()->id;
	 if (!empty($request->photo)) {
		$url  = Config::get('constants.settings.aws_url');
		// dd($url);
        if ($request->name == 'photo') {
			$image = $request->photo;  
            $data = explode( ',', $image );
			//  dd($data);
			$b64 = $data[1];

			// Obtain the original content (usually binary data)
			$bin = base64_decode($b64);
			// Gather information about the image using the GD library
			$size = getImageSizeFromString($bin);
			/*
			$size2 = getBase64ImageSize($image);
			
			if ($size2 > 1) {
				$message='Upload Image Max Size is 1Mb';
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message, '');			
			}
			*/
			
			$ext = substr($size['mime'], 6);
			if (!in_array($ext, ['jpg','png','jpeg'])) {
				$message='Upload Image only jpg,png or jpeg format';
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message, '');
			
			}	
			$imageName = rand().$ext;
			$filenametostore='profile/'. $imageName;
			$fileName = Storage::disk('s3')->put($filenametostore, base64_decode($data[1]), 'public');
			$newUrl              = $url.$filenametostore;
			$updateData['user_image'] = $newUrl;
			$updateData1['photo'] = $newUrl;
			$updateData1['user_id'] = $id;
			$updateData1['phdate'] = \Carbon\Carbon::now();

			// $updateData['photo_v'] = 'Unverified';
			// $updateData['id'] = $id;
        } else if ($request->name == 'pan') {
            // $request->photo->move(public_path('uploads/pancard'), $imageName);
            $path = public_path('uploads/pancard/' . $imageName);
            $updateData['pancard'] = $imageName;
            // $updateData['pancard_v'] = 'Unverified';
        } else if ($request->name == 'address') {
            // $request->photo->move(public_path('uploads/addressproof'), $imageName);
            $path = public_path('uploads/addressproof/' . $imageName);
            $updateData['address']=$imageName;
            $updateData['address_v'] = 'Unverified';
        }
	}else{
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please Select Profile Image!', '');  
	}	
        $checkexist = User::where('id',$id)->first();
		$checkexist1 = KYC::where('user_id',$id)->first();
		if (!empty($checkexist && $checkexist1 )) {
			$updateOtpSta = KYC::where('user_id', $id)->update($updateData1);
			$updateOtpSta = User::where('id', $id)->update($updateData);
		}else{
			$updateOtpSta = KYC::create($updateData1);
		}

        if (!empty($updateOtpSta)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image uploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }

	public function uploadPhotosNew(Request $request) {
		try{
        $rules = array(
            'photo' => 'required|mimes:jpg,jpeg,png',
            'name' => 'required'
        );
        $messages = array(
            'photo.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        // $getuser = Auth::user();
        $id = Auth::user()->id;
        if (!empty($request->photo)) {
            $imageName = time() . '.' . $request->photo->getClientOriginalExtension();
        }
        if ($request->name == 'photo') {
            // $request->photo->move(public_path('uploads/photo'), $imageName);
            // $path = public_path('uploads/photo/' . $imageName);
			// dd($path);
            // $updateData['bot_image'] = $imageName;
            // $updateData['photo_v'] = 'Unverified';
            // $updateData['user_id'] = $id; 

            $url = Config::get('constants.settings.aws_url');
			 
			$fileName = Storage::disk('s3')->put("images", $request->file('photo'), "public");
			 
			$newUrl = $url . $fileName;
			$updateData['user_image'] = $newUrl;
			//dd($updateData['user_image']);
			// $updateData['photo_v'] = 'Unverified';
			//$updateData['id'] = $id;
        } else if ($request->name == 'pan') {
            //$request->photo->move(public_path('uploads/pancard'), $imageName);
            $path = public_path('uploads/pancard/' . $imageName);
            $updateData['pancard'] = $imageName;
            // $updateData['pancard_v'] = 'Unverified';
        } else if ($request->name == 'address') {
            //$request->photo->move(public_path('uploads/addressproof'), $imageName);
            $path = public_path('uploads/addressproof/' . $imageName);
            $updateData['address']=$imageName;
            $updateData['address_v'] = 'Unverified';
        }
        $checkexist = User::where('id',$id)->first();
		if (!empty($checkexist)) {
			$updateOtpSta = User::where('id', $id)->update($updateData);
		}

        if (!empty($updateOtpSta)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image uploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
	}catch(Exception $e){
		dd($e);
		$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
		$arrCode = Response::$statusTexts[$arrStatus];
		$arrMessage = 'Something went wrong,Please try again';
		return sendResponse($arrStatus, $arrCode, $arrMessage, '');
	}
    }

	public function getProfileImg() {

		$url    = url('/uploads/photo/');
		$userid = Auth::user()->id;

		$img = KYC::selectRaw('CONCAT("'.$url.'","/",photo) as attachment')->where('user_id', $userid)->first();
		if (!empty($img)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image found successfully!', $img);
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not found.', '');
		}
	}

	public function getUserId(Request $request) {
		$uid    = $request->uid;
		$userid = User::where('unique_user_id', $uid)->pluck('user_id')->first();
		if (!empty($userid)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User found successfully!', $userid);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user', '');
		}
	}

	public function GetHeaderDetails(Request $request)
	{	
		// dd(Auth::user());	
		$arrData['userid'] = Auth::user()->user_id;
		$arrData['ip_address'] = $_SERVER['REMOTE_ADDR'];
		$current_time            = getTimeZoneByIP($arrData['ip_address']);
		$arrData['current_time'] = $current_time;
		// dd($arrData);
		if (!empty($arrData)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found successfully!', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
		}
	}

	public function checkDownline(Request $request) {

		$userId   = Auth::User()->id;
		$settings = Config::get('constants.settings');
		$rules    = array(
			'user_id' => 'required',
		);

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			$err     = '';
			foreach ($message->all() as $error) {
				$err = $err." ".$error;
			}
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, 0);
		}

		$from_user_id = User::where('user_id', $request->user_id)->pluck('id')->first();
		// dd($from_user_id,$userId);
		if ($from_user_id != $userId) {
			$todaydetailsexist = TodayDetails::where('to_user_id',$userId)->where('from_user_id',$from_user_id)->get();
			if (count($todaydetailsexist) > 0) {
				// dd($from_user_id);
				$dashboarddata = Dashboard::where('id', $from_user_id)->first();
				// dd($dashboarddata);
				if (!empty($dashboarddata)) {
					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], ' paid downline user', 1);
				}
				return sendresponse($this->statuscode[200]['code'],$this->statuscode[200]['status'], 'Downline user', 2);			
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Not a Downline user', 0);
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Not a Downline user', 0);
		}
	}
	public function getFranchiseUsers(Request $request) {
		$user = Auth::user();

		//dd($request->country);
		if (!empty($user)) {
			$users_list = User::select('id', 'user_id', 'fullname')
				->where('is_franchise', '1')
				->where('income_per', '=', '3')
				->where('country', '=', $request->country)
				->where('status', '=', 'Active')
				->get();
			if (!empty($users_list)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $users_list);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User Unaunthenticated', '');
		}
	}

		public function TransferToOthermember(Request $request)
	{
		try {
			$id = Auth::user()->id;
			$user_id = Auth::user()->user_id;
			$full_name = Auth::user()->fullname;
			$email = Auth::user()->email;
			// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message,'');

			$arrInput = $request->all();
			$rules = array(
				'Amount' => 'required|numeric|min:20',
				'to_userId' => 'required'
			);
			$validator = Validator::make($arrInput, $rules);

			if ($validator->fails()) {
				$message = messageCreator($validator->errors());
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message, '');
			}

			//check wether user exist or not by user_id
			$checkUserExist = User::where('user_id', trim($request->to_userId))->select('id', 'user_id', 'fullname', 'remember_token')->first();
			//dd($checkUserExist);
			if (!empty($checkUserExist)) {
			} else {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'You have enter wrong user id';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}


			if ($request->to_userId == $user_id) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You cannot transfer to self wallet!!', '');
			}

			// for downline users
			$from_user_id = User::where('user_id', $request->to_userId)->pluck('id')->first();

			$todaydetailsexist = TodayDetails::where('to_user_id', $id)->where('from_user_id', $from_user_id)->get();
			if (count($todaydetailsexist) > 0) {
				// $arrStatus  = Response::HTTP_OK;
				// $arrCode    = Response::$statusTexts[$arrStatus];
				// $arrMessage = 'User available';
				// return sendResponse($arrStatus, $arrCode, $arrMessage, $arrObject);

			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Not a Downline user', 0);
			}

			// for downline users

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
			$id = Auth::User()->id;
			if (!empty($id)) {

				$transfer_status = verifyOtpStatus::select('transfer_update_status')
					->where('statusID', '=', 1)->get();
				if ($transfer_status[0]->transfer_update_status == 1) {
					$arrInput            = $request->all();

					$arrInput['user_id'] = Auth::user()->id;
					$arrRules            = ['otp' => 'required|min:6|max:6'];
					$validator           = Validator::make($arrInput, $arrRules);

					if ($validator->fails()) {
						return setValidationErrorMessage($validator);
					}
					// dd(123);
					$otpdata         = verify_Otp($arrInput);
					//dd($otpdata);
				} else {
					$otpdata['status'] = 200;
				}
				if ($otpdata['status'] == 200) {

					$dash1 = Dashboard::where('id', $id)->first();
					$working_wallet_balance = $dash1->fund_wallet - $dash1->fund_wallet_withdraw;

					if ($request->Amount <= $working_wallet_balance && $request->Amount > 0) {


						// update data for to user id
						$user_data = DB::table('tbl_users')
							->select('id', 'user_id')
							->where('user_id', $request->to_userId)
							->get();

						if (!empty($user_data) && count($user_data) > 0) {
							$topup_user = Dashboard::where('id', $user_data[0]->id)->count();

							if ($topup_user) {
								// update data for from user_id
								$updateData = array();
								$updateData['fund_wallet_withdraw'] = DB::raw("fund_wallet_withdraw + " . $request->Amount);
								$updtdash = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

								$updateData1 = array();
								$updateData1['fund_wallet'] = DB::raw("fund_wallet + " . $request->Amount);
								$updtdash1 = DB::table('tbl_dashboard')->where('id', $user_data[0]->id)->update($updateData1);

								$dash = Dashboard::where('id', $user_data[0]->id)->select('fund_wallet', 'fund_wallet_withdraw')->first();

								$available_balance = $dash->fund_wallet - $dash->fund_wallet_withdraw;

								$values = array('id' => $id, 'to_user_id' => $user_data[0]->id, 'balance' => $request->Amount, 'transfer_type' => 'Bitrobix Wallet', 'entry_time' => \Carbon\Carbon::now());

								DB::table('transfer_to_other_member')->insert($values);

								$Trandata1 = array(
									'id' => $id,
									'refference' => $id,
									'debit' => $request->working_balance_transfer,
									'balance' => $available_balance,
									'type' => "Bitrobix Wallet",
									'status' => 1,
									'remarks' => '$' . $request->working_balance_transfer . ' has transfer from Bitrobix Wallet',
									'entry_time' => \Carbon\Carbon::now(),
									'ip_address'	=> getIPAddress()
								);

								$Trandata3 = array(
									'id' => $id,
									'message' => '$' . $request->working_balance_transfer . 'transfer from Bitrobix Wallet',
									'status' => 1,
									'entry_time' => \Carbon\Carbon::now()
								);

								$TransactionDta1 = AllTransaction::insert($Trandata1);
								$actDta = Activitynotification::insert($Trandata3);

								return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Wallet transfer Succesfully', '');
							} else {
								return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Problem in transferring fund', '');
							}
						} else {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not exists', '');
						}
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You Have a insufficient balance', '');
					}
				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invaild OTP';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			}
		} catch (Exception $e) {
			dd($e);
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}



	public function getMasterFranchiseUsers(Request $request) {
		$user = Auth::user();

		//dd($request->country);
		if (!empty($user)) {
			$users_list = User::select('id', 'user_id', 'fullname')
				->where('is_franchise', '1')
				->where('income_per', '=', '2')
				->where('status', '=', 'Active')
				->get();
			if (!empty($users_list)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $users_list);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User Unaunthenticated', '');
		}
	}

	/**
	 * Function to send opt for registration
	 *
	 * @param $arrInput : Array of input
	 *
	 */
	public function sendRegisterOtp($arrInput) {
		$arrOutputData = [];
		$intCode       = Response::HTTP_INTERNAL_SERVER_ERROR;
		$strMessage    = trans('user.error');
		$strStatus     = Response::$statusTexts[$intCode];
		DB::beginTransaction();
		try {

			$mobile = trim($arrInput['mobile_number']);
			/* $mobile = str_replace("/^\+(?:48|22)-/", "", $mobile);*/
			//$mobile = preg_replace("/^\+\d+-/", "", ($mobile));
			//dd($mobile);

			$user         = new User;
			$user->mobile = $mobile;

			$random    = rand(100000, 999999);
			$insertotp = array();
			/* $insertotp['id'] = $arrInput['id'];*/
			/* $mobile = trim($arrInput['whatsapp_no']);
			$mobile = str_replace(" ", "", $mobile);*/
			date_default_timezone_set('Europe/London');
			$mytime                  = \Carbon\Carbon::now();
			$current_time            = $mytime->toDateTimeString();
			$insertotp['entry_time'] = $current_time;
			$insertotp['id']         = $arrInput['user_id'];
			$insertotp['mobile_no']  = $mobile;
			$insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
			$insertotp['otp']        = md5($random);
			$insertotp['otp_status'] = 0;
			$insertotp['type']       = "mobile";
			$msg                     = $random.' is your verification code';
			sendSMS($mobile, $msg);
			$sendotp    = Otp::insert($insertotp);
			$intCode    = Response::HTTP_OK;
			$strMessage = trans('User OTP Sent');

		} catch (PDOException $e) {
			dd($e);
			DB::rollBack();
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		} catch (Exception $e) {
			dd($e);
			DB::rollBack();
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		}
		DB::commit();
		$strStatus = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
	}
	public function sendRegistrationOtp(Request $request) {
		$arrOutputData = [];
		try {
			$arrInput  = $request->all();
			$arrRules  = ['email' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
			/*dd($arrRules  = ['whatsapp_no']);*/
			if ($validator->fails()) {

				return setValidationErrorMessage($validator);
			}

			/*    $getMobileRegCount =  User::where('mobile',$request->whatsapp_no)->count();
			if($getMobileRegCount > 3){
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Already registered 3 times with this mobile No.', '');
			} */
			/*$checkMobileNoExist =  UserModel::where('mobile',$request->whatsapp_no)->first();
			if(!empty($checkMobileNoExist)){
			$strMessage         = " Registration with this mobile no. already exist ";
			$intCode            = Response::HTTP_NOT_FOUND;
			$strStatus          = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage,[]);
			}*/

			//return $this->sendRegisterOtp($arrInput);
			//dd($request->user_id);

			return $this->sendotponmail($request->user_id, $request->email);
		} catch (Exception $e) {
			// dd($e);
			$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = trans('admin.defaultexceptionmessage');
		}
		$strStatus = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
	}
	public function sendotponmail($users, $email, $type = null) {
		/*dd($email);*/
		$checotpstatus = Otp::where('id', '=', $users)->orderBy('entry_time', 'desc')->first();
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

		$pagename = "emails.registrationotp";
		$subject  = "OTP sent successfully";
		$random   = rand(100000, 999999);
		$data     = array('pagename' => $pagename, 'otp' => $random, 'username' => $users);

		$mail = sendMail($data, $email, $subject);

		$insertotp = array();
		date_default_timezone_set('Europe/London');
		$mytime_new              = \Carbon\Carbon::now();
		$current_time_new        = $mytime_new->toDateTimeString();
		$insertotp['entry_time'] = $current_time_new;
		$insertotp['id']         = $users;
		$insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
		$insertotp['otp']        = md5($random);
		$insertotp['otp_status'] = 0;
		$insertotp['type']       = 'email';

		$sendotp = Otp::create($insertotp);

		$arrData = array();
		// $arrData['id']   = $users->id;
		// $arrData['remember_token'] = $users->remember_token;

		$arrData['mailverification']   = 'TRUE';
		$arrData['google2faauth']      = 'FALSE';
		$arrData['mailotp']            = 'TRUE';
		$arrData['mobileverification'] = 'TRUE';
		$arrData['otpmode']            = 'FALSE';
		/* dd($arrData);*/
		//$mask_mobile = maskmobilenumber($users->mobile);
		$mask_email       = maskEmail($email);
		$arrData['email'] = $mask_email;
		//$arrData['mobile'] = $mask_mobile;

		/* if($type == null)
		{
		return $random;
		}*/

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');

		return $sendotp;

		//}  // end of users
	}

	/**
	 * Function to send opt for registration
	 *
	 * @param $arrInput : Array of input
	 *
	 */
  
	public function verifyRegisterOtp($arrInput) {

		$strMessage    = trans('user.error');
		$arrOutputData = [];
		try {
			// $arrInput   = ['mobile_number'];
			/* $otp        = $arrInput['otp'];*/

			// $checotpstatus = Otp::where('id', $arrInput['user_id'])->where('otp', md5($arrInput['otp']))->orderBy('entry_time', 'desc')->first();
			$checotpstatus = Otp::where('id', $arrInput['user_id'])->where('otp', hash('sha256',$arrInput['otp']))->orderBy('entry_time', 'desc')->first();
			// dd($checotpstatus);
			// check otp status 1 - already used otp
			if (empty($checotpstatus)) {
				$strMessage = 'Invalid otp';
				$intCode    = Response::HTTP_BAD_REQUEST;
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
			}

			if (!empty($checotpstatus)) {

				//date_default_timezone_set("Asia/Kolkata");
				$entry_time = $checotpstatus->entry_time;
				//$out_time = $checotpstatus->out_time;
				//$checkmin = date('Y-m-d H:i:s', strtotime('+1 minutes', strtotime($entry_time)));
				//$current_time = date('Y-m-d H:i:s');
				//$mytime = \Carbon\Carbon::now();
				//$current_time = $mytime->toDateTimeString();

				if ($entry_time != '' && $checotpstatus->otp_status != '1') {
					// if (md5($arrInput['otp']) == $checotpstatus['otp']) {
						if (hash('sha256', $arrInput['otp']) == $checotpstatus['otp']) {
						Otp::where('otp_id', $checotpstatus->otp_id)->update(['otp_status' => '1']);
						$intCode    = Response::HTTP_OK;
						$strStatus  = Response::$statusTexts[$intCode];
						$strMessage = "OTP Verified.";
						return sendResponse($intCode, $strStatus, $strMessage, 1);
					} else {
						$strMessage = 'Invalid otp';
						$intCode    = Response::HTTP_BAD_REQUEST;
						$strStatus  = Response::$statusTexts[$intCode];
						return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
					}
	            } 
	            else
	            {
	                $updateData = array();
	                $updateData['otp_status'] = '1';
					$updateOtpSta = Otp::where([['otp_id', $checotpstatus->otp_id],['otp_status','0']])->update($updateData);
					$intCode        = Response::HTTP_BAD_REQUEST;
		            $strStatus      = Response::$statusTexts[$intCode];
		            $strMessage     = "Otp is expired. Please resend"; 
		            return sendResponse($intCode, $strStatus, $strMessage,1);
	                //return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp is expired. Please resend', '');
	            }
	        }
            // make otp verify
            //$this->secureLogindata($user->user_id, $user->password, 'Login successfully');
            $updateOtpSta = Otp::where('otp_id', /*Auth::user()->id*/1)->update([
                'otp_status' => 1, //1 -verify otp
                'out_time'  => now(),
            ]);

        
        } catch (Exception $e) {
        	dd($e);
            $intCode    = Response::HTTP_BAD_REQUEST;
            $strStatus  = Response::$statusTexts[$intCode];
            $strMessage = 'Something went wrong. Please try later.';
            return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
        }
    }

      /**
     * Function to verify the registration Otp
     * 
     * @param $request : HTTP Request Object
     * 
     */
    public function verifyRegistrationOtp(Request $request)
    {
        $arrOutputData  = [];
        try {
            $arrInput  = $request->all();
            $arrRules  = ['otp' => 'required|min:6|max:6'];
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                return setValidationErrorMessage($validator);
            }
           return $this->verifyRegisterOtp($arrInput);
        } catch (Exception $e) {
            $intCode       = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strMessage    = trans('admin.defaultexceptionmessage');
        }
        $strStatus  = Response::$statusTexts[$intCode];
        return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
    }

  public function checkUplineUserExist(Request $request)
	{

		try {
			$arrInput = $request->all();
		//	print_r($arrInput);
			//validate the info, create rules for the inputs
			$rules = array(
				'upline_user_id' => 'required',
			);
			$user_id=Auth::User()->user_id;

			// run the validation rules on the inputs from the form
			$validator = Validator::make($request->all(), $rules);
			// if the validator fails, redirect back to the form
			if ($validator->fails()) {
				$message = $validator->errors();
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Input credentials is invalid or required', $message);
			} else {
				//check wether user exist or not by user_id

				$checkUserExist = User::where(['user_id' => $user_id])->first();
				$checkUpUserExist = User::where(['user_id' => $arrInput['upline_user_id']])->first();

				if (!empty($checkUserExist) && !empty($checkUpUserExist)) {
					$checkUpline = TodayDetails::where('tbl_today_details.to_user_id', $checkUpUserExist->id)->where('tbl_today_details.from_user_id', $checkUserExist->id)->count('today_id');
					if ($checkUpline == 1) {
						
						if ($checkUserExist->type != 'sub-admin' || $checkUpUserExist->type != 'sub-admin') {

							$dash = Dashboard::where('id', $checkUpUserExist->id)->first();
							// $stopstatus = User::select('stop_roi_status')->where('id', $checkUpUserExist->id)->first();
							// $withstatus = User::select('auth_status')->where('id', $checkUpUserExist->id)->first();

							// $bvcount = CurrentAmountDetails::select('user_id','left_bv','right_bv')->where('user_id', $checkUpUserExist->id)->first();
							// if(!empty($bvcount))
							// {
							$arrObject['balance'] = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
							$arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
							$arrObject['acc_wallet'] = $dash->working_wallet - $dash->working_wallet_withdraw;
							$arrObject['id'] = $checkUpUserExist->id;
							$arrObject['remember_token'] = $checkUpUserExist->remember_token;
							$arrObject['fullname'] = $checkUpUserExist->fullname;
							$arrObject['username'] = $checkUpUserExist->fullname;
							$arrObject['email'] = $checkUpUserExist->email;
							$arrObject['power_lbv'] = $checkUpUserExist->power_l_bv;
							$arrObject['power_rbv'] = $checkUpUserExist->power_r_bv;
							$arrObject['l_bv'] = $checkUpUserExist->curr_l_bv;
							$arrObject['r_bv'] = $checkUpUserExist->curr_r_bv;

							// }else
							// {
							// 	$arrObject['balance'] = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
							// 	$arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
							// 	$arrObject['user_id'] = $user_id;
							// 	$arrObject['id'] = $checkUpUserExist->id;
							// 	$arrObject['fullname'] = $checkUpUserExist->fullname;
							// 	$arrObject['username'] = $checkUpUserExist->fullname;
							// 	$arrObject['email'] = $checkUpUserExist->email;
							// 	$arrObject['l_bv'] = 0;
							// 	$arrObject['r_bv'] = 0;
							// }
						}
						/* else
					 	{*/

						$arrObject['user_id'] = $checkUpUserExist->user_id;
						$arrObject['id'] = $checkUpUserExist->id;
						$arrObject['fullname'] = $checkUpUserExist->fullname;
						$arrObject['username'] = $checkUpUserExist->fullname;
						$arrObject['l_business'] = $checkUpUserExist->l_bv;
						$arrObject['r_business'] = $checkUpUserExist->r_bv;
						$arrObject['coin_balance'] = $dash->coin - $dash->coin_withdrawal;
						// $arrObject['stop_roi_status'] = $stopstatus->stop_roi_status;
						// $arrObject['auth_status'] = $withstatus->auth_status;

						/*  } */

						// /dd($user_id, $dash->coin, $dash->coin_withdraw);

						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User available', $arrObject);
					}else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid upline ID', '');
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
				}
			}
		} catch (\Exception $e) {


			dd($e);
		}
	}

    public function verifyUserOtp(Request $request)
    {
        $arrOutputData  = [];
        try {
        	$id = Auth::User()->id;
            $arrInput  = $request->all();
            $arrInput['user_id'] = $id;
            $arrRules  = ['otp' => 'required|min:6|max:6'];
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                return setValidationErrorMessage($validator);
            } 
           return $this->verifyRegisterOtp($arrInput);
        } catch (Exception $e) {
            $intCode       = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strMessage    = trans('admin.defaultexceptionmessage');
        }
        $strStatus  = Response::$statusTexts[$intCode];
        return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
    }
    public function verfiyRegEmailOtp(Request $request)
    {

        $arrOutputData = [];
        try {
            $arrInput  = $request->all();
            if($arrInput['type'] == 'email'){
                $arrRules  = array('email_otp' => 'required|min:6|max:6','email' => 'required');
            }elseif($arrInput['type'] == 'mobile'){
                $arrRules  = array('mobile_otp' => 'required|min:6|max:6','mobile' => 'required');
            }else{
                $strMessage = 'Something went wrong!!';
                $intCode    = Response::HTTP_NOT_FOUND;
                $strStatus  = Response::$statusTexts[$intCode];
                $arrOutputData['status'] = $arrInput['type'];
                return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData); 
            }
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                return setValidationErrorMessage($validator);
            }
            return $this->verifyRegAllOtp($arrInput);
            $arrOutputData['status'] = $arrInput['type'];
        } catch (Exception $e) {
            $intCode       = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strMessage    = trans('admin.defaultexceptionmessage');
            $arrOutputData['status'] = $arrInput['type'];
        }
        $strStatus  = Response::$statusTexts[$intCode];
        return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);
    }


public function verifyRegAllOtp($arrInput)
    {
        $arrOutputData  = [];
        try {
            if($arrInput['type'] == 'email'){
                // $where = array('otp' => $arrInput['email_otp'],'email' => $arrInput['email'], 'status' => '0');
				$where = array('otp' => hash('sha256', $arrInput['email_otp']),'email' => $arrInput['email'], 'status' => '0');
                $strMessage     = "Email Otp Verified";
            }elseif($arrInput['type'] == 'mobile'){
                // $where = array('otp' => $arrInput['mobile_otp'],'mobile' => $arrInput['mobile'], 'status' => '0');
				$where = array('otp' => hash('sha256', $arrInput['mobile_otp']),'mobile' => $arrInput['mobile'], 'status' => '0');
                $strMessage     = "Mobile Otp Verified";
            }
            $check_details = RegTempInfo::where($where)->orderBy('id', 'desc')->first();

            // check otp status 1 - already used otp
            if(empty($check_details)){
                $strMessage = 'Invalid Otp';
                $intCode    = Response::HTTP_BAD_REQUEST;
                $strStatus  = Response::$statusTexts[$intCode];
                $arrOutputData['status'] = $arrInput['type'];
                return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);
            }
            if($check_details->status == 1){
                $strMessage     = trans('user.otpverified');
                $intCode        = Response::HTTP_BAD_REQUEST;
                $strStatus      = Response::$statusTexts[$intCode];
                $arrOutputData['status'] = $arrInput['type'];
                return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);
            }
            $otpId = $check_details->id;
            $updateData = array();
            $updateData['status']=1; //1 -verify otp
            // $updateData['out_time']=date('Y-m-d H:i:s');
            $updateOtpSta =  RegTempInfo::where('id', $otpId)->update($updateData);
            if($arrInput['type'] == 'mobile'){
                if($arrInput['user_type'] != 'bulk_entry'){
                    $check_user = User::where('user_id', $arrInput['user_id'])->exists();
                    if($check_user){
                        $update = array('mobile' => $arrInput['mobile'],'country' => $arrInput['country'],'active_status' => 1);
                        $result = User::where('user_id', $arrInput['user_id'])->update($update);
                        $arrOutputData['userid'] = $arrInput['user_id'];
                        $arrOutputData['status'] = $arrInput['type'];
                    }else{
                        $strMessage = 'Invalid User';
                        $intCode    = Response::HTTP_NOT_FOUND;
                        $strStatus  = Response::$statusTexts[$intCode];
                        return sendResponse($intCode, $strStatus, $strMessage,'');
                    }
                }
            }
			$req1 = new request();

			//$reqCommingData = $arrInput['RegisterData'];
			$req1['user_id']               = $arrInput['user_id'];
			$req1['fullname']              = $arrInput['fullname'];
			$req1['email']                 = $arrInput['email'];
			$req1['ref_user_id']           = $arrInput['ref_user_id'];
			$req1['mobile']                = $arrInput['mobile'];
			$req1['password']              = $arrInput['password'];
			$req1['password_confirmation'] = $arrInput['password_confirmation'];
			$req1['position']              = $arrInput['position'];
			$req1['country']               = $arrInput['country'];
			//echo $req1 ." \n";

			$response = $this->register($req1);

			$content = $response->getContent();
			//$content = '{"code":200,"status":"OK","message":"User registered successfully","data":{"userid":"abhayvp21","sponsor_name":null,"password":"Abhay@1234"}}';
			$array = json_decode($content, true);

			$code = $array['code'];
			if ($code == 200) {
				$intCode                       = Response::HTTP_OK;
				$strStatus                     = Response::$statusTexts[$intCode];
				$arrOutputData['status']       = $arrInput['type'];
				$arrOutputData['userid']       = $array['data']['userid'];
				$arrOutputData['sponsor_name'] = $array['data']['sponsor_name'];
				$arrOutputData['password']     = $array['data']['password'];

			}

		} catch (Exception $e) {
			//dd($e);
			$intCode                 = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage              = trans('admin.defaultexceptionmessage');
			$arrOutputData['status'] = $arrInput['type'];
		}
		$intCode   = Response::HTTP_OK;
		$strStatus = Response::$statusTexts[$intCode];
		// $arrOutputData['status'] = $arrInput['type'];
		// $arrOutputData['userid'] = $arrInput['user_id'];
		// $arrOutputData['password'] = $arrInput['password'];

		return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
	}

	public function sendRegEmailOtp(Request $request) {
		$arrOutputData = [];
		
		try {
			
			if ($request->position == 1 || $request->position == 2) {
				//for condition true
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Please select Valid position';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$arrValidation = User::registrationValidationRules();
			$validator     = checkvalidation($request->all(), $arrValidation['arrRules'], $arrValidation['arrMessage']);

			if (!empty($validator)) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			// $getuser = $this->checkSpecificUserData(['user_id' => $request->Input('user_id'), 'status' => 'Active']);

			// if (empty($getuser)) {

			// } else {

			// 	$arrStatus = Response::HTTP_CONFLICT;
			// 	$arrCode = Response::$statusTexts[$arrStatus];
			// 	$arrMessage = 'User already registered exist';
			// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			// }

			$refUserExist = User::select('user_id')->where([['user_id', '=', $request->Input('ref_user_id')], ['status', '=', 'Active']])->count();

			if ($refUserExist > 0) {

			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Sponser not exist';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$arrInput = $request->all();
			if ($arrInput['type'] == 'email') {
				$arrRules = ['email' => 'email|required'];
			} elseif ($arrInput['type'] == 'mobile') {
				$arrRules = ['mobile' => 'numeric|required'];
			} else {
				$strMessage              = 'Something went wrong';
				$intCode                 = Response::HTTP_NOT_FOUND;
				$strStatus               = Response::$statusTexts[$intCode];
				$arrOutputData['status'] = $arrInput['type'];
				return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
			}
			$validator = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				return setValidationErrorMessage($validator);
			}
			if ($arrInput['type'] == 'email') {
				$random = rand(100000, 999999);
				$arr    = array('otp' => hash('sha256', $random), 'email' => $arrInput['email']);
				$result = RegTempInfo::insert($arr);
				if (!empty($result)) {
					$subject  = "Email Verification Otp is $random";
					$pagename = "emails.OtpVerification";
					$data     = array('pagename' => $pagename, 'email' => $arrInput['email'], 'otp' => $random);
					$email    = $arrInput['email'];
					$mail     = sendMail($data, $email, $subject);

					$strMessage = 'Otp send on your email id';
					// .$random;
					$intCode                 = Response::HTTP_OK;
					$strStatus               = Response::$statusTexts[$intCode];
					$arrOutputData['status'] = $arrInput['type'];
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
					// dd($mail);
				} else {
					$strMessage              = 'Please Try Again!!';
					$intCode                 = Response::HTTP_NOT_FOUND;
					$strStatus               = Response::$statusTexts[$intCode];
					$arrOutputData['status'] = $arrInput['type'];
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}

			} elseif ($arrInput['type'] == 'mobile') {
				//dd(111);
				$mobile = $arrInput['mobile'];
				$random = rand(100000, 999999);
				$arr    = array('otp' => $random, 'mobile' => $mobile);
				$result = RegTempInfo::insert($arr);

				$sms = "Your verification code is $random . Please use the code on the  website. Do not provide this code to anyone.";
				//return twilioSms($mobile,$sms,$arrInput['type']);

				// $sms = "Your verification code is $random . Please use the code on the BeastFox website. Do not provide this code to anyone.";
				$sms = "$random is your One Time Password for verifying your SignUp on . Do not share this code with anyone.";
				return plivSms($mobile, $sms, $arrInput['type']);
				$arrOutputData['status'] = $arrInput['type'];
			} else {
				// dd(20);
				$strMessage              = 'Something went wrong!!';
				$intCode                 = Response::HTTP_NOT_FOUND;
				$strStatus               = Response::$statusTexts[$intCode];
				$arrOutputData['status'] = $arrInput['type'];
				return sendResponse($intCode, $strStatus, $strMessage, '');
			}

			// return $this->sendRegisterOtp($arrInput);
		} catch (Exception $e) {
			/*dd("123");*/
			dd($e);
			$intCode                 = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage              = trans('admin.defaultexceptionmessage');
			$arrOutputData['status'] = $arrInput['type'];
		}
		$strStatus = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
	}
	public function checkUserAddrToken(Request $request){
		try{
            $arrInput  = $request->all();
            $arrRules  = ['token' => 'required'];
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                return setValidationErrorMessage($validator);
            }
			
			$get_data = UserWithdrwalSetting::select('srno','id','currency','currency_address')->where([['token',$request->token],['token_status',0]])->first();
			
			if(!empty($get_data)){
				
				$today = \Carbon\Carbon::now();
				$check_data = UserCurrAddrHistory::select('id')->where([['user_id',$get_data->id],['entry_time',$today]])->first();
				// dd($check_data);
				if(empty($check_data)){
					$insert = new UserCurrAddrHistory;
					$insert->user_id = $get_data->id;
					$insert->currency = $get_data->currency;
					$insert->currency_address = $get_data->currency_address;
					$insert->entry_time = \Carbon\Carbon::now();
					$insert->save();
					// UserWithdrwalSetting::where('srno',$get_data->srno)->update(array('token_status' => 1,'currency_address' => NULL, 'block_user_date_time' => NULL));
					UserWithdrwalSetting::where('srno',$get_data->srno)->delete();
					$data['flag'] = 1;
					$data['currency'] = $get_data->currency;
					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Address Reset Successfully!', $data);
				}else{
					$data['flag'] = 1;
					$data['currency'] = $get_data->currency;
					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Already Address Reset Successfully!', $data);
				}
			}else{
				
				$data['flag'] = 0;
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Link expired or invalid request', $data);
			}

		}catch(\Exception $e){
			dd($e);
			$data['flag'] = 0;
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $data);
		}
	}
	public function getUpdateCount(Request $request) {
		$user = Auth::user();

		//dd($request->country);
		if (!empty($user)) {
			$users_list['user']  = UserUpdateProfileCount::select('*')->where('id', $user->id)->first();
			$users_list['admin'] = DB::table('tbl_manage_update_profile')->select('filed_name', 'count')->get();
			if (!empty($users_list)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $users_list);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User Unaunthenticated', '');
		}
	}
	public function GetUserTreeImages(Request $request) {

		$objPasswordData = DB::table('tbl_tree_imges')->where([['type', '=', $request->input('type')]])->get();

		if (!empty($objPasswordData)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Images Found', $objPasswordData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Images Not Found', '');
		}
	}

	public function getToolData(Request $request){
		try{
			$user = Auth::user();
			if (!empty($user)) {

				if (!empty($request->tool_type)) {
					$getToolData = MarketTool::where('tool_type',$request->tool_type)->orderBy('srno', 'asc')->get();				
				}else{
					$getToolData = MarketTool::orderBy('srno', 'asc')->get();
				}
				if (empty($getToolData) && count($getToolData) > 0) {
	                //Country not found
					$arrStatus   = Response::HTTP_NOT_FOUND;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'Data not found'; 
					return sendResponse($arrStatus,$arrCode,$arrMessage,'');

				} else {
	                //Country found
					$arrData = $getToolData;
					$arrStatus   = Response::HTTP_OK;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'Data Found'; 
					return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);

				}
			}else{
					$arrStatus   = Response::HTTP_UNAUTHORIZED;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'User Unaunthenticated'; 
					return sendResponse($arrStatus,$arrCode,$arrMessage,'');
			}
		}catch(Exception $e){
			dd($e);
			$arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode     = Response::$statusTexts[$arrStatus];
			$arrMessage  = 'Something went wrong,Please try again'; 
			return sendResponse($arrStatus,$arrCode,$arrMessage,'');
		}
	}
 
}