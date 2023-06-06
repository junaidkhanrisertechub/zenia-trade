<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Controllers\user\Google2FAController;
use App\Models\FundRequest;
use App\Models\Gallerya;
use App\Models\Invoice;
use App\Models\Otp;
use App\Models\Packages;
use App\Models\ProjectSettings;
use App\Models\Template;
use App\Models\Questions;
use App\Models\Resetpassword;
use App\Models\Topup;
use App\Models\WithdrawMode;
use App\User;
use Auth;
use Config;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;

class PackageController extends Controller {

	public function __construct(Google2FAController $google2facontroller) {
		$this->statuscode = Config::get('constants.statuscode');
		$this->google2facontroller = $google2facontroller;
	}

	public function sendotponmail($users, $username, $type = null) {

		$checotpstatus = Otp::select('entry_time','out_time')->where([['id', '=', $users->id]])->orderBy('entry_time', 'desc')->first();

		/* if (!empty($checotpstatus)) {
			$entry_time = $checotpstatus->entry_time;
			$out_time = $checotpstatus->out_time;
			$checkmin = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($entry_time)));
			// $current_time = date('Y-m-d H:i:s');
			// dd($current_time);
			// $expireotp = date('Y-m-d H:i:s', strtotime('+5 minutes', strtotime($current_time)));
			// $today = \Carbon\Carbon::now();
			// dd($expireotp);

			$current_time = \Carbon\Carbon::now();
            $expireotp = $current_time->toDateTimeString();
             
            
			// dd($current_time,$expireotp1);
		} */
		$otpExpireMit=Config::get('constants.settings.otpExpireMit');

       $mytime_new = \Carbon\Carbon::now();
       $expire_time = \Carbon\Carbon::now()->addMinutes($otpExpireMit)->toDateTimeString();
       $current_time_new = $mytime_new->toDateTimeString();
		/* if(!empty($checotpstatus) && $entry_time!='' && strtotime($checkmin)>=strtotime($current_time) && $checotpstatus->otp_status!='1'){
			          $updateData=array();
			          $updateData['otp_status']=0;
 
			          $updateOtpSta=Otp::where('id', $users->id)->update($updateData);

			          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'OTP already sent to your mail id', $this->emptyArray);

		*/
		if ($type == 1) { 

			$query=DB::table('tbl_templates')
			->select('title','subject','content')
			->where('title','ForgotPassword Mail')
			->get();

			$temp_data = Template::where('title', '=', 'Otp')->first();
			// dd($temp_data);
			$project_set_data = ProjectSettings::select('icon_image','domain_name')->first();
			$pagename = "emails.forgotpassmail";
			$subject = $temp_data->subject;
			$content = $temp_data->content;
			$domain_name = $project_set_data->domain_name;
			$random = rand(100000, 999999);
			$data = array('pagename' => $pagename, 'otp' => $random, 'username' => $users->user_id,'content'=>$content,'domain_name' =>$domain_name);
		} else {
			$temp_data = Template::where('title', '=', 'Otp')->first();
			$project_set_data = ProjectSettings::select('icon_image','domain_name')->first();
			$pagename = "emails.otpsend";
			$subject = $temp_data->subject;
			$content = $temp_data->content;
			$domain_name = $project_set_data->domain_name;
			// $subject = "OTP sent successfully";
			$random = rand(100000, 999999);
			$data = array('pagename' => $pagename, 'otp' => $random, 'username' => $users->user_id,'content'=>$content,'domain_name' =>$domain_name);
			
		}

		$mail = sendMail($data, $username, $subject);
		//$expireotp1 = date('Y-m-d H:i:s', strtotime($expireotp. ' +5 minutes'));
		$insertotp = array();
		$insertotp['id'] = $users->id;
		$insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
		$insertotp['otp'] = md5($random);
		//$insertotp['otp'] = hash('sha256',$random);
		$insertotp['otp_status'] = 0;
		$insertotp['type'] = 'email';
		$insertotp['otpexpire'] = $expire_time;
		// dd($insertotp);
		$sendotp = Otp::create($insertotp);
		// dd($sendotp);
		$arrData = array();
		// $arrData['id']   = $users->id;
		$arrData['remember_token'] = $users->remember_token;

		$arrData['mailverification'] = 'TRUE';
		$arrData['google2faauth'] = 'FALSE';
		$arrData['mailotp'] = 'TRUE';
		$arrData['mobileverification'] = 'TRUE';
		$arrData['otpmode'] = 'FALSE';
		//$mask_mobile = maskmobilenumber($users->mobile);
		//$mask_email = maskEmail($users->email);
		//$arrData['email'] = $mask_email;
		//$arrData['mobile'] = $mask_mobile;

		if ($type == null) {
			return $random;
		}

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', $random);

		return $sendotp;

		//}  // end of users
	}

	public function getRoiPer() {
		$packages = Packages::select('roi', 'package_type')->where([['status', '=', 'Active'], ['user_show_status', '=', 'Active']])->orderBy('id', 'asc')->groupBy('roi')->get();
		if (count($packages) > 0) {
			$arrStatus = Response::HTTP_OK;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Roi Percentages found successfully';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $packages);

		} else {
			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data not found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Get all packages
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getpackage(Request $request) {
		try {

			$extra = ProjectSettings::where('status', '=', 1)->pluck('extra')->first();

			$USDtoINR = ProjectSettings::where('status', '=', 1)->pluck('USD-to-INR')->first();

			//$CheckFirstTopupExist=Topup::where('id','=',$users->id)->first();
			$CheckFirstTopupExist = Topup::where('id', '=', Auth::user()->id)->orderBy('srno', 'desc')->max('amount');
			
			$packages = Packages::where([['status', '=', 'Active'], ['user_show_status', '=', 'Active']])->orderBy('id', 'asc')->get();
			/*if (!empty($CheckFirstTopupExist)) {
	            $packages = Packages::where('cost', '>=', $CheckFirstTopupExist)->orderBy('cost', 'asc')->get();
	            if (!empty($extra) && count($packages) > 0) {

	                foreach ($packages as $pa) {
	                    $pa->extra = $extra;
	                }
	            } else {
	                foreach ($packages as $pa) {
	                    $pa->extra = 0;
	                }
	            }
	        } else {
	            $packages = Packages::orderBy('id', 'asc')->get();
	            foreach ($packages as $pa) {
	                $pa->extra = 0;
	        }
			*/

			$id = Auth::user()->id;
			$country = Auth::user()->country;
			$countrycode = getCountryCode($country);

			$checkTopup = Topup::where('id', $id)->select('id')->first();
			if (!empty($checkTopup)) {

				$mode1 = WithdrawMode::where('id', $id)->select('network_type')->orderBy('id', 'desc')->first();

				if (empty($mode1)) {
					$mode = Invoice::where('id', $id)->select('payment_mode')->where('in_status', 1)->orderBy('id', 'asc')->first();

					if (!empty($mode)) {
						$type = $mode->payment_mode;
					} else {
						$mode2 = FundRequest::where('user_id', $id)->select('user_id')->where('status', 'Approve')->orderBy('id', 'asc')->first();

						if (!empty($mode2)) {
							//$type = 'INR';
							//$mode = '6';
							$type = "BTC";
						} else {
							$type = "BTC";
						}
					}
				} else {
					$mode = $mode1;
					$type = $mode->network_type;
				}
			} else {
				$type = "BOTH";
			}

			$packages[0]['convert'] = $USDtoINR;
			$packages[0]['type'] = $type;
			$packages[0]['countryCode'] = $countrycode;

			return $packages;
			// if (!empty($packages) && count($packages) > 0) {
			// 	$arrStatus = Response::HTTP_OK;
			// 	$arrCode = Response::$statusTexts[$arrStatus];
			// 	$arrMessage = 'Packages found successfully';
			// 	return sendResponse($arrStatus, $arrCode, $arrMessage, $packages);

			// } else {
			// 	$arrStatus = Response::HTTP_NOT_FOUND;
			// 	$arrCode = Response::$statusTexts[$arrStatus];
			// 	$arrMessage = 'Invalid user';
			// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			// }

		} catch (Exception $e) {

			// $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			// $arrCode = Response::$statusTexts[$arrStatus];
			// $arrMessage = 'Something went wrong,Please try again';
			//return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}


	/**
	 * Get all packages
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getpackagebyid(Request $request) {
		try {

			$extra = ProjectSettings::where('status', '=', 1)->pluck('extra')->first();

			$USDtoINR = ProjectSettings::where('status', '=', 1)->pluck('USD-to-INR')->first();

			//$CheckFirstTopupExist=Topup::where('id','=',$users->id)->first();
			$CheckFirstTopupExist = Topup::where('id', '=', Auth::user()->id)->orderBy('srno', 'desc')->max('amount');
			
			$packages = Packages::where([['status', '=', 'Active'], ['user_show_status', '=', 'Active'],['id', '=', $request->package_id]])->orderBy('id', 'asc')->get();
			/*if (!empty($CheckFirstTopupExist)) {
	            $packages = Packages::where('cost', '>=', $CheckFirstTopupExist)->orderBy('cost', 'asc')->get();
	            if (!empty($extra) && count($packages) > 0) {

	                foreach ($packages as $pa) {
	                    $pa->extra = $extra;
	                }
	            } else {
	                foreach ($packages as $pa) {
	                    $pa->extra = 0;
	                }
	            }
	        } else {
	            $packages = Packages::orderBy('id', 'asc')->get();
	            foreach ($packages as $pa) {
	                $pa->extra = 0;
	        }
			*/

			$id = Auth::user()->id;
			$country = Auth::user()->country;
			$countrycode = getCountryCode($country);

			$checkTopup = Topup::where('id', $id)->select('id')->first();
			if (!empty($checkTopup)) {

				$mode1 = WithdrawMode::where('id', $id)->select('network_type')->orderBy('id', 'desc')->first();

				if (empty($mode1)) {
					$mode = Invoice::where('id', $id)->select('payment_mode')->where('in_status', 1)->orderBy('id', 'asc')->first();

					if (!empty($mode)) {
						$type = $mode->payment_mode;
					} else {
						$mode2 = FundRequest::where('user_id', $id)->select('user_id')->where('status', 'Approve')->orderBy('id', 'asc')->first();

						if (!empty($mode2)) {
							//$type = 'INR';
							//$mode = '6';
							$type = "BTC";
						} else {
							$type = "BTC";
						}
					}
				} else {
					$mode = $mode1;
					$type = $mode->network_type;
				}
			} else {
				$type = "BOTH";
			}

			$packages[0]['convert'] = $USDtoINR;
			$packages[0]['type'] = $type;
			$packages[0]['countryCode'] = $countrycode;

			if (!empty($packages) && count($packages) > 0) {
				$arrStatus = Response::HTTP_OK;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Packages found successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $packages);

			} else {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

		} catch (Exception $e) {

			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function getpackage1(Request $request) {
		try {

			$extra = ProjectSettings::where('status', '=', 1)->pluck('extra')->first();

			$USDtoINR = ProjectSettings::where('status', '=', 1)->pluck('USD-to-INR')->first();

			//$CheckFirstTopupExist=Topup::where('id','=',$users->id)->first();
			$CheckFirstTopupExist = Topup::where('id', '=', Auth::user()->id)->orderBy('srno', 'desc')->max('amount');
			$packages = DB::table('tbl_product1')->where([['status', '=', 'Active'], ['user_show_status', '=', 'Active']])->orderBy('id', 'asc')->get();

			return $packages;
			// if (!empty($packages) && count($packages) > 0) {
			// 	$arrStatus = Response::HTTP_OK;
			// 	$arrCode = Response::$statusTexts[$arrStatus];
			// 	$arrMessage = 'Packages found successfully';
			// 	return sendResponse($arrStatus, $arrCode, $arrMessage, $packages);

			// } else {
			// 	$arrStatus = Response::HTTP_NOT_FOUND;
			// 	$arrCode = Response::$statusTexts[$arrStatus];
			// 	$arrMessage = 'Invalid user';
			// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			// }

		} catch (Exception $e) {
			// dd($e);
			// $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			// $arrCode = Response::$statusTexts[$arrStatus];
			// $arrMessage = 'Something went wrong,Please try again';
			// return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function index(Request $request){
	
	$packagelists = $this->getpackage($request);
	return view('user.PackagesPlan')->with(compact('packagelists'));
	}
	public function getPackageFront(Request $request) {
		try {

			$extra = ProjectSettings::where('status', '=', 1)->pluck('extra')->first();
			$USD = ProjectSettings::where('status', '=', 1)->pluck('USD-to-INR')->first();
			//$CheckFirstTopupExist=Topup::where('id','=',$users->id)->first();
			/* $CheckFirstTopupExist = Topup::where('id', '=', Auth::user()->id)->orderBy('srno', 'desc')->max('amount');*/
			$packages = Packages::where([['status', '=', 'Active'], ['user_show_status', '=', 'Active']])->orderBy('id', 'asc')->get();

			$html = '';
			if (!empty($packages) && count($packages) > 0) {

				foreach ($packages as $pkey => $pvalue) {
					$html .= "<option value='" . $pvalue['roi'] . "' data-package='" . $pvalue['name'] . "' data-duration='" . $pvalue['duration'] . "'>" . $pvalue['roi'] . " % Daily Return With $" . /*$pvalue['name']*/$pvalue['min_hash'] . " - $" . $pvalue['max_hash'] . "</option>";
				}

				/*echo "<pre>"; print_r($html); exit();*/
				return $html;
			} else {
				return NULL;
			}

		} catch (Exception $e) {

			return NULL;
		}
	}

	public function getPackageFrontRupee(Request $request) {
		try {

			$extra = ProjectSettings::where('status', '=', 1)->pluck('extra')->first();
			$USD = ProjectSettings::where('status', '=', 1)->pluck('USD-to-INR')->first();
			//$CheckFirstTopupExist=Topup::where('id','=',$users->id)->first();
			/* $CheckFirstTopupExist = Topup::where('id', '=', Auth::user()->id)->orderBy('srno', 'desc')->max('amount');*/
			$packages = Packages::where([['status', '=', 'Active'], ['user_show_status', '=', 'Active']])->orderBy('id', 'asc')->get();

			$html = '';
			if (!empty($packages) && count($packages) > 0) {

				foreach ($packages as $pkey => $pvalue) {
					$html .= "<option value='" . $pvalue['roi'] . "' data-package='" . $pvalue['name_rupee'] . "' data-duration='" . $pvalue['duration'] . "'>" . $pvalue['roi'] . " % Daily Return With " . /*$pvalue['name']*/"Rs." . ($pvalue['min_hash'] * $USD) . "- " . "Rs." . ($pvalue['max_hash'] * $USD) . "" . "</option>";
				}

				/*echo "<pre>"; print_r($html); exit();*/
				return $html;
			} else {
				return NULL;
			}

		} catch (Exception $e) {

			return NULL;
		}
	}

	public function getImageFront(Request $request) {
		try {

			$url = url('uploads/gallery');
			/* $query  = Gallerya::selectRaw('*,(CASE WHEN attachment IS NOT NULL THEN CONCAT("'.$url.'","/",attachment) ELSE "" END) as attachment')->where('gid',$request->gid);*/

			$query = Gallerya::join('tbl_gallery as tg', 'tg.id', '=', 'tbl_gallerya.gid')
				->select('tg.name', DB::raw('CASE WHEN tbl_gallerya.attachment IS NOT NULL THEN CONCAT("' . $url . '","/",tbl_gallerya.attachment) ELSE "" END as attachment'));
			$query = $query->get();

			//echo "<pre>"; print_r($query); exit();
			/*     $html = "";
	              foreach($query as $key => $value)
	              {
	                $html .= '<div class="col-lg-3 col-md-4 col-sm-6" data-toggle="modal" data-target="#modal">';
	                $html .= '<a href="#lightbox" data-slide-to="1">';
	                $html .= '<img src='.$value['attachment'].' class="img-thumbnail pb-0">';
	                 $html .= '<p class="bg-dark text-white text-center">';
	                 $html .= '.'$value['name']'.';
	                 $html .= "</p></a></div>";
*/

			if (!empty($query) && count($query) > 0) {

				/*echo "<pre>"; print_r($html); exit();*/
				return $query;
			} else {
				return NULL;
			}

		} catch (Exception $e) {
			dd($e);
			return NULL;
		}
	}

	public function getQuestions() {
		$ques = Questions::all();

		if (!empty($ques)) {
			$arrStatus = Response::HTTP_OK;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Questions found successfully';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $ques);

		} else {
			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Questions not found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

	}

	public function generateRandomNo() {
		$rand = mt_rand(100000, 999999);
		$arrStatus = Response::HTTP_OK;
		$arrCode = Response::$statusTexts[$arrStatus];
		$arrMessage = 'Questions found successfully';
		return sendResponse($arrStatus, $arrCode, $arrMessage, $rand);

	}

	public function sendotpEditUserProfile(Request $request) {
		$user = User::select('remember_token','email','id','user_id','fullname','mobile')->where('user_id', $request->user_id)->first();
		//$username = $user->fullname;
		$mail = $user->email;
		//$mobileResponse = $this->sendotponmobile($user,$username);
		$emailResponse = $this->sendotponmail($user, $mail, $type = 1);

		// $whatsappMsg = "Your OTP is -: " . $emailResponse ;

		// $countrycode = getCountryCode($user->country);

		// $mobile = $user->mobile;

		//sendSMS($mobile, $whatsappMsg);
		//sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
	}
// 	public function SendOtpForForgotPassword(Request $request) {
// 		// dd($request->all());
// 		$user = Auth::User();
// 		// $user = User::select('remember_token','email','id','user_id','fullname','mobile')->where('user_id', $request->user_id)->first();
// 		//$username = $user->fullname;
// 		if(!empty($user))
// 		{
// 			// $mail = $user->email;
// 			//$mobileResponse = $this->sendotponmobile($user,$username);
// 			// $emailResponse = $this->sendotponmail($user, $mail, $type = 1);
// 			// $emailResponse = $this->SendOtpForAll($user);
// 			// $result=SendOtpForAll($user);

// 			// $whatsappMsg = "Your OTP is -: " . $emailResponse ;

// 			// $countrycode = getCountryCode($user->country);

// 			// $mobile = $user->mobile;

// 			//sendSMS($mobile, $whatsappMsg);
// 			//sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

// 			// return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
// 		}
// 		else 
// 		{
// 			$arrStatus = Response::HTTP_NOT_FOUND;
// 			$arrCode = Response::$statusTexts[$arrStatus];
// 			$arrMessage = 'Invalid User Id';
// 			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
// 		}
// }
public function SendOtpForForgotPassword(Request $request) {
    //  dd($request->all());
    // $user = Auth::User();
    $user = User::select('remember_token','email','id','user_id','fullname','mobile')->where('user_id', $request->user_id)->first();
    
    //$username = $user->fullname;
    if(!empty($user))
    {
		// $user = Auth::User();

		$result=SendOtpForAll($user);

		if($result)
		{
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
		}
        // $user = Auth::User();

            // $result=SendOtpForAll($user);

            // if($result)
            // {
            //     return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
            // }

        // $mail = $user->email;
        //$mobileResponse = $this->sendotponmobile($user,$username);
    	//  $emailResponse = $this->sendotponmail($user, $mail, $type = 1);
        // $emailResponse = $this->SendOtpForAll($user);
        // $result=SendOtpForAll($user);

        // $whatsappMsg = "Your OTP is -: " . $emailResponse ;

        // $countrycode = getCountryCode($user->country);

        // $mobile = $user->mobile;

        //sendSMS($mobile, $whatsappMsg);
        //sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

    // return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
    }
    else 
    {
        $arrStatus = Response::HTTP_NOT_FOUND;
        $arrCode = Response::$statusTexts[$arrStatus];
        $arrMessage = 'Invalid User Id';
        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
    }
}
	public function sendotp2faUser(Request $request) {

	    $user = User::select('remember_token','email','id','user_id','fullname','mobile')->where('user_id', $request->user_id)->first();
	    
	    if(!empty($user))
	    {

			$mail = $user->email;
			$emailResponse = $this->sendotponmail($user, $mail);

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email ', '');
	    }else {
	        $arrStatus = Response::HTTP_NOT_FOUND;
	        $arrCode = Response::$statusTexts[$arrStatus];
	        $arrMessage = 'Invalid User Id';
	        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
	    }
	}

	public function reset2faUser(Request $request){
		try {
			$getuser = User::where('user_id', $request->user_id)->first();
			$otpdata = Otp::where('id', $getuser->id)->where('otp', md5($request->otp))->orderBy('entry_time', 'desc','otpexpire')->first();
			$today = \Carbon\Carbon::now();
			if (!empty($otpdata)) {
				if ($otpdata->otp_status == 0) {
					if($today > $otpdata->otpexpire){
						$arrStatus = Response::HTTP_NOT_FOUND;
						$arrCode = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Otp Expire';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

				} else {
					$arrStatus = Response::HTTP_NOT_FOUND;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Otp Already Used';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} 
			else {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Incorrect Otp';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			$updateUser=User::where('user_id',$request->user_id)->update(['google2fa_secret'=>NULL,'google2fa_status'=>'disable']);
			$arrStatus = Response::HTTP_OK;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Your G2FA reset successfully';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			
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
		//dd($request);
		try {
			$getuser = User::where('user_id', $request->user_id)->first();

			$otpdata = Otp::where('id', $getuser->id)->where('otp', md5($request->otp))->orderBy('entry_time', 'desc','otpexpire')->first();
			$today = \Carbon\Carbon::now();
			//dd($getuser->id);
			// dd(md5($request->otp));
			if($getuser->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$getuser->id;
				$arrIn['otp']=$request->otp_2fa;
				$arrIn['google2fa_secret'] = $getuser->google2fa_secret;
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
					$intCode = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}
			}else{
				
				if (!empty($otpdata)) {
					if ($otpdata->otp_status == 0) {
						if($today > $otpdata->otpexpire){
							$arrStatus = Response::HTTP_NOT_FOUND;
							$arrCode = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Otp Expire';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}
						
					} else {
						$arrStatus = Response::HTTP_NOT_FOUND;
						$arrCode = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Otp Already Used';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				} 
				else {
					$arrStatus = Response::HTTP_NOT_FOUND;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Incorrect Otp';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			}
			$resetpassword['reset_password_token'] = md5(uniqid(rand(), true));
			$resetpassword = array();
			$resetpassword['reset_password_token'] = md5(uniqid(rand(), true));
			$resetpassword['id'] = $getuser->id;
			$resetpassword['request_ip_address'] = $request->ip();

			$insertresetDta = Resetpassword::create($resetpassword);

			$path = Config::get('constants.settings.domainpath-vue');

			$domain = $path . 'reset-password?resettoken=' . $resetpassword['reset_password_token'];

			$pagename = "emails.reset_password";
			$username = $getuser->user_id;
			$subject='Reset Your Password for your HSCC Account '.$username;
			$data = array('pagename' => $pagename, 'username' => $username,'name'=>$getuser->fullname,'path'=>$domain);
			$mail = sendMail($data, $getuser->email, $subject);
			if ($mail) {
				$arrStatus = Response::HTTP_OK;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'A reset-password link has been sent to your email.';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $domain);
			} else {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Failed to send email for reset-password';
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
}
