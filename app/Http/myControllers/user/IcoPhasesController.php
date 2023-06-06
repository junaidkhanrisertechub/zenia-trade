<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Controllers\user\LevelController;

use App\Models\AllTransaction;

use App\Models\Currencyrate;
use App\Models\Dashboard;
use App\Models\ExchangeRateReport;
use App\Models\IcoPhases;
use App\Models\ProjectSettings;
use App\Models\RqzUserAddress;
use App\Models\TodaySummary;

use App\Models\WalletTransactionLog;
use App\User;
use Auth;
use Config;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;

class IcoPhasesController extends Controller {

	// Constructor
	public function __construct(CurrencyConvertorController $conversion, LevelController $level) {
		$this->statuscode = Config::get('constants.statuscode');
		$this->conversion = $conversion;
		$this->level      = $level;
		$this->emptyArray = (object) array();
	}

	public function sendCoinRep(Request $request) {

		$arrInput = $request->all();
		try {
			$Checkexist = Auth::User()->id;// check use is active or not
			if (!empty($Checkexist)) {

				$topupReport = DB::table('tbl_coin_wallet_transfer')
					->select('*')
				/*  ->whereRaw("NOT (from_address IS NULL OR  to_address IS NULL OR trans_hash IS NULL )")*/
					->where('from_user_id', Auth::User()->id)->orderBy('entry_time', 'desc');

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
	public function getApiAuthToken() {

		try {

			/*
			$fields = array(
			'user_id' => '63463c5f-3bb5-26c9-84f9-f2f079deb3b6',
			'password' =>'qwe!@#rty$%^',
			);

			$fields = json_encode($fields);

			//$NEWAPIURL=Config::get('constants.settings.woz_auth_token');
			$NEWAPIURL='https://www.ademo.com/ademo-exchange/api/get_auth_token';

			// dd($NEWAPIURL);

			$headers = array(
			'Content-Type: application/json'
			);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $NEWAPIURL."get_auth_token");
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

			$result = curl_exec($ch);
			curl_close($ch);

			$result=json_decode($result,true);
			dd($result);
			if(!empty($result) && isset($result['code']) && $result['code']==200){

			return $result['data']['access_token'];

			}
			return "";*/
			$NEWAPIURL = Config::get('constants.settings.woz_auth_token');
			//$NEWAPIURL='http://localhost/-exchange/api/get_auth_token';
			//dd($NEWAPIURL);
			$fields = array(

			);
			$curl = curl_init();

			$user_id  = Config::get('constants.settings.woz_auth_user');
			$password = Config::get('constants.settings.woz_auth_pass');

			curl_setopt_array($curl, array(
					CURLOPT_URL            => $NEWAPIURL,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING       => "",
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 30,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => "POST",
					CURLOPT_POSTFIELDS     => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"user_id\"\r\n\r\n".$user_id."\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"password\"\r\n\r\n".$password."\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
					CURLOPT_HTTPHEADER     => array(
						"cache-control: no-cache",
						"content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
						"postman-token: b846686d-eb80-3dd5-4983-69a49b6c8f5f",
					),
				));

			$response = curl_exec($curl);
			$err      = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:".$err;
			} else {
				return $result = json_decode($response, true);
				//dd($response);
			}

		} catch (Exception $e) {

			dd($e);

		}

	}

	public function sendCoinBalance(Request $request) {
		$id = Auth::user()->id;
		// dd($getuser->id,$getuser->user_id);
		$rules = array(
			'trnf_amount' => 'required',
			'transfer_to' => 'required',
			'remark'      => 'required',
			//'fee'     => 'required'

		);
		//dd($request);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message, '');
		}

		if (!empty($getuser)) {

			try {
				DB::beginTransaction();

				$temp_info = md5($request->header('User-Agent'));
				if ($temp_info == Auth::User()->temp_info) {
					//dd(true);

				} else {

					$strMessage = 'BAD REQUEST';
					$intCode    = Response::HTTP_BAD_REQUEST;
					$strStatus  = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, $strMessage, '');
				}

				$sender_address = RqzUserAddress::where('user_id', $id)->first();

				if (empty($sender_address)) {

					$randomnum           = substr(number_format(time()*rand(), 0, '', ''), 0, '4');
					$rqzAddress          = md5(microtime(true).mt_Rand().$randomnum);
					$saveRqz             = new RqzUserAddress();
					$saveRqz->user_id    = $id;
					$saveRqz->address    = $rqzAddress;
					$saveRqz->remark     = 'First address';
					$saveRqz->entry_time = \Carbon\Carbon::now();
					$saveRqz->user_ip    = $_SERVER['REMOTE_ADDR'];
					$saveRqz->save();

					$sender_address = RqzUserAddress::where('user_id', $id)->first();
				}
				// dd($sender_address);
				$transfer_by = $sender_address->address;
				$trnf_amount = $request->Input('trnf_amount');
				$transfer_to = $request->Input('transfer_to');
				$remark      = $request->Input('remark');

				$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
				$trans_hash = 'txn'.substr(str_shuffle($str_result), 0, 32);

				//check records in tbl_riquezaa_invoice

				$sender_wallet_bal = Dashboard::select('coin', 'coin_withdrawal')->where('id', '=', $id)->first();

				$sender_bal = $sender_wallet_bal->coin-$sender_wallet_bal->coin_withdrawal;

				if ($sender_bal >= $request->Input('trnf_amount') && $request->Input('trnf_amount') > 0) {
					//dd($sender_bal);
					//--- check receiver

					$resAuthToken = $this->getApiAuthToken();
					// dd($resAuthToken['code']);

					// dd($resAuth);
					if (isset($resAuthToken['code']) && $resAuthToken['code'] == 200) {

						$access_token = $resAuthToken['data']['access_token'];

						$headers = array(
							'Content-Type: application/json',
							'Authorization: Bearer '.$access_token,
						);
						//dd($headers);

						$ch = curl_init();

						// $urlData="trnf_amount=".$trnf_amount."&transfer_to=".$transfer_to."&transfer_by=".$transfer_by."&remark=".$remark."&trans_hash=".$trans_hash;

						$fields = array(
							'trnf_amount' => $trnf_amount,
							'transfer_to' => $transfer_to,
							'transfer_by' => $transfer_by,
							'trans_hash'  => $trans_hash,
							'remark'      => $remark,
						);

						$fields = json_encode($fields);

						$woz_exchange = Config::get('constants.settings.woz_exchange');

						curl_setopt($ch, CURLOPT_URL, $woz_exchange);
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

						$server_output = curl_exec($ch);
						//  dd('hi',$server_output);
						$res = json_decode($server_output);

						//dd($res);
						//
						curl_close($ch);

						// Further processing ...
						if ($res->code == 200) {

							$wallet_arr = ['from_user_id' => $id, 'to_user_id' => 0, 'from_address' => $transfer_by, 'to_address' => $transfer_to, 'trans_hash' => $trans_hash, 'amount' => $trnf_amount, 'remark' => $request->Input('remark'), 'type' => 2];

							$tbl_wallet_transfer = DB::table('tbl_coin_wallet_transfer')->insert($wallet_arr);

							$coin_withdrawal = $sender_wallet_bal->coin_withdrawal+$trnf_amount;

							Dashboard::where('id', '=', $id)->update(array('coin_withdrawal' => $coin_withdrawal));

							DB::commit();
							return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Balance Transfer Successful!', '');

							// echo "successfully send coin";

						} else {

							return $server_output;

						}
					} else {

						return $resAuthToken;
					}

				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Enter  transfer  Coin less than your Balance and greater than 0', '');
					// Enter  transfer  amount less than your Balance
				}
			} catch (Exception $e) {

				DB::rollback();
				$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
				$strStatus  = Response::$statusTexts[$intCode];
				$strMessage = 'Something went wrong,Please try again';
				return sendResponse($intCode, $strStatus, $strMessage, '');
			}

			DB::commit();
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Balance Transfer Successful!', '');

		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User id does not exist', '');

		}

	}

	//===================== insert ico phases====================================
	public function insertIcoPhases(Request $request) {

		$rules = array(
			'name'      => 'required',
			'from_date' => 'required',
			'to_date'   => 'required|after:from_date',
			'usd_rate'  => 'required|regex:/^\d*(\.\d{1,4})?$/',
		);

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
		$date_a   = new DateTime($request->from_date);
		$date_b   = new DateTime($request->to_date);
		$interval = date_diff($date_a, $date_b);
		// dd($request->sold_percentage);

		if ($request->sold_percentage == '') {
			$request['sold_percentage'] = 0;
		}
		if ($request->total_supply == '') {
			$request['total_supply'] = 1800000;
		}
		if ($request->sold_supply == '') {
			$request['sold_supply'] = 0;
		}
		if ($request->status == '') {
			$request['status'] = 'starting';
		}
		if ($request->status == '') {
			$request['min_coin'] = 10;
		}

		if ($request->status == '') {
			$request['status'] = 10;
		}

		$request['percentage'] = 100;
		$request['days']       = $interval->days;

		$insertdata     = IcoPhases::create($request->all());
		$data           = array();
		$data['id']     = $insertdata->id;
		$data['status'] = $insertdata->status;
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "record inserted successfully", $data);
	}

	//====================================================================
	public function getIcoPhases(Request $request) {
		$myArray         = [];
		$purchase_wallet = 0;
		if (isset($request->id)) {
			$phases = IcoPhases::where('srno', '=', $request->id)->get();

			$dash = Dashboard::select('coin', 'top_up_wallet', 'top_up_wallet_withdraw', 'fund_wallet_withdraw', 'fund_wallet')->where('id', '=', Auth::user()->id)->first();

			$purchase_wallet = $dash->top_up_wallet-$dash->top_up_wallet_withdraw;
			$fund_wallet     = $dash->fund_wallet-$dash->fund_wallet_withdraw;
			# code...

		} else {
			$phases = IcoPhases::select('srno', 'name', 'usd_rate', 'from_date', 'to_date', 'percentage', 'sold_percentage', 'total_supply', 'sold_supply', 'days', 'bonus_percentage', 'min_coin', 'status', 'entry_time')->get();

			$dash = Dashboard::select('fund_wallet_withdraw', 'fund_wallet', 'coin', 'top_up_wallet', 'top_up_wallet_withdraw')->where('id', '=', Auth::user()->id)->first();

			$fund_wallet = $dash->fund_wallet-$dash->fund_wallet_withdraw;

		}

		$date  = \Carbon\Carbon::now('Asia/Kolkata');
		$today = $date->toDateTimeString();

		$coin_name = ProjectSettings::orderBy('id', 'desc')->where('status', 1)->pluck('coin_name')->first();

		foreach ($phases as $phase) {
			$status = $phase->status;

			if ($phase->sold_supply >= $phase->total_supply) {

				$updateArr = array("status" => "SoldOut");
				IcoPhases::where('srno', '=', $phase->srno)->update($updateArr);

				$updateArr = array("status" => "Available");
				IcoPhases::where('srno', '=', $phase->srno+1)->update($updateArr);

				$status = 'SoldOut';

			}

			$arr = ['name' => $phase->name, 'from_date' => $phase->from_date, 'to_date' => $phase->to_date, 'percentage' => $phase->percentage, 'sold_percentage' => $phase->sold_percentage, 'total_supply' => $phase->total_supply, 'sold_supply' => $phase->sold_supply, 'days' => $phase->days, 'usd_rate' => $phase->usd_rate, 'min_coin' => $phase->min_coin, 'bonus_percentage' => $phase->bonus_percentage, 'status' => $status, 'entry_time' => $phase->entry_time, 'coin_name' => $coin_name, 'purchase_wallet' => $purchase_wallet, 'fund_wallet' => $fund_wallet, 'coin' => $dash->coin, 'now_time' => $today];
			array_push($myArray, $arr);
		}

		if (count($phases) > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "Record Found", $myArray);
		} else {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "No Record Found", $this->emptyArray);
		}
	}

	//==================  Todays phase display  =======================

	public function displayTodayPhase(Request $request) {
		/*  $rules = array(
		// 'remember_token'    => 'required',
		// 'phase_id'    => 'required'

		);

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
		$message = $validator->errors();
		$err ='';
		foreach($message->all() as $error)
		{
		if(count($message->all())>1){
		$err=$err.' '.$error;
		}else{
		$err=$error;
		}
		}
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,'');
		} */

		$date               = \Carbon\Carbon::now();
		$today              = $date->toDateTimeString();
		$phases             = IcoPhases::all();
		$myArray            = [];
		$beforephase_status = 0;// to check if there are still some phase remaining to come
		// dd($today);
		if (count($phases) > 0) {
			foreach ($phases as $phase) {

				if ((date("Y-m-d", strtotime($today)) >= date("Y-m-d", strtotime($phase->from_date))) && (date("Y-m-d", strtotime($today)) <= (date("Y-m-d", strtotime($phase->to_date))))) {
					//dd('hi');
					$days = $phase->days;

					//  -- convert string date to date  --
					$date_a = new DateTime($phase->to_date);
					$date_b = new DateTime($today);

					// --- get date difference ---
					$interval = date_diff($date_a, $date_b);
					// dd($interval);
					// --  Get current day  --
					$current_day = $days-$interval->days.'/'.$days;
					//dd($current_day);
					//  get remaining time from todays date time
					$to              = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $phase->to_date);
					$from            = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $today);
					$diff_in_seconds = $to->diffInSeconds($from);
					$diff_in_minutes = $to->diffInMinutes($from);
					$diff_in_hours   = $to->diffInHours($from);
					$diff_in_days    = $interval->days;

					$today_supply = $phase->total_supply/$days;
					$date         = $date_b->format('Y/m/d');
					$day          = $days-$interval->days;//get day to get data fromm summary
					//$userid=Auth::user()->id;
					//  $userexist=User::where('remember_token',trim($request->remember_token))->first();

					/* if(!empty($userexist))
					{
					$userid=$userexist->id;
					}else{
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Token is invalid','');
					} */

					// $dash=Dashboard::where([['id','=',$userid]])->first();
					// dd($date);

					$summary = TodaySummary::where([['day', '=', $day], ['phase_id', '=', $phase->srno]])->first();
					// dd($summary);
					if (empty($summary)) {

						$summarydata                    = array();
						$summarydata['date']            = $date;
						$summarydata['day']             = $days-$interval->days;
						$summarydata['today_supply']    = custom_round($today_supply, 7);
						$summarydata['today_sold']      = 0;
						$summarydata['today_available'] = custom_round($today_supply, 7);
						// $summarydata['coin_wallet']=$dash->coin;
						//  $summarydata['BTC_wallet']=$dash->btc;
						// $summarydata['USD_wallet']=$dash->usd;
						$summarydata['phase_id'] = $phase->srno;
						$summarydata['status']   = 'Available';

						TodaySummary::create($summarydata);
					}

					IcoPhases::where('srno', $phase->srno)->update(['status' => 'Available']);

					// getTodaySoldPhase($day,$phase->srno);
					$end_date  = strtotime("+1 day");
					$end_date1 = date('Y-m-d'.' 00:00:00', $end_date);

					$arr = ['name' => $phase->name, 'usd_rate' => $phase->usd_rate, 'days' => $diff_in_days, 'hours' => $diff_in_hours, 'minutes' => $diff_in_minutes, 'seconds' => $diff_in_seconds, 'current' => $current_day, 'start_date' => $today, 'next_date' => $end_date1];
					array_push($myArray, $arr);
				} elseif ((date("Y-m-d", strtotime($today)) > date("Y-m-d", strtotime($phase->from_date))) && (date("Y-m-d", strtotime($today)) > (date("Y-m-d", strtotime($phase->to_date))))) {
					IcoPhases::where('srno', $phase->srno)->update(['status' => 'Sold Out']);
				}//elseif end
				 elseif ((date("Y-m-d", strtotime($today)) < date("Y-m-d", strtotime($phase->from_date)))) {
					$beforephase_status = 1;
				}
			}
			// dd($myArray);

			if (!empty($myArray)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $myArray);
			} else {
				if ($beforephase_status == 1) {
					$icophase = IcoPhases::orderBy('from_date', 'ASC')->where('status', 'starting')->first();
					//dd($icophase);

					$arr = ['name' => $icophase->name, 'usd_rate' => 0, 'days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0, 'current' => 0, 'start_date' => $today, 'next_date' => $icophase->from_date];
					array_push($myArray, $arr);
					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'No phase started yet', $myArray);
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'No phase to start', $this->emptyArray);
				}
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'No Data Found', $this->emptyArray);
		}
	}

	//------------- Get todays Sold Phase --------------------------

	public function getTodaySoldPhase(Request $request) {
		$rules = array(
			'remember_token' => 'required',
			// 'phase_id'    => 'required'
		);

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
		//$userexist=

		$date         = \Carbon\Carbon::now();
		$today        = $date->toDateTimeString();
		$current_time = date("H:i:s");
		$next_date    = strtotime("+1 day");
		$next_date1   = date('Y-m-d'.' 00:00:00', $next_date);

		//dd(date("Y-m-d",strtotime($today)));
		$summary = TodaySummary::where([['date', '=', date("Y-m-d", strtotime($today))]])->first();
		// dd($summary);
		$myArray = [];
		if (!empty($summary)) {
			$userid = User::where([['remember_token', '=', trim($request->Input('remember_token'))]])->pluck('id')->first();
			//$userid=Auth::user()->id;
			$dash = Dashboard::where([['id', '=', $userid]])->first();
			if (empty($dash)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found in dashboard', $this->emptyArray);
			}
			$today_sold_percentage = custom_round(($summary->today_sold/$summary->today_supply)*100, 7);
			// dd($today_sold_percentage);
			$phase             = IcoPhases::where('srno', $summary->phase_id)->first();
			$overall_available = custom_round($phase->total_supply-$phase->sold_supply, 7);
			$arr               = ['date' => $summary->date, 'today_supply' => $summary->today_supply, 'today_sold' => custom_round($summary->today_sold, 7), 'today_available' => custom_round($summary->today_available, 7), 'coin_wallet' => custom_round($dash->coin, 7), 'btc_wallet' => custom_round($dash->btc, 7), 'usd_wallet' => custom_round($dash->usd, 7), 'status' => $summary->status, 'overall_total_supply' => custom_round($phase->total_supply, 7), 'overall_total_sold' => custom_round($phase->sold_supply, 7), 'overall_available' => custom_round($overall_available, 7), 'sold_percentage' => custom_round($today_sold_percentage, 7), 'current_time' => $current_time, 'start_date' => $today, 'next_date' => $next_date1];
			array_push($myArray, $arr);
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record Found', $myArray);
		} else {// dd('hi');
			$userid = User::where([['remember_token', '=', trim($request->Input('remember_token'))]])->pluck('id')->first();

			$icophase = IcoPhases::orderBy('from_date', 'ASC')->where('status', 'starting')->first();
			if (empty($icophase)) {
				$next = '';
			} else {
				$next = $icophase->from_date;
			}
			// dd($icophase);
			//$userid=Auth::user()->id;
			$dash = Dashboard::where([['id', '=', $userid]])->first();
			if (empty($dash)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found in dashboard', $this->emptyArray);
			}
			$arr = ['date' => $today, 'today_supply' => 0, 'today_sold' => 0, 'today_available' => 0, 'coin_wallet' => custom_round($dash->coin, 7), 'btc_wallet' => custom_round($dash->btc, 7), 'usd_wallet' => custom_round($dash->usd, 7), 'status' => '', 'current_time' => $current_time, 'start_date' => $today, 'next_date' => $next];
			array_push($myArray, $arr);
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record Found', $myArray);
		}
	}

	//------------  coin to btc and usd
	public function getIcoBtcUsd(Request $request) {
		$rules = array(
			'coin' => 'required',
		);

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
		if ($request->coin <= 0) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Coin value should be greater than 0', $this->emptyArray);
		}

		$date  = \Carbon\Carbon::now();
		$today = $date->toDateTimeString();
		//dd($today);
		$phases             = IcoPhases::all();
		$myArray            = [];
		$beforephase_status = 0;
		if (count($phases) > 0) {
			foreach ($phases as $phase) {
				if ((date("Y-m-d", strtotime($today)) >= date("Y-m-d", strtotime($phase->from_date))) && (date("Y-m-d", strtotime($today)) <= (date("Y-m-d", strtotime($phase->to_date))))) {// dd('hi');
					$usd = custom_round($phase->usd_rate*$request->coin, 7);

					$request->request->add(['usdvalue' => $usd]);
					$btcvalue = $this->conversion->usdTobtc($request);// btc value of current rate

					if ($btcvalue->original['status'] == 'OK') {
						// dd('hi');
						$btc_value = json_encode($btcvalue->original['data']['btc']);
					}
					$coin_name = ProjectSettings::orderBy('id', 'desc')->where('status', 1)->pluck('coin_name')->first();

					$arr = ['coin' => $request->coin, 'usd' => $usd, 'btc' => custom_round($btc_value, 7), 'coin_name' => $coin_name, 'min_coin' => $phase->min_coin, 'today_date' => $today, 'usd_rate' => $phase->usd_rate, 'phase' => 'true', 'admin_rate' => 'false'];
					array_push($myArray, $arr);
				} elseif ((date("Y-m-d", strtotime($today)) < date("Y-m-d", strtotime($phase->from_date)))) {
					$beforephase_status = 1;
				}
			}
			if (!empty($myArray)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $myArray);
			} else {
				// dd($beforephase_status);
				if ($beforephase_status == 0) {
					$usd_arr = ExchangeRateReport::orderBy('entry_time', 'DESC')->first();
					//dd($usd_arr);
					if (!empty($usd_arr)) {
						$usd = $usd_arr->usd;
					} else {
						$usd_arr = Currencyrate::orderBy('srno', 'desc')->where('status', 1)->first();
						$usd     = $usd_arr->usd;
					}
				} else {
					//dd('hi');
					$usd_arr = Currencyrate::orderBy('srno', 'desc')->where('status', 1)->first();
					$usd     = $usd_arr->usd;
				}
				$usd_arr  = Currencyrate::orderBy('srno', 'desc')->where('status', 1)->first();
				$min_coin = $usd_arr->min_coin;
				$usd1     = custom_round($usd*$request->coin, 7);
				//dd($usd1);

				$request->request->add(['usdvalue' => $usd1]);
				$btcvalue = $this->conversion->usdTobtc($request);// btc value of current rate
				// dd($btcvalue->original['status']);
				if ($btcvalue->original['status'] == 'OK') {
					$btc_value = json_encode($btcvalue->original['data']['btc']);
				}
				$coin_name = ProjectSettings::orderBy('id', 'desc')->where('status', 1)->pluck('coin_name')->first();

				$arr = ['coin' => $request->coin, 'usd' => $usd1, 'btc' => custom_round($btc_value, 7), 'coin_name' => $coin_name, 'min_coin' => $min_coin, 'today_date' => $today, 'use_rate' => $usd_arr->usd, 'phase' => 'false', 'admin_rate' => 'true'];
				array_push($myArray, $arr);
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $myArray);
			}
		}
	}

	//------------  btc to coin and usd
	public function getIcoCoinUsd(Request $request) {
		$rules = array(
			'btc' => 'required',
		);

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

		if ($request->btc <= 0) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'btc value should be greater than 0', $this->emptyArray);
		}

		$date    = \Carbon\Carbon::now();
		$today   = $date->toDateTimeString();
		$phases  = IcoPhases::all();
		$myArray = [];
		if (count($phases) > 0) {
			foreach ($phases as $phase) {
				if ((date("Y-m-d", strtotime($today)) >= date("Y-m-d", strtotime($phase->from_date))) && (date("Y-m-d", strtotime($today)) <= (date("Y-m-d", strtotime($phase->to_date))))) {

					$request->request->add(['btcvalue' => $request->btc]);
					$usdvalue = $this->conversion->btc_to_usd($request);// btc value of current rate
					if ($usdvalue->original['status'] == 'OK') {
						$usd_value = custom_round(json_encode($usdvalue->original['data']['usd']), 7);
					}
					$coin_name = ProjectSettings::orderBy('id', 'desc')->where('status', 1)->pluck('coin_name')->first();
					// if ($phase->usd_rate % $usd_value== 1)
					// {
					$coin = custom_round($usd_value/$phase->usd_rate, 7);
					// }
					$arr = ['coin' => $coin, 'usd' => $usd_value, 'btc' => custom_round($request->btc, 7), 'coin_name' => $coin_name, 'min_coin' => $phase->min_coin, 'today_date' => $today, 'usd_rate' => $phase->usd_rate, 'phase' => 'true', 'admin_rate' => 'false'];
					array_push($myArray, $arr);
				}
			}
			if (!empty($myArray)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $myArray);
			} else {

				$usd_arr  = Currencyrate::orderBy('srno', 'desc')->where('status', 1)->first();
				$usd      = $usd_arr->usd;
				$min_coin = $usd_arr->min_coin;
				$request->request->add(['btcvalue' => $request->btc]);
				$usdvalue = $this->conversion->btc_to_usd($request);// btc value of current rate
				if ($usdvalue->original['status'] == 'OK') {
					$usd_value = custom_round(json_encode($usdvalue->original['data']['usd']), 7);
				}
				// if ($phase->usd_rate % $usd_value== 1)
				// {
				$coin      = custom_round($usd_value/$usd, 7);
				$coin_name = ProjectSettings::orderBy('id', 'desc')->where('status', 1)->pluck('coin_name')->first();
				// }
				$arr = ['coin' => $coin, 'usd' => $usd_value, 'btc' => custom_round($request->btc, 7), 'coin_name' => $coin_name, 'min_coin' => $min_coin, 'today_date' => $today, 'usd_rate' => $usd_arr->usd, 'phase' => 'false', 'admin_rate' => 'true'];
				array_push($myArray, $arr);
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $myArray);
			}
		}
	}

	//------------  usd to coin and btc
	public function getIcoBtcCoin(Request $request) {
		$rules = array(
			'usd' => 'required|regex:/^\d*(\.\d{1,4})?$/',
		);

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

		if ($request->usd <= 0) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'usd value should be greater than 0', $this->emptyArray);
		}

		$date    = \Carbon\Carbon::now();
		$today   = $date->toDateTimeString();
		$phases  = IcoPhases::all();
		$myArray = [];
		if (count($phases) > 0) {
			foreach ($phases as $phase) {
				if ((date("Y-m-d", strtotime($today)) >= date("Y-m-d", strtotime($phase->from_date))) && (date("Y-m-d", strtotime($today)) <= (date("Y-m-d", strtotime($phase->to_date))))) {
					$usd = $request->usd;
					// dd($phase->usd_rate);
					$coin = custom_round($usd/$phase->usd_rate, 7);

					$request->request->add(['usdvalue' => $usd]);
					$btcvalue = $this->conversion->usdTobtc($request);// btc value of current rate
					if ($btcvalue->original['status'] == 'OK') {
						$btc_value = custom_round(json_encode($btcvalue->original['data']['btc']), 7);
					}
					$coin_name = ProjectSettings::orderBy('id', 'desc')->where('status', 1)->pluck('coin_name')->first();

					$arr = ['coin' => $coin, 'usd' => $usd, 'btc' => custom_round($btc_value, 7), 'coin_name' => $coin_name, 'min_coin' => $phase->min_coin, 'today_date' => $today, 'usd_rate' => $phase->usd_rate, 'phase' => 'true', 'admin_rate' => 'false'];
					array_push($myArray, $arr);
				}
			}
			if (!empty($myArray)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $myArray);
			} else {

				$usd_arr  = Currencyrate::orderBy('srno', 'desc')->where('status', 1)->first();
				$usd1     = $usd_arr->usd;
				$min_coin = $usd_arr->min_coin;
				$usd      = $request->usd;
				$coin     = custom_round($usd/$usd1, 7);
				$request->request->add(['usdvalue' => $usd]);
				$btcvalue = $this->conversion->usdTobtc($request);// btc value of current rate
				if ($btcvalue->original['status'] == 'OK') {
					$btc_value = custom_round(json_encode($btcvalue->original['data']['btc']), 7);
				}
				$coin_name = ProjectSettings::orderBy('id', 'desc')->where('status', 1)->pluck('coin_name')->first();

				$arr = ['coin' => $coin, 'usd' => $usd, 'btc' => custom_round($btc_value, 7), 'coin_name' => $coin_name, 'min_coin' => $min_coin, 'today_date' => $today, 'usd_rate' => $usd_arr->usd, 'phase' => 'false', 'admin_rate' => 'true'];
				array_push($myArray, $arr);
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $myArray);
			}
		}
	}

	public function getpurchaseCoinRep(Request $request) {

		$arrInput = $request->all();
		try {
			$Checkexist = Auth::User()->id;// check use is active or not
			if (!empty($Checkexist)) {

				$topupReport = AllTransaction::select('tbl_all_transaction.coin', 'tbl_all_transaction.debit', 'tbl_all_transaction.credit', 'tbl_all_transaction.entry_time', 'tbl_all_transaction.phases_id',
					'tbl_all_transaction.remarks', 'tbl_all_transaction.refference', 'ph.name')
					->join('tbl_phases as ph', 'ph.srno', '=', 'tbl_all_transaction.phases_id')
					->where('tbl_all_transaction.id', '=', $Checkexist)
					->where('tbl_all_transaction.type', '=', 'Buy Coin')
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

	//
	//----------   purchase coin  -------------------------------

	public function purchaseCoin(Request $request) {
		$rules = array(
			//'remember_token' => 'required',
			// 'btc'=>'required',
			// 'usd'=>'required',
			'coin' => 'required',
			'srno' => 'required|int',
		);

		$temp_info = md5($request->header('User-Agent'));
		if ($temp_info == Auth::User()->temp_info) {
			//dd(true);

		} else {

			$strMessage = 'BAD REQUEST';
			$intCode    = Response::HTTP_BAD_REQUEST;
			$strStatus  = Response::$statusTexts[$intCode];
			//return sendResponse($intCode, $strStatus, $strMessage, '');
		}
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

		$todayphase = IcoPhases::select('srno', 'name', 'from_date', 'to_date', 'total_supply', 'sold_supply', 'usd_rate', 'status', 'min_coin')->where('srno', '=', $request->srno)->where('status', '=', 'Available')->first();

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

				$paymentDate = date('Y-m-d H:i:s');
				$paymentDate = date('Y-m-d H:i:s', strtotime($paymentDate));
				//echo $paymentDate; // echos today!
				$contractDateBegin = date('Y-m-d H:i:s', strtotime($todayphase->from_date));
				$contractDateEnd   = date('Y-m-d H:i:s', strtotime($todayphase->to_date));

				if (($paymentDate >= $contractDateBegin) && ($paymentDate <= $contractDateEnd)) {
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'No Token available Sold Out', '');
				}

				$userdash = Dashboard::select('tbl_dashboard.top_up_wallet', 'tbl_dashboard.id', 'tbl_dashboard.coin')->join('tbl_users as tu', 'tu.id', '=', 'tbl_dashboard.id')
				                                                                                                      ->where('tu.status', '=', 'Active')
				                                                                                                      ->where('tu.type', '=', '')
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

			if ((int) $supplyBal < $request->coin) {

				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], '  Available coins '.$supplyBal, '');
			}

			$userdash = Dashboard::select('fund_wallet_withdraw', 'fund_wallet', 'top_up_wallet', 'id', 'top_up_wallet_withdraw', 'coin')->where('id', Auth::user()->id)->first();

			//$userTopupBal=$userdash->top_up_wallet - $userdash->top_up_wallet_withdraw;
			$userTopupBal = $userdash->fund_wallet-$userdash->fund_wallet_withdraw;

			$userBuycoinCost = $arrInput['coin']*$todayphase->usd_rate;

			if ($userTopupBal < $userBuycoinCost) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Check Your  New fund wallet Balance ', '');
			}

			$coin = custom_round($userdash->coin+$request->coin);
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
			$trans->remarks          = 'Buy Coin '.$request->coin;
			$trans->save();

			$transWallet = new WalletTransactionLog;

			$transWallet->from_user_id = Auth::user()->id;
			$transWallet->to_user_id   = Auth::user()->id;
			$transWallet->amount       = $userBuycoinCost;
			$transWallet->wallet_type  = 3;
			$transWallet->remark       = 'Buy Coin '.$request->coin;
			$transWallet->entry_time   = \Carbon\Carbon::now();
			$transWallet->save();

			//  dd($userTopupBal,$userBuycoinCost);

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Token Purchase successfully '.$request->coin, $this->emptyArray);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Phase Not Available ', '');

			# code...
		}

	}

	//------------  usd to coin and btc
	public function currenyConverter(Request $request) {
		$rules = array(
			'usd' => 'required|regex:/^\d*(\.\d{1,4})?$/',
		);

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

		if ($request->usd <= 0) {
			$arr = ['USD' => 0, 'BTC' => 0, 'ETH' => 0];
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arr);
		}
		$usd = $request->usd;

		$ethvalue  = $this->conversion->usdToEth();
		$eth_value = $ethvalue->original['data']['ETH'];
		$eth       = custom_round($usd*$eth_value, 7);

		$request->request->add(['usdvalue' => $usd]);
		$btcvalue = $this->conversion->usdTobtc($request);// btc value of current rate
		if ($btcvalue->original['status'] == 'OK') {
			$btc_value = custom_round(json_encode($btcvalue->original['data']['btc']), 7);
		}

		$arr = ['USD' => $usd, 'BTC' => custom_round($btc_value, 7), 'ETH' => $eth];
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arr);
	}

}
