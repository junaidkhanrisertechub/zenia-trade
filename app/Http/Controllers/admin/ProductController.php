<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\admin\CommonController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\LevelController;
use App\Models\Activitynotification;

use App\Models\AssignRank;
use App\Models\Dashboard;
use App\Models\FundRequest;
use App\Models\Product;
use App\Models\Rank;
use App\Models\supermatching;
use App\Models\Topup;
use App\Models\TopupLogs;
use App\Models\TopupRequest;
use App\Models\UserStructureModel;
use App\Models\Packages;
use App\Models\verifyAdminOtpStatus;
use App\Models\AddFailedLoginAttempt;
use App\Traits\Income;
use App\Traits\Users;
use App\User;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Validator;
use Exception;

class ProductController extends Controller
{
	use Income;
	use Users;
	/**
	 * define property variable
	 *
	 * @return
	 */
	public $statuscode, $commonController, $level;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(CommonController $commonController, LevelController $level)
	{
		$this->statuscode       = Config::get('constants.statuscode');
		$this->commonController = $commonController;
		$this->level            = $level;
	}

	/**
	 * store topup
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function AddRank(Request $request)
	{
		// dd($request->all());
		// try{
		$arrInput = $request->all();
		$rules    = array(
			'id'   => 'required',
			'rank' => 'required',

		);
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
		}

		$adminOtpStatusData = verifyAdminOtpStatus::select('add_rank_otp_status')->first();
			$validator = Validator::make($arrInput, $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
			} else if ($adminOtpStatusData->add_rank_otp_status == 1) {
				if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
				}


				$arrInput['user_id'] = Auth::User()->id;
				$arrInput['remark'] = 'Add Rank';
				$arrInput['otp'] = $request->otp;
				$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
				if (!empty($verify_otp)) {
					if ($verify_otp['status'] == 200) {
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
				}
			}


		// $tp = Topup::where('id', $request->id)->count('srno');
		// if ($tp < 1) {
		// 	return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User is not paid user', '');
		// }
		$userrank = User::select('rank')->where('id', $request->id)->pluck('rank')->first();
		if ($userrank != '' || !empty($userrank) || $userrank != null) {
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User already achieved rank', '');
		}

		$request->rank = "Ace";
		$addedBy       = Auth::user()->id;
		$invoice_id    = substr(number_format(time() * rand(), 0, '', ''), 0, '15');

		$Insertdata             = array();
		$Insertdata['pin']      = $invoice_id;
		$Insertdata['rank']     = $request->rank;
		$Insertdata['user_id']  = $request->id;
		$Insertdata['added_by'] = $addedBy;

		$insertAdd = supermatching::create($Insertdata);

		$data['rank'] = $request->rank;
		DB::table('tbl_users')
			->where('id', $request->id)
			->update($data);

		$Rankdata                = array();
		$Rankdata['assigned_by'] = $addedBy;
		$Rankdata['rank']        = $request->rank;
		$Rankdata['user_id']     = $request->id;
		$Rankdata['entry_time']  = \Carbon\Carbon::now();

		$insertAdd = AssignRank::create($Rankdata);
		if ($request->rank == "Ace") {
			$updateLCountArr                       = array();
			$updateLCountArr['l_ace']              = DB::raw('l_ace + 1');
			$updateLCountArr['l_ace_check_status'] = DB::raw('l_ace_check_status + 1');

			DB::table('tbl_users as a')
				// ->select('a.from_user_id','a.from_user_id_l_c_count','a.from_user_id_r_c_count')
				->join('tbl_today_details as b', 'b.to_user_id', '=', 'a.id')
				->where('b.from_user_id', '=', $request->id)
				->where('b.position', '=', 1)
				->update($updateLCountArr);

			$updateRCountArr                       = array();
			$updateRCountArr['r_ace']              = DB::raw('r_ace + 1');
			$updateRCountArr['r_ace_check_status'] = DB::raw('r_ace_check_status + 1');

			DB::table('tbl_users as a')
				// ->select('a.from_user_id','a.from_user_id_l_c_count','a.from_user_id_r_c_count')
				->join('tbl_today_details as b', 'b.to_user_id', '=', 'a.id')
				->where('b.from_user_id', '=', $request->id)
				->where('b.position', '=', 2)
				->update($updateRCountArr);
		}

		if (!empty($insertAdd)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Rank added successfully', $invoice_id);
		} else {
			return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding rank', '');
		}
	// }
	// catch (Exception $e) {
	// 	dd($e);
	// }
	}

    public function InfluencerTopupsBlade(){
		return view('admin.Topup.InfluencerTopupReport');
	}

	public function addTopBlade(Request $request){
        $request->replace(['status' => 'Active']);
        $product_list = Product::select('id', 'package_name as name', 'cost', 'direct_income', 'roi', 'duration', 'entry_time', 'min_hash', 'max_hash', 'binary', 'capping')->where([['status', $request->status], ['admin_show_status', 1]])->get();
        return view('admin.Topup.AddTopUpBlade')->with(compact('product_list'));
    }

	public function storeTopup(Request $request)
	{

		try {

			$arrInput = $request->all();
			$top_up_by = Auth::user()->id;
			// $top_up_by = 1;
			// dd($arrInput);
			$rules = array(
				// 'user_id'    => 'required',
				'id'         => 'required',
				'product_id' => 'required',
				/*'franchise_user_id' => 'required',
				'masterfranchise_user_id' => 'required',*/
				'hash_unit' => 'required',
				// 'otp' => 'required',
			);
			$adminOtpStatusData = verifyAdminOtpStatus::select('topup_update_status')->first();
			$validator = Validator::make($arrInput, $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
			} else if ($adminOtpStatusData->topup_update_status == 1) {
				if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
				}


				$arrInput['user_id'] = Auth::User()->id;
				// $arrInput['user_id'] = 1;
				$arrInput['remark'] = 'admin topup';
				$arrInput['otp'] = $request->otp;
				$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
				if (!empty($verify_otp)) {
					if ($verify_otp['status'] == 200) {
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
				}
			}

			// $objProduct = $this->commonController->getAllProducts(['id' => $arrInput['product_id']])[0];
			$objProduct = Packages::where([['id', '=', $request->Input('product_id')],['admin_show_status',1]])->first();
			if (!empty($objProduct)) {
				// $TotalRoiPer = $objProduct->total_roi_percentage;
				// dd($arrInput['hash_unit']);
				if ($arrInput['hash_unit'] < $objProduct->min_hash || $arrInput['hash_unit'] > $objProduct->max_hash) {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Amount range should be min' . $objProduct->min_hash . 'max' . $objProduct->max_hash, '');
				}

				$touser = User::select('id', 'user_id', 'virtual_parent_id', 'ref_user_id', 'position','topup_status','login_allow_status')->where('id', $arrInput['id'])->first();

				$getPrice = Topup::select('entry_time')->where([['id', '=', $arrInput['id']], ['amount', '>', 0]])->select('entry_time', 'id','amount')->orderBy('srno', 'desc')->first();
				if ($touser->login_allow_status == '0') {

					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User ID not applicable for this topup', '');
				}

				/*if (!empty($getPrice) && $arrInput['hash_unit'] < $getPrice->amount) {*/
					//dd($arrInput['hash_unit'],$getPrice->amount,"hii");
				/*	$arrStatus = Response::HTTP_NOT_FOUND;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Topup amount should be greater or equal to last topup amount.';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');*/
					/*return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Topup amount should be greater or equal to last topup amount.', '');

				}*/
				if ($request->Input('product_id') == 7) {
					// code...
					$checktopup = Topup::where([['id', $request->id],['type',7]])->count();
					if ($checktopup > 0) {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one Influencer topup allowed for one user', '');
					}
				}
				if (!empty($getPrice)) {

					$date     = $getPrice->entry_time;
					$datework = \Carbon\Carbon::parse($date);
					$now      = \Carbon\Carbon::now();
					$testdate = $datework->diffInMinutes($now);
					//$testdate1 = $datework->diffInDays($now);

					//	dd($getPrice, $getPrice->entry_time, $now, $testdate);
					/*if ($testdate <= 2) {

						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Try Next Topup after 2 Minutes', '');
						}*/
				} else {
					//dd(22);
				}

				/*if($objProduct->package_type=='Franchise')*/
				if ($arrInput['hash_unit'] >= 10000) {
					/*$checkAlready=User::where([['is_franchise','1'],['id',$touser->id]])->first();
							if(!empty($checkAlready))
							{
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is already franchise','');
							*/
					/*else{

							// Check if user has topup
							$checkTopup=Topup::where([['id',$touser->id],['amount','>',0]])->first();
							if(!empty($checkTopup))
							*/
					$amount = $arrInput['hash_unit'];
					//Update user's entry

					/*if($amount >=10000 && $amount <100000){

							$income_per=3;
							if($touser->is_franchise !=1 && $touser->income_per !=2){

							$updateUser = User::where('id', $touser->id)->update(['is_franchise' => '1','income_per'=>$income_per]);

							}
							}
							if($amount >= 100000){

							$income_per=2;

							$updateUser = User::where('id', $touser->id)->update(['is_franchise' => '1','income_per'=>$income_per]);
							}*/

					/*}
						else{
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User must have topup to become franchise','');
						}
						*/
				} else {
					$amount = $arrInput['hash_unit'];
				}
				$package_type =$objProduct->package_type;
				/*$franchise_user = User::where('id', $request->franchise_user_id)->first();

						$masterfranchise_user = User::where('id', $request->masterfranchise_user_id)->first();
						*/
				$ProductRoi = $objProduct->roi;
				$ProductDuration = $objProduct->duration;
				$binary_cap = $objProduct->capping;
				$percentage = ($ProductRoi * $ProductDuration);//144
				$totalIncomePer = $objProduct->total_income_percentage;
				$product_name = $objProduct->package_name;
				$binaryPer = $objProduct->binary;
				$directPer = $objProduct->direct_income;
				$roi_per =  $objProduct->roi;
				$roi_duration = $objProduct->duration;
				if ($package_type == "Zero PIN ROI" || $package_type == "Influencer") {
					$product_roi = Product::select("roi","duration")->where('min_hash','<=',$amount)->where("max_hash",">=",$amount)->whereNotIn("package_type",["Zero PIN","Zero PIN ROI","Influencer"])->first();
					$roi_per =  $product_roi->roi;
					$roi_duration = $product_roi->duration;
				}
				if ($package_type == "Zero PIN") {
					$roi_stop_status = 0;
				}else{
					$roi_stop_status = 1;

				}
				if ($package_type == "Influencer") {
					if (!empty($arrInput['target_business'])) {
						$target_business = $arrInput['target_business'];
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Target Value is required', '');
					}

				}else{
					$target_business = 0;
				}

				$check_topup_count = Topup::where('id',$arrInput['id'])->count();
				$invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
				$arrInsert  = [
					'id'         => $arrInput['id'],
					'pin'        => $invoice_id,
					'amount'     => $amount,
					'amount_roi' => ($amount*$roi_per)/100,
					'binary_capping' => $binary_cap,
					'percentage' => $roi_per,
					'direct_roi' => ($amount*$directPer)/100,
					'binary_percentage' => $binaryPer,
					'total_income' => ($amount * $totalIncomePer) / 100,
					'type'       => $objProduct->id,
					'topupfrom'  => '1',
					'duration'	 => $roi_duration,
					'product_name' => $product_name,
					'remark'       => 'TOPUP BY ADMIN',
					'top_up_by'   => $top_up_by,
					'top_up_type' => 2, //admin topup type
					'binary_pass_status' => '1',
					'usd_rate'           => '0',
					'roi_status'         => 'Active',
					'roi_stop_status'    => $roi_stop_status,
					'target_business'    => $target_business,
					'total_usd'          => 0.001,
					'ip_address'         => getIPAddress(),
					'entry_time'         => \Carbon\Carbon::now()->toDateTimeString(),
					'last_roi_entry_time'  => \Carbon\Carbon::now()->toDateTimeString(),
					// 'total_roi' => ($amount*$TotalRoiPer)/100,
 					/*'payment_type' => $arrInput['payment_type'],*/
					/*'franchise_id' => $franchise_user->id,
								'master_franchise_id' => $masterfranchise_user->id,*/
				];
				$storeId = Topup::insertGetId($arrInsert);

				if (!empty($storeId)) {

					$price_in_usd = $arrInput['hash_unit'];
					// $zero_amount = (in_array($package_type, ["Zero PIN","Zero PIN ROI","Influencer"]))?0:$price_in_usd;
					$zero_amount = $price_in_usd;
					$direct_income = $objProduct->direct_income;
					$settings      = $this->commonController->getProjectSettings();

					$updateCoinData = array();
					$updateCoinData['total_investment']  = DB::raw("total_investment + ". $zero_amount);
					$updateCoinData['active_investment'] = DB::raw("active_investment + ". $zero_amount);
					$updateCoinData = Dashboard::where('id', $arrInput['id'])->limit(1)->update($updateCoinData);

					if ($settings->level_plan == 'on') {
						$getlevel = $this->pay_level($arrInput['id'], $price_in_usd, $arrInput['product_id']);
						/* $getlevel = $this->uplineIncome($arrInput['id'],$price_in_usd,$arrInput['product_id']);*/
					}

					if ($settings->binary_plan == 'on') {

						// update direct business

						$updateLCountArrDirectBusiness               = array();
						$updateLCountArrDirectBusiness['power_l_bv'] = DB::raw('power_l_bv + ' . $zero_amount . '');

						$updateRCountArrDirectBusiness               = array();
						$updateRCountArrDirectBusiness['power_r_bv'] = DB::raw('power_r_bv + ' . $zero_amount . '');

						if ($touser->position == 1) {
							User::where('id', $touser->ref_user_id)->update($updateLCountArrDirectBusiness);
						} else if ($touser->position == 2) {
							User::where('id', $touser->ref_user_id)->update($updateRCountArrDirectBusiness);
						}

						$usertopup = array('amount' => DB::raw('amount + ' . $price_in_usd), 'topup_status' => "1");
						User::where('id', $touser->id)->update($usertopup);

						if($check_topup_count == 0 && !in_array($package_type, ["Zero PIN","Zero PIN ROI"]))
						{
							$ref_usertopup = array('bonus_count' => DB::raw('bonus_count + 1'));
							User::where('id', $touser->ref_user_id)->update($ref_usertopup);
						}
						if (!in_array($package_type, ["Zero PIN","Zero PIN ROI","Influencer"])) {
							$getlevel = $this->pay_binary($arrInput['id'], $price_in_usd, $touser->topup_status);
							$getlevel = $this->pay_binary_level($arrInput['id'], $price_in_usd, $touser->topup_status);
						}
						$this->check_rank_vpid($touser->id);
						$this->check_rank_vpid($touser->ref_user_id);
					}

					if ($settings->direct_plan == 'on' && $direct_income > 0) {

						//	$getlevel = $this->pay_direct($arrInput['id'], $price_in_usd, $direct_income, $invoice_id);
						// check rank for direct user to give direct income

						$this->check_rank($touser->ref_user_id);

						//$getlevel = $this->pay_direct($users->id, $Productcost, $direct_income, $random);
						if (!in_array($package_type, ["Zero PIN","Zero PIN ROI","Influencer"])) {
					    	$this->pay_directbulk($touser->id, $price_in_usd, $direct_income, $invoice_id, $touser->ref_user_id, $touser->user_id);
					    }

					}
					/*if ($settings->leadership_plan == 'on') {
								$getlevel = $this->pay_leadership($arrInput['id'], $price_in_usd, $arrInput['product_id']);
								}*/
					// Give franchise income

					/*$franchise_income_per=$franchise_user->income_per;
								$this->pay_franchise($arrInput['id'], $franchise_user->id, $franchise_income_per, $price_in_usd, $storeId, $invoice_id);*/
					/* }*/

					/*$ms_percentageincome=$masterfranchise_user->income_per;
								$this->pay_franchise($arrInput['id'], $masterfranchise_user->id,$ms_percentageincome, $price_in_usd, $storeId, $invoice_id);*/

					// $subject  = "Package Activated!";
					// $pagename = "emails.deposit";
					// $data     = array('pagename' => $pagename, 'email' => $touser->email, 'amount' => $amount, 'username' => $touser->user_id, 'Package' => $objProduct->name);
					// $email    = $touser->email;
					// $mail     = sendMail($data, $email, $subject);

					/*$actdata['id']          = $arrInput['id'];
								$actdata['message']     = 'Paid for mining wallet id : '.$invoice_id.' and amount :'.$price_in_usd;
								$actdata['status']      = 1;
								$actdata['entry_time']  = now();
								*/
					$actDta = 1;
					if (!empty($actDta)) {
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Topup done successfully', $invoice_id);
					} else {
						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding activity notification', '');
					}
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding topup', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product details not available', '');
			}
		}
		catch (Exception $e) {
			dd($e);
		}
	}

    public function BulkPowerTopupReportBlade(){
		return view('admin.Topup.BulkPowerTopupReport');
	}


    public function BulkPowerTopupBlade(Request $request){
        return view('admin.Topup.BulkPowerTopup');

    }
	public function storeBulkTopup(Request $request)
	{

		try {

			$arrInput = $request->all();
			$top_up_by = Auth::user()->id;
			$rules = array(
				'user_id_arr'    => 'required',
				'hash_unit' => 'required',
			);
			$adminOtpStatusData = verifyAdminOtpStatus::select('topup_update_status')->first();
			$validator = Validator::make($arrInput, $rules);
			if ($validator->fails()) {
				$message = $validator->errors();
				return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
			} else if ($adminOtpStatusData->topup_update_status == 1) {
				if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
				}


				$arrInput['user_id'] = Auth::User()->id;
				$arrInput['remark'] = 'admin topup';
				$arrInput['otp'] = $request->otp;
				$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
				if (!empty($verify_otp)) {
					if ($verify_otp['status'] == 200) {
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
				}
			}
			$userid_arr = array_filter(array_map('trim',explode(',',$request->user_id_arr)));
			$dups = array();
			$invalid_id_arr = array();
			$valid_id_arr = array();
			$insert_arr_topup = array();
			$amount=$arrInput['hash_unit'];
			foreach(array_count_values($userid_arr) as $val => $c){
				if($c > 1) $dups[] = $val;
			}
			$duplicate_users=implode(',',$dups);
			if (!empty($dups)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Duplicate Users found:'.$duplicate_users, $duplicate_users);
			} else {
				foreach ($userid_arr as $value) {
					$checkUserExist = User::where([['user_id', trim($value)]])->whereNotIn('type', ['Admin','sub-admin'])->whereNotIn('ref_user_id', ['','0'])->select('id', 'user_id', 'fullname','login_allow_status','topup_status')->first();
					if (!empty($checkUserExist)) {
						if (($checkUserExist->topup_status == '0' && $checkUserExist->login_allow_status == '1') || ($checkUserExist->topup_status == '1' && $checkUserExist->login_allow_status == '0')) {

							$product_info = Product::select('package_name','id','package_type','total_income_percentage')->where('min_hash','<=',$amount)->where("max_hash",">=",$amount)->whereNotIn("package_type",["Zero PIN","Zero PIN ROI","Influencer"])->first();
							$package_type =$product_info->package_type;
							$total_income_percentage = $product_info->total_income_percentage;
							$touser = User::select('id', 'user_id', 'virtual_parent_id', 'ref_user_id', 'position','topup_status')->where('id', $checkUserExist->id)->first();
							$invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
							$arrInsert  = [
								'id'         => $checkUserExist->id,
								'pin'        => $invoice_id,
								'amount'     => $amount,
								'amount_roi' => 0,
								'binary_capping' => 0,
								'percentage' => 0,
								'direct_roi' => 0,
								'binary_percentage' => 0,
								'total_income' => ($amount * $total_income_percentage) / 100,
								'type'       => 0,
								'topupfrom'  => '1',
								'duration'	 => 0,
								'product_name' => $product_info->package_name,
								'remark'       => 'TOPUP BY ADMIN',
								'top_up_by'   => $top_up_by,
								'top_up_type' => 2, //admin topup type
								'binary_pass_status' => '1',
								'usd_rate'           => '0',
								'roi_status'         => 'Inactive',
								'roi_stop_status'    => 0,
								'target_business'    => 0,
								'total_usd'          => 0.001,
								'ip_address'         => getIPAddress(),
								'entry_time'         => \Carbon\Carbon::now()->toDateTimeString(),
								'last_roi_entry_time'  => \Carbon\Carbon::now()->toDateTimeString(),
							];
							$storeId = Topup::insertGetId($arrInsert);
							array_push($valid_id_arr,$value);
							if (!empty($storeId)) {

								$price_in_usd = $arrInput['hash_unit'];
								$zero_amount = $price_in_usd;
								$settings      = $this->commonController->getProjectSettings();

								$updateCoinData = array();
								$updateCoinData['total_investment']  = DB::raw("total_investment + ". $zero_amount);
								$updateCoinData['active_investment'] = DB::raw("active_investment + ". $zero_amount);
								$updateCoinData = Dashboard::where('id', $checkUserExist->id)->limit(1)->update($updateCoinData);

								if ($settings->binary_plan == 'on') {

									// update direct business

									$updateLCountArrDirectBusiness               = array();
									$updateLCountArrDirectBusiness['power_l_bv'] = DB::raw('power_l_bv + ' . $zero_amount . '');

									$updateRCountArrDirectBusiness               = array();
									$updateRCountArrDirectBusiness['power_r_bv'] = DB::raw('power_r_bv + ' . $zero_amount . '');

									if ($touser->position == 1) {
										User::where('id', $touser->ref_user_id)->update($updateLCountArrDirectBusiness);
									} else if ($touser->position == 2) {
										User::where('id', $touser->ref_user_id)->update($updateRCountArrDirectBusiness);
									}

									$usertopup = array('amount' => DB::raw('amount + ' . $price_in_usd), 'topup_status' => "1",'login_allow_status'=>'0');
									User::where('id', $touser->id)->update($usertopup);

									/*if($check_topup_count == 0 && !in_array($package_type, ["Zero PIN","Zero PIN ROI"]))
									{
										$ref_usertopup = array('bonus_count' => DB::raw('bonus_count + 1'));
										User::where('id', $touser->ref_user_id)->update($ref_usertopup);
									}*/
									if (!in_array($package_type, ["Zero PIN","Zero PIN ROI","Influencer"])) {

										$getlevel = $this->pay_binary($checkUserExist->id, $price_in_usd, $touser->topup_status);

									}
								}

							} else {
								return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding topup', '');
							}
						} else {
							$message="User ID not applicable for this topup";
							array_push($invalid_id_arr,$value);
						}
					} else {
						$message="Users Not Available";
						array_push($invalid_id_arr,$value);
					}
				}
				$invalid_users=implode(',',$invalid_id_arr);
				$valid_users=implode(',',$valid_id_arr);
				if (!empty($invalid_id_arr)) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message.": ".$invalid_users, $invalid_users);
				} else {

					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Bulk Power topup done successfully', '');
				}

			}

		}
		catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong!', '');
		}
	}

	/**
	 * store topup
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function storeTopup1(Request $request)
	{
		$arrInput = $request->all();

		$rules     = array('id' => 'required', 'product_id' => 'required');
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
		} else {
			$top_up_by = $this->commonController->getLoggedUserData(['remember_token' => $arrInput['remember_token']])->id;
			//$top_up_by = Auth::user()->id;

			$objProduct = $this->commonController->getAllProducts(['id' => $arrInput['product_id']])[0];

			if (!empty($objProduct)) {
				$invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
				$arrInsert  = [
					'id'          => $arrInput['id'],
					'pin'         => $invoice_id,
					'amount'      => $objProduct->cost,
					'percentage'  => $objProduct->roi * $objProduct->duration_month,
					'type'        => $objProduct->id,
					'top_up_by'   => $top_up_by,
					'top_up_type' => 2, //admin topup type
					'entry_time'  => now(),
				];
				$storeId = Topup::insertGetId($arrInsert);

				if (!empty($storeId)) {
					$price_in_usd  = $objProduct->cost;
					$direct_income = $objProduct->direct_income;
					$settings      = $this->commonController->getProjectSettings();
					//update dahboatd value
					$total_investment                    = Dashboard::where('id', $arrInput['id'])->pluck('total_investment')->first();
					$active_investment                   = Dashboard::where('id', $arrInput['id'])->pluck('active_investment')->first();
					$updateCoinData['total_investment']  = custom_round(($total_investment + $price_in_usd), 7);
					$updateCoinData['active_investment'] = custom_round(($active_investment + $price_in_usd), 7);
					$updateCoinData                      = Dashboard::where('id', $arrInput['id'])->limit(1)->update($updateCoinData);

					if ($settings->level_plan == 'on') {
						$getlevel = $this->level->pay_level($arrInput['id'], $price_in_usd, $arrInput['product_id']);
					}

					if ($settings->binary_plan == 'on') {
						$getlevel = $this->level->pay_binary($arrInput['id'], $price_in_usd);
					}

					if ($settings->direct_plan == 'on' && $direct_income > 0) {
						$getlevel = $this->level->pay_direct($arrInput['id'], $price_in_usd, $direct_income, $invoice_id);
					}
					if ($settings->leadership_plan == 'on') {
						$getlevel = $this->level->pay_leadership($arrInput['id'], $price_in_usd, $arrInput['product_id']);
					}

					$actdata['id']         = $arrInput['id'];
					$actdata['message']    = 'Paid for lending wallet id : ' . $invoice_id . ' and amount :' . $price_in_usd . '';
					$actdata['status']     = 1;
					$actdata['entry_time'] = now();
					$actDta                = Activitynotification::insertGetId($actdata);
					if (!empty($actDta)) {
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Topup added successfully', '');
					} else {
						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding activity notification', '');
					}
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding topup', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product details not available', '');
			}
		}
	}
	/**
	 * store topup
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function storeFreeTopup(Request $request)
	{
		$arrInput = $request->all();

		$rules = array(
			'id'         => 'required',
			'product_id' => 'required',
			'hash_unit'  => 'required',
		);
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
		} else {
			$top_up_by = Auth::user()->id;

			$objProduct = $this->commonController->getAllProducts(['id' => $arrInput['product_id']])[0];
			if ($arrInput['hash_unit'] < $objProduct->min_hash && $arrInput['hash_unit'] > $objProduct->max_hash) {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Amount range should be min' . $objProduct->min_hash . 'max' . $objProduct->max_hash, '');
			}

			if (!empty($objProduct)) {
				$invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
				$arrInsert  = [
					'id'     => $arrInput['id'],
					'pin'    => $invoice_id,
					'amount' => $arrInput['hash_unit'],
					//'amount'        => $objProduct->cost * $arrInput['hash_unit'],
					// 'hash_rate'     => $objProduct->hash_rate,
					// 'hash_unit'     => $arrInput['hash_unit'],
					//  'price_unit'    => $objProduct->cost,
					'percentage'        => $objProduct->roi * $objProduct->duration_month,
					'type'              => $objProduct->id,
					'payment_type'      => $arrInput['payment_type'],
					'top_up_by'         => $top_up_by,
					'top_up_type'       => 1, //Free topup type
					'level_pass_status' => 1,
					'old_status'        => '1',
					'entry_time'        => now(),
				];
				$storeId = Topup::insertGetId($arrInsert);

				if (!empty($storeId)) {

					$price_in_usd = $arrInput['hash_unit']; //$objProduct->cost *

					$direct_income = $objProduct->direct_income;
					$settings      = $this->commonController->getProjectSettings();
					//update dahboatd value
					$total_investment                    = Dashboard::where('id', $arrInput['id'])->pluck('total_investment')->first();
					$active_investment                   = Dashboard::where('id', $arrInput['id'])->pluck('active_investment')->first();
					$updateCoinData['total_investment']  = custom_round(($total_investment + $price_in_usd), 7);
					$updateCoinData['active_investment'] = custom_round(($active_investment + $price_in_usd), 7);
					$updateCoinData                      = Dashboard::where('id', $arrInput['id'])->limit(1)->update($updateCoinData);

					$actdata['id']         = $arrInput['id'];
					$actdata['message']    = 'Paid for mining wallet id : ' . $invoice_id . ' and amount :' . $price_in_usd;
					$actdata['status']     = 1;
					$actdata['entry_time'] = now();
					$actDta                = Activitynotification::insertGetId($actdata);

					if (!empty($actDta)) {
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Free Topup done successfully', $invoice_id);
					} else {
						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding activity notification', '');
					}
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding topup', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product details not available', '');
			}
		}
	}
	/**
	 * get topups
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getqualifyranks(Request $request)
	{
		$arrInput = $request->all();

		$query = User::join('tbl_super_matching as td', 'td.user_id', '=', 'tbl_users.id');

		/*		$query = supermatching::select('tu.user_id','tu.fullname','tbl_super_matching.entry_time','tbl_super_matching.pin','tbl_super_matching.rank')
		->join('tbl_users as tu', 'tu.id', '=', 'tbl_super_matching.user_id')
		->where('tbl_super_matching.added_by', '=', 1);*/
		/*dd($query);*/

		if (isset($arrInput['id'])) {
			$query = $query->where('tbl_users.user_id', $arrInput['id']);
		}
		/*if (isset($arrInput['rank'])) {
		$query = $query->where('tbl_users.rank', $arrInput['rank']);
		}*/
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(td.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_super_matching');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('td.' . $field, 'LIKE', '%' . $search . '%');
				}
				// $query->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%')
				// 	->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%');

			});
		}
		if (!empty($arrInput['rank']) && isset($arrInput['rank'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_super_matching');
			$search = $arrInput['rank'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('td.' . $field, 'LIKE', '%' . $search . '%');
				}
				// $query->orWhere('tbl_super_matching.rank', 'LIKE', '%' . $search . '%');
				// 	->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%');

			});
		}

		if (isset($arrInput['mathing_income_status'])) {
			$query = $query->where('td.maching_income_status', $arrInput['mathing_income_status']);
		}
		if (isset($arrInput['club_capping_status'])) {
			$query = $query->where('td.freedom_club_capping_status', $arrInput['club_capping_status']);
		}
		if (isset($arrInput['user_status'])) {
			$query = $query->where('td.user_status', $arrInput['user_status']);
		}
		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->selectRaw('tbl_users.user_id,tbl_users.fullname,td.entry_time,td.rank,td.maching_income_status,(CASE td.freedom_club_capping_status WHEN 0 THEN "Not Achieved" WHEN 1 THEN "Achieved" ELSE "" END) as club_capping_status,td.user_status');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}
		$query       = $query->selectRaw('tbl_users.user_id, tbl_users.fullname, td.entry_time, td.rank, td.maching_income_status, (CASE td.freedom_club_capping_status WHEN 0 THEN "Not Achieved" WHEN 1 THEN "Achieved" ELSE "" END) as club_capping_status,td.user_status');
		$totalRecord = $query->count('td.user_id');
		$query       = $query->orderBy('tbl_users.id', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getAllRank(Request $request)
	{

		$objAdminDetails = Rank::select('rank')->get();

		if (!empty($objAdminDetails)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $objAdminDetails);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 * get topups
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getranks(Request $request)
	{
		$arrInput = $request->all();

		$query = User::select('tbl_users.user_id', 'tbl_users.fullname', 'td.entry_time', 'td.pin', 'td.rank')
			->join('tbl_super_matching as td', 'td.user_id', '=', 'tbl_users.id')
			// ->where('td.added_by', '=', NULL);
			->where('td.added_by', '=', 1);

		/*		$query = supermatching::select('tu.user_id','tu.fullname','tbl_super_matching.entry_time','tbl_super_matching.pin','tbl_super_matching.rank')
		->join('tbl_users as tu', 'tu.id', '=', 'tbl_super_matching.user_id')
		->where('tbl_super_matching.added_by', '=', 1);*/
		/*dd($query);*/

		if (isset($arrInput['id'])) {
			$query = $query->where('tbl_users.user_id', $arrInput['id']);
		}
		/*if (isset($arrInput['rank'])) {
		$query = $query->where('tbl_users.rank', $arrInput['rank']);
		}*/
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(td.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_super_matching');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_super_matching.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%');
			});
		}

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->select('tbl_users.user_id', 'tbl_users.fullname', 'td.pin', 'td.rank', 'td.entry_time');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$totalRecord = $query->count('td.user_id');
		$query       = $query->orderBy('tbl_users.id', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function Structurereport(Request $request)
	{
		//$Checkexist = Auth::User();
		$arrInput = $request->all();
		$userdata = UserStructureModel::select('tbl_user_structure.no_structure', 'tbl_user_structure.amount_topup', 'tu.user_id', 'tbl_user_structure.entry_time')
			->join('tbl_users as tu', 'tu.id', '=', 'tbl_user_structure.user_id');
		/*->where('tbl_user_structure.user_id', '=', $Checkexist->id)*/
		if (isset($arrInput['user_id'])) {
			$userdata = $userdata->where('tu.user_id', $arrInput['user_id']);
		}
		/*if (isset($arrInput['rank'])) {
		$query = $query->where('tbl_users.rank', $arrInput['rank']);
		}*/
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$userdata             = $userdata->whereBetween(DB::raw("DATE_FORMAT(tbl_user_structure.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		$userdata    = $userdata->orderBy('tbl_user_structure.entry_time', 'desc');
		$totalRecord = $userdata->count('tbl_user_structure.user_id');
		//	$totalRecord = $userdata->count();
		$arrPendings = $userdata->skip($request->input('start'))->take($request->input('length'))->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrPendings;
		/*	dd($arrData);*/
		if (!empty($arrPendings) && count($arrPendings) > 0) {
			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data Found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
		} else {
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data not Found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function topupRoiStop(Request $request)
	{
		$arrInput = $request->all();

		$admin = Auth::user();

            $adminaccess = $admin->admin_access;

            if (empty($admin->email)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please set email id', '');
            }

            if($adminaccess == 0 && $admin->type == "Admin")
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You Dont Have Access For This Functionality', '');
            }

		$rules = array(
			'sr_no' => 'required'
			/*'remark' =>'required'*/
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			$objTopup = Topup::select('roi_stop_status','pin','duration','id','roi_status','type')->where('id', $request->sr_no)->first();
			if (!empty($objTopup)) {
				if($objTopup->type == 5){
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Not allowed to change zero pin topup roi status.', '');
				}
				if ($objTopup->roi_stop_status == 1) {
					$roi_stop_status = 0;
				} else {
					$roi_stop_status = 1;
				}
				$msg = ($roi_stop_status == 1) ? 'started' : 'stopped';
				$storeId = TopupLogs::insertGetId([
					'user_id' => $objTopup->id,
					'pin' => $objTopup->pin,
					'roi_last_status' => $objTopup->roi_stop_status,
					'roi_current_status' => $roi_stop_status,
					'entry_time' => \Carbon\Carbon::now()->toDateTimeString()
				]);
				// dd($storeId);
				if (!empty($storeId)) {
					$update = Topup::where('id', $request->sr_no)->update(['roi_stop_status'=>$roi_stop_status]);
					if (!empty($update)) {
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'ROI ' . $msg . ' successfully.', '');

					} else {
						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
					}
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
				}
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Record not found.', '');
			}
		}
	}

	public function topupChangeRoiStop(Request $request)
	{
		$arrInput = $request->all();

		$rules = array(
			'sr_no' => 'required'
			/*'remark' =>'required'*/
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			$objTopup = Topup::select('roi_stop_status','pin','duration','id','roi_status','type')->where('srno', $request->sr_no)->first();
			if (!empty($objTopup)) {
				if($objTopup->type == 5){
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Not allowed to change zero pin topup roi status.', '');
				}
				if ($objTopup->roi_stop_status == 1) {
					$roi_stop_status = 0;
				} else {
					$roi_stop_status = 1;
				}
				$msg = ($roi_stop_status == 1) ? 'started' : 'stopped';
				$storeId = TopupLogs::insertGetId([
					'user_id' => $objTopup->id,
					'pin' => $objTopup->pin,
					'roi_last_status' => $objTopup->roi_stop_status,
					'roi_current_status' => $roi_stop_status,
					'entry_time' => \Carbon\Carbon::now()->toDateTimeString()
				]);
				// dd($storeId);
				if (!empty($storeId)) {
					$update = Topup::where('srno', $request->sr_no)->update(['roi_stop_status'=>$roi_stop_status]);
					if (!empty($update)) {
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'ROI ' . $msg . ' successfully.', '');

					} else {
						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
					}
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
				}
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Record not found.', '');
			}
		}
	}

	public function getTopupReport(){
		return view('admin.Topup.TopUpReport');
	}

	public function getInfluencerTrackReport(){
		return view('admin.Topup.InfluencerTrackingReport');
	}

	public function getTopups(Request $request)
	{
		$arrInput = $request->all();
		$auth = Auth::user()->id;
		if ($auth != 1) {
		$query = Topup::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')->where([['tbl_topup.type','!=','0'],['tbl_topup.top_up_by',$auth]]);
		}else{
			$query = Topup::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')->where([['tbl_topup.type','!=','0']]);
		}
		/*->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup.franchise_id');*/

		//(CASE tbl_topup.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END) as binary_pass_status,(CASE tbl_topup.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END) as level_pass_status,(CASE tbl_topup.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END) as direct_pass_status
		if (isset($arrInput['top_up_type'])) {
			/*(CASE tbl_topup.top_up_type WHEN 0 THEN "Direct Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" ELSE "" END)*/
			$query = $query->where('tbl_topup.top_up_type', $arrInput['top_up_type']);
		}
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		/*if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {

		$fields = getTableColumns('tbl_topup');
		$search = $arrInput['search']['value'];
		$query = $query->where(function ($query) use ($fields, $search) {

		$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
		->orWhere('tu.fullname', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.pin', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.amount', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.percentage', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.percentage', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.payment_type', 'LIKE', '%' . $search . '%')
		->orWhere('tp.name', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.entry_time', 'LIKE', '%' . $search . '%')
		->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.roi_status', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.usd_rate', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.top_up_type WHEN 0 THEN "Direct Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" ELSE "" END)', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END)', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END)', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END)', 'LIKE', '%' . $search . '%');
		});
		}*/
		$totalRecord = $query->count('tbl_topup.id');
		$query       = $query->orderBy('tbl_topup.srno', 'desc');
		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->selectRaw('tu.user_id,tbl_topup.topupfrom,tu.fullname,tbl_topup.pin as deposite_id,tbl_topup.amount,tp.name as product,tu1.user_id as topup_by,tbl_topup.payment_type,tbl_topup.entry_time');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}
		$totalRecord = $query->count();
		$query       = $query->selectRaw('tbl_topup.srno,tbl_topup.roi_stop_status,tbl_topup.topupfrom,tu.user_id,tu.fullname,tbl_topup.pin,tbl_topup.amount,tp.name,tp.package_type,tbl_topup.entry_time,tu1.user_id as top_up_by,(CASE tbl_topup.top_up_type WHEN 0 THEN "BTC Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" WHEN 3 THEN "Self Topup" ELSE "" END) as top_up_type');
		// $arrTopup    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
		$arrTopup    = $query->get();
		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getBulkTopups(Request $request)
	{
		$arrInput = $request->all();

		$query = Topup::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')->where('tbl_topup.type','0');

		if (isset($arrInput['top_up_type'])) {

			$query = $query->where('tbl_topup.top_up_type', $arrInput['top_up_type']);
		}
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}

		$totalRecord = $query->count('tbl_topup.id');
		$query       = $query->orderBy('tbl_topup.srno', 'desc');
		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->selectRaw('tu.user_id,tbl_topup.topupfrom,tu.fullname,tbl_topup.pin as deposite_id,tbl_topup.amount,tbl_topup.product_name as product,tu1.user_id as topup_by,tbl_topup.payment_type,tbl_topup.entry_time');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}
		$totalRecord = $query->count();
		$query       = $query->selectRaw('tbl_topup.srno,tbl_topup.roi_stop_status,tu.login_allow_status,tbl_topup.topupfrom,tu.user_id,tu.fullname,tbl_topup.pin,tbl_topup.amount,tbl_topup.product_name,tbl_topup.entry_time,tu1.user_id as top_up_by,(CASE tbl_topup.top_up_type WHEN 0 THEN "BTC Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" WHEN 3 THEN "Self Topup" ELSE "" END) as top_up_type');
		$arrTopup    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

    Public function AddInfluencerTopupBlade(Request $request){

        $request->replace(['status' => 'Active']);
        $product_list = Product::select('id', 'package_name as name', 'cost', 'direct_income', 'roi', 'duration', 'entry_time', 'min_hash', 'max_hash', 'binary', 'capping')->where([['status', $request->status], ['admin_show_status', 1]])->orderBy('id', 'DESC')->skip(0)->take(1)->get();
        return view('admin.Topup.Add-influencer')->with(compact('product_list'));

    }

	public function getInfluencerTopups(Request $request)
	{
		$arrInput = $request->all();
		$auth = Auth::user()->id;
		if ($auth != 1) {
		$query = Topup::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')->where([['tbl_topup.type','=', '7'],['tbl_topup.top_up_by',$auth]]);
		}else{
			$query = Topup::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')->where('tbl_topup.type','=', '7');
		}
		/*->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup.franchise_id');*/

		//(CASE tbl_topup.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END) as binary_pass_status,(CASE tbl_topup.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END) as level_pass_status,(CASE tbl_topup.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END) as direct_pass_status
		if (isset($arrInput['top_up_type'])) {
			/*(CASE tbl_topup.top_up_type WHEN 0 THEN "Direct Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" ELSE "" END)*/
			$query = $query->where('tbl_topup.top_up_type', $arrInput['top_up_type']);
		}
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		/*if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {

		$fields = getTableColumns('tbl_topup');
		$search = $arrInput['search']['value'];
		$query = $query->where(function ($query) use ($fields, $search) {

		$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
		->orWhere('tu.fullname', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.pin', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.amount', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.percentage', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.percentage', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.payment_type', 'LIKE', '%' . $search . '%')
		->orWhere('tp.name', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.entry_time', 'LIKE', '%' . $search . '%')
		->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.roi_status', 'LIKE', '%' . $search . '%')
		->orWhere('tbl_topup.usd_rate', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.top_up_type WHEN 0 THEN "Direct Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" ELSE "" END)', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END)', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END)', 'LIKE', '%' . $search . '%')
		->orWhereRaw('(CASE tbl_topup.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END)', 'LIKE', '%' . $search . '%');
		});
		}*/
		$totalRecord = $query->count('tbl_topup.id');
		$query       = $query->orderBy('tbl_topup.srno', 'desc');
		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->selectRaw('tu.user_id,tbl_topup.topupfrom,tu.fullname,tbl_topup.pin as deposite_id,tbl_topup.amount,tp.name as product,tu1.user_id as topup_by,tbl_topup.payment_type,tbl_topup.entry_time');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}
		$totalRecord = $query->count();
		$query       = $query->selectRaw('tbl_topup.srno,tbl_topup.roi_stop_status,tbl_topup.topupfrom,tu.user_id,tu.fullname,tbl_topup.pin,tbl_topup.amount,tp.name,tp.package_type,tbl_topup.entry_time,tu1.user_id as top_up_by,(CASE tbl_topup.top_up_type WHEN 0 THEN "BTC Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" WHEN 3 THEN "Self Topup" ELSE "" END) as top_up_type');
		$arrTopup    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function getInfluencerTrackingReport(Request $request)
	{
		$arrInput = $request->all();
		$auth = Auth::user()->id;

		$adminaccess = Auth::user()->admin_access;
		$admintype = Auth::user()->type;
		
		if ($auth != 1 && $admintype != "Admin") {
			$query = Topup::select('tu.user_id','tu.id','tu.ref_user_id', 'tbl_topup.amount', 'tbl_topup.entry_time',
			DB::raw('(select count(*) as total_directs from tbl_users as tu2 where tu2.ref_user_id = tbl_topup.id) as total_directs'),
			DB::raw('(select sum(tu.l_guardian + tu.r_guardian) as total_investment from tbl_users as tu where tu.id = tbl_topup.id) as total_investment'),
			DB::raw('(select sum(td2.roi_wallet_withdraw + td2.working_wallet_withdraw + td2.hscc_bonus_wallet_withdraw) as total_withdrawal from tbl_dashboard as td2 where td2.id = tbl_topup.id) as total_withdrawal'),
			DB::raw('(select count(twp.sr_no) as withdrawal_count from tbl_withdrwal_pending as twp where twp.id = tbl_topup.id) as withdrawal_count'),
			'tu.roi_withdraw_status',
			'tbl_topup.target_business' ,'td.direct_income', 'td.binary_income','td.hscc_bonus_income','tbl_topup.roi_stop_status',
			'tu.fullname')
			->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')
			->join('tbl_dashboard as td', 'td.id', '=', 'tbl_topup.id')
			->where([['tbl_topup.type','=', '7'],['tbl_topup.top_up_by',$auth]]);
		}else{
		/*$sub = Abc::where('tu2.ref_user_id','tbl_topup.id')->groupBy('tu2.ref_user_id')->->count('tu2.id');*/
		$query = Topup::select('tu.user_id','tu.id','tu.ref_user_id', 'tbl_topup.amount', 'tbl_topup.entry_time',
			DB::raw('(select count(*) as total_directs from tbl_users as tu2 where tu2.ref_user_id = tbl_topup.id) as total_directs'),
			DB::raw('(select sum(tu.l_guardian + tu.r_guardian) as total_investment from tbl_users as tu where tu.id = tbl_topup.id) as total_investment'),
			DB::raw('(select sum(td2.roi_wallet_withdraw + td2.working_wallet_withdraw + td2.hscc_bonus_wallet_withdraw) as total_withdrawal from tbl_dashboard as td2 where td2.id = tbl_topup.id) as total_withdrawal'),
			DB::raw('(select count(twp.sr_no) as withdrawal_count from tbl_withdrwal_pending as twp where twp.id = tbl_topup.id) as withdrawal_count'),
			/*DB::raw('(CASE WHEN tu.topup_status = "0" OR tu.capping_withdrawal_status = "Inactive" THEN "Block" ELSE "Unblock" END) as withdrawal_status'),*/
			'tu.roi_withdraw_status',
			'tbl_topup.target_business' ,'td.direct_income', 'td.binary_income','td.hscc_bonus_income','tbl_topup.roi_stop_status',
			'tu.fullname')
			->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			// ->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')
			->join('tbl_dashboard as td', 'td.id', '=', 'tbl_topup.id')
			->where('tbl_topup.type','=', '7');
		/*->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup.franchise_id');*/

		//(CASE tbl_topup.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END) as binary_pass_status,(CASE tbl_topup.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END) as level_pass_status,(CASE tbl_topup.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END) as direct_pass_status
	}
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}

		$totalRecord = $query->count('tbl_topup.id');
		$query       = $query->orderBy('tbl_topup.srno', 'desc');
		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->select('tu.user_id', 'tbl_topup.amount', 'tbl_topup.entry_time','tbl_topup.target_business' ,
			DB::raw('(select count(*) as total_directs from tbl_users as tu2 where tu2.ref_user_id = tbl_topup.id) as total_directs'),
			DB::raw('(select sum(tu.l_guardian + tu.r_guardian) as total_investment from tbl_users as tu where tu.id = tbl_topup.id) as total_investment'),
			'td.direct_income', 'td.binary_income','td.hscc_bonus_income',
			DB::raw('(select sum(td2.roi_wallet_withdraw + td2.working_wallet_withdraw + td2.hscc_bonus_wallet_withdraw) as total_withdrawal from tbl_dashboard as td2 where td2.id = tbl_topup.id) as total_withdrawal'),
			DB::raw('(select count(twp.sr_no) as withdrawal_count from tbl_withdrwal_pending as twp where twp.id = tbl_topup.id) as withdrawal_count'),
			/*DB::raw('(CASE WHEN tu.topup_status = "0" OR tu.capping_withdrawal_status = "Inactive" THEN "Block" ELSE "Unblock" END) as withdrawal_status'), */
			'tu.roi_withdraw_status',
			'tbl_topup.roi_stop_status');
			/*->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup.top_up_by')
			->join('tbl_dashboard as td', 'td.id', '=', 'tbl_topup.id')
			->where('tbl_topup.type','=', '7');*/
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}
		$totalRecord = $query->count();

		$arrTopup    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function getTopupsRequest(Request $request)
	{
		$arrInput = $request->all();
		//$url = 'public/uploads/files';
		$url   = config::get('constants.settings.aws_url');
		$query = TopupRequest::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup_request.id')
			//->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup_request.type')//tp.name,
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup_request.top_up_by')
			->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup_request.franchise_id')
			->selectRaw('tbl_topup_request.*,tbl_topup_request.srno,tbl_topup_request.franchise_id,tbl_topup_request.withdraw,tbl_topup_request.payment_type,tu.user_id,tu.fullname,tbl_topup_request.pin,tbl_topup_request.amount,tbl_topup_request.percentage,tbl_topup_request.entry_time,tu1.user_id as top_up_by,tbl_topup_request.roi_status,tu2.user_id as franchise_user_id,(CASE tbl_topup_request.top_up_type WHEN 0 THEN "BTC Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" WHEN 3 THEN "Self Topup" ELSE "" END) as top_up_type,tbl_topup_request.usd_rate,(CASE tbl_topup_request.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END) as binary_pass_status,(CASE tbl_topup_request.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END) as level_pass_status,(CASE tbl_topup_request.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END) as direct_pass_status,admin_status,IF(tbl_topup_request.attachment IS NOT NULL,CONCAT("' . $url . '",tbl_topup_request.attachment),NULL) attachment');
		if (isset($arrInput['top_up_type'])) {
			/*(CASE tbl_topup_request.top_up_type WHEN 0 THEN "Direct Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" ELSE "" END)*/
			$query = $query->where('tbl_topup_request.top_up_type', $arrInput['top_up_type']);
		}

		if (isset($arrInput['status'])) {
			/*(CASE tbl_topup_request.top_up_type WHEN 0 THEN "Direct Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" ELSE "" END)*/
			$query = $query->where('tbl_topup_request.admin_status', $arrInput['status']);
		}

		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup_request.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_topup_request');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				/*foreach($fields as $field){
					$query->orWhere('tbl_topup_request.'.$field,'LIKE','%'.$search.'%');
					 */
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tu.fullname', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.pin', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.amount', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.percentage', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.percentage', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.payment_type', 'LIKE', '%' . $search . '%')
					->orWhere('tp.name', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.entry_time', 'LIKE', '%' . $search . '%')
					->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.roi_status', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_topup_request.usd_rate', 'LIKE', '%' . $search . '%')
					->orWhereRaw('(CASE tbl_topup_request.top_up_type WHEN 0 THEN "Direct Topup" WHEN 1 THEN "Free Topup" WHEN 2 THEN "Admin Topup" ELSE "" END)', 'LIKE', '%' . $search . '%')
					->orWhereRaw('(CASE tbl_topup_request.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END)', 'LIKE', '%' . $search . '%')
					->orWhereRaw('(CASE tbl_topup_request.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END)', 'LIKE', '%' . $search . '%')
					->orWhereRaw('(CASE tbl_topup_request.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END)', 'LIKE', '%' . $search . '%');
			});
		}
		$query       = $query->orderBy('tbl_topup_request.srno', 'desc');
		$totalRecord = $query->count();
		$arrTopup    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 * get topups
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getUnpaidTopups(Request $request)
	{
		$arrInput = $request->all();

		$query = User::selectRaw('tbl_users.id,tbl_users.user_id,tbl_users.fullname,COALESCE(tt.amount,0) as amount,tbl_users.entry_time')
			->leftjoin('tbl_topup as tt', 'tt.id', '=', 'tbl_users.id')
			->where('tt.id', '=', null)
			->where('tbl_users.type', '=', '');

		if (isset($arrInput['id'])) {
			$query = $query->where('tbl_users.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tt.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = ['tbl_users.user_id', 'tbl_users.fullname', 'tt.amount'];
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere($field, 'LIKE', '%' . $search . '%');
				}
			});
		}
		$totalRecord = $query->count('tbl_users.id');
		$query       = $query->orderBy('tbl_users.id', 'desc');
		// $totalRecord = $query->count();
		$arrTopup = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 * get Products report
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getProducts(Request $request)
	{
		$arrInput = $request->all();
		$query = Product::select('id', 'package_name as name', 'cost', 'direct_income', 'roi', 'duration', 'entry_time', 'min_hash', 'max_hash', 'binary', 'capping')->where([['status', $arrInput['status']], ['admin_show_status', 1]]);
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
		}

		if (isset($arrInput['id'])) {
			$query = $query->where(['id', $arrInput['id']]);
		}

		if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = ['name', 'cost', 'direct_income', 'roi', 'duration'];
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere($field, 'LIKE', '%' . $search . '%');
				}
			});
		}
		$query = $query->orderBy('id', 'asc');
		if (isset($arrInput['start']) && isset($arrInput['length'])) {
			$arrData = setPaginate($query, $arrInput['start'], $arrInput['length']);
		} else {
			$arrData = $query->get();
		}

		if ((isset($arrData['totalRecord']) > 0) || (count($arrData) > 0)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 * add new product
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function storeProduct(Request $request)
	{
		$rules = array(
			'name'          => 'required',
			'cost'          => 'required',
			'direct_income' => 'required',
			'roi'           => 'required',
			'duration'      => 'required',
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			$countProduct = Product::where('name', $request->name)->count();
			if ($countProduct > 0) {
				return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Product already exited.', '');
			}
			$arrInsert = [
				'name'          => $request->name,
				'cost'          => $request->cost,
				'direct_income' => $request->direct_income,
				'roi'           => $request->roi,
				'duration'      => $request->duration,
				'entry_time'    => now(),
			];
			$storeId = Product::insertGetId($arrInsert);

			if ($storeId) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Product added successfully', '');
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
			}
		}
	}
	/**
	 * edit product
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function editProduct(Request $request)
	{
		$rules = array(
			'product_id' => 'required',
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			$objProduct = Product::select('id', 'name', 'cost', 'direct_income', 'roi', 'duration', 'entry_time')->where('id', $request->product_id)->first();

			if (count($objProduct) > 0) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found.', $objProduct);
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Record not found.', '');
			}
		}
	}
	/**
	 * update product
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function updateProduct(Request $request)
	{
		$rules = array(
			'product_id'    => 'required',
			'name'          => 'required',
			'cost'          => 'required',
			'direct_income' => 'required',
			'roi'           => 'required',
			'duration'      => 'required',
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			/*$countProduct = Product::where('name',$request->name)->count();
			if($countProduct > 0){
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Product already exited.', '');
			 */
			$arrInsert = [
				'name'          => $request->name,
				'cost'          => $request->cost,
				'direct_income' => $request->direct_income,
				'roi'           => $request->roi,
				'duration'      => $request->duration,
				'entry_time'    => now(),
			];
			$update = Product::where('id', $request->product_id)->update($arrInsert);

			if ($update) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Product updated successfully', '');
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
			}
		}
	}
	/**
	 * delete product
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function deleteProduct(Request $request)
	{
		$rules = array(
			'product_id' => 'required',
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			$update = Product::where('id', $request->product_id)->update(['status' => 'Deleted']);
			if ($update) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Product deleted successfully', '');
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
			}
		}
	}


	

	Public function invalidLoginPage(Request $request){
		return view('admin.SecurityValidations.index');
	}

	public function getLoginCountUsersDetails(Request $request) {
		try {

			$arrInput = $request->all();
			

			$query = AddFailedLoginAttempt::select('user_id','ip_address','remark','attempted_at','status');

			
			if (isset($arrInput['id'])) {
				$query = $query->where('user_id', $arrInput['id']);
				
			}
			if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
				$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
				$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
				$query = $query->whereBetween("DATE_FORMAT(tbl_user_failed_logins.attempted_at,'%Y-%m-%d')", [$arrInput['frm_date'], $arrInput['to_date']]);
			}
			

			$totalRecord = $query->count('id');
			$query = $query->orderBy('id', 'desc');
			if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
				$qry = $query;
				$qry = $qry->select('tbl_user_failed_logins.user_id','tbl_user_failed_logins.ip_address','tbl_user_failed_logins.remark',DB::raw('(CASE tbl_user_failed_logins.status WHEN 0 THEN "Unblock" WHEN 1 THEN "-"  END) as action'),'tbl_user_failed_logins.attempted_at');
				$records = $qry->get();
				$res = $records->toArray();
				if (count($res) <= 0) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
				}
				$var = $this->commonController->exportToExcel($res,"AllUsers");
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
			}


			$totalRecord = $query->count();
			/*$query = $query->select('tbl_user_failed_logins.user_id','tbl_user_failed_logins.ip_address','tbl_user_failed_logins.remark','tbl_user_failed_logins.attempted_at');*/
			$arrTopup = $query->skip($arrInput['start'])->take($arrInput['length'])->get();


			$arrData['recordsTotal'] = $totalRecord;
			$arrData['recordsFiltered'] = $totalRecord;
			$arrData['records'] = $arrTopup;

			if ($arrData['recordsTotal'] > 0) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
			}
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong', '');
		}
	}

	/**
	 * update topup status (Stop/Start ROI of user by admin)
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function updateTopupStatus(Request $request)
	{
		$rules = array(
			'srno'       => 'required',
			'roi_status' => 'required',
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			$objTopup = Topup::where('srno', $request->srno)->first();
			if (!empty($objTopup)) {
				$storeId = TopupLogs::insertGetId([
					'id'                 => $objTopup->id,
					'pin'                => $objTopup->pin,
					'roi_last_status'    => $objTopup->roi_status,
					'roi_current_status' => $request->roi_status,
					'entry_time'         => now(),
				]);
				if (!empty($storeId)) {
					$update = Topup::where('srno', $request->srno)->limit(1)->update([
						'roi_status' => $request->roi_status,
					]);
					$msg = ($request->roi_status == 'Active') ? 'started' : 'stopped';
					if (!empty($update)) {
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'ROI ' . $msg . ' successfully.', '');
					} else {
						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
					}
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
				}
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Record not found.', '');
			}
		}
	}
	/**
	 * get topup logs
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getTopupLogs(Request $request)
	{
		$arrInput = $request->all();

		$query = TopupLogs::select('tbl_topup_logs.srno', 'tbl_topup_logs.pin', 'tbl_topup_logs.roi_current_status', 'tbl_topup_logs.roi_last_status', 'tu.user_id', 'tu.fullname', 'tbl_topup_logs.entry_time')->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup_logs.id');
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
		}
		if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = ['tbl_topup_logs.pin', 'tbl_topup_logs.current_status', 'tbl_topup_logs.last_status', 'tu.user_id', 'tu.fullname'];
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere($field, 'LIKE', '%' . $search . '%');
				}
			});
		}
		$totalRecord = $query->count('tbl_topup_logs.srno');
		$query       = $query->orderBy('tbl_topup_logs.srno', 'desc');
		// $totalRecord = $query->count();
		$arrProducts = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrProducts;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 * store topup
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function approveFundRequest(Request $request)
	{
		$arrInput = $request->all();
		$status   = $this->storeTopup($request);
		/*echo "<pre>";
		print_r($status);
		exit();
		 */
		if ($status->original['code'] == 200) {
			$fundreq               = FundRequest::find($arrInput['f_id']);
			$fundreq->status       = 'Approve';
			$fundreq->admin_remark = $request->remark;
			$fundreq->invoice_id   = $status->original['data'];
			$fundreq->approve_date = now();
			$fundreq->update();
		}
		return $status;
	}

	public function approveTopupRequest(Request $request)
	{
		$arrInput = $request->all();

		//$status = $this->storeTopup($request);
		/*echo "<pre>";
		print_r($status);
		exit();
		 */

		//dd($arrInput['id']);
		try {
			$user_id = $arrInput['id'];

			$top_up_wallet = Dashboard::where([['id', '=', $user_id]])->pluck('top_up_wallet')->first();

			$updateData['top_up_wallet'] = custom_round($top_up_wallet + $arrInput['hash_unit'], 7);

			$updateOtpSta = Dashboard::where('id', $user_id)->limit(1)->update($updateData);
			TopupRequest::where('srno', $arrInput['srno'])->limit(1)->update(['admin_status' => 'approve', 'approve_date' => \Carbon\Carbon::now()]);

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Fund successfully added', '');
		} catch (Exception $e) {

			return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'server error', '');
		}
		/*if ($status->original['code'] == 200) {
	// dd($arrInput['srno']);
	//  $fundreq = TopupRequest::find($arrInput['srno']);
	//  $fundreq->admin_status = 'approve';
	//  //$fundreq->admin_remark = $request->remark;
	// //$fundreq->invoice_id = $status->original['data'];
	//  $fundreq->approve_date = \Carbon\Carbon::now();
	//  $fundreq->update();
	TopupRequest::where('srno', $arrInput['srno'])->limit(1)->update(['admin_status' => 'approve', 'approve_date' => \Carbon\Carbon::now()]);
	}
	return $status;
	 */
	}

	public function rejectTopupRequest(Request $request)
	{
		$arrInput = $request->all();
		// $status = $this->storeTopup($request);
		/*echo "<pre>";
		print_r($status);
		exit();
		 */

		TopupRequest::where('srno', $arrInput['id'])->limit(1)->update(['admin_status' => 'reject', 'reject_date' => \Carbon\Carbon::now()]);
		//}
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Request rejected !!!!', '');
	}

	/**
	 * get topups
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function oldgetTopups(Request $request)
	{
		$arrInput = $request->all();

		$query = DB::table('tbl_topup_old as tto')
			->join('tbl_users as tu', 'tu.id', '=', 'tto.id')
			/*->join('tbl_product as tp','tp.id','=','tbl_topup.type')*/
			/*->join('tbl_users as tu1','tu1.id','=','tbl_topup.top_up_by')*/
			->selectRaw('tto.amount,tto.withdraw,tu.user_id,tu.fullname,tu.email,tu.mobile');

		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}

		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tto.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}

		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tto');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				/*foreach($fields as $field){
					$query->orWhere('tbl_topup.'.$field,'LIKE','%'.$search.'%');
					 */
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tu.fullname', 'LIKE', '%' . $search . '%')
					->orWhere('tto.amount', 'LIKE', '%' . $search . '%')
					->orWhere('tto.withdraw', 'LIKE', '%' . $search . '%')
					->orWhere('tu.mobile', 'LIKE', '%' . $search . '%')
					->orWhere('tu.email', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tto.srno');
		$query       = $query->orderBy('tto.srno', 'desc');
		// $totalRecord = $query->count();
		$arrTopup = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function RankPowerReport(Request $request)
	{
		$arrInput = $request->all();

		$query = User::select('tbl_users.user_id', 'tbl_users.fullname', 'td.entry_time', 'td.position', 'td.type', 'td.power_bv', 'td.before_bv', 'td.after_bv', 'td.rank')
			->join('tbl_add_remove_rank_business as td', 'td.user_id', '=', 'tbl_users.id');
		if (isset($arrInput['id'])) {
			$query = $query->where('tbl_users.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(td.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_add_remove_rank_business.*');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('td.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%');
			});
		}

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->select('tbl_users.user_id', 'tbl_users.fullname', 'td.rank', 'td.position', 'td.before_bv', 'td.power_bv', 'td.after_bv', 'td.entry_time');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$totalRecord = $query->count('td.user_id');
		$query       = $query->orderBy('td.entry_time', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function RankPowerUplineReport(Request $request)
	{
		$arrInput = $request->all();

		$query = User::select('tbl_users.user_id', 'tbl_users.fullname', 'td.entry_time', 'td.position', 'td.type', 'td.power_bv', 'td.before_bv', 'td.after_bv', 'td.rank')
			->join('tbl_add_remove_rank_business_upline as td', 'td.user_id', '=', 'tbl_users.id');
		if (isset($arrInput['id'])) {
			$query = $query->where('tbl_users.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(td.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_add_remove_rank_business_upline.*');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('td.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%');
			});
		}

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->select('tbl_users.user_id', 'tbl_users.fullname', 'td.rank', 'td.position', 'td.before_bv', 'td.power_bv', 'td.after_bv', 'td.entry_time');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$totalRecord = $query->count('td.user_id');
		$query       = $query->orderBy('td.entry_time', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function BussinessUplineReport(Request $request)
	{
		$arrInput = $request->all();

		$query = User::select('tbl_users.user_id', 'tbl_users.fullname', 'td.entry_time', 'td.position', 'td.bussiness_bv', 'td.before_bv', 'td.after_bv')
			->join('tbl_add_remove_business_upline as td', 'td.user_id', '=', 'tbl_users.id');
		if (isset($arrInput['id'])) {
			$query = $query->where('tbl_users.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(td.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_add_remove_business_upline.*');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('td.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%');
			});
		}

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry     = $query;
			$qry     = $qry->select('tbl_users.user_id', 'tbl_users.fullname', 'td.position', 'td.before_bv', 'td.bussiness_bv', 'td.after_bv', 'td.entry_time');
			$records = $qry->get();
			$res     = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$totalRecord = $query->count('td.user_id');
		$query       = $query->orderBy('td.entry_time', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
}
