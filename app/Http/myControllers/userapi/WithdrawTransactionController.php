<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;

use App\Http\Controllers\userapi\CurrencyConvertorController;
use App\Http\Controllers\userapi\SendotpController;
use App\Http\Controllers\userapi\Google2FAController;
use App\Models\Activitynotification;
use App\Models\AllTransaction;
use App\Models\CoinTransactionLog;
use App\Models\DailyBonus;
use App\Models\Dashboard;
use App\Models\Depositaddress;
use App\Models\verifyOtpStatus;
use App\Models\WhiteListIpAddress;
use App\Models\UserWithdrwalSetting;

use App\Models\ProjectSettings;
use App\Models\Topup;
use App\Models\Withdrawbydate;
use App\Models\UserInfo;

use App\Models\WithdrawPending;
use App\Models\WithdrawSettings;
use App\Traits\AddressValid;
use App\Traits\WithdrawTMode;
use App\User;
use Exception;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class WithdrawTransactionController extends Controller
{

	use WithdrawTMode, AddressValid;
	public function __construct(CurrencyConvertorController $conversion, Google2FAController $google2facontroller)
	{
		$this->statuscode = Config::get('constants.statuscode');
		$this->conversion = $conversion;
		$date             = \Carbon\Carbon::now();
		$this->today      = $date->toDateTimeString();
		$this->emptyArray = (object) array();
		$this->google2facontroller = $google2facontroller;
	}

	//========================================
	public function withdrawIncome(Request $request)
	{
		$day = \Carbon\Carbon::now()->format('D');
		try {
			$rules = array(
				'level_income_balance'  => 'required|',
				'direct_income_balance' => 'required|',
				'roi_balance'           => 'required|',
				'binary_income_balance' => 'required|',
				'topup_wallet'          => 'required|',
				'transfer_wallet'       => 'required|',
				'working_wallet'        => 'required|',
				'mode'                  => 'required|',
				'addTopup'              => 'required',
				'Currency_type'         => 'required|',
			);

			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			// dd(11);
			$arrTemp = [];
			$err     = '';
			foreach ($request->all() as $key => $value) {

				if ($key != 'remember_token' && $value != 0 && $value < 1) {
					$err = $err ./* ' ' . $key . */ ' withdraw amount should be minimum 1.';
				}/* else if ($key != 'remember_token' && $value % 10 != 0) {
				$err = ' withdraw amount should be multiple of 10.';
				}*//*else if ($key != 'remember_token' && $value > 5000) {*/
				//     $err = $err./* ' ' . $key . */' withdraw amount should be less than or equal to 5000.';
				// }
			}
			$user_id  = Auth::user()->id;
			$arrInput = $request->all();
			if ($arrInput['Currency_type'] == "BTC") {
				$currency_address = User::where('id', $user_id)->pluck('btc_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update BTC address to withdraw amount', '');
				}
			}
			// if ($arrInput['Currency_type'] == "TRX") {
			// 	$currency_address = User::where('id', $user_id)->pluck('trn_address')->first();

			// 	if (empty($currency_address)) {
			// 		$intCode   = Response::HTTP_NOT_FOUND;
			// 		$strStatus = Response::$statusTexts[$intCode];
			// 		return sendResponse($intCode, $strStatus, 'Please update Tron address to withdraw amount', '');
			// 	}
			// }
			if ($arrInput['Currency_type'] == "ETH") {
				$currency_address = User::where('id', $user_id)->pluck('ethereum')->first();

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

			/*if($day != 'Thu')
			{
			// dd('In');
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Wihdrwal is allowed on Thurday Only', '');
			}*/

			if (!empty($err)) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $err;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$users = User::select(
				'dash.id',
				'dash.level_income',
				'dash.level_income_withdraw',
				'dash.roi_income',
				'dash.roi_income_withdraw',
				'dash.binary_income',
				'dash.binary_income_withdraw',
				'dash.direct_income',
				'dash.direct_income_withdraw',
				'dash.top_up_wallet',
				'dash.top_up_wallet_withdraw',
				'dash.transfer_wallet',
				'dash.transfer_wallet_withdraw',
				'dash.working_wallet',
				'dash.working_wallet_withdraw',
				'dash.total_withdraw',
				'dash.usd',
				'tbl_users.btc_address'
			)->join('tbl_dashboard as dash', 'dash.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::User()->id], ['tbl_users.status', '=', 'Active']])->first();
			if (!empty($users)) {

				$getWithDraw = $this->getWithdrawMode($request);
				$arrInput    = $request->all();

				/*if ($users->google2fa_status == 'enable') {
				$intResponse = verify2Fa($arrInput['otp']);
				} else {
				$intResponse = verifyOtp($arrInput['otp']);
				}*/

				//  $getWithDraw=$this->getWithdrawMode($request);
				//  if($getWithDraw->original['code']=='200'){
				$intResponse = 200;
				if ($intResponse == '200') {

					// if ($getWithDraw->original['code'] == '200') {

					$level_bal    = $users->level_income - $users->level_income_withdraw;
					$roi_bal      = $users->roi_income - $users->roi_income_withdraw;
					$binary_bal   = $users->binary_income - $users->binary_income_withdraw;
					$direct_bal   = $users->direct_income - $users->direct_income_withdraw;
					$topup_bal    = $users->top_up_wallet - $users->top_up_wallet_withdraw;
					$transfer_bal = $users->transfer_wallet - $users->transfer_wallet_withdraw;
					$working_bal  = $users->working_wallet - $users->working_wallet_withdraw;

					$total_blce = $request->input('level_income_balance') + $request->input('roi_balance') + $request->input('binary_income_balance') + $request->input('direct_income_balance') + $request->input('topup_wallet') + $request->input('transfer_wallet') + $request->input('working_wallet') + $request->input('addTopup');

					if ($total_blce <= 0) {
						$arrMessage = 'Balance should be greater than 0';
					}/*else if ($level_bal < $request->input('level_income_balance')) {
					$arrMessage  = 'You have insufficient level  balance';
					} else if ($roi_bal < $request->input('roi_balance')) {
					$arrMessage  = 'You have insufficient roi  balance';
					} else if ($binary_bal < $request->input('binary_income_balance')) {
					$arrMessage  = 'You have insufficient binary income  balance';
					} else if ($direct_bal < $request->input('direct_income_balance')) {
					$arrMessage  = 'You have insufficient direct income  balance';
					} else if ($topup_bal < $request->input('topup_wallet')) {
					$arrMessage  = 'You have insufficient topup wallet  balance';
					} else if ($transfer_bal < $request->input('transfer_wallet')) {
					$arrMessage  = 'You have insufficient transfer wallet  balance';
					} */ else if ($working_bal < $request->input('working_wallet')) {
						$arrMessage = 'You have insufficient balance';
					} else if ($working_bal < $request->input('addTopup')) {
						$arrMessage = 'You have insufficient balance';
					}

					if (!empty($arrMessage)) {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = $arrMessage;
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
					$updateCoinData  = array();
					$tempData        = [];
					$Deductionamount = 0;
					$Deduction       = 0;
					$amount          = 0;

					$CheckFirstTopupExist = Topup::select('amount', 'id')->where([['id', '=', $users->id], ['amount', '>', 0]])->first();

					if (empty($CheckFirstTopupExist)) {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Kindly Invest first to withdraw your income';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
					//---------update level income wd
					if ($request->input('level_income_balance') != 0) {
						$updateCoinData['level_income_withdraw'] = custom_round(($users->level_income_withdraw + $request->input('level_income_balance')), 7);
						$tempData['level_income']                = $request->input('level_income_balance');
						$Deduction += WithdrawSettings::where([['income', '=', 'level_income_balance'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('level_income_balance') - (($request->input('level_income_balance') * $Deduction) / 100);

						$amount += (($request->input('level_income_balance') * $Deduction) / 100);
					}
					//---------update roi income wd
					if ($request->input('roi_balance') != 0) {
						$updateCoinData['roi_income_withdraw'] = custom_round(($users->roi_income_withdraw + $request->input('roi_balance')), 7);
						$tempData['roi_income']                = $request->input('roi_balance');

						$Deduction += WithdrawSettings::where([['income', '=', 'roi_balance'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('roi_balance') - (($request->input('roi_balance') * $Deduction) / 100);

						$amount += (($request->input('roi_balance') * $Deduction) / 100);
					}
					//---------update binary income wd
					if ($request->input('binary_income_balance') != 0) {
						$updateCoinData['binary_income_withdraw'] = custom_round(($users->binary_income_withdraw + $request->input('binary_income_balance')), 7);
						$tempData['binary_income']                = $request->input('binary_income_balance');

						$Deduction += WithdrawSettings::where([['income', '=', 'binary_income_balance'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('binary_income_balance') - (($request->input('binary_income_balance') * $Deduction) / 100);

						$amount += (($request->input('binary_income_balance') * $Deduction) / 100);
					}
					//---------update direct income wd
					if ($request->input('direct_income_balance') != 0) {
						$updateCoinData['direct_income_withdraw'] = custom_round(($users->direct_income_withdraw + $request->input('direct_income_balance')), 7);
						$tempData['direct_income']                = $request->input('direct_income_balance');

						$Deduction += WithdrawSettings::where([['income', '=', 'direct_income_balance'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('direct_income_balance') - (($request->input('direct_income_balance') * $Deduction) / 100);

						$amount += (($request->input('direct_income_balance') * $Deduction) / 100);
					}

					//--topup wallet

					if ($request->input('topup_wallet') != 0) {
						$updateCoinData['top_up_wallet_withdraw'] = custom_round(($users->top_up_wallet_withdraw + $request->input('topup_wallet')), 7);
						$tempData['top_up_wallet']                = $request->input('topup_wallet');

						$Deduction += WithdrawSettings::where([['income', '=', 'topup_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('topup_wallet') - (($request->input('topup_wallet') * $Deduction) / 100);

						$amount += (($request->input('topup_wallet') * $Deduction) / 100);
					}

					// transfer wallet

					if ($request->input('transfer_wallet') != 0) {
						$updateCoinData['transfer_wallet_withdraw'] = custom_round(($users->transfer_wallet_withdraw + $request->input('transfer_wallet')), 7);
						$tempData['transfer_wallet']                = $request->input('transfer_wallet');

						$Deduction += WithdrawSettings::where([['income', '=', 'transfer_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('transfer_wallet') - (($request->input('transfer_wallet') * $Deduction) / 100);
						$amount += (($request->input('transfer_wallet') * $Deduction) / 100);
					}
					// working wallet

					if ($request->input('working_wallet') != 0) {
						$updateCoinData['working_wallet_withdraw'] = custom_round(($users->working_wallet_withdraw + $request->input('working_wallet')), 7);
						$tempData['working_wallet']                = $request->input('working_wallet');

						$Deduction += WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('working_wallet') - (($request->input('working_wallet') * $Deduction) / 100);

						$amount += (($request->input('working_wallet') * $Deduction) / 100);
					}

					//---------update total withdraw amount
					$updateCoinData['usd']            = custom_round(($users->usd - $total_blce), 7);
					$updateCoinData['total_withdraw'] = custom_round(($users->total_withdraw + $total_blce), 7);

					//======withdraw pending
					$Toaddress   = $currency_address;
					$NetworkType = $request->input('Currency_type');
					/* dd($NetworkType );*/

					$withDrawdata = array();

					if ($request->input('addTopup') != 0 && $request->input('mode') === "transferToTopup") {
						$updateCoinData['top_up_wallet'] = custom_round(($users->top_up_wallet + $request->input('addTopup')), 7);
						$withDrawdata['status']          = 1;
						$withDrawdata['withdraw_type']   = 6;

						$updateCoinData['working_wallet_withdraw'] = custom_round(($users->working_wallet_withdraw + $request->input('addTopup')), 7);
						$Deduction += WithdrawSettings::where([['income', '=', 'transfer_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

						$Deductionamount += $request->input('addTopup') - (($request->input('addTopup') * $Deduction) / 100);
						$amount += (($request->input('addTopup') * $Deduction) / 100);
					}

					$withDrawdata['id']              = $users->id;
					$withDrawdata['amount']          = $request->input('working_wallet');
					$withDrawdata['transaction_fee'] = 0;
					$withDrawdata['deduction']       = 0;
					$withDrawdata['from_address']    = '';
					$withDrawdata['to_address']      = trim($Toaddress);
					$withDrawdata['network_type']    = $NetworkType;
					$withDrawdata['entry_time']      = $this->today;
					if ($request->input('roi_balance') != 0) {

						$withDrawdata['withdraw_type'] = 3;
					} else if ($request->input('working_wallet') != 0) {

						$withDrawdata['withdraw_type'] = 2;
					}

					$current_day = \Carbon\Carbon::now()->day;

					/* if ((($current_day === 01) || ($current_day === 16)) || $request->input('addTopup')) {*/

					$updateCoinData = Dashboard::where('id', $users->id)->limit(1)->update($updateCoinData);

					$WithDrawinsert = WithdrawPending::create($withDrawdata);

					$getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

					$Trandata1 = []; //insert in transaction
					$Trandata3 = [];
					foreach ($tempData as $key => $value) {
						$balance = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();
						array_push($Trandata1, [
							'id'           => $users->id,
							'network_type' => $getCoin,
							'refference'   => $users->id,
							'debit'        => $value,
							'balance'      => $balance - $value,
							'type'         => $key,
							'status'       => 1,
							'remarks'      => '$' . $value . ' has withdrawan from ' . $key,
							'entry_time'   => $this->today
						]);

						array_push($Trandata3, [
							'id'         => $users->id,
							'message'    => '$' . $value . ' has withdrawan from ' . $key,
							'status'     => 1,
							'entry_time' => $this->today
						]);
					}

					$TransactionDta1 = AllTransaction::insert($Trandata1);
					//----into acitviy notification
					$actDta = Activitynotification::insert($Trandata3);

					//------ email for withdrawal request
					$user_details = Auth::user();

					//   $eth_address = User::where('id',$user_id)->pluck('btc_address')->first();
					$email    = $user_details->email;
					$user_id  = $user_details->user_id;
					$subject  = " Withdrawal request send!";
					$pagename = "emails.withdraw_request";
					$data     = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id, 'amount' => $request->input('working_wallet'));
					$email    = $email;
					$mail     = sendMail($data, $email, $subject);

					//------ email for withdrawal request

					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Amount withdraw successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					/*  } else {
				$arrStatus   = Response::HTTP_NOT_FOUND;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = 'You can withdraw only on 1st and 16th day of the month';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}*/
				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid Otp';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} else {
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

	/**
	 * Function to send the otp
	 *
	 */
	public function withdrawOtp(Request $request)
	{
		$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
		$strMessage = trans('user.error');
		try {
			$user = Auth::user();

			$user_name = $user->email;
			$mail      = $user->email;
			$sendopt   = new SendotpController;

			$emailResponse = $sendopt->sendotponmail($user, $mail);

			$strMessage = trans('user.otpsent');
			$strStatus  = Response::$statusTexts[$intCode];
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Otp Send successfully', '');
		} catch (Exception $e) {
			dd($e);
		}
	}

	public function CheckAddressValid($UserExistid, $request)
	{
		$userAddress                     = Depositaddress::where([['id', $UserExistid]])->pluck('address')->first();
		$BtcTranFees                     = ProjectSettings::where([['status', 1]])->pluck('withdraw_btc_transaction_fee')->first();
		$userBtc                         = Dashboard::where([['id', $UserExistid]])->pluck('btc')->first();
		$withDrawdata                    = array();
		$withDrawdata['id']              = $UserExistid;
		$withDrawdata['amount']          = $request->input('btc');
		$withDrawdata['transaction_fee'] = $BtcTranFees;
		$withDrawdata['from_address']    = $userAddress;
		$withDrawdata['to_address']      = trim($request->input('to_address'));
		$withDrawdata['network_type']    = 'BTC';
		$withDrawdata['entry_time']      = $this->today;
		$WithDrawinsert                  = WithdrawPending::create($withDrawdata);
		if (!empty($WithDrawinsert)) {
			$balance                  = AllTransaction::where('id', '=', $UserExistid)->orderBy('srno', 'desc')->pluck('balance')->first();
			$Trandata                 = array(); // insert in transaction
			$Trandata['id']           = $UserExistid;
			$Trandata['network_type'] = 'BTC';
			$Trandata['refference']   = $WithDrawinsert->id;
			$Trandata['debit']        = $request->input('btc');
			$Trandata['balance']      = $balance - $request->input('btc');
			$Trandata['status']       = 1;
			$Trandata['entry_time']   = $this->today;
			$Trandata['remarks']      = 'Withdraw from BTC wallet to address: ' . trim($request->input('to_address')) . 'Withdraw id:' . $WithDrawinsert->id . '';
			$TransactionDta           = AllTransaction::create($Trandata);

			$totalBtc        = custom_round(($userBtc - $request->input('btc')), 7);
			$dashboardUpdate = Dashboard::where('id', $UserExistid)->update(['btc' => $totalBtc]);
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Withdraw btc successfully', $this->emptyArray);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
		}
	}

	//=============withdraw mode=================
	public function getWithdrawROIMode($id)
	{

		$userData = User::where('id', $id)->first();
		// dd($userData);
		if (!empty($userData)) {
			// $mode1 = WithdrawMode::where('id', $userData->id)->select('network_type')->orderBy('id', 'desc')->first();
			// dd($mode1);
			/*    if (empty($mode1)) {

			$checkTopup = Topup::where('id', $userData->id)->select('id','payment_type')->first();

			if (!empty($checkTopup)) {
			$type = $checkTopup->payment_type;
			}

			} else {
			$mode = $mode1;
			$type = $mode->network_type;
			}*/

			/* if (!isset($type) || $type == '') {
			$type = 'BTC';
			$mode = '1';
			}*/
			$type = 'BTC';
			$mode = '1';

			//  $arrData['mode'] = $type;
			//  return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arrData);

			if (!empty($mode) && $type == 'BTC') {

				$btc_address = User::where('id', $userData->id)->pluck('btc_address')->first();
				if (!empty($btc_address)) {
					// validate address using block chain
					$blockchain_address = blockchain_address(trim($btc_address));
					if ($blockchain_address['msg'] == 'failed') {
						$blockio_address = blockio_address(trim($btc_address));
						if ($blockio_address['msg'] == 'fail') {
							$blockcyper_address = blockcyper_address(trim($btc_address));
							if ($blockcyper_address['msg'] == 'failed') {
								$blockbitaps_address = blockbitaps_address(trim($btc_address));
								if ($blockbitaps_address['msg'] == 'failed') {
									return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Address is not valid', '');
								} else {
									$arrData['address'] = $btc_address;
									$arrData['mode']    = $type;
									return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Address found', $arrData);
								}
							} else {
								$arrData['address'] = $btc_address;
								$arrData['mode']    = $type;
								return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Address found', $arrData);
							}
						} else {
							$arrData['address'] = $btc_address;
							$arrData['mode']    = $type;
							return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Address found', $arrData);
						}
					} else {
						$arrData['address'] = $btc_address;
						$arrData['mode']    = $type;
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Address found', $arrData);
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BTC address not found', '');
				}
			} else if (!empty($mode) && $type == 'ETH') {
				$eth_address = User::where('id', $userData->id)->pluck('ethereum')->first();
				if (!empty($eth_address)) {
					$eth_status = ETHConfirmation($eth_address);
					if ($eth_status['msg'] == 'success') {
						$arrData['address'] = $eth_address;
						$arrData['mode']    = $type;
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'ETH address found', $arrData);
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ETH address not valid', '');
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ETH address not found', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Address not found', '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
		}
	}

	public function withdrawROIIncome($cost, $remember_token, $dailyid)
	{

		$updateCoinData = array();
		$tempData       = [];

		$users = User::join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['remember_token', '=', trim($remember_token)], ['status', '=', 'Active']])->first();
		//---------update roi income wd

		$updateCoinData['roi_income_withdraw'] = custom_round(($users->roi_income_withdraw + $cost), 7);
		$tempData['roi_income']                = $cost;
		//---------update total withdraw amount
		$updateCoinData['usd']            = custom_round(($users->usd - $cost), 7);
		$updateCoinData['total_withdraw'] = custom_round(($users->total_withdraw + $cost), 7);
		$updateCoinData                   = Dashboard::where('id', $users->id)->limit(1)->update($updateCoinData);

		$updateDailyData['status'] = 'Paid';
		$updateDailyData           = DailyBonus::where('sr_no', $dailyid)->limit(1)->update($updateDailyData);

		$getWithDraw = $this->getWithdrawROIMode($remember_token);
		if ($getWithDraw->original['data']['address'] != '') {

			//======withdraw pending
			$Toaddress   = $getWithDraw->original['data']['address'];
			$NetworkType = $getWithDraw->original['data']['mode'];

			$withDrawdata                    = array();
			$withDrawdata['id']              = $users->id;
			$withDrawdata['amount']          = $cost;
			$withDrawdata['transaction_fee'] = 0;
			$withDrawdata['from_address']    = trim($Toaddress);
			$withDrawdata['network_type']    = $NetworkType;
			$withDrawdata['entry_time']      = $this->today;
			$WithDrawinsert                  = WithdrawPending::create($withDrawdata);

			$getCoin   = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();
			$Trandata1 = []; //insert in transaction
			$Trandata3 = [];
			foreach ($tempData as $key => $value) {
				$balance = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();
				array_push($Trandata1, [
					'id'           => $users->id,
					'network_type' => $getCoin,
					'refference'   => $users->id,
					'debit'        => $value,
					'balance'      => $balance - $value,
					'type'         => $key,
					'status'       => 1,
					'remarks'      => 'Withdraw ' . $key . ' amount :' . $value . '$'
				]);

				array_push($Trandata3, [
					'id'      => $users->id,
					'message' => 'Withdraw ' . $key . ' amount :' . $value . '$',
					'status'  => 1,
				]);
			}

			$TransactionDta1 = AllTransaction::insert($Trandata1);
			//----into acitviy notification
			$actDta = Activitynotification::insert($Trandata3);

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully ', $this->emptyArray);
		} else {
			return $getWithDraw;
		}
	}

	/**
	 * [showWithdrawHistory description]
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function showWithdrawHistory(Request $request)
	{
		$arrInput    = $request->all();
		$user_id     = Auth::user()->id;
		$isExistUser = User::Select("id")->Where("id", $user_id)->first();
		if (!is_null($isExistUser)) {
			$query = DB::table('tbl_withdrwal_history AS withdraw')
				->Select(
					"withdraw.id",
					"withdraw.transaction_hash",
					"withdraw.toUserId",
					"withdraw.amount",
					"withdraw.amount_in_btc",
					"withdraw.deduction",
					"withdraw.total_amount",
					"withdraw.rec_date",
					"withdraw.from_address",
					"withdraw.to_address",
					"withdraw.remark",
					"withdraw.status",
					"withdraw.w_status",
					"withdraw.type",
					"withdraw.payment_mode_amt",
					"withdraw.paid_date",
					"withdraw.coinpayment_w_id",
					"withdraw.notification",
					"users.user_id AS User"
				)
				->join('tbl_users AS users', function ($join) {
					$join->on('users.id', '=', 'withdraw.toUserId');
				})
				->where("withdraw.toUserId", $user_id);
			if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
				//searching loops on fields
				$fields = getTableColumns('tbl_withdrwal_history');
				$search = $arrInput['search']['value'];
				$query  = $query->where(function ($query) use ($fields, $search) {
					foreach ($fields as $field) {
						$query->orWhere('withdraw.' . $field, 'LIKE', '%' . $search . '%');
					}
					$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
				});
			}

			$query        = $query->orderBy('withdraw.id', 'desc');
			$totalRecord  = $query->get()->count();
			$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

			$arrData['recordsTotal']    = $totalRecord;
			$arrData['recordsFiltered'] = $totalRecord;
			$arrData['records']         = $arrDirectInc;

			if (!empty($arrDirectInc) && count($arrDirectInc) > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found successfully';
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
	}

	/**
	 * [sAuto withdraw working Income ]
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function AutoWithdrawWorkingIncome($id)
	{

		$users = User::join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')
			->where([['tbl_users.id', '=', trim($id)], ['status', '=', 'Active']])
			->first();

		$working_wallet_balance = $users->working_wallet - $users->working_wallet_withdraw;
		$roi_income_balance     = $users->roi_income - $users->roi_income_withdraw;

		$getWithDraw = $this->getWithdrawROIMode($id);
		if (!empty($getWithDraw->original['code']) && $getWithDraw->original['code'] != 404) {
			$tempData        = [];
			$Deductionamount = 0;
			$Deduction       = 0;
			$amount          = 0;
			$total_blce      = 0;
			$working_amount  = $users->working_wallet;

			if ($working_wallet_balance != 0 && $working_wallet_balance >= 10) {
				$updateCoinData['working_wallet_withdraw'] = custom_round(($users->working_wallet_withdraw + $working_wallet_balance), 7);
				$Deduction += WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

				$Deduction1 = WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

				$Deductionamount += $working_wallet_balance - (($working_wallet_balance * $Deduction1) / 100);
				$amount += (($working_wallet_balance * $Deduction1) / 100);
				$total_blce += $working_wallet_balance;

				$updateCoinData['usd']            = custom_round(($users->usd - $total_blce), 7);
				$updateCoinData['total_withdraw'] = custom_round(($users->total_withdraw + $total_blce), 7);
				$updateCoinData                   = Dashboard::where('id', $users->id)->limit(1)->update($updateCoinData);

				$transaction_type = 'working_wallet';
				$remark           = 'Withdraw working_wallet amount $:' . $working_wallet_balance;
				$withdraw_type    = 2;

				$Toaddress = $getWithDraw->original['data']['address'];
				// $NetworkType = $getWithDraw->original['data']['mode'];
				$NetworkType = 'USD';
				//$getWithDraw->original['data']['mode'];
				$withDrawdata       = array();
				$withDrawdata['id'] = $users->id;
				// $withDrawdata['withdraw_id'] = rand(1000000000, 9999999999);
				$withDrawdata['amount']          = custom_round($Deductionamount, 7);
				$withDrawdata['transaction_fee'] = custom_round($Deduction, 7);
				$withDrawdata['deduction']       = custom_round($amount, 7);
				$withDrawdata['to_address']      = trim($Toaddress);
				$withDrawdata['network_type']    = $NetworkType;
				$withDrawdata['entry_time']      = $this->today;
				$withDrawdata['withdraw_type']   = $withdraw_type;
				$WithDrawinsert                  = WithdrawPending::create($withDrawdata);

				$getCoin                  = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();
				$balance                  = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();
				$Trandata                 = array(); // insert in transaction
				$Trandata['id']           = $users->id;
				$Trandata['network_type'] = $getCoin;
				$Trandata['refference']   = $users->id;
				$Trandata['debit']        = custom_round($total_blce, 7);
				$Trandata['balance']      = custom_round(($balance - $total_blce), 7);
				$Trandata['status']       = 1;
				$Trandata['type']         = $transaction_type;
				$Trandata['remarks']      = $remark;
				$Trandata['entry_time']   = $this->today;
				$TransactionDta           = AllTransaction::create($Trandata);
				//----into acitviy notification
				$actdata               = array(); // insert in transaction
				$actdata['id']         = $users->id;
				$actdata['message']    = $remark;
				$actdata['status']     = 1;
				$actdata['entry_time'] = $this->today;
				$actDta                = Activitynotification::create($actdata);
				$whatsappMsg           = "Congratulation,\nAuto withdrawal of " . custom_round($Deductionamount, 7) . " USD has been successfully placed in your User id:" . $users->user_id . " on date:" . $this->today . " \nAmount will be credited  soon.\n www.dollardevice.net";
				$countrycode           = getCountryCode($users->country);
				$mobile                = $users->mobile;
				//sendWhatsappMsg($countrycode,$mobile,$whatsappMsg);
				//  sendSMS($mobile, $whatsappMsg);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully ', $this->emptyArray);
			}
		} else {
			$whatsappMsg = "Dear " . $users->user_id . ", \nTo withdraw  amount please contact to admin. \n www.dollardevice.net";

			$countrycode = getCountryCode($users->country);
			$mobile      = $users->mobile;
			// sendSMS($mobile, $whatsappMsg);
			//sendWhatsappMsg($countrycode,$mobile,$whatsappMsg);
		}
	}

	/**
	 * [sAuto withdraw ROI Income ]
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function AutoWithdrawRoiIncome($id)
	{

		$users = User::join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')
			->where([['tbl_users.id', '=', trim($id)], ['status', '=', 'Active']])
			->first();

		//  $working_wallet_balance = $users->working_wallet-$users->working_wallet_withdraw;
		$roi_income_balance = $users->roi_income - $users->roi_income_withdraw;

		$getWithDraw = $this->getWithdrawROIMode($id);
		// dd($getWithDraw);
		if (!empty($getWithDraw->original['code']) && $getWithDraw->original['code'] != 404) {
			$tempData        = [];
			$Deductionamount = 0;
			$Deduction       = 0;
			$amount          = 0;
			$total_blce      = 0;
			//$working_amount=$users->working_wallet;
			$roi_amount = $users->roi_income;

			if ($roi_income_balance != 0 && $roi_income_balance >= 10) {
				$updateCoinData['roi_income_withdraw'] = custom_round(($users->roi_income_withdraw + $roi_income_balance), 7);
				// $updateCoinData['working_wallet_withdraw'] = custom_round(($users->working_wallet_withdraw + $working_wallet_balance), 7);
				//  $Deduction += WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();
				//
				//     $Deduction1= WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

				//  $Deductionamount += $working_wallet_balance - (($working_wallet_balance* $Deduction1) / 100);
				//    $amount+=(($working_wallet_balance * $Deduction1) / 100);
				$total_blce += $roi_income_balance;

				$updateCoinData['usd']            = custom_round(($users->usd - $total_blce), 7);
				$updateCoinData['total_withdraw'] = custom_round(($users->total_withdraw + $total_blce), 7);
				$updateCoinData                   = Dashboard::where('id', $users->id)->limit(1)->update($updateCoinData);

				$transaction_type = 'roi_income';
				$remark           = 'Withdraw Roi wallet amount $:' . $roi_income_balance;
				$withdraw_type    = 3;

				$Toaddress   = $getWithDraw->original['data']['address'];
				$NetworkType = 'USD';
				//$getWithDraw->original['data']['mode'];
				$withDrawdata       = array();
				$withDrawdata['id'] = $users->id;
				// $withDrawdata['withdraw_id'] = rand(1000000000, 9999999999);
				$withDrawdata['amount']          = custom_round($total_blce, 7);
				$withDrawdata['transaction_fee'] = 0;
				$withDrawdata['deduction']       = 0;
				//custom_round($amount,7);
				$withDrawdata['to_address']    = trim($Toaddress);
				$withDrawdata['network_type']  = $NetworkType;
				$withDrawdata['entry_time']    = $this->today;
				$withDrawdata['withdraw_type'] = $withdraw_type;
				$WithDrawinsert                = WithdrawPending::create($withDrawdata);

				$getCoin                  = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();
				$balance                  = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();
				$Trandata                 = array(); // insert in transaction
				$Trandata['id']           = $users->id;
				$Trandata['network_type'] = $getCoin;
				$Trandata['refference']   = $users->id;
				$Trandata['debit']        = custom_round($total_blce, 7);
				$Trandata['balance']      = custom_round(($balance - $total_blce), 7);
				$Trandata['status']       = 1;
				$Trandata['type']         = $transaction_type;
				$Trandata['remarks']      = $remark;
				$Trandata['entry_time']   = $this->today;
				$TransactionDta           = AllTransaction::create($Trandata);
				//----into acitviy notification
				$actdata               = array(); // insert in transaction
				$actdata['id']         = $users->id;
				$actdata['message']    = $remark;
				$actdata['status']     = 1;
				$actdata['entry_time'] = $this->today;
				$actDta                = Activitynotification::create($actdata);
				$whatsappMsg           = "Congratulation,\nAuto withdrawal of " . custom_round($total_blce, 7) . " USD has been successfully placed in your User id:" . $users->user_id . " on date:" . $this->today . " \nAmount will be credited  soon.";
				$countrycode           = getCountryCode($users->country);
				$mobile                = $users->mobile;
				//sendWhatsappMsg($countrycode,$mobile,$whatsappMsg);
				// sendSMS($mobile, $whatsappMsg);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully ', $this->emptyArray);
			}
		} else {
			$whatsappMsg = "Dear " . $users->user_id . ", \nTo withdraw  amount please contact to admin.";

			$countrycode = getCountryCode($users->country);
			$mobile      = $users->mobile;
			// sendSMS($mobile, $whatsappMsg);
			//sendWhatsappMsg($countrycode,$mobile,$whatsappMsg);
		}
	}

	public function workingWalletWithdraw(Request $request)
	{
		DB::beginTransaction();
		try {
			$rules = array(
				'amount'  => 'required|int|min:25',
				'address' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$address = $this->checkAddressValidation($request);

			if ($address != 'BTC' && $address != 'ETH') {
				return $address;
			}

			$id                     = Auth::user()->id;
			$dash                   = Dashboard::where('id', $id)->first();
			$working_wallet_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

			if ($request->amount <= $working_wallet_balance) {
				$updateData['working_wallet_withdraw'] = custom_round(($dash->working_wallet_withdraw + $request->amount), 7);
				Dashboard::where('id', $id)->limit(1)->update($updateData);

				$withDrawdata       = array();
				$withDrawdata['id'] = $id;
				// $withDrawdata['withdraw_id'] = rand(1000000000, 9999999999);
				$withDrawdata['amount']          = custom_round($request->amount, 7);
				$withDrawdata['transaction_fee'] = 0;
				$withDrawdata['deduction']       = 0;
				//$withDrawdata['to_address'] = trim($Toaddress);
				$withDrawdata['network_type']  = 'USD';
				$withDrawdata['entry_time']    = \Carbon\Carbon::now();
				$withDrawdata['withdraw_type'] = 2;
				$WithDrawinsert                = WithdrawPending::create($withDrawdata);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Amount should be  under your wallet balance', '');
			}
		} catch (Exception $e) {
			DB::rollBack();
		}
		DB::commit();
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
	}
	public function withdrawWorkingWalletold(Request $request)
	{
		try {
			$message = array('');
			$rules = array(
				'working_wallet' => 'required|numeric|min:20',
				'Currency_type' => 'required',
			);
			$messages = array(
				'working_wallet.required' => 'Please enter amount',
				'Currency_type.required' => 'Please select currency',
				'working_wallet.numeric' => 'Please enter valid amount',
				'working_wallet.min' => 'Amount must be minimum ',
			);
			/*$request->Currency_type = "USDT.TRC20"*/
			$validator = checkvalidation($request->all(), $rules, $messages);
			if (!empty($validator)) {
				$arrStatus   = Response::HTTP_NOT_FOUND;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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

			$id = Auth::User()->id;
			$statusw = UserInfo::where('id', $id)->select("auto_withdraw_status", "withdraw_status")->first();
			$auto_status = $statusw->auto_withdraw_status;
			$withdraw_status = $statusw->withdraw_status;
			$user_id = $id;
			if ($withdraw_status == 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], "Your withdraw requests stopped please contact with support team", '');
			}
			$check_if_ref_exist = Topup::select('id')->where('roi_status', 'Active')->where('id', $user_id)->count('id');

			if ($check_if_ref_exist == 0) {

				$arrStatus = Response::HTTP_NOT_FOUND;;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Not having active topup';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$projectSettings = ProjectSettings::where('status', 1)
				->select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg', 'auto_withdrawal_limit', 'auto_withdrawal_status', 'withdraw_method')->first();

			$day = \Carbon\Carbon::now()->format('D');
			// dd($day);          
			$hrs = \Carbon\Carbon::now()->format('H');
			$hrs = (int) $hrs;
			$currmonth = \Carbon\Carbon::now()->format('m');
			$curryear = \Carbon\Carbon::now()->format("Y");
			$currday = \Carbon\Carbon::now()->format("d");
			$currsecmon =  date("d", strtotime("second monday", mktime(0, 0, 0, $currmonth, 1, $curryear)));
			$currforthmon =  date("d", strtotime("fourth monday", mktime(0, 0, 0, $currmonth, 1, $curryear)));
			$days = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");
			if ($projectSettings->withdraw_status == "off") {
				$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals next Sunday.';
				if ($projectSettings->withdraw_off_msg != '' && $projectSettings->withdraw_off_msg != NULL) {
					$msg = $projectSettings->withdraw_off_msg;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			}
			if($day != $projectSettings->withdraw_day){
				$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals on next '.$days[$projectSettings->withdraw_day];
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			}
			/* if($currday == $currsecmon || $currday == $currforthmon){

            }else{
                 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on 2nd & 4th '.$days[$projectSettings->withdraw_day], ''); 
            } */
			// elseif($hrs < $projectSettings->withdraw_start_time){
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on 2nd & 4th '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, ''); 
			// }

			$id = Auth::User()->id;
			$arrInput            = $request->all();
			$arrInput['user_id'] = $id;
			$arrRules            = ['otp' => 'required|min:6|max:6'];
			$validator           = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				return setValidationErrorMessage($validator);
			}
			$verify_otp = verify_Otp($arrInput);

			if (!empty($verify_otp)) {
				if ($verify_otp['status'] == 200) {
				} else {
					$arrStatus = Response::HTTP_NOT_FOUND;;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid or expired Otp!';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
				}
			} else {
				$arrStatus = Response::HTTP_NOT_FOUND;;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid or expired Otp!';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			}


			$arrInput = $request->all();
			$currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
			if (empty($currency_address)) {
				$intCode      = Response::HTTP_NOT_FOUND;
				$strStatus    = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
			} else {
				if (!empty($currency_address->block_user_date_time)) {
					$today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
					if ($currency_address->block_user_date_time >= $today) {
						$intCode      = Response::HTTP_NOT_FOUND;
						$strStatus    = Response::$statusTexts[$intCode];
						/*return sendResponse($intCode, $strStatus, 'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)', '');*/
					}
				}
			}

			if ($arrInput['Currency_type'] == "BTC") {
				if ($request->working_wallet < 60) {
					$intCode      = Response::HTTP_NOT_FOUND;
					$strStatus    = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Amount must be greater than or equal to 60', '');
				}
			}
			/* if($arrInput['Currency_type1'] == "BTC")
            {
                if($request->working_wallet < 60)
                {   
                    $intCode      = Response::HTTP_NOT_FOUND;
                    $strStatus    = Response::$statusTexts[$intCode];
                    return sendResponse($intCode,$strStatus,'Amount must be greater than or equal to 60','');
                }
            }*/
			// dd($arrInput); 
			/*if($arrInput['Currency_type'] == "BTC")
            {
                $currency_address = User::where('id',$user_id)->pluck('btc_address')->first();
            
                if (empty($currency_address)) {
                    $intCode      = Response::HTTP_NOT_FOUND;
                    $strStatus    = Response::$statusTexts[$intCode];
                    return sendResponse($intCode,$strStatus,'Please update BTC address to withdraw amount','');
                }
            }
            if($arrInput['Currency_type'] == "TRX")
            {
                $currency_address = User::where('id',$user_id)->pluck('trn_address')->first();
            
                if (empty($currency_address)) {
                    $intCode      = Response::HTTP_NOT_FOUND;
                    $strStatus    = Response::$statusTexts[$intCode];
                    return sendResponse($intCode,$strStatus,'Please update Tron address to withdraw amount','');
                }
            }
            if($arrInput['Currency_type'] == "ETH")
            {
                $currency_address = User::where('id',$user_id)->pluck('ethereum')->first();
            
                if (empty($currency_address)) {
                    $intCode      = Response::HTTP_NOT_FOUND;
                    $strStatus    = Response::$statusTexts[$intCode];
                    return sendResponse($intCode,$strStatus,'Please update ETH address to withdraw amount','');
                }
            }
            if($arrInput['Currency_type'] == "BNB.ERC20")
            {
                $currency_address = User::where('id',$user_id)->pluck('bnb_address')->first();
            
                if (empty($currency_address)) {
                    $intCode      = Response::HTTP_NOT_FOUND;
                    $strStatus    = Response::$statusTexts[$intCode];
                    return sendResponse($intCode,$strStatus,'Please update BNB address to withdraw amount','');
                }
            }*/
			$dash = Dashboard::where('id', $id)->select('working_wallet', 'working_wallet_withdraw')->first();
			$working_wallet_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

			if ($request->working_wallet <= $working_wallet_balance) {
				// $Deduction = WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();
				$insertId = 0;
				$method = $projectSettings->withdraw_method;
				/*if ($request->working_wallet <= 200) {*/
				if ($auto_status == 1 && $request->input('working_wallet') <= $projectSettings->auto_withdrawal_limit && $projectSettings->auto_withdrawal_status == 'on') {
					$admin_otp = DB::table('tbl_names_memo')->where('name_type', $method)->select('subject')->pluck('subject')->first();
					$insertId = generateName($admin_otp);
				}
				/*}*/
				$Deduction = WithdrawSettings::where([['income', '=', 'wallet_withdrawal'], ['status', '=', 'Active']])->pluck('deduction')->first();

				$Deductionamount = (($request->input('working_wallet') * $Deduction) / 100);

				$amount = $request->input('working_wallet') - $Deductionamount;
				$updateData = array();
				$updateData['working_wallet_withdraw'] = DB::raw("working_wallet_withdraw + " . $request->working_wallet);
				$updtdash = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

				$tpFrom = 'S';

				$topupFrom = Topup::where('id', '=', $id)->where('top_up_by', 1)->count('srno');
				if ($topupFrom >= 1) {
					$tpFrom = 'A';
				}

				$Toaddress = $currency_address->currency_address;
				$NetworkType = $request->Currency_type;
				$withDrawdata = array();
				$withDrawdata['id'] = $id;
				$withDrawdata['amount'] = $amount;
				$withDrawdata['transaction_fee'] = 0;
				$withDrawdata['deduction'] = $Deductionamount;
				$withDrawdata['from_address'] = '';
				$withDrawdata['to_address'] = trim($Toaddress);
				$withDrawdata['network_type'] = $NetworkType;
				$withDrawdata['entry_time'] = $this->today;
				$withDrawdata['withdraw_type'] = 2;
				$withDrawdata['remark'] = $request->remark;
				$withDrawdata['topupfrom'] = $tpFrom;
				$withDrawdata['withdraw_method'] = $method;
				$withDrawdata['api_sr_no'] = $insertId;
				$withDrawdata['ip_address'] = $_SERVER['REMOTE_ADDR'];
				$WithDrawinsert = WithdrawPending::create($withDrawdata);

				$insertIPDetails = array();
				$insertIPDetails['user_id'] = $id;
				if ($method == "N") {
					$insertIPDetails['product_url'] = "NodeApi";
				} else if ($method == "C") {
					$insertIPDetails['product_url'] = "CoinPayment";
				} else {
					$insertIPDetails['product_url'] = "-";
				}

				$getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

				$balance = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
				$Trandata1 = array(
					'id' => $id,
					'network_type' => $getCoin,
					'refference' => $id,
					'debit' => $request->working_wallet,
					'balance' => $balance - $request->working_wallet,
					'type' => "working_wallet",
					'status' => 1,
					'remarks' => '$' . $request->working_wallet . ' has withdrawan from working wallet',
					'entry_time' => $this->today
				);

				$Trandata3 = array(
					'id' => $id,
					'message' => '$' . $request->working_wallet . ' has withdrawan from working wallet',
					'status' => 1,
					'entry_time' => $this->today
				);

				$TransactionDta1 = AllTransaction::insert($Trandata1);
				//----into acitviy notification
				$actDta = Activitynotification::insert($Trandata3);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
			}
		} catch (Exception $e) {
			dd($e);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}



	public function withdrawWorkingWallet(Request $request)
	{
		try {
			$message = array('');
			$rules   = array(
				'working_wallet' => 'required|numeric|min:10',
				'Currency_type'  => 'required',
			);
			$messages = array(
				'working_wallet.required' => 'Please enter amount',
				'Currency_type.required'  => 'Please select currency',
				'working_wallet.numeric'  => 'Please enter valid amount',
				'working_wallet.min'      => 'Amount must be minimum 10',
				'working_wallet.digit'	  => 'You can enter maximum 8 digit'
			);
			$validator = checkvalidation($request->all(), $rules, $messages);
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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

			if(Auth::User()->topup_status == 0)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Inactive User", ''); 
            }

            $statusw = UserInfo::where('id', Auth::User()->id)->pluck("withdraw_block_by_admin")->first();
            $withdraw_status = $statusw;
            if($withdraw_status == 1)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Your withdraw requests stopped please contact with support team", ''); 
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
					//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of month', '');
				}
			}
			// if ($projectSettings->withdraw_status == "off") {
			// 	$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals next Sunday.';
			// 	if ($projectSettings->withdraw_off_msg != '' && $projectSettings->withdraw_off_msg != NULL) {
			// 		$msg = $projectSettings->withdraw_off_msg;
			// 	}
			// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			// }

			$user_id = Auth::user()->id;
			$arrInput = $request->all();
			$currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
			if (empty($currency_address)) {
				$intCode      = Response::HTTP_NOT_FOUND;
				$strStatus    = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
			} else {
				if (!empty($currency_address->block_user_date_time)) {
					$today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
					if ($currency_address->block_user_date_time >= $today) {
						$intCode      = Response::HTTP_NOT_FOUND;
						$strStatus    = Response::$statusTexts[$intCode];
						/*return sendResponse($intCode, $strStatus, 'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)', '');*/
					}
				}
			}

			// if($day != $projectSettings->withdraw_day)
			// {
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// } elseif($hrs < $projectSettings->withdraw_start_time){
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// }
			$id       = Auth::user()->id;
			$user_id  = $id;
			$arrInput = $request->all();
			/*if ($arrInput['Currency_type'] == "BTC") {
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
			}*/


			$withdraw_status = verifyOtpStatus::select('withdraw_update_status')
				->where('statusID', '=', 1)->get();
				$arrInput            = $request->all();
			$userData=User::where('id',$user_id)->first();
			if ($withdraw_status[0]->withdraw_update_status == 1) {
				if ($userData->google2fa_status=='disable') {
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
				
			} else {
				$otpdata['status'] = 200;
			}
			$users_id=Auth::user()->id;

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
			// dd($otpdata['status']);
			if ($otpdata['status'] == 200) {
				$dash = Dashboard::where('id', $id)->select('working_wallet', 'working_wallet_withdraw')
					->first();
				$working_wallet_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

				if ($request->working_wallet <= $working_wallet_balance) {
					$Deduction = WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])
						->pluck('deduction')->first();

					$Deductionamount = (($request->input('working_wallet') * $Deduction) / 100);

					$amount                                = $request->input('working_wallet') - $Deductionamount;
					$updateData                            = array();
					$updateData['working_wallet_withdraw'] = DB::raw("working_wallet_withdraw + " . $request->working_wallet);
					$updtdash                              = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

					$Toaddress                       = $currency_address->currency_address;
					$NetworkType                     = $request->Currency_type;
					$withDrawdata                    = array();
					$withDrawdata['id']              = $id;
					$withDrawdata['amount']          = $amount;
					$withDrawdata['transaction_fee'] = 0;
					$withDrawdata['deduction']       = $Deductionamount;
					$withDrawdata['from_address']    = '';
					$withDrawdata['to_address']      = trim($Toaddress);
					$withDrawdata['network_type']    = $NetworkType;
					$withDrawdata['ip_address']    = getIPAddress();
					$withDrawdata['entry_time']      = $this->today;
					$withDrawdata['withdraw_type']   = 2;
					$WithDrawinsert                  = WithdrawPending::create($withDrawdata);

					$getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

					$balance   = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
					$Trandata1 = array(
						'id'           => $id,
						'network_type' => $getCoin,
						'refference'   => $id,
						'debit'        => $request->working_wallet,
						'balance'      => $balance - $request->working_wallet,
						'type'         => "working_wallet",
						'status'       => 1,
						'remarks'      => '$' . $request->working_wallet . ' has withdrawn from working wallet',
						'entry_time'   => $this->today
					);

					$Trandata3 = array(
						'id'         => $id,
						'message'    => '$' . $request->working_wallet . ' has withdrawn from working wallet',
						'status'     => 1,
						'entry_time' => $this->today
					);

					$TransactionDta1 = AllTransaction::insert($Trandata1);
					//----into acitviy notification
					$actDta = Activitynotification::insert($Trandata3);

					$subject = "Withdraw request has been submitted successfully";
					$pagename = "emails.Withdrawal_fund";
					$withdraw_from="Working Wallet";
					$entry_time= date('d M Y',strtotime($this->today));
					$data = array('pagename' => $pagename, 'username' => Auth::User()->user_id, 'name' => Auth::User()->fullname,'date'=>$entry_time,'withdraw_amount'=>$request->working_wallet,'fees'=>$Deductionamount,'wallet_address'=>trim($Toaddress),'withdraw_from'=>$withdraw_from);
					$mail = sendMail($data, Auth::User()->email, $subject);

					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $otpdata['msg'], '');
			}
		} catch (Exception $e) {
			dd($e);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}



	public function withdrawROIWallet(Request $request)
	{
		try {

			/*$intCode   = Response::HTTP_NOT_FOUND;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, 'Your gateway to a strong financial future is going to open soon!', '');

			dd("stop");*/

			$message = array('');
			$rules   = array(
				'working_wallet' => 'required|numeric|min:10',
				'Currency_type'  => 'required',
			);
			$messages = array(
				'working_wallet.required' => 'Please enter amount',
				'Currency_type.required'  => 'Please select currency',
				'working_wallet.numeric'  => 'Please enter valid amount',
				'working_wallet.min'      => 'Amount must be minimum 10',
				'working_wallet.digit'	  => 'You can enter maximum 8 digit'
			);
			$validator = checkvalidation($request->all(), $rules, $messages);
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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
					//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of month', '');
				}
			}
			// if ($projectSettings->withdraw_status == "off") {
			// 	$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals next Sunday.';
			// 	if ($projectSettings->withdraw_off_msg != '' && $projectSettings->withdraw_off_msg != NULL) {
			// 		$msg = $projectSettings->withdraw_off_msg;
			// 	}
			// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			// }

			$user_id = Auth::user()->id;
			$arrInput = $request->all();
			$target_business = Topup::where([['id', Auth::User()->id],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
	        $total_business= Auth::User()->l_guardian + Auth::User()->r_guardian;
	        if (!empty($target_business)) {
		        if ($total_business < $target_business) {
		        	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Please achieve your target first", ''); 
		        }
	        }
			$currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
			if (empty($currency_address)) {
				$intCode      = Response::HTTP_NOT_FOUND;
				$strStatus    = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
			} else {
				if (!empty($currency_address->block_user_date_time)) {
					$today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
					if ($currency_address->block_user_date_time >= $today) {
						$intCode      = Response::HTTP_NOT_FOUND;
						$strStatus    = Response::$statusTexts[$intCode];
						/*return sendResponse($intCode, $strStatus, 'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)', '');*/
					}
				}
			}


			// if($day != $projectSettings->withdraw_day)
			// {
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// } elseif($hrs < $projectSettings->withdraw_start_time){
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// }
			$id       = Auth::user()->id;
			$user_id  = $id;
			$arrInput = $request->all();
			/*if ($arrInput['Currency_type'] == "BTC") {
				// dd($user_id);
				// $currency_address = User::where('id', $user_id)->pluck('btc_address')->first();
				$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'BTC')->pluck('currency_address')->first();
				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update BTC address to withdraw amount', '');
				}
			}*/
			/*if ($arrInput['Currency_type'] == "TRX") {
				// $currency_address = User::where('id', $user_id)->pluck('trn_address')->first();
				$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'TRX')->pluck('currency_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update Tron address to withdraw amount', '');
				}
			}*/
			/*if ($arrInput['Currency_type'] == "ETH") {
				// $currency_address = User::where('id', $user_id)->pluck('ethereum')->first();
				$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'ETH')->pluck('currency_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update ETH address to withdraw amount', '');
				}
			}*/
			/*if ($arrInput['Currency_type'] == "BNB.ERC20") {
				$currency_address = User::where('id', $user_id)->pluck('bnb_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update BNB address to withdraw amount', '');
				}
			}*/


			$withdraw_status = verifyOtpStatus::select('withdraw_update_status')
				->where('statusID', '=', 1)->get();
			$userData=User::where('id',$user_id)->first();
			if ($withdraw_status[0]->withdraw_update_status == 1) {
				if ($userData->google2fa_status=='disable') {

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
				
			} else {
				$otpdata['status'] = 200;
			}
			if($userData->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$user_id;
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
			// dd($otpdata['status']);
			if ($otpdata['status'] == 200) {
				$dash = Dashboard::where('id', $id)->select('roi_income', 'roi_income_withdraw', 'working_wallet', 'working_wallet_withdraw')
					->first();
				 $roi_income_balance = $dash->roi_income - $dash->roi_income_withdraw;
				//$roi_income_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

				if ($request->working_wallet <= $roi_income_balance) {
					$Deduction = WithdrawSettings::where([['income', '=', 'roi_balance'], ['status', '=', 'Active']])
						->pluck('deduction')->first();

					$Deductionamount = (($request->input('working_wallet') * $Deduction) / 100);

					$amount                                = $request->input('working_wallet') - $Deductionamount;
					$updateData                            = array();
					// $updateData['working_wallet_withdraw'] = DB::raw("roi_income_withdraw + " . $request->working_wallet);
					$updateData['roi_income_withdraw'] = DB::raw("roi_income_withdraw + " . $request->working_wallet);
					$updtdash                              = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

					$Toaddress                       = $currency_address->currency_address;

					$NetworkType                     = $request->Currency_type;
					$withDrawdata                    = array();
					$withDrawdata['id']              = $id;
					$withDrawdata['amount']          = $amount;
					$withDrawdata['transaction_fee'] = 0;
					$withDrawdata['deduction']       = $Deductionamount;
					$withDrawdata['from_address']    = '';
					$withDrawdata['to_address']      = trim($Toaddress);
					$withDrawdata['network_type']    = $NetworkType;
					$withDrawdata['ip_address']    = getIPAddress();
					$withDrawdata['entry_time']      = $this->today;
					$withDrawdata['withdraw_type']   = 3;
					$WithDrawinsert                  = WithdrawPending::create($withDrawdata);

					$getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

					$balance   = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
					$Trandata1 = array(
						'id'           => $id,
						'network_type' => $getCoin,
						'refference'   => $id,
						'debit'        => $request->working_wallet,
						'balance'      => $balance - $request->working_wallet,
						'type'         => "roi_wallet",
						'status'       => 1,
						'remarks'      => '$' . $request->working_wallet . ' has withdrawan from roi wallet',
						'entry_time'   => $this->today
					);

					$Trandata3 = array(
						'id'         => $id,
						'message'    => '$' . $request->working_wallet . ' has withdrawan from roi wallet',
						'status'     => 1,
						'entry_time' => $this->today
					);

					$TransactionDta1 = AllTransaction::insert($Trandata1);
					$actDta = Activitynotification::insert($Trandata3);
					$subject = "Withdraw request has been submitted successfully";
					$pagename = "emails.Withdrawal_fund";
					$withdraw_from="ROI Wallet";
					$entry_time= date('d M Y',strtotime($this->today));
					$data = array('pagename' => $pagename, 'username' => Auth::User()->user_id, 'name' => Auth::User()->fullname,'date'=>$entry_time,'withdraw_amount'=>$request->working_wallet,'fees'=>$Deductionamount,'wallet_address'=>trim($Toaddress),'withdraw_from'=>$withdraw_from);
					$mail = sendMail($data, Auth::User()->email, $subject);

					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $otpdata['msg'], '');
			}
		} catch (Exception $e) {
			dd($e);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}

	// withdraw HSCC bonus Wallet
	public function withdrawHBonusWallet(Request $request)
	{
		try {

			/*$intCode   = Response::HTTP_NOT_FOUND;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, 'Your gateway to a strong financial future is going to open soon!', '');

			dd("stop");*/

			$message = array('');
			$rules   = array(
				'working_wallet' => 'required|numeric|min:10',
				'Currency_type'  => 'required',
			);
			$messages = array(
				'working_wallet.required' => 'Please enter amount',
				'Currency_type.required'  => 'Please select currency',
				'working_wallet.numeric'  => 'Please enter valid amount',
				'working_wallet.min'      => 'Amount must be minimum 10',
				'working_wallet.digit'	  => 'You can enter maximum 8 digit'
			);
			$validator = checkvalidation($request->all(), $rules, $messages);
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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
					//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of month', '');
				}
			}
			// if ($projectSettings->withdraw_status == "off") {
			// 	$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals next Sunday.';
			// 	if ($projectSettings->withdraw_off_msg != '' && $projectSettings->withdraw_off_msg != NULL) {
			// 		$msg = $projectSettings->withdraw_off_msg;
			// 	}
			// 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			// }

			$user_id = Auth::user()->id;
			$arrInput = $request->all();
			$currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
			if (empty($currency_address)) {
				$intCode      = Response::HTTP_NOT_FOUND;
				$strStatus    = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
			} else {
				if (!empty($currency_address->block_user_date_time)) {
					$today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
					if ($currency_address->block_user_date_time >= $today) {
						$intCode      = Response::HTTP_NOT_FOUND;
						$strStatus    = Response::$statusTexts[$intCode];
						/*return sendResponse($intCode, $strStatus, 'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)', '');*/
					}
				}
			}

			// if($day != $projectSettings->withdraw_day)
			// {
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// } elseif($hrs < $projectSettings->withdraw_start_time){
			//     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You can withdraw only on '.$days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			// }
			$id       = Auth::user()->id;
			$user_id  = $id;
			$arrInput = $request->all();
			/*if ($arrInput['Currency_type'] == "BTC") {
				// dd($user_id);
				// $currency_address = User::where('id', $user_id)->pluck('btc_address')->first();
				$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'BTC')->pluck('currency_address')->first();
				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update BTC address to withdraw amount', '');
				}
			}*/
			/*if ($arrInput['Currency_type'] == "TRX") {
				// $currency_address = User::where('id', $user_id)->pluck('trn_address')->first();
				$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'TRX')->pluck('currency_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update Tron address to withdraw amount', '');
				}
			}*/
			/*if ($arrInput['Currency_type'] == "ETH") {
				// $currency_address = User::where('id', $user_id)->pluck('ethereum')->first();
				$currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'ETH')->pluck('currency_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update ETH address to withdraw amount', '');
				}
			}*/
			/*if ($arrInput['Currency_type'] == "BNB.ERC20") {
				$currency_address = User::where('id', $user_id)->pluck('bnb_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update BNB address to withdraw amount', '');
				}
			}*/


			$withdraw_status = verifyOtpStatus::select('withdraw_update_status')
				->where('statusID', '=', 1)->get();
			$userData=User::where('id',$user_id)->first();
			if ($withdraw_status[0]->withdraw_update_status == 1) {
				if ($userData->google2fa_status=='disable') {

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
				
			} else {
				$otpdata['status'] = 200;
			}
			$users_id=Auth::user()->id;

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
			// dd($otpdata['status']);
			if ($otpdata['status'] == 200) {
				$dash = Dashboard::where('id', $id)->select('hscc_bonus_wallet', 'hscc_bonus_wallet_withdraw', 'working_wallet', 'working_wallet_withdraw')
					->first();
				 $hscc_bonus_balance = $dash->hscc_bonus_wallet - $dash->hscc_bonus_wallet_withdraw;
				//$roi_income_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

				if ($request->working_wallet <= $hscc_bonus_balance) {
					$Deduction = WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])
						->pluck('deduction')->first();

					$Deductionamount = (($request->input('working_wallet') * $Deduction) / 100);

					$amount                                = $request->input('working_wallet') - $Deductionamount;
					$updateData                            = array();
					// $updateData['working_wallet_withdraw'] = DB::raw("roi_income_withdraw + " . $request->working_wallet);
					$updateData['hscc_bonus_wallet_withdraw'] = DB::raw("hscc_bonus_wallet_withdraw + " . $request->working_wallet);
					$updtdash                              = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

					$Toaddress                       = $currency_address->currency_address;

					$NetworkType                     = $request->Currency_type;
					$withDrawdata                    = array();
					$withDrawdata['id']              = $id;
					$withDrawdata['amount']          = $amount;
					$withDrawdata['transaction_fee'] = 0;
					$withDrawdata['deduction']       = $Deductionamount;
					$withDrawdata['from_address']    = '';
					$withDrawdata['to_address']      = trim($Toaddress);
					$withDrawdata['network_type']    = $NetworkType;
					$withDrawdata['ip_address']    = getIPAddress();
					$withDrawdata['entry_time']      = $this->today;
					$withDrawdata['withdraw_type']   = 3;
					$WithDrawinsert                  = WithdrawPending::create($withDrawdata);

					$getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

					$balance   = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
					$Trandata1 = array(
						'id'           => $id,
						'network_type' => $getCoin,
						'refference'   => $id,
						'debit'        => $request->working_wallet,
						'balance'      => $balance - $request->working_wallet,
						'type'         => "roi_wallet",
						'status'       => 1,
						'remarks'      => '$' . $request->working_wallet . ' has withdrawan from HSCC Bonus wallet',
						'entry_time'   => $this->today
					);

					$Trandata3 = array(
						'id'         => $id,
						'message'    => '$' . $request->working_wallet . ' has withdrawan from HSCC Bonus wallet',
						'status'     => 1,
						'entry_time' => $this->today
					);

					$TransactionDta1 = AllTransaction::insert($Trandata1);
					$actDta = Activitynotification::insert($Trandata3);
					$subject = "Withdraw request has been submitted successfully";
					$pagename = "emails.Withdrawal_fund";
					$withdraw_from="HSCC Bonus Wallet";
					$entry_time= date('d M Y',strtotime($this->today));
					$data = array('pagename' => $pagename, 'username' => Auth::User()->user_id, 'name' => Auth::User()->fullname,'date'=>$entry_time,'withdraw_amount'=>$request->working_wallet,'fees'=>$Deductionamount,'wallet_address'=>trim($Toaddress),'withdraw_from'=>$withdraw_from);
					$mail = sendMail($data, Auth::User()->email, $subject);

					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $otpdata['msg'], '');
			}
		} catch (Exception $e) {
			dd($e);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}







	public function coinTransferUser(Request $request)
	{
		try {
			$message = array('');
			$rules   = array(
				//'coin' => 'required|numeric|min:20',
				'coin'    => 'required|numeric|min:20',
				'user_id' => 'required',
			);
			$messages = array(
				'user_id.required' => 'Please select User_id',
				'coin.numeric'     => 'Please enter valid Token',
				'coin.min'         => 'Token must be minimum 20',
			);
			$validator = checkvalidation($request->all(), $rules, $messages);
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			$from_id      = Auth::user()->id;
			$from_user_id = Auth::user()->user_id;
			if ($from_id != $request->id) {
				$get_dashboard = Dashboard::select('srno', 'id', 'coin', 'coin_withdrawal', DB::raw('coin - coin_withdrawal as avail_coin'))->where([['id', '=', $from_id]])->first();
				if (!empty($get_dashboard)) {
					if (User::where('user_id', $request->user_id)->exists()) {
						if ($get_dashboard->avail_coin >= $request->coin) {
							$today                = \Carbon\Carbon::now();
							$insert               = new CoinTransactionLog;
							$insert->from_id      = $from_id;
							$insert->from_user_id = $from_user_id;
							$insert->to_id        = $request->id;
							$insert->to_user_id   = $request->user_id;
							$insert->coin         = $request->coin;
							$insert->remark       = "From User $from_user_id Transfer To User $request->user_id , Coin is $request->coin";
							$insert->entry_time   = $today;
							$insert->save();

							//update from user dashboard
							$update['coin_withdrawal'] = DB::raw('coin_withdrawal +' . $request->coin);
							Dashboard::where('srno', $get_dashboard->srno)->update($update);

							// update to user dashboard
							$update_to['coin'] = DB::raw('coin +' . $request->coin);
							Dashboard::where('id', $request->id)->update($update_to);

							return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Token Transfer To User Successfully ', '');
						} else {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Token Balance !!', '');
						}
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid User Id, Please try again!!', '');
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong !', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Cannot Transfer Token to self!!', '');
			}
		} catch (Exception $e) {
			dd($e);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}

	public function coinTransferReport(Request $request)
	{
		try {
			$UserExistid = Auth::User()->id;
			if (!empty($UserExistid)) {

				$query = CoinTransactionLog::select('from_user_id', 'to_user_id', 'coin', 'entry_time', DB::raw("CASE from_id WHEN $UserExistid THEN 'Sender' ELSE 'Receiver' END As user_status"))->where('from_id', '=', $UserExistid)->orWhere('to_id', '=', $UserExistid)->orderBy('entry_time', 'desc');
				if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
					//searching loops on fields
					$fields = getTableColumns('tbl_coin_transaction_log');
					$search = $arrInput['search']['value'];
					$query  = $query->where(function ($query) use ($fields, $search) {
						foreach ($fields as $field) {
							$query->orWhere('tbl_coin_transaction_log.' . $field, 'LIKE', '%' . $search . '%');
						}
						$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
					});
				}
				// $totalRecord = $query->count();
				$query        = $query->orderBy('tbl_coin_transaction_log.entry_time', 'desc');
				$totalRecord  = $query->get()->count();
				$arrDirectInc = $query->skip($request->input('start'))->take($request->input('length'))->get();

				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrDirectInc;

				if (!empty($arrDirectInc) && count($arrDirectInc) > 0) {
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
			dd($e);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}

	public function getCoinBalance(Request $request)
	{
		try {
			$UserExistid = Auth::User()->id;
			if (!empty($UserExistid)) {
				$query = Dashboard::select(DB::raw('coin - coin_withdrawal as avail_bal'))->where('id', '=', $UserExistid)->first();
				if (!empty($query)) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $query);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}

	public function passiveIncomeWithdrawList(Request $request)
	{
		try {
			// ini_set('memory_limit', '-1');
			$arrInput    = $request->all();
			$UserExistid = Auth::User()->id;

			if (!empty($UserExistid)) {

				$query = WithdrawPending::select('tbl_withdrwal_pending.amount', 'tbl_withdrwal_pending.id', 'tbl_withdrwal_pending.deduction', 'tbl_withdrwal_pending.status', 'tbl_withdrwal_pending.to_address', 'tbl_withdrwal_pending.withdraw_type', 'tbl_withdrwal_pending.entry_time', 'tbl_withdrwal_pending.remark', 'tbl_withdrwal_pending.on_amount')
					->join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
					->select('tbl_withdrwal_pending.*', 'tu.user_id')
					->where([['tbl_withdrwal_pending.id', '=', $UserExistid]])
					->where([['tbl_withdrwal_pending.withdraw_type', 7]])
					->orderBy('tbl_withdrwal_pending.entry_time', 'desc');

				if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
					//searching loops on fields
					$fields = getTableColumns('tbl_withdrwal_pending');
					$search = $arrInput['search']['value'];
					$query  = $query->where(function ($query) use ($fields, $search) {
						foreach ($fields as $field) {
							$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
						}
						$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
					});
				}
				$totalRecord = $query->count('tbl_withdrwal_pending.sr_no');
				$query       = $query->orderBy('tbl_withdrwal_pending.entry_time', 'desc');
				// $totalRecord = $query->get()->count();
				$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrDirectInc;

				if (!empty($arrDirectInc) && count($arrDirectInc) > 0) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Withdraw pending data found successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Withdraw pending data not found';
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

	public function withdrawPassiveWallet(Request $request)
	{
		try {
			$message = array('');
			$rules   = array(
				'working_wallet' => 'required|numeric|min:20',
				'Currency_type'  => 'required',
			);
			$messages = array(
				'working_wallet.required' => 'Please enter amount',
				'Currency_type.required'  => 'Please select currency',
				'working_wallet.numeric'  => 'Please enter valid amount',
				'working_wallet.min'      => 'Amount must be minimum 20',
			);
			$validator = checkvalidation($request->all(), $rules, $messages);
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			$projectSettings = ProjectSettings::where('status', 1)
				->select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->first();

			$day  = \Carbon\Carbon::now()->format('D');
			$hrs  = \Carbon\Carbon::now()->format('H');
			$hrs  = (int) $hrs;
			$days = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");
			if ($projectSettings->withdraw_status == "off") {
				$msg = 'Thank you for requesting but requests are closed now. You can place withdrawals next Sunday.';
				if ($projectSettings->withdraw_off_msg != '' && $projectSettings->withdraw_off_msg != NULL) {
					$msg = $projectSettings->withdraw_off_msg;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
			}
			if ($day != $projectSettings->withdraw_day) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on ' . $days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			} elseif ($hrs < $projectSettings->withdraw_start_time) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on ' . $days[$projectSettings->withdraw_day]/*.' after '.$projectSettings->withdraw_start_time.' AM'*/, '');
			}

			$id       = Auth::user()->id;
			$user_id  = $id;
			$arrInput = $request->all();
			if ($arrInput['Currency_type'] == "BTC") {
				$currency_address = User::where('id', $user_id)->pluck('btc_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update BTC address to withdraw amount', '');
				}
			}
			if ($arrInput['Currency_type'] == "TRX") {
				$currency_address = User::where('id', $user_id)->pluck('trn_address')->first();

				if (empty($currency_address)) {
					$intCode   = Response::HTTP_NOT_FOUND;
					$strStatus = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, 'Please update Tron address to withdraw amount', '');
				}
			}
			if ($arrInput['Currency_type'] == "ETH") {
				$currency_address = User::where('id', $user_id)->pluck('ethereum')->first();

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
			$amount = $request->working_wallet;

			$bal = Dashboard::where('id', $id)->selectRaw('round(passive_income-passive_income_withdraw,2) as balance')->pluck('balance')->first();
			/*$checksunday = date("Y-m-d");
			$getlastsun = ProjectSettings::select('fourth_sunday_date')->pluck('fourth_sunday_date')->first();
			$fourthsunday = date("Y-m-d", strtotime("" . ' fourth sunday'));
			if ($checksunday == $fourthsunday) {
			$checkWexist = WithdrawPending::where('id',$id)->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [date("Y-m-01"), $fourthsunday])
			->count('sr_no');
			if ($checkWexist > 0) {
			$balance = $bal / 10;
			} else {
			$balance = $bal / 20;
			}

			} else {
			$balance = $bal / 20;
			}*/

			$balance = ($bal * 2.5) / 100;

			if ($amount <= $balance) {
				$Deduction = WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])->pluck('deduction')->first();

				$Deductionamount = (($amount * $Deduction) / 100);

				$updateData                            = array();
				$updateData['passive_income_withdraw'] = DB::raw("passive_income_withdraw + " . $amount);
				$updtdash                              = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

				$amount = $amount - $Deductionamount;

				$Toaddress                       = $currency_address;
				$NetworkType                     = $request->Currency_type;
				$withDrawdata                    = array();
				$withDrawdata['id']              = $id;
				$withDrawdata['amount']          = $amount;
				$withDrawdata['transaction_fee'] = 0;
				$withDrawdata['deduction']       = $Deductionamount;
				$withDrawdata['from_address']    = '';
				$withDrawdata['to_address']      = trim($Toaddress);
				$withDrawdata['network_type']    = $NetworkType;
				$withDrawdata['entry_time']      = $this->today;
				$withDrawdata['withdraw_type']   = 7;
				$WithDrawinsert                  = WithdrawPending::create($withDrawdata);

				$getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

				$balance   = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
				$Trandata1 = array(
					'id'           => $id,
					'network_type' => $getCoin,
					'refference'   => $id,
					'debit'        => $amount,
					'balance'      => $balance - $amount,
					'type'         => "working_wallet",
					'status'       => 1,
					'remarks'      => '$' . $amount . ' has withdrawan from working wallet',
					'entry_time'   => $this->today
				);

				$Trandata3 = array(
					'id'         => $id,
					'message'    => '$' . $amount . ' has withdrawan from working wallet',
					'status'     => 1,
					'entry_time' => $this->today
				);

				$TransactionDta1 = AllTransaction::insert($Trandata1);
				//----into acitviy notification
				$actDta = Activitynotification::insert($Trandata3);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
			}
		} catch (Exception $e) {
			dd($e);

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
		}
	}
}
