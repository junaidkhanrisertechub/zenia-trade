<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Activitynotification as ActivitynotificationModel;

use App\Models\Resetpassword;
use App\Models\Resetpassword as ResetpasswordModel;
use App\Models\User as UserModel;
use Config;
use DB;
use Exception;
use Illuminate\Http\Request;
// use model here
use Illuminate\Http\Response as Response;
use PDOException;
use Validator;

class ForgotPasswordController extends Controller
{

	public $arrOutputData = [];

	public function index()
	{
		return view('user.auth.reset-password.sendEmail');
	}
	/**
	 * Function to send the reset passowrd link
	 *
	 * @param $request : HTTP Request object
	 */
	public function sendResetPasswordLink(Request $request)
	{
		// $intCode       = Response::HTTP_NOT_FOUND;
		// $strStatus     = Response::$statusTexts[$intCode];
		// $strMessage    = "Something went wrong";
		// $arrOutputData = [];
		// DB::beginTransaction();
		try {
			$arrInput  = $request->all();
			$validator = Validator::make($arrInput, [
				'user_id' => 'required',
				'g-recaptcha-response' => 'required'
				// 'email' => 'required',
			]);
			//check for validation
			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator);

			}
			$arrWhere = [['user_id', $arrInput['user_id']]];
			// $arrWhere = [['email', $arrInput['email']]];
			$user     = UserModel::select('email', 'id', 'user_id', 'mobile', 'fullname')->where($arrWhere)->first();
			// dd($user);
			if (empty($user)) {
				$strMessage = "Invalid User-ID.";
				return redirect()->back()->withErrors($strMessage);
			} else {
				//
				$arrResetPassword                         = array();
				$arrResetPassword['reset_password_token'] = md5(uniqid(rand(), true));
				$arrResetPassword['id']                   = $user->id;
				$arrResetPassword['request_ip_address']   = $request->ip();

				$insertresetDta = ResetpasswordModel::create($arrResetPassword);
				// dd($arrResetPassword);

				$actdata            = array();
				$actdata['id']      = $user->id;
				$actdata['message'] = 'Reset password link sent successfully to your registered email id.';
				$actdata['status']  = 1;
				$actDta             = ActivitynotificationModel::create($actdata);

				//$username=$user->email;
				$reset_token = $arrResetPassword['reset_password_token'];
				// dd($reset_token);

				// $arrEmailData = [];
				// $arrEmailData['email'] = $user->email;
				// $arrEmailData['template'] = 'emails.user-emails.forgot-password';
				// $arrEmailData['fullname'] = $user->fullname;
				// $arrEmailData['subject'] = "RESET PASSWORD";
				// $arrEmailData['reset_token'] = $reset_token;
				// $arrEmailData['user_id'] = $user->user_id;
				// $mail = sendEmail($arrEmailData);

				$subject   = "RESET PASSWORD";
				$pagename  = "emails.reset_password";
				$path      = Config::get('constants.settings.domainpath');
				$resetpath = $path . 'resetPassword/' . $reset_token .'/'.encrypt($user->user_id);
				$data      = array('pagename' => $pagename, 'name' => $user->fullname, 'username' => $user->user_id, 'path' => $resetpath);
				$email     = $user->email;
				$mail      = sendMail($data, $email, $subject);
				//$message   = "Please reset your password using link : " . $resetpath;
				// sendSMS($user->mobile,$message);
				//$mail = true;

				if ($mail) {
					// dd("Test1");
					$intCode    = Response::HTTP_OK;
					$strMessage = 'Reset password link sent successfully to your registered Email ID. Please Check & Verify...';
				} else {
					// dd("Test2");
					$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
					$strMessage = "Something went wrong";
				}
			}
		} catch (Exception $e) {
			// dd("test");
			$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = "Something went wrong";
		}
		// DB::commit();
		if ($intCode == 200) {
			return redirect()->back()->withSuccess($strMessage);
		} else {
			// $strStatus = Response::$statusTexts[$intCode];
			// return sendResponse($intCode, $strStatus, $strMessage, $this->arrOutputData);
			return redirect()->back()->withErrors($strMessage);
		}
	}

	public function getLink($token,$user_id)
	{
        $user_id = decrypt($user_id);
        return view('user.auth.reset-password.resetPassword',compact('token','user_id'));
	}

	/**
	 * Function to reset the passowrd
	 *
	 * @param $requet : HTTP Request object
	 */
	public function resetPassword(Request $request)
	{
		$linkexpire = Config::get('constants.settings.linkexpire');
		$intCode    = Response::HTTP_BAD_REQUEST;
		DB::beginTransaction();

		try {
			$arrInput    = $request->all();
			// $arrMesssage = array(
			// 	'resettoken' => 'Reset token required',
			// );
			$arrRules = [
//				'email' => 'required',
				'password'   => 'required|confirmed|min:5|max:15',
				'confirm_password' => ['required', 'same:password']
			];
			$validator = Validator::make($arrInput, $arrRules);

			$password = $arrInput['password'];

			$lettercase   = preg_match('@[a-zA-Z]@', $password);
			$number       = preg_match('@[0-9]@', $password);
			$specialChars = preg_match('@[^\w]@', $password);
//            dd('hiii');
			if (!$lettercase || !$number || !$specialChars || strlen($password) < 8) {

				$intCode   = Response::HTTP_NOT_FOUND;
				$strStatus = Response::$statusTexts[$intCode];

				return sendresponse($intCode, $strStatus, 'Pasword contains atleast 8 characters, Password contains atleast 1 letter, contains atleast 1 number and contains atleast 1 special character i.e. ! @ # $ *', '');
			}

			//check for validation
			// if ($validator->fails()) {
			//     return setValidationErrorMessage($validator);
			// }
			$resetPassword = ResetpasswordModel::where([['reset_password_token', '=', $arrInput['resettoken']]])->first();

			if (empty($resetPassword)) {
				$strMessage = "Invalid reset token";
			} else {
				if ($resetPassword->otp_status == 1) {
					$strMessage = 'Link already used';
				} else {
					$datetime                           = now();
					$userId                             = $resetPassword->id;
					$entry_time                         = $resetPassword->entry_time;
					$current_time                       = now();
					$hourdiff                           = round((strtotime($current_time) - strtotime($entry_time)) / 3600, 1);
					$updateData                         = array();
					$updateData['reset_password_token'] = $arrInput['resettoken'];
					$updateData['otp_status']           = 1;
					ResetpasswordModel::where('id', $userId)->update($updateData);

					if (round($hourdiff) == $linkexpire && round($hourdiff) >= $linkexpire) {
						$strMessage = 'Link Expired';
					} else {
						$arrUserWhere = [['id', $userId]];
						$user         = UserModel::where($arrUserWhere)->first();
						if (empty($user)) {
							$strMessage = "User not found";
							$intCode    = Response::HTTP_NOT_FOUND;
						} else {

							$user->password        = encrypt($arrInput['password']);
							$user->bcrypt_password = bcrypt($arrInput['password']);
							$user->save();
							$resetPassword->request_ip_address = $_SERVER['REMOTE_ADDR'];
							$resetPassword->out_time           = $datetime;
							$resetPassword->save();

							/* $arrEmailData = [];
							$arrEmailData['email'] = $user->email;
							$arrEmailData['template'] = 'emails.user-emails.password-reseted';
							$arrEmailData['fullname'] = $user->fullname;
							$arrEmailData['subject'] = "Password reseted";
							//$arrEmailData['reset_token']= $reset_token;
							$arrEmailData['user_id'] = $user->id;
							$mail = sendEmail($arrEmailData); */

							$subject  = "Password reseted";
							$pagename = "emails.user-emails.password-reseted";
							$data     = array('pagename' => $pagename, 'email' => $user->email, 'reset_token' => $arrInput['resettoken']);
							$email    = $user->email;
							$mail     = sendMail($data, $email, $subject);

							$actdata            = array();
							$actdata['id']      = $user->id;
							$actdata['message'] = 'Password reset successfully';
							$actdata['status']  = 1;
							$actDta             = ActivitynotificationModel::create($actdata);

							$intCode    = Response::HTTP_OK;
							$strMessage = "Password reseted successfully";
						}
					}
				}
			}
		} catch (PDOException $e) {
			DB::rollBack();
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		} catch (Exception $e) {

			dd($e);

			$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = "Something went wrong";
		}
		DB::commit();

		$strStatus = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, $strMessage, $this->arrOutputData);
	}

	public function resetUserPassword(Request $request)
	{
		$intCode       = Response::HTTP_NOT_FOUND;
		$strStatus     = Response::$statusTexts[$intCode];
		$strMessage    = "Something went wrong";
		$arrOutputData = [];
		DB::beginTransaction();
		try {
			$arrInput  = $request->all();
			$validator = Validator::make($arrInput, [
//				'user_id' => 'required',
				// 'email' => 'required',
				'password'   => 'required|min:8|max:15',
				'confirm_password' => ['required', 'same:password']
			]);

			$password = $arrInput['password'];

			$lettercase   = preg_match('@[a-zA-Z]@', $password);
			$number       = preg_match('@[0-9]@', $password);
			$specialChars = preg_match('@[^\w]@', $password);

			if (!$lettercase || !$number || !$specialChars || strlen($password) < 8) {

				// $intCode   = Response::HTTP_NOT_FOUND;
				// $strStatus = Response::$statusTexts[$intCode];
				$strMessage = "Pasword contains atleast 8 characters, Password contains atleast 1 letter, contains atleast 1 number and contains atleast 1 special character i.e. ! @ # $ *";
				// return sendresponse($intCode, $strStatus, 'Pasword contains atleast 8 characters, Password contains atleast 1 letter, contains atleast 1 number and contains atleast 1 special character i.e. ! @ # $ *', '');
				return redirect()->back()->withErrors($strMessage);
			}

			//check for validation
			if ($validator->fails()) {
				// return setValidationErrorMessage($validator);
				return redirect()->back()->withErrors($validator)->withInput();
			}

			$arrWhere = [['user_id', $arrInput['user_id']]];
			$user     = UserModel::select('email', 'id', 'user_id', 'mobile')->where($arrWhere)->first();
			if (empty($user)) {
				$strMessage = "Invalid User ID";
				return redirect()->back()->withErrors($strMessage);
			} else {
				// $random                = substr(number_format(time() * rand(), 0, '', ''), 0, '6');
				// $path                  = Config::get('constants.settings.domainpath');
				// $user->password        = encrypt($random);
				// $user->bcrypt_password = bcrypt($random);
				// $user->save();

				$user->password        = encrypt($arrInput['password']);
				$user->bcrypt_password = bcrypt($arrInput['password']);
				$user->save();


                $user_id  = $user->user_id;
                $username = $user->email;
                $subject  = "Your HSCC Account Password has been Changed";
                $pagename = "emails.success_reset_password";
                $data     = array('pagename' => $pagename, 'username' => $username, 'password' => $password, 'user_id' => $user_id,'name'=>$user->fullname);

                $mail      = sendMail($data, $username, $subject);

				$projectname = Config::get('constants.settings.projectname');
				// $message     = "Your password reset succesfull! please use folliowing credentials to login.\nUser Id: ".$request->input('user_id')."\nPassword: ".$random."\nRegards ".$projectname;
				// sendSMS($user->mobile, $message);

				$mail = true;

				if ($mail) {
					$intCode    = Response::HTTP_OK;
					$strMessage = "Your password reset successfully. Please use the following credentials to login.\nUser Id: " . $user->user_id . "\nPassword: " . $request->input('password') . "\nRegards " . $projectname;
				} else {
					$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
					$strMessage = "Something went wrong";
					return redirect()->back()->withErrors($strMessage);
				}
			}
		} catch (PDOException $e) {
		    dd($e);
			DB::rollBack();
			return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
		} catch (Exception $e) {
            dd($e);
            $intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = "Something went wrong";
			return redirect()->back()->withErrors($strMessage);
		}
		// DB::commit();
		if ($intCode == 200) {
			return redirect('/login')->withSuccess($strMessage);
		} else {
			// $strStatus = Response::$statusTexts[$intCode];
			// return sendResponse($intCode, $strStatus, $strMessage, $this->arrOutputData);
			return redirect()->back()->withErrors($strMessage);
		}

		// $strStatus = Response::$statusTexts[$intCode];
		// return sendResponse($intCode, $strStatus, $strMessage, $this->arrOutputData);
	}
}
