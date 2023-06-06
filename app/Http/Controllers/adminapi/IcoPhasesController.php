<?php

namespace App\Http\Controllers\adminapi;

use App\Http\Controllers\Controller;
use App\Models\AllTransaction;
use App\Models\CoinTransactionLog;
use App\Models\Dashboard;
use App\Models\IcoPhases;
use App\Models\ProjectSettings;
use App\Models\TodaySummary;
use App\Models\TopupIco;

use Auth;

use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;

class IcoPhasesController extends Controller {
	/**
	 * define property variable
	 *
	 * @return
	 */
	public $statuscode;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->statuscode = Config::get('constants.statuscode');
	}

	/**
	 * get all ico phases
	 *
	 * @return void
	 */
	public function getIcoAdminBuyRep(Request $request) {
		$arrInput = $request->all();
		try {
			$Checkexist = Auth::User();// check use is active or not
			if (!empty($Checkexist)) {

				$topupReport = AllTransaction::select('tbl_all_transaction.coin', 'tbl_all_transaction.debit', 'tbl_all_transaction.credit', 'tbl_all_transaction.entry_time', 'tbl_all_transaction.phases_id',
					'tbl_all_transaction.remarks', 'tu.user_id', 'tu.fullname', 'ph.name as phase_name')
					->join('tbl_users as tu', 'tu.id', '=', 'tbl_all_transaction.id')
					->join('tbl_phases as ph', 'ph.srno', '=', 'tbl_all_transaction.phases_id')
				;
				// if($arrInput['frompage']=='admin'){

				$topupReport->where('tbl_all_transaction.id', '=', 1);
				//}

				$topupReport->where('tbl_all_transaction.type', '=', 'Buy Coin')
				            ->orderBy('tbl_all_transaction.entry_time', 'desc');

				/*if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
				//searching loops on fields
				$fields = getTableColumns('tbl_all_transaction');
				$search = $request->input('search')['value'];
				$topupReport = $topupReport->where(function ($topupReport) use ($fields, $search) {
				foreach ($fields as $field) {
				$topupReport->orWhere('tbl_all_transaction.' . $field, 'LIKE', '%' . $search . '%');
				}
				// $topupReport->orWhere('tp.name', 'LIKE', '%' . $search . '%')
				// ->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
				});
				}*/

				if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
					$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
					$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
					$topupReport          = $topupReport->whereBetween(DB::raw("DATE_FORMAT(tbl_all_transaction.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
				}
				$totalRecord = $topupReport->count();
				$arrPendings = $topupReport->skip($arrInput['start'])->take($arrInput['length'])->get();

				//150 days 1 % daily
				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrPendings;
				//$arrData['Totalrecords1']   = count($arrPendings);
				if (count($arrPendings) > 0) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
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

	}/**
	 * get all ico phases
	 *
	 * @return void
	 */
	public function getIcoBuyRep(Request $request) {
		$arrInput = $request->all();
		try {
			$Checkexist = Auth::User();// check use is active or not
			if (!empty($Checkexist)) {

				$topupReport = AllTransaction::select('tbl_all_transaction.coin', 'tbl_all_transaction.debit', 'tbl_all_transaction.credit', 'tbl_all_transaction.entry_time', 'tbl_all_transaction.phases_id',
					'tbl_all_transaction.remarks', 'tu.user_id', 'tu.fullname')
					->join('tbl_users as tu', 'tu.id', '=', 'tbl_all_transaction.id');
				if ($arrInput['frompage'] == 'admin') {

					$topupReport->where('tbl_all_transaction.refference', '=', 1);
				}

				$topupReport->where('tbl_all_transaction.type', '=', 'Buy Coin')
				            ->orderBy('tbl_all_transaction.entry_time', 'desc');

				/*if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
				//searching loops on fields
				$fields = getTableColumns('tbl_all_transaction');
				$search = $request->input('search')['value'];
				$topupReport = $topupReport->where(function ($topupReport) use ($fields, $search) {
				foreach ($fields as $field) {
				$topupReport->orWhere('tbl_all_transaction.' . $field, 'LIKE', '%' . $search . '%');
				}
				// $topupReport->orWhere('tp.name', 'LIKE', '%' . $search . '%')
				// ->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
				});
				}*/

				if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
					$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
					$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
					$topupReport          = $topupReport->whereBetween(DB::raw("DATE_FORMAT(tbl_all_transaction.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
				}

				if (!empty($arrInput['id']) && isset($arrInput['id'])) {
					$search = $arrInput['id'];

					$topupReport->where('tu.user_id', 'LIKE', '%'.$search.'%');
				}
				$totalRecord = $topupReport->count();
				$arrPendings = $topupReport->skip($arrInput['start'])->take($arrInput['length'])->get();

				//150 days 1 % daily
				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrPendings;
				//$arrData['Totalrecords1']   = count($arrPendings);
				if (count($arrPendings) > 0) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
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

	/* get all ico phases
	 *
	 * @return void
	 */
	public function getAdminSendCoinRep(Request $request) {
		$arrInput = $request->all();
		try {
			$Checkexist = Auth::User();// check use is active or not
			if (!empty($Checkexist)) {

				$topupReport = DB::table('tbl_coin_transaction_log as ct')->select('ct.coin', 'tu.user_id', 'tu.fullname', 'ct.entry_time')
				                                                          ->join('tbl_users as tu', 'tu.id', '=', 'ct.to_id');

				$topupReport->where('ct.from_id', '=', 1);

				$topupReport->where('ct.trans_type', '=', '1')
				            ->orderBy('ct.entry_time', 'desc');

				/*if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
				//searching loops on fields
				$fields = getTableColumns('tbl_all_transaction');
				$search = $request->input('search')['value'];
				$topupReport = $topupReport->where(function ($topupReport) use ($fields, $search) {
				foreach ($fields as $field) {
				$topupReport->orWhere('tbl_all_transaction.' . $field, 'LIKE', '%' . $search . '%');
				}

				});
				}*/
				if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
					$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
					$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
					$topupReport          = $topupReport->whereBetween(DB::raw("DATE_FORMAT(ct.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
				}
				//  dd($arrInput['id']);
				if (!empty($arrInput['id']) && isset($arrInput['id'])) {
					$search = $arrInput['id'];

					$topupReport->where('tu.user_id', 'LIKE', '%'.$search.'%');
				}
				$totalRecord = $topupReport->count();
				$arrPendings = $topupReport->skip($arrInput['start'])->take($arrInput['length'])->get();

				//150 days 1 % daily
				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrPendings;
				//$arrData['Totalrecords1']   = count($arrPendings);
				if (count($arrPendings) > 0) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
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

	}/* get all ico phases
	 *
	 * @return void
	 */
	public function getUserToUserSendCoinRep(Request $request) {
		$arrInput = $request->all();
		try {
			$Checkexist = Auth::User();// check use is active or not
			if (!empty($Checkexist)) {

				$topupReport = CoinTransactionLog::select('*');

				$topupReport->where('tbl_coin_transaction_log.trans_type', '=', '1')
				            ->orderBy('tbl_coin_transaction_log.entry_time', 'desc');

				if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
					$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
					$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
					$topupReport          = $topupReport->whereBetween(DB::raw("DATE_FORMAT(tbl_coin_transaction_log.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
				}
				//  dd($arrInput['id']);
				if (!empty($arrInput['id']) && isset($arrInput['id'])) {
					$search = $arrInput['id'];

					$topupReport->Where('tbl_coin_transaction_log.to_user_id', 'LIKE', '%'.$search.'%');
					//$topupReport->orWhere('tbl_coin_transaction_log.from_user_id', 'LIKE', '%' . $search . '%');
				}
				$totalRecord = $topupReport->count();
				$arrPendings = $topupReport->skip($arrInput['start'])->take($arrInput['length'])->get();

				//150 days 1 % daily
				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrPendings;
				//$arrData['Totalrecords1']   = count($arrPendings);
				if (count($arrPendings) > 0) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
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

	}/**
	 * get all ico phases
	 *
	 * @return void
	 */
	public function getIcoPhasesLive(Request $request) {
		$arrInput = $request->all();

		$phases = IcoPhases::select('srno', 'name', 'from_date', 'to_date', 'total_supply', 'sold_supply', 'usd_rate', 'usd_rate', 'status', 'min_coin')->where('status', '=', 'Available')
		                                                                                                                                                ->get();

		foreach ($phases as $phase) {
			$status = $phase->status;

			if ($phase->sold_supply >= $phase->total_supply) {

				$updateArr = array("status" => "SoldOut");
				IcoPhases::where('srno', '=', $phase->srno)->update($updateArr);

				$updateArr = array("status" => "Available");
				IcoPhases::where('srno', '=', $phase->srno+1)->update($updateArr);

				$status = 'SoldOut';

			}

		}

		$arrData = IcoPhases::select('srno', 'name', 'from_date', 'to_date', 'total_supply', 'sold_supply', 'usd_rate', 'usd_rate', 'status', 'min_coin')
			->where('status', '=', 'Available')

			->get();

		if (count($arrData) > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData[0]);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	//----------   purchase coin  -------------------------------

	public function purchaseCoinAdmin(Request $request) {
		$rules = array(
			//'remember_token' => 'required',
			// 'btc'=>'required',
			// 'usd'=>'required',
			'coin' => 'required',
			'srno' => 'required|int',
		);

		// checkOtherDevice($temp_info);
		$proSettings = ProjectSettings::select('ico_status', 'ico_admin_error_msg')->first();

		if ($proSettings->ico_status == 'off') {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $proSettings->ico_admin_error_msg, '');

		}

		//----------------------------------------------------
		$validator = Validator::make($request->all(), $rules);
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
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, $this->emptyArray);
		}
		//  call display today phase -------------------------
		$default = '';
		$request->request->add(['default' => $default]);
		//  $todayphase = $this->displayTodayPhase($request); //
		//$this->displayTodayPhase(request);
		//----Get Phasse iD  of current ico phase-------------
		$arrInput = $request->all();

		$todayphase = IcoPhases::select('srno', 'name', 'from_date', 'to_date', 'total_supply', 'sold_supply', 'usd_rate', 'usd_rate', 'status', 'min_coin')->where('srno', '=', $request->srno)->where('status', '=', 'Available')->first();

		if ($todayphase != null) {

			$date  = \Carbon\Carbon::now('Asia/Kolkata');
			$today = $date->toDateTimeString();

			//dd(date("Y-m-d h:m:s", strtotime($today)));
			/*if (strtotime($today) >= strtotime($todayphase->from_date)
			&& strtotime($today) <= strtotime($todayphase->to_date)) {

			//  dd('a');


			}else{

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Coin Sold Out Check Next Phase once', '');
			}*/

			$supplyBal = $todayphase->total_supply-$todayphase->sold_supply;

			// $summary->status = "Sold Out";
			if ($supplyBal <= 0) {

				/* $paymentDate = date('Y-m-d H:i:s');
				$paymentDate=date('Y-m-d H:i:s', strtotime($paymentDate));
				//echo $paymentDate; // echos today!
				$contractDateBegin = date('Y-m-d H:i:s', strtotime($todayphase->from_date));
				$contractDateEnd = date('Y-m-d H:i:s', strtotime($todayphase->to_date));

				if (($paymentDate >= $contractDateBegin) && ($paymentDate <= $contractDateEnd)){
				}else{
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'No Token available Sold Out', '');
				}*/

				$userdash = Dashboard::select('tbl_dashboard.top_up_wallet', 'tbl_dashboard.id', 'tbl_dashboard.coin')->join('tbl_users as tu', 'tu.id', '=', 'tbl_dashboard.id')
				                                                                                                      ->where('tu.status', '=', 'Active')
				                                                                                                      ->where('tu.id', '=', 1)
				                                                                                                      ->first();
				if ($userdash == null) {

					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');

				}

				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Sold Out', '');
			}
			// dd($supplyBal,180>100);

			if ($supplyBal > 100) {

				if ($arrInput['coin'] < $todayphase->min_coin) {

					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Enter greater than '.$todayphase->min_coin, '');
				}

			}

			//   dd((int)$supplyBal < $request->coin);

			if ((int) $supplyBal < $request->coin) {return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], '  available Token '.$supplyBal, '');

				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], '  Available coins '.$supplyBal, '');
			}

			$userdash = Dashboard::select('fund_wallet_withdraw', 'fund_wallet', 'top_up_wallet', 'id', 'top_up_wallet_withdraw', 'coin')->where('id', Auth::user()->id)->first();

			//$userTopupBal=$userdash->top_up_wallet - $userdash->top_up_wallet_withdraw;
			$userTopupBal = $userdash->fund_wallet-$userdash->fund_wallet_withdraw;

			$userBuycoinCost = $arrInput['coin']*$todayphase->usd_rate;

			if ($userTopupBal < $userBuycoinCost) {
				//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Check Your  New fund wallet Balance ', '');
			}

			$coin = round($userdash->coin+$request->coin);
			// $top_up_wallet_wi = custom_round($userdash->top_up_wallet_withdraw + $userBuycoinCost, 7);
			$top_up_wallet_wi = custom_round($userdash->fund_wallet_withdraw+$userBuycoinCost, 7);
			// Dashboard Update
			Dashboard::where('id', Auth::user()->id)->limit(1)->update(['fund_wallet_withdraw' => $top_up_wallet_wi, 'coin' => $coin]);

			//Activity insert
			$message     = $request->coin.' purchase successfully ';
			$status      = "Available";
			$sold_supply = $request->coin+$todayphase->sold_supply;
			IcoPhases::where('srno', $todayphase->srno)->update(['sold_supply' => $sold_supply, 'status' => $status]);

			//All Transaction insert
			$trans               = new AllTransaction;
			$trans->id           = Auth::user()->id;
			$trans->phases_id    = $todayphase->srno;
			$trans->coin         = $request->coin;
			$trans->network_type = 'USD';
			$trans->credit       = 0;
			$trans->debit        = $userBuycoinCost;
			//$trans->prev_balance = $userTopupBal;
			//$trans->final_balance = $userBuycoinCost;
			//$trans->balance = $userTopupBal-$userBuycoinCost;
			$trans->refference       = 0;
			$trans->transaction_date = \Carbon\Carbon::now();
			$trans->status           = 1;
			$trans->type             = 'Buy Coin';
			$trans->remarks          = 'Buy Token '.$request->coin;
			$trans->save();

			//  dd($userTopupBal,$userBuycoinCost);

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Token Purchase successfully '.$request->coin, '');
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Phase Not Available ', '');

			# code...
		}

	}
	/**
	 * get all ico phases
	 *
	 * @return void
	 */
	public function transferIcoCoin(Request $request) {
		$arrInput = $request->all();

		$rules = array(
			'coin'    => 'required',
			'user_id' => 'required',
			'srno'    => 'required',
		);

		//----------------------------------------------------
		$validator = Validator::make($request->all(), $rules);
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
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		}

		$userdash = Dashboard::select('tbl_dashboard.top_up_wallet', 'tbl_dashboard.id', 'tbl_dashboard.coin', 'tbl_dashboard.coin_withdrawal', 'tbl_dashboard.coin', 'tu.user_id')
			->join('tbl_users as tu', 'tu.id', '=', 'tbl_dashboard.id')
			->where('tu.status', '=', 'Active')
			->where('tu.type', '=', '')
			->where('tu.user_id', '=', $arrInput['user_id'])
			->first();

		// dd($arrInput['user_id']);
		if ($userdash == null) {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found Or  user Inactive', '');

		}

		$admindash = Dashboard::select('tbl_dashboard.coin_withdrawal', 'tbl_dashboard.coin')
			->where('tbl_dashboard.id', '=', 1)
			->first();

		$cr_coin_bal = $admindash->coin-$admindash->coin_withdrawal;

		if ($cr_coin_bal >= $request->coin) {

		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Available Token '.$cr_coin_bal, '');

		}

		//--- user Dashboard Update
		Dashboard::where('id', $userdash->id)->limit(1)->update(['coin' => $userdash->coin+$request->coin]);

		//--- admin
		Dashboard::where('id', '=', 1)->limit(1)->update(['coin_withdrawal' => $admindash->coin_withdrawal+$request->coin]);

		//dd($admindash->coin_withdrawal+ $request->coin);

		//All Transaction insert
		$trans               = new CoinTransactionLog;
		$trans->from_id      = Auth::user()->id;
		$trans->to_id        = $userdash->id;
		$trans->coin         = $request->coin;
		$trans->from_user_id = Auth::user()->user_id;
		$trans->to_user_id   = $userdash->user_id;
		$trans->trans_type   = 1;
		$trans->entry_time   = \Carbon\Carbon::now();
		$trans->remark       = 'Send By Admin Token '.$request->coin;
		$trans->save();

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'coin Purchase successfully '.$request->coin, '');

	}

	public function updateStatusPhasesStatus(Request $request) {
		$arrInput = $request->all();

		$rules = array(
			'srno'   => 'required',
			'status' => 'required',
		);

		//----------------------------------------------------
		$validator = Validator::make($request->all(), $rules);
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
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		}

		//dd($arrInput['status'],$arrInput['srno']);
		$newStatus = $arrInput['status'] == 'Available'?'Notavailable':'Available';
		//dd($newStatus);
		$data = IcoPhases::where('srno', '=', $arrInput['srno'])->update(['status' => $newStatus]);

		$newStatus = $arrInput['status'] == 'Available'?'Inactive':'Active';
		if ($data) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'successfully updated '.$newStatus, '');
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Not Updated', '');
		}

	}

	/**
	 * get all ico phases
	 *
	 * @return void
	 */
	public function getIcoPhases(Request $request) {
		$arrInput = $request->all();

		$query = IcoPhases::query();
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_phases');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
					foreach ($fields as $field) {
						$query->orWhere($field, 'LIKE', '%'.$search.'%');
					}
				});
		}
		$query        = $query->orderBy('srno', 'asc');
		$totalRecord  = $query->count();
		$arrIcoPhases = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrIcoPhases;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 * get all ico phases
	 *
	 * @return void
	 */
	public function getTodayPhaseSummary(Request $request) {
		$arrInput = $request->all();

		$query = TodaySummary::selectRaw('ANY_VALUE(DATE_FORMAT(date,"%d-%m-%Y")) as date,SUM(today_supply) as today_supply,SUM(today_sold) as today_sold,SUM(today_available) as today_available');
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_today_phase_summary');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
					foreach ($fields as $field) {
						$query->orWhere($field, 'LIKE', '%'.$search.'%');
					}
				});
		}
		$query          = $query->groupBy(DB::raw("DATE_FORMAT(date,'%Y-%m-%d')"))->orderBy('srno', 'desc');
		$totalRecord    = $query->get()->count();
		$arrPhasesSummy = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrPhasesSummy;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 * get all ico phases details from tbl_topup_ico
	 *
	 * @return void
	 */
	public function getIcoPhasesTopup(Request $request) {
		$arrInput = $request->all();

		$query = TopupIco::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup_ico.id')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_topup_ico.top_up_by')
			->select('tbl_topup_ico.*', 'tu.user_id', 'tu1.user_id as top_up_by');

		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
			$query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup_ico.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_topup_ico');
			$search = $arrInput['search']['value'];
			$query  = $query->where(function ($query) use ($fields, $search) {
					foreach ($fields as $field) {
						$query->orWhere('tbl_topup_ico.'.$field, 'LIKE', '%'.$search.'%');
					}
					$query->orWhere('tu.user_id', 'LIKE', '%'.$search.'%')
					->orWhere('tu1.user_id', 'LIKE', '%'.$search.'%');
				});
		}
		$query             = $query->orderBy('tbl_topup_ico.srno', 'desc');
		$totalRecord       = $query->count();
		$arrIcoPhasesTopup = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal']    = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records']         = $arrIcoPhasesTopup;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function getIcoStatus() {

		try {

			$arrData = ProjectSettings::select('ico_status', 'ico_admin_error_msg')->first();

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);

		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function saveIcoStatus(Request $request) {

		try {

			$arrInput = $request->all();
			//validate the info, create rules for the inputs
			$rules = array(
				'ico_status'          => 'required',
				'ico_admin_error_msg' => 'required',

			);

			// run the validation rules on the inputs from the form
			$validator = Validator::make($arrInput, $rules);
			// if the validator fails, redirect back to the form
			if ($validator->fails()) {
				$message    = $validator->errors();
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $message;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$arrData = ProjectSettings::where('id', '=', '1')->update($arrInput);

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'successfully Updated';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);

		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
}