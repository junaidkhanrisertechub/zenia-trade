<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\IcoPhasesController;
use App\Http\Controllers\userapi\SettingsController;
use App\Http\Controllers\userapi\Google2FAController;
use App\Models\Activitynotification;
use App\Models\AllTransaction;
use App\Models\Dashboard;
use App\Models\FundRequest;
use App\Models\Invoice;
use App\Models\Packages;
use App\Models\ProjectSettings;
use App\Models\TodayDetails;
use App\Models\Topup;
use App\Models\TopupRequest;
use App\Models\UserStructureModel;
use App\Models\WalletTransactionLog;
use App\Models\UserSettingFund;
use App\Traits\Income;
use App\Models\WhiteListIpAddress;
use App\Models\verifyOtpStatus;

use App\Traits\Users;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class TopupController extends Controller {

	use Income;
	use Users;
	public function __construct(SettingsController $projectsetting, IcoPhasesController $IcoPhases, Google2FAController $google2facontroller) {

		$this->statuscode      = Config::get('constants.statuscode');
		$this->projectsettings = $projectsetting;
		$this->IcoPhases       = $IcoPhases;
		$this->emptyArray      = (object) array();
		$date                  = \Carbon\Carbon::now();
		$this->today           = $date->toDateTimeString();
		$this->google2facontroller = $google2facontroller;
	}

	/**
	 * Lending function
	 * Make topup
	 * @return \Illuminate\Http\Response
	 */
	public function selectpackages($invoice_id, $price_in_usd, $userid) {

		try {

			$checkPinExist = Topup::where([['pin', '=', $invoice_id]])->first();

			// check invoice already exist
			if (empty($checkPinExist)) {
				$invoice = DB::table('tbl_invoices as inv_pay')
					->select('p.id', 'p.roi', 'p.duration', 'inv_pay.price_in_usd as price_in_usd', 'inv_pay.invoice_id', 'direct_income')
					->leftjoin('tbl_product as p', function ($join) {
						$join->on('p.id', '=', 'inv_pay.plan_id');
					})
					->where('inv_pay.invoice_id', $invoice_id)	->get();
				//-set level plan and binary plan status
				$plan = ProjectSettings::selectRaw('(CASE level_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as level_plan,(CASE binary_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as binary_plan ,(CASE direct_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END ) as direct_plan,(CASE leadership_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as leadership_plan ')->first();

				if (!empty($plan)) {
					$level_plan      = $plan->level_plan;
					$binary_plan     = $plan->binary_plan;
					$direct_plan     = $plan->direct_plan;
					$leadership_plan = $plan->leadership_plan;
					$usersid         = $userid;
					$ProductRoi      = $invoice[0]->roi;
					$ProductDuration = $invoice[0]->duration;
					$pacakgeId       = $invoice[0]->id;
					$direct_income   = $invoice[0]->direct_income;
					// insert into toup
					$Topupdata                       = array();
					$Topupdata['id']                 = $usersid;
					$Topupdata['pin']                = $invoice_id;
					$Topupdata['amount']             = $invoice[0]->price_in_usd;
					$Topupdata['percentage']         = ($ProductRoi*$ProductDuration);
					$Topupdata['type']               = $pacakgeId;
					$Topupdata['payment_type']       = "BTC";
					$Topupdata['top_up_by']          = $usersid;
					$Topupdata['usd_rate']           = 0;
					$Topupdata['roi_status']         = 'Active';
					$Topupdata['top_up_type']        = '0';
					$Topupdata['binary_pass_status'] = $binary_plan;
					$Topupdata['level_pass_status']  = $level_plan;
					$Topupdata['direct_pass_status'] = $direct_plan;
					$Topupdata['entry_time']         = $this->today;

					$insertTopupDta = Topup::create($Topupdata);
					//--update invoice topup status
					$updateCInvoiceData                  = array();
					$updateCInvoiceData['top_up_status'] = 1;
					$updateCInvoiceData['top_up_date']   = $this->today;
					$updateCInvoiceData                  = Invoice::where('invoice_id', $invoice_id)->update($updateCInvoiceData);
					// update dahboatd value----------
					$total_investment                    = Dashboard::where([['id', '=', $usersid]])->pluck('total_investment')->first();
					$active_investment                   = Dashboard::where([['id', '=', $usersid]])->pluck('active_investment')->first();
					$updateCoinData['total_investment']  = custom_round(($total_investment+$invoice[0]->price_in_usd), 7);
					$updateCoinData['active_investment'] = custom_round(($active_investment+$invoice[0]->price_in_usd), 7);
					$updateCoinData                      = Dashboard::where('id', $usersid)->limit(1)->update($updateCoinData);

					if ($level_plan == '1') {
						$getlevel = $this->pay_level($usersid, $invoice[0]->price_in_usd, $pacakgeId);
						//$getlevel = $this->uplineIncome($usersid, $invoice[0]->price_in_usd, $pacakgeId);
					}

					if ($binary_plan == '1') {
						$getlevel = $this->pay_binary($usersid, $invoice[0]->price_in_usd);
					}

					if ($direct_plan == '1' && $direct_income > 0) {
						$getlevel = $this->pay_direct($usersid, $invoice[0]->price_in_usd, $direct_income, $invoice_id);
					}

					if ($leadership_plan == '1') {
						$getlevel = $this->pay_leadership($usersid, $invoice[0]->price_in_usd, $pacakgeId);
					}

					$actdata               = array();// insert in transaction
					$actdata['id']         = $usersid;
					$actdata['message']    = 'Paid for lending wallet id : '.$invoice_id.' and amount :'.$invoice[0]->price_in_usd;
					$actdata['status']     = 1;
					$actdata['entry_time'] = $this->today;
					$actDta                = Activitynotification::create($actdata);

					$userDetails = User::select('country', 'mobile')->where('id', $usersid)->first();

					$domain = Config::get('constants.domainpath');

					$whatsappMsg = "Congratulations,\nDeposit of $ ".$invoice[0]->price_in_usd."  Order Id: ".$invoice_id." has been confirmed successfully \n".$domain."";

					$countrycode = getCountryCode($userDetails->country);
					$mobile      = $userDetails->mobile;
					//  sendWhatsappMsg($countrycode,$mobile,$whatsappMsg);
					sendSMS($mobile, $whatsappMsg);

				}
			}
		} catch (Exception $e) {

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Self topup
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function createstructure(Request $request) {
		/*$intCode = Response::HTTP_NOT_FOUND;
		$strStatus = Response::$statusTexts[$intCode];
		$strMessage = 'Creating Structure are stopped till 12th September';
		return sendResponse($intCode, $strStatus, $strMessage, array());*/

		$user_id = Auth::user()->id;
		/*dd($user_id);*/
		$rules = array(
			'user_id'     => 'required',
			'no_stucture' => 'required|max:1100',
			'email'       => 'required',
			'mobile'      => 'required',
			'password'    => 'required',
			'fullname'    => 'required',

			'transcation_type' => 'required',
			/*'franchise_user_id' => 'required',
		'masterfranchise_user_id' => 'required',*/
		);
		$validator = checkvalidation($request->all(), $rules, '');
		if (!empty($validator)) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
		}
		if ($request->transcation_type != 1 && $request->transaction_type != 2) {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
		}
		if ($request->topup_amount <= 0 || $request->amount <= 0) {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Amount must be greater than 0', $this->emptyArray);
		}
		$Productcost     = $request->amount;
		$purchase_deduct = $fund_deduct = 0;
		if ($request->transcation_type == 2) {

			$topupbalance = Dashboard::where('id', $user_id)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as balance')->pluck('balance')->first();

			if ($Productcost > $topupbalance) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Insufficient Fund Wallet Balance';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} elseif ($request->transcation_type == 1) {
			$balance = Dashboard::where('id', $user_id)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as fundbalance,round(top_up_wallet - top_up_wallet_withdraw,2) as purchasebalance')->first();

			$half_of_requested_amount = $Productcost/2;

			if ($balance->purchasebalance < 1) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Insufficient Purchase Wallet Balance';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} elseif ($balance->purchasebalance >= $half_of_requested_amount) {
				$purchase_deduct = $half_of_requested_amount;
			} else {
				$purchase_deduct = $balance->purchasebalance;
			}
			$half_of_requested_amount = $Productcost-$purchase_deduct;
			if ($half_of_requested_amount > $balance->fundbalance) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Insufficient Fund wallet Balance';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				$fund_deduct = $half_of_requested_amount;
			}

		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
		}
		$insertdata          = new UserStructureModel;
		$insertdata->user_id = Auth::user()->id;
		;
		$insertdata->no_structure = $request->no_stucture;
		$insertdata->password     = encrypt($request->password);
		//$insertdata->temp_pass = $request->password;
		$insertdata->bcrypt_password  = bcrypt($request->password_confirmation);
		$insertdata->amount_topup     = $request->topup_amount;
		$insertdata->email            = $request->email;
		$insertdata->fullname         = $request->fullname;
		$insertdata->mobile           = $request->mobile;
		$insertdata->entry_time       = $this->today;
		$insertdata->status           = 0;
		$insertdata->transaction_type = $request->transcation_type;
		$insertdata->save();

		$updateCoinData = array();
		$logArr         = $fundArr         = $purchaseArr         = array();
		if ($request->transcation_type == 2) {
			$updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$Productcost);
			$fundArr['from_user_id']                = $user_id;
			$fundArr['to_user_id']                  = 0;
			$fundArr['amount']                      = $Productcost;
			$fundArr['wallet_type']                 = 3;
			$fundArr['remark']                      = "Structure creation - new fund wallet";
			$fundArr['entry_time']                  = $this->today;
		} elseif ($request->transcation_type == 1) {
			$updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_deduct);
			$fundArr['from_user_id']                = $user_id;
			$fundArr['to_user_id']                  = 0;
			$fundArr['amount']                      = $fund_deduct;
			$fundArr['wallet_type']                 = 3;
			$fundArr['remark']                      = "Structure creation - New Fund Wallet";
			$fundArr['entry_time']                  = $this->today;

			$updateCoinData['top_up_wallet_withdraw'] = DB::raw('top_up_wallet_withdraw + '.$purchase_deduct);
			$purchaseArr['from_user_id']              = $user_id;
			$purchaseArr['to_user_id']                = 0;
			$purchaseArr['amount']                    = $purchase_deduct;
			$purchaseArr['wallet_type']               = 2;
			$purchaseArr['remark']                    = "Structure creation - Purchase wallet (max 50%)";
			$purchaseArr['entry_time']                = $this->today;
		}
		(count($fundArr) > 0)?array_push($logArr, $fundArr):'';
		(count($purchaseArr) > 0)?array_push($logArr, $purchaseArr):'';
		WalletTransactionLog::insert($logArr);
		$updateCoinData['total_withdraw'] = DB::raw('round(total_withdraw + '.$Productcost.',2)');

		$updateCoinData = Dashboard::where('id', $user_id)->update($updateCoinData);
		if (!empty($insertdata)) {
			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Structure Created successfully';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		} else {
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

	}
	public function registerandTopup(Request $request) {

		try {

			$arrValidation = User::registrationValidationRules();
			$validator     = checkvalidation($request->all(), $arrValidation['arrRules'], $arrValidation['arrMessage']);

			if (!empty($validator)) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			//---end to check wether give address is valid or not---//
			a:$radUserId        = substr(number_format(time()*rand(), 0, '', ''), 0, '10');
			$checkifrandnoExist = User::where('unique_user_id', $radUserId)->first();
			if (!empty($checkifrandnoExist)) {
				gotoa;
			}

			// $request->request->add(['user_id' => $radUserId ]);
			// $request->request->add(['user_id' => $request->Input('email')]);

			$getuser = $this->checkSpecificUserData(['user_id' => $request->Input('user_id'), '	status' => 'Active']);

			/*$getuser=User::where('status','Active')->where('user_id',$request->Input('user_id'))->first();*/

			if (empty($getuser)) {

				//   if ($request->input('password') == $request->input('password_confirmation')) {

				$refUserExist = User::where([['user_id', '=', $request->Input('ref_user_id')], ['status', '=', 'Active']])->first();

				if (!empty($refUserExist)) {
					$registation_plan = ProjectSettings::where([['status', '=', 1]])->pluck('registation_plan')->first();
					// if binary plan is on t
					//echo $registation_plan;
					exit();
					//dd($registation_plan);

					if ($registation_plan == 'binary' && $request->Input('position') != 0) {
						return $this->binaryPlanRegistration($request);
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
	public function gecurrentuser(Request $request) {
		$user_id = (Auth::user()->user_id);

		$arrStatus  = Response::HTTP_OK;
		$arrCode    = Response::$statusTexts[$arrStatus];
		$arrMessage = 'User successfully Find';
		return sendResponse($arrStatus, $arrCode, $arrMessage, $user_id);
	}

	public function selfTopup(Request $request) {

		/*$intCode = Response::HTTP_NOT_FOUND;
		$strStatus = Response::$statusTexts[$intCode];
		$strMessage = 'Topup are stopped till 12th September';
		return sendResponse($intCode, $strStatus, $strMessage, array());*/

		// $intCode = Response::HTTP_NOT_FOUND;
		// $strStatus = Response::$statusTexts[$intCode];
		// $strMessage = 'Your gateway to a strong financial future is going to open soon!';
		// return sendResponse($intCode, $strStatus, $strMessage, array());

		// dd("stop");

		try {
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
				return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]
					['status'], 'Invalid User Token!!!', '');
			}

			/*$auth_user = User::select('tbl_dashboard.top_up_wallet','tbl_dashboard.top_up_wallet_withdraw','tbl_dashboard.total_withdraw','tbl_users.id')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();*/
			$users = User::select('tbl_users.id','tbl_users.email','tbl_users.topup_status', 'tbl_users.user_id', 'tbl_users.virtual_parent_id', 'tbl_users.ref_user_id', 'tbl_dashboard.total_investment', 'tbl_dashboard.active_investment', 'tbl_users.position')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.user_id', '=', $request->user_id], ['tbl_users.status', '=', 'Active']])->first();
			if (empty($users)) {

		  		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This User ID is Blocked', $this->emptyArray);
			}

			$check_record = whiteListIpAddress($type=1,Auth::user()->id);
			// dd($check_record);
			$ip_Address = getIpAddrss();
			$check_user_hits = WhiteListIpAddress::select('id', 'topup_status', 'topup_expire')->where([['uid',Auth::user()->id],['ip_add',$ip_Address]])->first();
			// dd($check_user_hits);
			if(!empty($check_user_hits)){
				if($check_user_hits->topup_status == 1){
					$today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
					if($check_user_hits->topup_expire >= $today){
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Due to too many request hits, temporary you are block for 1 hour!', $this->emptyArray);
					}
				}
			}
			
			
			// if ($request->transcation_type != 2 && $request->transaction_type != 1) {

			// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
			// }

			// dd($request->transcation_type);
			// if ($checktopup > 0) {
			// 	 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one topup allowed for one user', '');
			// }
			$checktopup = Topup::where([['id', $users->id]])->count();

			/* to check user downline or not */
            $id = $users->id;
			$users_id = Auth::User()->id;
			$userId = $to_user_id = $users_id;
			$user_name = Auth::User()->user_id;
			if($to_user_id != $id){
				$todaydetailsexist = TodayDetails::where('to_user_id', $to_user_id)->where('from_user_id',$id)->get();
				if(count($todaydetailsexist) == 0){
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not Downline User','');
				}
			}
			$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
			/*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Topup amount should be greater or equal to last topup amount.';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}*/
			$projectSettings = ProjectSettings::where('status', 1)
				->select('topup_status', 'topup_msg')	->first();
			if ($projectSettings->topup_status == "off") {
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $projectSettings->topup_msg;
				;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			/* to check user downline or not */
			$profile_status = verifyOtpStatus::select('topup_update_status')
            ->where('statusID','=',1)->get();
				$arrInput            = $request->all();
			$userData=User::where('id',$users_id)->first();
			if ($profile_status[0]->topup_update_status == 1) 
			{
				if ($userData->google2fa_status=='disable') {

					$arrInput['user_id'] = Auth::user()->id;
					$arrRules            = ['otp' => 'required|min:6|max:6'];
					$validator           = Validator::make($arrInput, $arrRules);
					if ($validator->fails()) 
					{
						return setValidationErrorMessage($validator);
					}
					$otpdata         = verify_Otp($arrInput);
				} else {
					$otpdata['status'] = 200;
				}
				
				
			} else 
			{
				$otpdata['status'] = 200;
			}


			if($userData->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$users_id;
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
					$intCode = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}
			}
			
			if ($otpdata['status'] == 200) 
			{
			$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
			/*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Topup amount should be greater or equal to last topup amount.';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}*/
			/*if ($request->hash_unit < 100) {

			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Please check Topup amount now start from 100';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}*/
			if (!empty($getPrice)) {

				$date     = $getPrice->entry_time;
				$datework = \Carbon\Carbon::parse($date);
				$now      = \Carbon\Carbon::now();
				$testdate = $datework->diffInMinutes($now);
				//$testdate1 = $datework->diffInDays($now);

				//dd($getPrice->entry_time, $now, $this->today, $testdate, $testdate1);
				// if ($testdate <= 2) {

				// 	$arrStatus  = Response::HTTP_NOT_FOUND;
				// 	$arrCode    = Response::$statusTexts[$arrStatus];
				// 	$arrMessage = 'Try Next Topup after 2 Minutes';
				// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				// }

			} else {
				//dd(22);
			}

			if (!empty($users)) {
				$packageExist = Packages::where([['id', '=', $request->Input('product_id')],["user_show_status",'=',"Active"]])->first();
				if (!empty($packageExist)) {
					$pacakgeId = $packageExist->id;
					$ProductRoi = $packageExist->roi;
					$TotalRoiPer = $packageExist->total_roi_percentage;
					$ProductDuration = $packageExist->duration;
					$Productcost = $request->hash_unit;
					$direct_income = $packageExist->direct_income;
					$binary_cap = $packageExist->capping;
					
					$percentage = ($ProductRoi * $ProductDuration);//144
					$binaryPer = $packageExist->binary;
					$directPer = $packageExist->direct_income;
					$totalIncomePer = $packageExist->total_income_percentage;
					$product_name = $packageExist->package_name;
					$product_type = $packageExist->package_type;
					$product_range = $packageExist->name;
					//if($Productcost <= $request->Input('usd')){

					/*$UserToupWallet = ($auth_user->top_up_wallet - $auth_user->top_up_wallet_withdraw);*/
					$purchase_deduct = $fund_deduct = 0;
				
					if ($request->transcation_type == 2) {

						$topupbalance = Dashboard::where('id', $users_id)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as balance')->pluck('balance')->first();

						if ($Productcost > $topupbalance) {
							$arrStatus  = Response::HTTP_NOT_FOUND;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Insufficient Fund Wallet Balance';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}
					} elseif ($request->transcation_type == 1) {

						$per = UserSettingFund::select('topup_percentage')->where('user_id',$users_id)->orderBy('id','desc')->first();
						$topup_per = $per->topup_percentage;
						$balance = Dashboard::where('id',$userId)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as fundbalance,round(setting_fund_wallet - setting_fund_wallet_withdraw,2) as purchasebalance')->first(); 
						
						$half_of_requested_amount = ($Productcost *$topup_per)/100;

						if($balance->purchasebalance < 1)
						{
							$arrMessage = 'Insufficient Setting Fund Balance';
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
						}elseif ($balance->purchasebalance >= $half_of_requested_amount) {
							$purchase_deduct = $half_of_requested_amount;
						}else{
							$purchase_deduct = $balance->purchasebalance;
						}                   
						$half_of_requested_amount = $Productcost - $purchase_deduct;
						if($half_of_requested_amount > $balance->fundbalance)
						{
							$arrMessage = 'Insufficient Fund Wallet Balance';
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}else{
							$fund_deduct = $half_of_requested_amount;
						}           
		

					} else {

						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
					}

					$getPreviousTopup = Topup::join('tbl_product as tp','tp.id','tbl_topup.type')->select('tp.cost','tbl_topup.*')->where('tbl_topup.id',$users->id)->orderBy('tbl_topup.entry_time','desc')->first();

					//dd($getPreviousTopup);
					if(!empty($getPreviousTopup)){
						$topupno = $getPreviousTopup->topup_no + 1;
						/*$topupno = $getPreviousTopup->topup_no + 1;
						if($getPreviousTopup->amount > $request->hash_unit){
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Topup must be greater or equal to previous topup', '');
						}*/

						/*return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User have only one topup', '');*/
					}else{
						$topupno = 1;
					}

					// if ($UserToupWallet >= $Productcost) {

					/*$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount')->orderBy('srno', 'desc')->first();*/

					if ($Productcost >= $packageExist->min_hash && $Productcost <= $packageExist->max_hash) {

						if ($Productcost >= 10000) {
							$amount = $Productcost;

						} else {
							$amount = $Productcost;
						}

						$updateCoinData        = array();
						$updateCoinData['usd'] = custom_round(($users->usd-$Productcost), 7);
						$logArr                = $fundArr                = $purchaseArr                = array();
						if ($request->transcation_type == 2) {
							$updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$Productcost);
							$fundArr['from_user_id']                = $userId;
							$fundArr['to_user_id']                  = $users->id;
							$fundArr['amount']                      = $Productcost;
							$fundArr['wallet_type']                 = 3;
							$fundArr['remark']                      = "Topup from fund wallet";
							$fundArr['entry_time']                  = $this->today;
						}
						if ($request->transcation_type == 1) {
							$updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_deduct);
							$fundArr['from_user_id']                = $userId;
							$fundArr['to_user_id']                  = $users->id;
							$fundArr['amount']                      = $fund_deduct;
							$fundArr['wallet_type']                 = 3;
							$fundArr['remark']                      = "Topup from Fund Wallet(min ".(100-$topup_per)."%)";
							$fundArr['entry_time']                  = $this->today;

							$updateCoinData['setting_fund_wallet_withdraw'] = DB::raw('setting_fund_wallet_withdraw + '.$purchase_deduct);
							$purchaseArr['from_user_id']              = $userId;
							$purchaseArr['to_user_id']                = $users->id;
							$purchaseArr['amount']                    = $purchase_deduct;
							$purchaseArr['wallet_type']               = 4;
							$purchaseArr['remark']                    = "Topup from Setting Fund wallet (max ".$topup_per."%)";
							$purchaseArr['entry_time']                = $this->today;
						}
						(count($fundArr) > 0)?array_push($logArr, $fundArr):'';
						(count($purchaseArr) > 0)?array_push($logArr, $purchaseArr):'';
						WalletTransactionLog::insert($logArr);
						$updateCoinData['total_withdraw'] = DB::raw('round(total_withdraw + '.$Productcost.',2)');

						$updateCoinData = Dashboard::where('id', $userId)->update($updateCoinData);

						$updateCoinData1['total_investment']  = DB::raw('round(total_investment + '.$Productcost.',2)');
						$updateCoinData1['active_investment'] = DB::raw('round(active_investment + '.$Productcost.',2)');

						$updateCoinData1 = Dashboard::where('id', $users->id)->update($updateCoinData1);
						$topupfrom       = "-";
						if ($request->transcation_type == 1) {
							$topupfrom ="Setting Fund wallet (max ".$topup_per."%) + Fund Wallet (min ".(100-$topup_per)."%)";
						} elseif ($request->transcation_type == 2) {
							$topupfrom = "New Fund Wallet";
						}

						$check_topup_count = Topup::where('id',$users->id)->count();
						
						$random = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
						$Topupdata = array();
						$Topupdata['id'] = $users->id;
						$Topupdata['pin'] = $random;
						$Topupdata['amount'] = $amount;
						$Topupdata['amount_roi'] = ($amount*$ProductRoi)/100;
						$Topupdata['binary_capping'] = $binary_cap;
						$Topupdata['percentage'] = $ProductRoi;
						$Topupdata['direct_roi'] = ($amount*$directPer)/100;
						// $Topupdata['total_roi'] = ($amount * $TotalRoiPer) / 100;
						$Topupdata['binary_percentage'] = $binaryPer;
						$Topupdata['total_income'] = ($amount * $totalIncomePer) / 100;
						$Topupdata['type'] = $pacakgeId;
						$Topupdata['top_up_by'] = $userId;
						$Topupdata['duration'] = $packageExist->duration;
						$Topupdata['product_name'] = $product_name;
						/*	$Topupdata['franchise_id'] = $franchise_user->id;
						$Topupdata['master_franchise_id'] = $franchise_user->id;*/
						// $Topupdata['remark'] = $request->topupfrom;
						if($users->id == $userId) {
						$Topupdata['remark'] = "Self Topup";
						}else{
						$Topupdata['remark'] = "Topup from ".$user_name."";							
						}
						$Topupdata['usd_rate'] = '0';
						$Topupdata['topupfrom'] = $topupfrom;
						$Topupdata['roi_status'] = 'Active';
						$Topupdata['top_up_type'] = 3;
						$Topupdata['binary_pass_status'] = '1';
						$Topupdata['total_usd'] = 0.001;
						$Topupdata['fund_wallet_usage'] = $amount;
						$Topupdata['ip_address'] = $_SERVER['REMOTE_ADDR'];
						$Topupdata['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
						$Topupdata['last_roi_entry_time'] = \Carbon\Carbon::now()->toDateTimeString();

						if ($request->Input('device') != '') {
							$Topupdata['device'] = $request->Input('device');
						} else {
							$Topupdata['device'] = '-';
						}
						$storeId = Topup::insertGetId($Topupdata);
						$this->add_transaction_activity(Auth::User()->id,1,'Activation using Fund Wallet',0,$amount,$topupbalance,custom_round($topupbalance-$amount,2));

						if (!empty($storeId)) {
							//-----check which plan is on in setting--------
							$plan = ProjectSettings::selectRaw('(CASE level_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as level_plan,(CASE binary_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as binary_plan ,(CASE direct_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END ) as direct_plan')->first();

							if (!empty($plan)) {
								$level_plan      = $plan->level_plan;
								$binary_plan     = $plan->binary_plan;
								$direct_plan     = $plan->direct_plan;
								$leadership_plan = $plan->leadership_plan;

								if ($level_plan == '1') {
									$getlevel = $this->pay_level($users->id, $Productcost, $pacakgeId);
								}

								if ($binary_plan == '1') {

									
									// update direct business

									$updateLCountArrDirectBusiness               = array();
									$updateLCountArrDirectBusiness['power_l_bv'] = DB::raw('power_l_bv + '.$Productcost.'');

									$updateRCountArrDirectBusiness               = array();
									$updateRCountArrDirectBusiness['power_r_bv'] = DB::raw('power_r_bv + '.$Productcost.'');

									if ($users->position == 1) {
										User::where('id', $users->ref_user_id)->update($updateLCountArrDirectBusiness);
									} else if ($users->position == 2) {
										User::where('id', $users->ref_user_id)->update($updateRCountArrDirectBusiness);
									}

									$usertopup = array('amount' => DB::raw('amount + '.$Productcost), 'topup_status' => "1");
									User::where('id', $users->id)->update($usertopup);
									
									if($check_topup_count == 0){
										$ref_usertopup = array('bonus_count' => DB::raw('bonus_count + 1'));
										User::where('id', $users->ref_user_id)->update($ref_usertopup);
									}

									$getlevel = $this->pay_binary($users->id, $Productcost,$users->topup_status );
									// check rank of vpid

									//$this->check_rank_vpid($users->virtual_parent_id);
									$this->check_rank_vpid($users->id);
									$this->check_rank_vpid($users->ref_user_id);

								}

								if ($direct_plan == '1' && $direct_income > 0) {
									// check rank for direct user to give direct income

									$this->check_rank($users->ref_user_id);

									//$getlevel = $this->pay_direct($users->id, $Productcost, $direct_income, $random);

									$this->pay_directbulk($users->id, $Productcost, $direct_income, $random, $users->ref_user_id, $users->user_id);

								}
								if ($leadership_plan == '1') {
									$getlevel = $this->pay_leadership($users->id, $Productcost, $pacakgeId);
								}
								// Give franchise income//
								/*$percentageincome=$franchise_user->income_per;
								$this->pay_franchise($users->id, $franchise_user->id,$percentageincome, $Productcost, $storeId, $random);*/
								// Give master franchise income

								/*$ms_percentageincome=$masterfranchise_user->income_per;
							$this->pay_franchise($users->id, $masterfranchise_user->id,$ms_percentageincome, $Productcost, $storeId, $random);*/
							}

							if($users->id == $userId) {
								$subject  = $product_type."-".$product_range." purchased Successfully";
								$pagename = "emails.topup";
								$daily_roi= ($amount*$ProductRoi)/100;
								$entry_date= date('d M Y',strtotime($this->today));
								$get_validity_date = addWorkdays($entry_date,$ProductDuration);
								$max_benefit_per = $percentage > 210 ? 210 : $percentage;
								$max_benefit=round(($max_benefit_per*$amount)/100,2);
								$validity_date= date('d/m/Y',strtotime($get_validity_date));
								$data     = array('pagename' => $pagename, 'email' => $users->email, 'amount' => $amount, 'username' => $users->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'max_benefit'=>$max_benefit);
								$email    = $users->email;
								$mail     = sendMail($data, $email, $subject);

							}else{						
								$subject_to  = "Successfully purchased a plan for another member";
								$subject_from  = "Someone just purchased a ".$product_type."-".$product_range." for you";
								$pagename_to = "emails.topup_downline";
								$pagename_from = "emails.topup_from_other";
								$daily_roi= ($amount*$ProductRoi)/100;
								$entry_date= date('d M Y',strtotime($this->today));
								$get_validity_date = addWorkdays($entry_date,$ProductDuration);
								$email    = Auth::User()->email;
								$to_email = User::where('user_id', $users->user_id)->pluck('email')->first();
								$downline_username = User::where('user_id', $users->user_id)->pluck('user_id')->first();
								$max_benefit_per = $percentage > 210 ? 210 : $percentage;
								$max_benefit=round(($max_benefit_per*$amount)/100,2);
								$validity_date= date('d/m/Y',strtotime($get_validity_date));

								$data_to     = array('pagename' => $pagename_to, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
								$data_from    = array('pagename' => $pagename_from, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
								$mail_to     = sendMail($data_to, $email, $subject_to);
								$mail_from     = sendMail($data_from, $to_email, $subject_from);
							}


							$arrStatus  = Response::HTTP_OK;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Topup Done successfully';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');

						} else {
							$arrStatus  = Response::HTTP_NOT_FOUND;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Something went wrong,Please try again';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');

						}
					} else {
						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Amount should be range of package select';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Package is not exist';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');

				}
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
		}else if ($otpdata['status'] == 403) 
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
		} catch (Exception $e) {
			dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function selfTopupMultipleWallet(Request $request) {

		/*$intCode = Response::HTTP_NOT_FOUND;
		$strStatus = Response::$statusTexts[$intCode];
		$strMessage = 'Topup are stopped till 12th September';
		return sendResponse($intCode, $strStatus, $strMessage, array());*/

		// $intCode = Response::HTTP_NOT_FOUND;
		// $strStatus = Response::$statusTexts[$intCode];
		// $strMessage = 'Your gateway to a strong financial future is going to open soon!';
		// return sendResponse($intCode, $strStatus, $strMessage, array());

		// dd("stop");

		try {
			$rules = array(
				'product_id'       => 'required',
				'hash_unit'        => 'required|numeric|min:1',
				'fund_amount'        => 'required|numeric|min:1',
				'wallet_amount'        => 'required|numeric|min:1',
				'user_id'          => 'required',
				'transcation_type' => 'required',
				'wallet_type' => 'required',
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
				return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]
					['status'], 'Invalid User Token!!!', '');
			}

			/*$auth_user = User::select('tbl_dashboard.top_up_wallet','tbl_dashboard.top_up_wallet_withdraw','tbl_dashboard.total_withdraw','tbl_users.id')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();*/
			$users = User::select('tbl_users.id','tbl_users.email','tbl_users.topup_status', 'tbl_users.user_id', 'tbl_users.virtual_parent_id', 'tbl_users.ref_user_id', 'tbl_dashboard.total_investment', 'tbl_dashboard.active_investment', 'tbl_users.position')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.user_id', '=', $request->user_id], ['tbl_users.status', '=', 'Active']])->first();
			if (empty($users)) {

		  		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This User ID is Blocked', $this->emptyArray);
			}

			$check_record = whiteListIpAddress($type=1,Auth::user()->id);
			// dd($check_record);
			$ip_Address = getIpAddrss();
			$check_user_hits = WhiteListIpAddress::select('id', 'topup_status', 'topup_expire')->where([['uid',Auth::user()->id],['ip_add',$ip_Address]])->first();
			// dd($check_user_hits);
			if(!empty($check_user_hits)){
				if($check_user_hits->topup_status == 1){
					$today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
					if($check_user_hits->topup_expire >= $today){
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Due to too many request hits, temporary you are block for 1 hour!', $this->emptyArray);
					}
				}
			}
			
			
			// if ($request->transcation_type != 2 && $request->transaction_type != 1) {

			// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
			// }

			// dd($request->transcation_type);
			// if ($checktopup > 0) {
			// 	 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one topup allowed for one user', '');
			// }
			$checktopup = Topup::where([['id', $users->id]])->count();

			/* to check user downline or not */
            $id = $users->id;
			$users_id = Auth::User()->id;
			$userId = $to_user_id = $users_id;
			$user_name = Auth::User()->user_id;
			if($to_user_id != $id){
				$todaydetailsexist = TodayDetails::where('to_user_id', $to_user_id)->where('from_user_id',$id)->get();
				if(count($todaydetailsexist) == 0){
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not Downline User','');
				}
			}
			$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
			/*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Topup amount should be greater or equal to last topup amount.';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}*/
			$projectSettings = ProjectSettings::where('status', 1)
				->select('topup_status', 'topup_msg')	->first();
			if ($projectSettings->topup_status == "off") {
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $projectSettings->topup_msg;
				;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			/* to check user downline or not */
			$profile_status = verifyOtpStatus::select('topup_update_status')
            ->where('statusID','=',1)->get();
				$arrInput            = $request->all();
			$userData=User::where('id',$users_id)->first();
			if ($profile_status[0]->topup_update_status == 1) 
			{
				if ($userData->google2fa_status=='disable') {

					$arrInput['user_id'] = Auth::user()->id;
					$arrRules            = ['otp' => 'required|min:6|max:6'];
					$validator           = Validator::make($arrInput, $arrRules);
					if ($validator->fails()) 
					{
						return setValidationErrorMessage($validator);
					}
					$otpdata         = verify_Otp($arrInput);
				} else {
					$otpdata['status'] = 200;
				}
				
				
			} else 
			{
				$otpdata['status'] = 200;
			}


			if($userData->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$users_id;
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
					$intCode = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
				}
			}
			
			if ($otpdata['status'] == 200) 
			{
			$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
			/*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Topup amount should be greater or equal to last topup amount.';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}*/
			/*if ($request->hash_unit < 100) {

			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Please check Topup amount now start from 100';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}*/
			if (!empty($getPrice)) {

				$date     = $getPrice->entry_time;
				$datework = \Carbon\Carbon::parse($date);
				$now      = \Carbon\Carbon::now();
				$testdate = $datework->diffInMinutes($now);
				//$testdate1 = $datework->diffInDays($now);

				//dd($getPrice->entry_time, $now, $this->today, $testdate, $testdate1);
				// if ($testdate <= 2) {

				// 	$arrStatus  = Response::HTTP_NOT_FOUND;
				// 	$arrCode    = Response::$statusTexts[$arrStatus];
				// 	$arrMessage = 'Try Next Topup after 2 Minutes';
				// 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				// }

			} else {
				//dd(22);
			}

			if (!empty($users)) {
				$packageExist = Packages::where([['id', '=', $request->Input('product_id')],["user_show_status",'=',"Active"]])->first();
				if (!empty($packageExist)) {
					$pacakgeId = $packageExist->id;
					$ProductRoi = $packageExist->roi;
					$TotalRoiPer = $packageExist->total_roi_percentage;
					$ProductDuration = $packageExist->duration;
					$Productcost = $request->hash_unit;
					$direct_income = $packageExist->direct_income;
					$binary_cap = $packageExist->capping;
					
					$percentage = ($ProductRoi * $ProductDuration);//144
					$binaryPer = $packageExist->binary;
					$directPer = $packageExist->direct_income;
					$totalIncomePer = $packageExist->total_income_percentage;
					$product_name = $packageExist->package_name;
					$product_type = $packageExist->package_type;
					$product_range = $packageExist->name;
					//if($Productcost <= $request->Input('usd')){

					/*$UserToupWallet = ($auth_user->top_up_wallet - $auth_user->top_up_wallet_withdraw);*/
					$purchase_deduct = $fund_deduct = 0;

					$fund_use_amount = ($Productcost * 50)/100;
					$remaing_amount = $Productcost - $fund_use_amount;

					$fund_amount = $request->Input('fund_amount');
					$wallet_amount = $request->Input('wallet_amount');

					if ($request->transcation_type == 2) {

						$topupbalance = Dashboard::where('id', $users_id)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as balance')->pluck('balance')->first();
						$roibalance = Dashboard::where('id', $users_id)->selectRaw('round(roi_wallet - roi_wallet_withdraw,2) as roi_balance')->pluck('roi_balance')->first();
						$workingbalance = Dashboard::where('id', $users_id)->selectRaw('round(working_wallet - working_wallet_withdraw,2) as working_balance')->pluck('working_balance')->first();
						$hsccbalance = Dashboard::where('id', $users_id)->selectRaw('round(hscc_bonus_wallet - hscc_bonus_wallet_withdraw,2) as hscc_balance')->pluck('hscc_balance')->first();
						
						if ($fund_amount > $topupbalance) {
							$arrStatus  = Response::HTTP_NOT_FOUND;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Insufficient Fund Wallet Balance';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}else{
							if ($fund_amount + $wallet_amount == $Productcost) {
								
								if ($request->wallet_type == 'roi') {
									/*if ($roibalance<$remaing_amount) {
										return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in ROI Wallet', '');
									}*/
									if ($roibalance<$wallet_amount) {
										return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in ROI Wallet', '');
									}
									$target_business = Topup::where([['id', Auth::User()->id],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
									$total_business= Auth::User()->l_guardian + Auth::User()->r_guardian;
									if (!empty($target_business)) {
										if ($total_business < $target_business) { 
											return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Please achieve your target first", ''); 
										}
									}
								} elseif ($request->wallet_type == 'working') {
									/*if ($workingbalance<$remaing_amount) {
										return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in Working Wallet', '');
									}*/
									if ($workingbalance<$wallet_amount) {
										return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in Working Wallet', '');
									}
								} elseif ($request->wallet_type == 'hscc') {
									/*if ($hsccbalance<$remaing_amount) {
										return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in HSCC Bonus Wallet', '');
									}*/
									if ($hsccbalance<$wallet_amount) {
										return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in HSCC Bonus Wallet', '');
									}
								}
							} else {
								$arrStatus  = Response::HTTP_NOT_FOUND;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'Fund amount and wallet amount total should match investment amount.';
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							}
							
							
						}
					} elseif ($request->transcation_type == 1) {

						$per = UserSettingFund::select('topup_percentage')->where('user_id',$users_id)->orderBy('id','desc')->first();
						$topup_per = $per->topup_percentage;
						$balance = Dashboard::where('id',$userId)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as fundbalance,round(setting_fund_wallet - setting_fund_wallet_withdraw,2) as purchasebalance')->first(); 
						
						$half_of_requested_amount = ($Productcost *$topup_per)/100;

						if($balance->purchasebalance < 1)
						{
							$arrMessage = 'Insufficient Setting Fund Balance';
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
						}elseif ($balance->purchasebalance >= $half_of_requested_amount) {
							$purchase_deduct = $half_of_requested_amount;
						}else{
							$purchase_deduct = $balance->purchasebalance;
						}                   
						$half_of_requested_amount = $Productcost - $purchase_deduct;
						if($half_of_requested_amount > $balance->fundbalance)
						{
							$arrMessage = 'Insufficient Fund Wallet Balance';
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}else{
							$fund_deduct = $half_of_requested_amount;
						}           
		

					} else {

						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
					}

					$getPreviousTopup = Topup::join('tbl_product as tp','tp.id','tbl_topup.type')->select('tp.cost','tbl_topup.*')->where('tbl_topup.id',$users->id)->orderBy('tbl_topup.entry_time','desc')->first();

					//dd($getPreviousTopup);
					if(!empty($getPreviousTopup)){
						$topupno = $getPreviousTopup->topup_no + 1;
						/*$topupno = $getPreviousTopup->topup_no + 1;
						if($getPreviousTopup->amount > $request->hash_unit){
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Topup must be greater or equal to previous topup', '');
						}*/

						/*return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User have only one topup', '');*/
					}else{
						$topupno = 1;
					}

					// if ($UserToupWallet >= $Productcost) {

					/*$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount')->orderBy('srno', 'desc')->first();*/

					if ($Productcost >= $packageExist->min_hash && $Productcost <= $packageExist->max_hash) {

						if ($Productcost >= 10000) {
							$amount = $Productcost;

						} else {
							$amount = $Productcost;
						}

						$updateCoinData        = array();
						$updateCoinData['usd'] = custom_round(($users->usd-$Productcost), 7);
						$logArr                = $fundArr                = $purchaseArr                = array();
						if ($request->transcation_type == 2) {
							if ($request->wallet_type == 'roi') {
								// $updateCoinData['roi_wallet_withdraw'] = DB::raw('roi_wallet_withdraw + '.$remaing_amount);
								$updateCoinData['roi_wallet_withdraw'] = DB::raw('roi_wallet_withdraw + '.$wallet_amount);
								$wallet_type=4;
								$remark="Topup from ROI wallet";
							} elseif ($request->wallet_type == 'working') {
								// $updateCoinData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw + '.$remaing_amount);
								$updateCoinData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw + '.$wallet_amount);
								$wallet_type=1;
								$remark="Topup from Working wallet";
							} elseif ($request->wallet_type == 'hscc') {
								// $updateCoinData['hscc_bonus_wallet_withdraw'] = DB::raw('hscc_bonus_wallet_withdraw + '.$remaing_amount);
								$updateCoinData['hscc_bonus_wallet_withdraw'] = DB::raw('hscc_bonus_wallet_withdraw + '.$wallet_amount);
								$wallet_type=5;
								$remark="Topup from HSCC wallet";
							}
							// $updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_use_amount);
							$updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_amount);
							$fundArr['from_user_id']                = $userId;
							$fundArr['to_user_id']                  = $users->id;
							$fundArr['amount']                      = $Productcost;
							$fundArr['wallet_type']                 = $wallet_type;
							$fundArr['remark']                      = $remark;
							$fundArr['entry_time']                  = $this->today;
						}
						if ($request->transcation_type == 1) {
							$updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_deduct);
							$fundArr['from_user_id']                = $userId;
							$fundArr['to_user_id']                  = $users->id;
							$fundArr['amount']                      = $fund_deduct;
							$fundArr['wallet_type']                 = 3;
							$fundArr['remark']                      = "Topup from Fund Wallet(min ".(100-$topup_per)."%)";
							$fundArr['entry_time']                  = $this->today;

							$updateCoinData['setting_fund_wallet_withdraw'] = DB::raw('setting_fund_wallet_withdraw + '.$purchase_deduct);
							$purchaseArr['from_user_id']              = $userId;
							$purchaseArr['to_user_id']                = $users->id;
							$purchaseArr['amount']                    = $purchase_deduct;
							$purchaseArr['wallet_type']               = 4;
							$purchaseArr['remark']                    = "Topup from Setting Fund wallet (max ".$topup_per."%)";
							$purchaseArr['entry_time']                = $this->today;
						}
						(count($fundArr) > 0)?array_push($logArr, $fundArr):'';
						(count($purchaseArr) > 0)?array_push($logArr, $purchaseArr):'';
						WalletTransactionLog::insert($logArr);
						$updateCoinData['total_withdraw'] = DB::raw('round(total_withdraw + '.$Productcost.',2)');

						$updateCoinData = Dashboard::where('id', $userId)->update($updateCoinData);

						$updateCoinData1['total_investment']  = DB::raw('round(total_investment + '.$Productcost.',2)');
						$updateCoinData1['active_investment'] = DB::raw('round(active_investment + '.$Productcost.',2)');

						$updateCoinData1 = Dashboard::where('id', $users->id)->update($updateCoinData1);
						$topupfrom       = "-";
						if ($request->transcation_type == 1) {
							$topupfrom ="Setting Fund wallet (max ".$topup_per."%) + Fund Wallet (min ".(100-$topup_per)."%)";
						} elseif ($request->transcation_type == 2) {
							if ($request->wallet_type == 'roi') {
								$topupfrom = "ROI Wallet";
							} elseif ($request->wallet_type == 'working') {
								$topupfrom = "Working Wallet";
							} elseif ($request->wallet_type == 'hscc') {
								$topupfrom = "HSCC Bonus Wallet";
							}else{

								$topupfrom = "New Fund Wallet";
							}
							
						}

						$check_topup_count = Topup::where('id',$users->id)->count();
						
						$random = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
						$Topupdata = array();
						$Topupdata['id'] = $users->id;
						$Topupdata['pin'] = $random;
						$Topupdata['amount'] = $amount;
						$Topupdata['amount_roi'] = ($amount*$ProductRoi)/100;
						$Topupdata['binary_capping'] = $binary_cap;
						$Topupdata['percentage'] = $ProductRoi;
						$Topupdata['direct_roi'] = ($amount*$directPer)/100;
						// $Topupdata['total_roi'] = ($amount * $TotalRoiPer) / 100;
						$Topupdata['binary_percentage'] = $binaryPer;
						$Topupdata['total_income'] = ($amount * $totalIncomePer) / 100;
						$Topupdata['type'] = $pacakgeId;
						$Topupdata['top_up_by'] = $userId;
						$Topupdata['duration'] = $packageExist->duration;
						$Topupdata['product_name'] = $product_name;
						/*	$Topupdata['franchise_id'] = $franchise_user->id;
						$Topupdata['master_franchise_id'] = $franchise_user->id;*/
						// $Topupdata['remark'] = $request->topupfrom;
						if($users->id == $userId) {
						$Topupdata['remark'] = "Self Topup";
						}else{
						$Topupdata['remark'] = "Topup from ".$user_name."";							
						}
						$Topupdata['usd_rate'] = '0';
						$Topupdata['topupfrom'] = $topupfrom;
						$Topupdata['roi_status'] = 'Active';
						$Topupdata['top_up_type'] = 3;
						$Topupdata['binary_pass_status'] = '1';
						$Topupdata['total_usd'] = 0.001;
						$Topupdata['fund_wallet_usage'] = $fund_amount;
						if ($request->wallet_type == 'roi') {
							$Topupdata['roi_wallet_usage'] = $wallet_amount;
						} elseif ($request->wallet_type == 'working') {
							$Topupdata['working_wallet_usage'] = $wallet_amount;
						} elseif ($request->wallet_type == 'hscc') {
							$Topupdata['hscc_wallet_usage'] = $wallet_amount;
						}
						$Topupdata['ip_address'] = $_SERVER['REMOTE_ADDR'];
						$Topupdata['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
						$Topupdata['last_roi_entry_time'] = \Carbon\Carbon::now()->toDateTimeString();

						if ($request->Input('device') != '') {
							$Topupdata['device'] = $request->Input('device');
						} else {
							$Topupdata['device'] = '-';
						}
						$storeId = Topup::insertGetId($Topupdata);
						$this->add_transaction_activity(Auth::User()->id,1,'Activation using Fund wallet',0,$fund_amount,$topupbalance,custom_round($topupbalance-$fund_amount,2));
						if ($request->transcation_type == 2) {
							if ($request->wallet_type == 'roi') {
								$old_balance = $roibalance;
								$wallet_txn_type = 2;
							} elseif ($request->wallet_type == 'working') {
								$old_balance = $workingbalance;
								$wallet_txn_type = 3;
							} elseif ($request->wallet_type == 'hscc') {
								$old_balance = $hsccbalance;
								$wallet_txn_type = 4;
							}
							$this->add_transaction_activity(Auth::User()->id,$wallet_txn_type,'Activation using '.$topupfrom.'',0,$wallet_amount,$old_balance,custom_round($old_balance-$wallet_amount,2));
						}

						if (!empty($storeId)) {
							//-----check which plan is on in setting--------
							$plan = ProjectSettings::selectRaw('(CASE level_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as level_plan,(CASE binary_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as binary_plan ,(CASE direct_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END ) as direct_plan')->first();

							if (!empty($plan)) {
								$level_plan      = $plan->level_plan;
								$binary_plan     = $plan->binary_plan;
								$direct_plan     = $plan->direct_plan;
								$leadership_plan = $plan->leadership_plan;

								if ($level_plan == '1') {
									$getlevel = $this->pay_level($users->id, $Productcost, $pacakgeId);
								}

								if ($binary_plan == '1') {

									
									// update direct business

									$updateLCountArrDirectBusiness               = array();
									$updateLCountArrDirectBusiness['power_l_bv'] = DB::raw('power_l_bv + '.$Productcost.'');

									$updateRCountArrDirectBusiness               = array();
									$updateRCountArrDirectBusiness['power_r_bv'] = DB::raw('power_r_bv + '.$Productcost.'');

									if ($users->position == 1) {
										User::where('id', $users->ref_user_id)->update($updateLCountArrDirectBusiness);
									} else if ($users->position == 2) {
										User::where('id', $users->ref_user_id)->update($updateRCountArrDirectBusiness);
									}

									$usertopup = array('amount' => DB::raw('amount + '.$Productcost), 'topup_status' => "1");
									User::where('id', $users->id)->update($usertopup);
									
									if($check_topup_count == 0){
										$ref_usertopup = array('bonus_count' => DB::raw('bonus_count + 1'));
										User::where('id', $users->ref_user_id)->update($ref_usertopup);
									}

									$getlevel = $this->pay_binary($users->id, $Productcost,$users->topup_status );
									// check rank of vpid

									//$this->check_rank_vpid($users->virtual_parent_id);
									$this->check_rank_vpid($users->id);
									$this->check_rank_vpid($users->ref_user_id);

								}

								if ($direct_plan == '1' && $direct_income > 0) {
									// check rank for direct user to give direct income

									$this->check_rank($users->ref_user_id);

									//$getlevel = $this->pay_direct($users->id, $Productcost, $direct_income, $random);

									$this->pay_directbulk($users->id, $Productcost, $direct_income, $random, $users->ref_user_id, $users->user_id);

								}
								if ($leadership_plan == '1') {
									$getlevel = $this->pay_leadership($users->id, $Productcost, $pacakgeId);
								}
								// Give franchise income//
								/*$percentageincome=$franchise_user->income_per;
								$this->pay_franchise($users->id, $franchise_user->id,$percentageincome, $Productcost, $storeId, $random);*/
								// Give master franchise income

								/*$ms_percentageincome=$masterfranchise_user->income_per;
							$this->pay_franchise($users->id, $masterfranchise_user->id,$ms_percentageincome, $Productcost, $storeId, $random);*/
							}

							if($users->id == $userId) {
								$subject  = $product_type."-".$product_range." purchased Successfully";
								$pagename = "emails.topup";
								$daily_roi= ($amount*$ProductRoi)/100;
								$entry_date= date('d M Y',strtotime($this->today));
								$get_validity_date = addWorkdays($entry_date,$ProductDuration);
								$max_benefit_per = $percentage > 210 ? 210 : $percentage;
								$max_benefit=round(($max_benefit_per*$amount)/100,2);
								$validity_date= date('d/m/Y',strtotime($get_validity_date));
								$data     = array('pagename' => $pagename, 'email' => $users->email, 'amount' => $amount, 'username' => $users->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'max_benefit'=>$max_benefit);
								$email    = $users->email;
								$mail     = sendMail($data, $email, $subject);

							}else{						
								$subject_to  = "Successfully purchased a plan for another member";
								$subject_from  = "Someone just purchased a ".$product_type."-".$product_range." for you";
								$pagename_to = "emails.topup_downline";
								$pagename_from = "emails.topup_from_other";
								$daily_roi= ($amount*$ProductRoi)/100;
								$entry_date= date('d M Y',strtotime($this->today));
								$get_validity_date = addWorkdays($entry_date,$ProductDuration);
								$email    = Auth::User()->email;
								$to_email = User::where('user_id', $users->user_id)->pluck('email')->first();
								$downline_username = User::where('user_id', $users->user_id)->pluck('user_id')->first();
								$max_benefit_per = $percentage > 210 ? 210 : $percentage;
								$max_benefit=round(($max_benefit_per*$amount)/100,2);
								$validity_date= date('d/m/Y',strtotime($get_validity_date));

								$data_to     = array('pagename' => $pagename_to, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
								$data_from    = array('pagename' => $pagename_from, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
								$mail_to     = sendMail($data_to, $email, $subject_to);
								$mail_from     = sendMail($data_from, $to_email, $subject_from);
							}

							$arrStatus  = Response::HTTP_OK;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Topup Done successfully';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');

						} else {
							$arrStatus  = Response::HTTP_NOT_FOUND;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Something went wrong,Please try again';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');

						}
					} else {
						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Amount should be range of package select';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Package is not exist';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');

				}
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
		}else if ($otpdata['status'] == 403) 
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
		} catch (Exception $e) {
			dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function manualTopup(Request $request) {
		try {
			dd($request);
			$rules = array(
				//'product_id' => 'required',
				'hash_unit' => 'required',
				//'franchise_user_id' => 'required',
				'contact_no' => 'required',
				'file'       => 'required|image',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
			}
			$fileName = null;
			/*$packageExist = Packages::where([['id', '=', $request->Input('product_id')]])->first();

			if ($request->hash_unit > $packageExist->max_hash || $request->hash_unit < $packageExist->min_hash) {
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Amount should be in range ' . $packageExist->min_hash . ' and ' . $packageExist->max_hash;
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$pacakgeId = $packageExist->id;
			$ProductRoi = $packageExist->roi;
			$ProductDuration = $packageExist->duration;
			$Productcost = $request->hash_unit;
			 */
			//if (!empty($packageExist)) {
			if ($request->hasFile('file')) {

				$file = $request->file('file');

				$fileName = time().'.'.$file->getClientOriginalExtension();

				//$file->move(public_path('uploads/files'), $fileName);

				$fileName = Storage::disk('s3')->put("projectname", $request->file('file'), "public");

				//$PinRequest->attachment = $fileName;
			} else {
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Please upload transaction slip image';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			//$franchise_user = User::where('id', $request->franchise_user_id)->first();
			//-----insert top up----------- ---
			$random                  = substr(number_format(time()*rand(), 0, '', ''), 0, '15');
			$Topupdata               = array();
			$Topupdata['id']         = Auth::user()->id;
			$Topupdata['pin']        = $random;
			$Topupdata['amount']     = $request->hash_unit;
			$Topupdata['percentage'] = 0;
			//($ProductRoi * $ProductDuration);
			$Topupdata['type'] = 0;
			// $pacakgeId;
			$Topupdata['top_up_by']    = Auth::user()->id;
			$Topupdata['franchise_id'] = 0;
			//$franchise_user->id;
			$Topupdata['usd_rate']           = '0';
			$Topupdata['roi_status']         = 'Active';
			$Topupdata['top_up_type']        = 3;
			$Topupdata['binary_pass_status'] = '1';
			$Topupdata['entry_time']         = $this->today;
			$Topupdata['attachment']         = $fileName;
			$storeId                         = TopupRequest::insertGetId($Topupdata);

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Fund Request Done successfully';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			/*} else {

		$arrStatus = Response::HTTP_NOT_FOUND;
		$arrCode = Response::$statusTexts[$arrStatus];
		$arrMessage = 'Package is not exist';
		return sendResponse($arrStatus, $arrCode, $arrMessage, '');

		}*/

		} catch (Exception $e) {
			dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Reinvest -Lending
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function reinvest(Request $request) {
		try {dd($request->all());
			$rules = array(
				'roi_balance'          => 'required|',
				'level_income_balance' => 'required|',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
			}
			$users = Auth::User();
			if (!empty($users)) {
				$level_blce = $users->level_income-$users->level_income_withdraw;
				$roi_blce   = $users->roi_income-$users->roi_incomelevel_income_balance_withdraw;
				if ($level_blce >= $request->input('level_income_balance')) {
					if ($roi_blce >= $request->input('roi_balance')) {
						// add level blce and roi blce
						$total_blce = $request->input('level_income_balance')+$request->input('roi_balance');

						//check toatal balance is between package amount
						$packageExist = Packages::where([['cost', '<=', $total_blce]])->orderBy('cost', 'dec')->limit(1)->first();
						$minCost      = Packages::orderBy('id', 'asc')->min('cost');
						$maxCost      = Packages::orderBy('id', 'asc')->max('cost');

						if (!empty($packageExist)) {

							// update dashboard value
							$updateCoinData                          = array();
							$updateCoinData['level_income_withdraw'] = custom_round(($users->level_income_withdraw+$request->input('level_income_balance')), 7);
							$updateCoinData['roi_income_withdraw']   = custom_round(($users->roi_income_withdraw+$request->input('roi_balance')), 7);
							$updateCoinData['active_investment']     = custom_round(($users->active_investment+$total_blce), 7);
							$updateCoinData['total_investment']      = custom_round(($users->total_investment+$total_blce), 7);
							$updateCoinData['total_withdraw']        = custom_round(($users->total_withdraw-$total_blce), 7);
							$updateCoinData['usd']                   = custom_round(($users->usd-$total_blce), 7);
							$updateCoinData                          = Dashboard::where('id', $users->id)->update($updateCoinData);
							//---------update topup data----------
							$request->request->add(['usd' => $total_blce]);
							$getvalue = $this->IcoPhases->getIcoBtcCoin($request);

							$random = substr(number_format(time()*rand(), 0, '', ''), 0, '15');
							if ($getvalue->original["code"] == '200') {
								$usd_rate = json_encode($getvalue->original['data'][0]['usd_rate']);
								//-----insert top up--------------
								$random                          = substr(number_format(time()*rand(), 0, '', ''), 0, '15');
								$Topupdata                       = array();
								$Topupdata['id']                 = $users->id;
								$Topupdata['pin']                = $random;
								$Topupdata['amount']             = $total_blce;
								$Topupdata['percentage']         = '0';
								$Topupdata['type']               = '0';
								$Topupdata['top_up_by']          = $users->id;
								$Topupdata['usd_rate']           = $usd_rate;
								$Topupdata['roi_status']         = 'Active';
								$Topupdata['top_up_type']        = '0';
								$Topupdata['binary_pass_status'] = '1';
								$insertTopupDta                  = Topup::create($Topupdata);

								$getCoin                  = $this->projectsettings->getProjectDetails();
								$Trandata                 = array();// insert in transaction
								$Trandata['id']           = $users->id;
								$Trandata['network_type'] = $getCoin->original["data"]["coin_name"];
								$Trandata['refference']   = $insertTopupDta->id;
								$Trandata['debit']        = $total_blce;
								$Trandata['status']       = 1;
								$Trandata['remarks']      = 'Paid Reinvest for lending wallet id : '.$Topupdata['pin'].'';
								$TransactionDta           = AllTransaction::create($Trandata);
								$actdata                  = array();// insert in transaction
								$actdata['id']            = $users->id;
								$actdata['message']       = 'Paid Reinvest for lending wallet id : '.$Topupdata['pin'].' and amount :'.$total_blce.'';
								$actdata['status']        = 1;
								$actDta                   = Activitynotification::create($actdata);

								if (!empty($insertTopupDta)) {

									$arrStatus  = Response::HTTP_OK;
									$arrCode    = Response::$statusTexts[$arrStatus];
									$arrMessage = 'Reinvestment done successfullyInvalid userReinvestment done successfully';
									return sendResponse($arrStatus, $arrCode, $arrMessage, '');
								} else {
									$arrStatus  = Response::HTTP_NOT_FOUND;
									$arrCode    = Response::$statusTexts[$arrStatus];
									$arrMessage = 'Something went wrong,Please try again';
									return sendResponse($arrStatus, $arrCode, $arrMessage, '');

								}
							} else {

								$arrStatus  = Response::HTTP_NOT_FOUND;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = $getvalue->original["message"];
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');

							}
						} else {

							$arrStatus  = Response::HTTP_NOT_FOUND;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'Investment not matched with package amount.It should be min '.$minCost.' and max '.$maxCost.'';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}
					} else {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'You have insufficient roi balance';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'You have insufficient level income balance';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');

				}
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
		} catch (Exception $e) {

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function fundRequest(Request $request) {

		// dd($request);
		/*
		payment_mode
		trn_ref_no
		holder_name
		bank_name
		deposit_date
		 */
		$rules = array('amount' => 'required', 'payment_mode' => 'required');
		if (!empty($rules)) {
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err     = '';
				foreach ($message->all() as $error) {
					$err = $err." ".$error;
				}
				$intCode   = Response::HTTP_NOT_FOUND;
				$strStatus = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $err, '');
			}
		}
		$fileName = 'no_image_available.png';
		if ($request->hasFile('file')) {

			$file = $request->file('file');

			$fileName = time().'.'.$file->getClientOriginalExtension();

			// $file->move(public_path('uploads/files'), $fileName);

			// $PinRequest->attachment = $fileName;
		}

		$id = Auth::user()->id;
		if (!empty($id)) {

			$insertfundreq               = new FundRequest;
			$insertfundreq->user_id      = $id;
			$insertfundreq->amount       = $request->amount;
			$insertfundreq->product_id   = $request->product_id;
			$insertfundreq->pay_slip     = $fileName;
			$insertfundreq->payment_mode = $request->payment_mode;
			$insertfundreq->trn_ref_no   = $request->trn_ref_no;
			$insertfundreq->holder_name  = $request->holder_name;
			$insertfundreq->bank_name    = $request->bank_name;
			$insertfundreq->deposit_date = $request->deposit_date;
			$insertfundreq->save();

			$intCode   = Response::HTTP_OK;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, 'Fund request sent Successfully', '');

		} else {
			$intCode   = Response::HTTP_NOT_FOUND;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, 'User invalid', '');
		}

	}

	/**
	 * [e fundRequestReport for perticular user ]
	 * @param  Request $request [token alpha-num]
	 * @return [Array]
	 */
	public function fundRequestReport(Request $request) {
		$id = Auth::user()->id;
		if (!empty($id)) {
			$url   = url('uploads/files');
			$query = FundRequest::select('tbl_fund_request.*', 'user.fullname', 'user.user_id', DB::raw('IF(tbl_fund_request.pay_slip IS NOT NULL,CONCAT("'.$url.'","/",tbl_fund_request.pay_slip),NULL) attachment'), DB::raw("DATE_FORMAT(tbl_fund_request.deposit_date,'%Y/%m/%d') as deposit_date"), DB::raw("DATE_FORMAT(tbl_fund_request.entry_time,'%Y/%m/%d') as entry_time"), 'tp.name_rupee as product_name')
				->join('tbl_users as user', 'user.id', '=', 'tbl_fund_request.user_id')
				->join('tbl_product as tp', 'tp.id', '=', 'tbl_fund_request.product_id')
				->where('tbl_fund_request.user_id', $id)
				->orderBy('tbl_fund_request.id', 'DESC');

			if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
				//searching loops on fields
				$fields = getTableColumns('tbl_fund_request');
				$search = $request->input('search')['value'];
				$query  = $query->where(function ($query) use ($fields, $search) {
						foreach ($fields as $field) {
							$query->orWhere('tbl_fund_request.'.$field, 'LIKE', '%'.$search.'%');
						}
						$query->orWhere('user.user_id', 'LIKE', '%'.$search.'%');
						//->orWhere('prod.name', 'LIKE', '%' . $search . '%');
					});
			}

			$data = setPaginate($query, $request->start, $request->length);
			if (!empty($data)) {

				$intCode   = Response::HTTP_OK;
				$strStatus = Response::$statusTexts[$intCode];

				return sendresponse($intCode, $strStatus, 'Data found Successfully!', $data);
			} else {

				$intCode   = Response::HTTP_INTERNAL_SERVER_ERROR;
				$strStatus = Response::$statusTexts[$intCode];

				return sendresponse($intCode, $strStatus, 'Data not found', '');
			}
		} else {

			$intCode   = Response::HTTP_NOT_FOUND;
			$strStatus = Response::$statusTexts[$intCode];

			return sendresponse($intCode, $strStatus, 'User id does not exist', '');

		}
	}

	public function sendWAMessage(Request $request) {
		$intCode   = Response::HTTP_NOT_FOUND;
		$strStatus = Response::$statusTexts[$intCode];

		return sendresponse($intCode, $strStatus, 'Stop By admin', '');
		$getuser     = Auth::user();
		$INR         = $request->amount*$request->INR;
		$whatsappMsg = "Dear User,\n Please deposit amount ".$INR." INR in below bank details: \nAccount name - TRENDING VIDS\nAccount no – 251820052005\nIFSC code – INDB0000999\nBranch – Wakad branch (0999)\n Thank You \n www.dollardevice.net";

		$countrycode = getCountryCode($getuser->country);
		sendSMS($getuser->mobile, $whatsappMsg);
		//sendWhatsappMsg($countrycode, $getuser->mobile, $whatsappMsg);
		$subject  = "Bank Account Details";
		$pagename = "emails.deposit";
		$data     = array('pagename' => $pagename, 'email' => $getuser->email, 'username' => $getuser->user_id, 'INR' => $INR);
		$email    = $getuser->email;
		$mail     = sendMail($data, $email, $subject);
	}
	public function gettopupdata(Request $request) {
		$id = Auth::user()->id;
		/*  dd($user->id);*/
		$checktopup = Topup::select('id')->where('id', $id)->count();
		if ($checktopup > 0) {
			$data['topupdata'] = 1;
		} else {
			$data['topupdata'] = 0;
		}

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'data fount', $data);
	}
}
