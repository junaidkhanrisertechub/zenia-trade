<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Controllers\user\IcoPhasesController;
use App\Http\Controllers\user\SettingsController;
use App\Http\Controllers\user\Google2FAController;
use App\Models\Activitynotification;
use App\Models\AllTransaction;
use App\Models\BalanceTransfer;
use App\Models\Dashboard;
use App\Models\DexToPurchaseFundTransfer;
use App\Models\FundTransfer;
use App\Models\ProjectSettings;
use App\Models\PurchaseBalanceTransfer;
use App\Models\TodayDetails;
use App\Models\Wallet;
use App\Models\verifyOtpStatus;
use App\Models\WhiteListIpAddress;
use App\Models\Topup;

use App\Traits\Users;
use App\User;
use Auth;
use Validator;

use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Config;


class TransferController extends Controller {
	use Users;

	public function __construct(SettingsController $projectsetting, IcoPhasesController $IcoPhases, Google2FAController $google2facontroller) {
		$this->statuscode = Config::get('constants.statuscode');
		$this->projectsettings = $projectsetting;
		$this->IcoPhases       = $IcoPhases;
		$this->emptyArray      = (object) array();
		$date                  = \Carbon\Carbon::now();
		$this->today           = $date->toDateTimeString();
		$this->google2facontroller = $google2facontroller;
	}

	/**
	 * Get wallet list
	 *
	 * @return \Illuminate\Http\Response
	 */
    public function transferFromFundWallet(Request $request)
    {
        $data['title'] = 'Transfer From Fund Wallet | HSCC';
        return view('user.transfer-funds.TransferFromFundWallet', compact('data'));
    }

    public function transferFromHSCCwallet(Request $request)
    {
        $data['title'] = 'Transfer From HSCC Wallet | HSCC';
        return view('user.transfer-funds.TransferFromHSCCwallet', compact('data'));
    }
	public function walletlist() {
		try {
			$getWallet = Wallet::where('status', 'Active')->get();
			if (empty($getWallet) && count($getWallet) > 0) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Wallet data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Wallet data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $getWallet);
			}
		} catch (Exception $e) {

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Transfer income to wallet
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function transferTowallet(Request $request) {
		try {
			$rules = array(

				'level_income_balance'      => 'required|',
				'direct_income_balance'     => 'required|',
				'roi_balance'               => 'required|',
				'binary_income_balance'     => 'required|',
				'leadership_income_balance' => 'required|',
				'wallet_id'                 => 'required|',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$users = User::join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();
			if (!empty($users)) {
				$WalletExist = Wallet::where([['srno', '=', $request->Input('wallet_id')]])->first();
				if (!empty($WalletExist)) {
					$level_bal      = $users->level_income-$users->level_income_withdraw;
					$roi_bal        = $users->roi_income-$users->roi_income_withdraw;
					$binary_bal     = $users->binary_income-$users->binary_income_withdraw;
					$direct_bal     = $users->direct_income-$users->direct_income_withdraw;
					$leadership_bal = $users->leadership_income-$users->leadership_income_withdraw;

					$total_blce = $request->input('level_income_balance')+$request->input('roi_balance')+$request->input('binary_income_balance')+$request->input('direct_income_balance')+$request->input('leadership_income_balance');

					if ($total_blce <= 0) {
						$arrMessage = 'Balance should be greater than 0';
					} else if ($level_bal < $request->input('level_income_balance')) {
						$arrMessage = 'You have insufficient level income balance';
					} else if ($roi_bal < $request->input('roi_balance')) {
						$arrMessage = 'You have insufficient roi income balance';
					} else if ($binary_bal < $request->input('binary_income_balance')) {
						$arrMessage = 'You have insufficient binary income balance';
					} else if ($direct_bal < $request->input('direct_income_balance')) {
						$arrMessage = 'You have insufficient direct income balance';
					} else if ($leadership_bal < $request->input('leadership_income_balance')) {
						$arrMessage = 'You have insufficient leadership income balance';
					}

					if (!empty($arrMessage)) {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = $arrMessage;
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

					$updateCoinData = array();
					$tempData       = [];
					//---------update level income wd
					if ($request->input('level_income_balance') != 0) {
						$updateCoinData['level_income_withdraw'] = custom_round(($users->level_income_withdraw+$request->input('level_income_balance')), 7);
						$tempData['level_income']                = $request->input('level_income_balance');
					}
					//---------update roi income wd
					if ($request->input('roi_balance') != 0) {
						$updateCoinData['roi_income_withdraw'] = custom_round(($users->roi_income_withdraw+$request->input('roi_balance')), 7);
						$tempData['roi_income']                = $request->input('roi_balance');
					}
					//---------update binary income wd
					if ($request->input('binary_income_balance') != 0) {
						$updateCoinData['binary_income_withdraw'] = custom_round(($users->binary_income_withdraw+$request->input('binary_income_balance')), 7);
						$tempData['binary_income']                = $request->input('binary_income_balance');
					}
					//---------update direct income wd
					if ($request->input('direct_income_balance') != 0) {
						$updateCoinData['direct_income_withdraw'] = custom_round(($users->direct_income_withdraw+$request->input('direct_income_balance')), 7);
						$tempData['direct_income']                = $request->input('direct_income_balance');
					}
					//---------update leadership income wd
					if ($request->input('leadership_income_balance') != 0) {
						$updateCoinData['leadership_income_withdraw'] = custom_round(($users->leadership_income_withdraw+$request->input('leadership_income_balance')), 7);
						$tempData['leadership_income']                = $request->input('leadership_income_balance');
					}
					//---------update total amount transfer

					$updateCoinData = Dashboard::where('id', $users->id)->limit(1)->update($updateCoinData);

					$updatWallet = Dashboard::where('id', $users->id)->update(array(''.$WalletExist->setting_name.'' => DB::raw(''.$WalletExist->setting_name.'+ '.$total_blce.'')));

					$getCoin = $this->projectsettings->getProjectDetails();

					$Trandata1 = [];//insert in transaction
					foreach ($tempData as $key => $value) {
						$balance = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();

						array_push($Trandata1, [
								'id'           => $users->id,
								'network_type' => $getCoin->original["data"]["coin_name"],
								'refference'   => $users->id,
								'debit'        => $value,
								'type'         => $key,
								'status'       => 1,
								'balance'      => $balance-$value,
								'remarks'      => '$'.$value.' debited from  '.$key.'for '.$WalletExist->name.' wallet ',
								'entry_time'   => $this->today
							]);
					}

					$TransactionDta1          = AllTransaction::insert($Trandata1);
					$balance1                 = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();
					$Trandata                 = array();// insert in transaction
					$Trandata['id']           = $users->id;
					$Trandata['network_type'] = $getCoin->original["data"]["coin_name"];
					$Trandata['refference']   = $users->id;
					$Trandata['credit']       = $total_blce;
					$Trandata['balance']      = $balance1+$total_blce;
					$Trandata['type']         = $WalletExist->name;
					$Trandata['status']       = 1;
					$Trandata['entry_time']   = $this->today;
					$Trandataww               = '';
					$transkey                 = '';
					foreach ($tempData as $key => $value) {
						$Trandataww .= $key.'  $'.$value.',';
						$transkey .= $key.',';
					}
					$Trandata['type']    = $transkey;
					$Trandata['remarks'] = $Trandataww.' credited to '.$WalletExist->name.' wallet';

					$TransactionDta = AllTransaction::create($Trandata);

					$actdata               = array();// insert in transaction
					$actdata['id']         = $users->id;
					$actdata['message']    = 'Wallet '.$WalletExist->name.' Credit transaction from  '.$Trandataww;
					$actdata['status']     = 1;
					$actdata['entry_time'] = $this->today;
					$actDta                = Activitynotification::create($actdata);

					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Amount transfer to wallet successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Wallet is not exist';
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

	/**
	 * Fund transfer
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function UserToUserTransfer(Request $request) {

		try {
			$rules = array(
				'amount'     => 'required',
				'to_user_id' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			//from user
			$users = User::join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();

			// to user
			$transfer_user_id = User::where('user_id', $request->to_user_id)->pluck('id')->first();

			/* to check user downline or not */

			$userId = Auth::User()->id;

			$from_user_id = $transfer_user_id;

			if ($from_user_id != $userId) {

				$todaydetailsexist = TodayDetails::where('to_user_id', $userId)->where('from_user_id', $from_user_id)->get();

				/* if (count($todaydetailsexist) == 0) {

			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Not a Downline user';
			return sendResponse($arrStatus, $arrCode, $arrMessage, 0);
			}*/

			}

			/* to check user downline or not */

			$tran_user_id = User::where('user_id', $request->to_user_id)->pluck('user_id')->first();

			$transfer_user_id_email = User::where('user_id', $request->to_user_id)->pluck('email')->first();
			$to_user_dashboard      = Dashboard::select('top_up_wallet', 'top_up_wallet_withdraw')->where('id', $transfer_user_id)->first();

			$to_user_topup_bal = $to_user_dashboard->top_up_wallet-$to_user_dashboard->top_up_wallet_withdraw;

			if (!empty($users)) {
				if ($users->user_id != $request->Input('to_user_id')) {
					$trans_bal = $users->top_up_wallet-$users->top_up_wallet_withdraw;

					if ($request->input('amount') > 0) {
						if ($trans_bal >= $request->input('amount')) {
							$to_user_id = User::where([['user_id', '=', $request->input('to_user_id')], ])->pluck('id')->first();
							if (!empty($to_user_id)) {
								$updateCoinData = array();
								$tempData       = [];
								//---------update level income wd---------//
								$updateCoinData['top_up_wallet_withdraw'] = custom_round(($users->top_up_wallet_withdraw+$request->input('amount')), 7);
								$updateCoinData['total_withdraw']         = custom_round(($users->top_up_wallet_withdraw+$request->input('amount')), 7);
								$updateCoinData['usd']                    = custom_round(($users->usd-$request->input('amount')), 7);
								$updateCoinData                           = Dashboard::where('id', $users->id)->limit(1)->update($updateCoinData);

								$to_userid_transfer_details = Dashboard::where([['id', '=', $to_user_id], ])->select('top_up_wallet', 'total_profit', 'usd')->first();

								$to_userid_transfer_wallet = $to_userid_transfer_details->top_up_wallet;
								$to_userid_total_profit    = $to_userid_transfer_details->total_profit;
								$to_userid_usd             = $to_userid_transfer_details->usd;
								/* $to_userid_total_profit =User::where([['id','=',$request->input('to_user_id')],])->pluck('total_profit')->first(); */

								$updateTouserData['top_up_wallet'] = $to_userid_transfer_wallet+$request->input('amount');

								$updateTouserData['total_profit'] = custom_round(($to_userid_total_profit+$request->input('amount')), 7);
								$updateTouserData['usd']          = custom_round(($to_userid_usd+$request->input('amount')), 7);
								$updateCoinData                   = Dashboard::where('id', $to_user_id)->limit(1)->update($updateTouserData);

								$getCoin = $this->projectsettings->getProjectDetails();

								// first entry

								$balance                  = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();
								$Trandata                 = array();// insert in transaction
								$Trandata['id']           = $users->id;
								$Trandata['network_type'] = $getCoin->original["data"]["coin_name"];
								$Trandata['refference']   = $users->id;
								$Trandata['debit']        = $request->input('amount');

								$Trandata['balance']       = $balance-$request->input('amount');
								$Trandata['type']          = 'TRANSFER';
								$Trandata['status']        = 1;
								$Trandata['entry_time']    = $this->today;
								$Trandata['remarks']       = 'Fund transfer to  '.$request->input('to_user_id');
								$Trandata['prev_balance']  = $request['topup_wallet_bal'];
								$Trandata['final_balance'] = $request['topup_wallet_bal']-$request->input('amount');

								$TransactionDta            = AllTransaction::create($Trandata);
								$balance1                  = AllTransaction::where('id', '=', $users->id)->orderBy('srno', 'desc')->pluck('balance')->first();
								$Trandata                  = array();// insert in transaction
								$Trandata['id']            = $transfer_user_id;
								$Trandata['network_type']  = $getCoin->original["data"]["coin_name"];
								$Trandata['refference']    = $to_user_id;
								$Trandata['credit']        = $request->input('amount');
								$Trandata['type']          = 'TRANSFER';
								$Trandata['balance']       = $balance1+$request->input('amount');
								$Trandata['status']        = 1;
								$Trandata['entry_time']    = $this->today;
								$Trandata['remarks']       = 'Fund Received from '.$users->user_id;
								$Trandata['prev_balance']  = $to_user_topup_bal;
								$Trandata['final_balance'] = $to_user_topup_bal+$request->input('amount');

								$TransactionDta = AllTransaction::create($Trandata);

								$actdata            = array();// insert in transaction
								$actdata['id']      = $users->id;
								$actdata['message'] = '$'.$request->input('amount').' Transfer to '.$users->user_id;
								//dd($users->user_id);
								$actdata['status']     = 1;
								$actdata['entry_time'] = $this->today;

								$actDta = Activitynotification::create($actdata);

								$subject  = "Transfer Amount submitted";
								$pagename = "emails.transfer_amount";
								$data     = array('pagename' => $pagename,
									'amount'                    => $request->input('amount'),
									'transfer_user_id'          => $tran_user_id,
									'username'                  => $users->user_id
								);
								// dd($data);
								$email = $transfer_user_id_email;
								//dd($email);
								//$mail = sendMail($data, $email, $subject);
								$arrStatus  = Response::HTTP_OK;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'Amount transfer successfully';
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							} else {
								$arrStatus  = Response::HTTP_NOT_FOUND;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'To user not exist';
								return sendResponse($arrStatus, $arrCode, $arrMessage, '');
							}
						} else {

							$arrStatus  = Response::HTTP_NOT_FOUND;
							$arrCode    = Response::$statusTexts[$arrStatus];
							$arrMessage = 'You have insufficient transfer balance';
							return sendResponse($arrStatus, $arrCode, $arrMessage, '');
						}
					} else {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Topup wallet balance should be greater than 0';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'You can not transfer fund to self account';
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
	public function AllIDBalanceTransfer(Request $request) {

		try {
			$touser = Auth::User();

			$arrPendings = User::join('tbl_dashboard as td', 'td.id', '=', 'tbl_users.id')->select('tbl_users.fullname as name', 'tbl_users.user_id', 'tbl_users.email', 'tbl_users.id as from_user_id', DB::raw('td.working_wallet - td.working_wallet_withdraw as amount'))
			                                                                              ->where('tbl_users.email', $touser->email)->where('tbl_users.id', '!=', $touser->id)->where('tbl_users.status', 'Active')
			->whereRaw('(td.working_wallet - td.working_wallet_withdraw) >=20')->get();

			/*dd($arrPendings->toArray());*/

			if (!empty($arrPendings)) {
				$to_user_id = $touser->id;
				foreach ($arrPendings as $key => $user) {
					$trans_bal    = $user->amount;
					$from_user_id = $user->from_user_id;
					if ($trans_bal > 20) {
						$updateCoinData = array();
						$tempData       = [];
						//---------update level income wd---------//
						$updateCoinData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw +'.$trans_bal);
						$updateCoinData['usd']                     = DB::raw('usd -'.$trans_bal);
						$updateCoinData                            = Dashboard::where('id', $from_user_id)->limit(1)->update($updateCoinData);

						$updateCoinData                     = array();
						$updateTouserData['working_wallet'] = DB::raw('working_wallet +'.$trans_bal);
						$updateCoinData                     = Dashboard::where('id', $to_user_id)->limit(1)->update($updateTouserData);

						$fundData                 = array();
						$fundData['to_user_id']   = $to_user_id;
						$fundData['from_user_id'] = $from_user_id;
						$fundData['amount']       = $trans_bal;
						$fundData['wallet_type']  = 4;
						$fundData['entry_time']   = $this->today;

						$insFund = FundTransfer::create($fundData);

						$balance                  = AllTransaction::where('id', '=', $from_user_id)->orderBy('srno', 'desc')->pluck('balance')->first();
						$balance                  = ($balance != null)?$balance:0;
						$Trandata                 = array();// insert in transaction
						$Trandata['id']           = $from_user_id;
						$Trandata['network_type'] = "";
						$Trandata['refference']   = $from_user_id;
						$Trandata['debit']        = $trans_bal;

						$Trandata['balance']       = $balance-$trans_bal;
						$Trandata['type']          = 'TRANSFER';
						$Trandata['status']        = 1;
						$Trandata['entry_time']    = $this->today;
						$Trandata['remarks']       = 'Fund transfer to  '.$touser->user_id;
						$Trandata['prev_balance']  = $balance;
						$Trandata['final_balance'] = $balance-$trans_bal;

						$TransactionDta            = AllTransaction::create($Trandata);
						$balance1                  = AllTransaction::where('id', '=', $to_user_id)->orderBy('srno', 'desc')->pluck('balance')->first();
						$balance1                  = ($balance1 != null)?$balance1:0;
						$Trandata                  = array();// insert in transaction
						$Trandata['id']            = $to_user_id;
						$Trandata['network_type']  = "";
						$Trandata['refference']    = $to_user_id;
						$Trandata['credit']        = $trans_bal;
						$Trandata['type']          = 'TRANSFER';
						$Trandata['balance']       = $trans_bal;
						$Trandata['status']        = 1;
						$Trandata['entry_time']    = $this->today;
						$Trandata['remarks']       = 'Fund Received from '.$user->user_id;
						$Trandata['prev_balance']  = $balance1;
						$Trandata['final_balance'] = $balance1+$trans_bal;

						$TransactionDta = AllTransaction::create($Trandata);

						$actdata            = array();// insert in transaction
						$actdata['id']      = $touser->id;
						$actdata['message'] = '$'.$trans_bal.' Transfer to '.$touser->user_id;
						//dd($users->user_id);
						$actdata['status']     = 1;
						$actdata['entry_time'] = $this->today;

						$actDta = Activitynotification::create($actdata);

						$subject  = "Transfer Amount submitted";
						$pagename = "emails.transfer_amount";
						$data     = array('pagename' => $pagename,
							'amount'                    => $trans_bal,
							'transfer_user_id'          => $user->user_id,
							'username'                  => $touser->user_id
						);
						// dd($data);
						$email = $user->email;
						//dd($email);
						$mail = sendMail($data, $email, $subject);
					} else {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = $user->user_id.' working wallet balance is less than 20';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
				}
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Amount transfer successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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
	public function AddPurchaseBalanceTransferRequest(Request $request) {
		try {
			$rules = array(
				'amount' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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
			}  */

			$touseremail = Auth::User()->email;
			$touserid    = Auth::User()->id;
			if (!empty($touserid)) {

				$checkexist = PurchaseBalanceTransfer::where([['email', $touseremail], ['status', 0]])->first();
				if (!empty($checkexist)) {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Request already exist for this email id';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				} else {
					$insArr               = array();
					$insArr['user_id']    = $touserid;
					$insArr['email']      = $touseremail;
					$insArr['amount']     = $request->amount;
					$insArr['entry_time'] = $this->today;
					$ins                  = PurchaseBalanceTransfer::create($insArr);
					if (!empty($ins)) {
						$arrStatus  = Response::HTTP_OK;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Request added successfully';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
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
	public function CheckTransferRequestExist() {
		$touser = Auth::User()->email;
		if (!empty($touser)) {
			$checkexist = BalanceTransfer::where([['email', $touser], ['status', 0]])->first();
			if (!empty($checkexist)) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Request already exist for this email id';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		}
	}

	public function AddBalanceTransferRequest(Request $request) {
		try {
			$rules = array(
				'amount' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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
			}    */

			$touseremail = Auth::User()->email;
			$touserid    = Auth::User()->id;
			if (!empty($touserid)) {
				$bal = Dashboard::selectRaw('round(working_wallet-working_wallet_withdraw,2) as balance')->where('id', $touserid)->pluck('balance')->first();
				if ($bal < 20) {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Your Dex Wallet is having less than 20$. Login from ID which have 20$ and then Try';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
				$checkexist = BalanceTransfer::where([['email', $touseremail], ['status', 0]])->first();
				if (!empty($checkexist)) {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Request already exist for this email id';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				} else {
					$insArr               = array();
					$insArr['user_id']    = $touserid;
					$insArr['email']      = $touseremail;
					$insArr['amount']     = $request->amount;
					$insArr['entry_time'] = $this->today;
					$ins                  = BalanceTransfer::create($insArr);
					if (!empty($ins)) {
						$arrStatus  = Response::HTTP_OK;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Request added successfully';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}
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

	public function CheckPurchaseTransferRequestExist() {
		$touser = Auth::User()->email;
		if (!empty($touser)) {
			$checkexist = PurchaseBalanceTransfer::where([['email', $touser], ['status', 0]])->first();
			if (!empty($checkexist)) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Request already exist for this email id';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		}
	}
	public function PurchaseToPurchaseTransfer(Request $request) {
		
		try {
			$rules = array(
				'amount'     => 'required|numeric|min:1',
				'to_user_id' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$id=Auth::User()->id;
			$arrInput            = $request->all();
			$arrInput['user_id'] = $id;
			$transfer_otp_status = verifyOtpStatus::select('transfer_update_status')
            ->where('statusID','=',1)->get();
	        $userData=User::where('id',$id)->first();
			
			if($userData->google2fa_status=='enable') {
				$arrIn  = array();
				

				$arrIn['id']=$id;
				
				$arrIn['otp']=$arrInput['otp_2fa'];
				
				$arrIn['google2fa_secret'] = Auth::user()->google2fa_secret;
				
				
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

		        if ($transfer_otp_status[0]->transfer_update_status == 1) {
			        $arrRules            = ['otp' => 'required|min:10|max:10'];
			        $verify_otp = verify_Otp($arrInput);
			            // dd($verify_otp);
			        if (!empty($verify_otp)) {
			            if ($verify_otp['status'] == 200) {
			            } else {
			                $arrStatus = Response::HTTP_NOT_FOUND;;
			                $arrCode = Response::$statusTexts[$arrStatus];
			                $arrMessage = 'Invalid or Otp Expired!';
			                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                    // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			            }
			        } else {
			            $arrStatus = Response::HTTP_NOT_FOUND;;
			            $arrCode = Response::$statusTexts[$arrStatus];
			            $arrMessage = 'Invalid or Otp Expired!';
			            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			        }
		        }
			}

			$from_id       = Auth::User()->id;
			$chck_transfer = User::where('user_id', $request->to_user_id)->pluck('transfer_block_by_admin')->first();

			if ($chck_transfer == "1") {
				//dd($chck_transfer);
				//dd($chck_transfer);
				$strMessage = 'Tansfer Stop By Admin';
				$intCode    = Response::HTTP_BAD_REQUEST;
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, '');
			}
			// dd($chck_transfer);
			if (!empty($from_id)) {

				$touser_id = User::where('user_id', $request->to_user_id)->pluck('id')->first();
				if (!empty($touser_id) && $from_id != $touser_id) {

					$topup_bal = Dashboard::selectRaw('fund_wallet - fund_wallet_withdraw as balance')->where('id', $from_id)->first();
					$amount    = $request->amount;
					$remark    = $request->remark;

					if ($topup_bal->balance >= $amount) {

						$updateFromData = array();

						$updateFromData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$amount.'');
						$updateFromData['usd']                    = DB::raw('usd - '.$amount.'');
						$updateqryfrom                            = Dashboard::where('id', $from_id)->limit(1)->update($updateFromData);

						$updateTouserData                  = array();
						$updateTouserData['fund_wallet'] = DB::raw('fund_wallet +'.$amount.'');
						$updateqryto                       = Dashboard::where('id', $touser_id)->limit(1)
						                                                 ->update($updateTouserData);

						$fundData                 = array();
						$fundData['to_user_id']   = $touser_id;
						$fundData['from_user_id'] = $from_id;
						$fundData['amount']       = $amount;
						$fundData['remark']       = 'TRANSFER';
						$fundData['wallet_type']  = 6;
						$fundData['entry_time']   = $this->today;

						$insFund = FundTransfer::create($fundData);

						// sssssssssssssssssssssssss

						$query = DB::table('tbl_templates')
							->select('title', 'subject', 'content')
							->where('title', 'Fund')
							->get();

								$project_setting = DB::table('tbl_project_settings')
								->select('project_name', 'icon_image', 'background_image', 'domain_name')
								->where('id', '1')
								->get();
							// dd($query, $project_setting);
							$pagename = "emails.fundtransfer";
							$pagename_reciever = "emails.funds_received";

							$subject_transfer='Fund Transfer Successful';
							$subject_reciever='Fund Received to your HSCC account';

							$from_id   = Auth::User()->user_id;
							$email     = Auth::User()->email;
							$fund      = $request->amount;
							$content   = $query[0]->content;

							$to_user_id = $request->to_user_id;
							$to_email = User::where('user_id', $request->to_user_id)->pluck('email')->first();
							$reciever_name = User::where('user_id', $request->to_user_id)->pluck('fullname')->first();

							$entry_time = date('d M Y', strtotime($this->today));
							$reciever_new_balance = Dashboard::selectRaw('fund_wallet - fund_wallet_withdraw as balance')->where('id', $touser_id)->first();
							$wallet_type="Fund";
							$this->add_transaction_activity(Auth::User()->id,1,'Fund Wallet Transfer',0,$amount,$topup_bal->balance,($topup_bal->balance-$amount));
							$this->add_transaction_activity($touser_id,1,'Received to Fund Wallet',$amount,0,($reciever_new_balance->balance - $amount),$reciever_new_balance->balance);

							$dd1         = ['$fund', '$touser_id', '$from_id'];
							$dd2         = [$fund, $touser_id, $from_id];
							$new_content = str_replace($dd1, $dd2, $content);


							$data_transfer = array(
								'title' => $query[0]->title,
								'subject' => $subject_transfer,
								'content' => $new_content,
								'date'=>$entry_time,
								'name'=>Auth::User()->fullname,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
							);
							$data_recieve = array(
								'title' => $query[0]->title,
								'subject' => $subject_reciever,
								'content' => $new_content,
								'date'=>$entry_time,
								'reciever_name'=>$reciever_name,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename_reciever,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
								'reciever_new_balance' => $reciever_new_balance->balance,
								'wallet_type'=>$wallet_type
							);

							$mail_transfer = sendMail($data_transfer, $email, $subject_transfer);
							$mail_recieve = sendMail($data_recieve, $to_email, $subject_reciever);

						// ssssssssssssssssssssssssssssss

						$arrStatus  = Response::HTTP_OK;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Amount Transferred Successfully!';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');

					} else {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Insufficient balance';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Unaunthenticated User';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

    public function transferFromROIWallet(Request $request)
    {
        $data['title'] = 'Transfer From ROI Wallet | HSCC';
        return view('user.transfer-funds.TransferFromROIWallet', compact('data'));
    }
	public function RoiToRoiTransfer(Request $request) {
		//dd($request);
		try {
			$rules = array(
				'amount'     => 'required|numeric|min:1',
				'to_user_id' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$target_business = Topup::where([['id', Auth::User()->id],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
	        $total_business= Auth::User()->l_guardian + Auth::User()->r_guardian;
	        if (!empty($target_business)) {
		        if ($total_business < $target_business) {
		        	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Please achieve your target first", '');
		        }
	        }

			

			$id=Auth::User()->id;
			$arrInput            = $request->all();
			$arrInput['user_id'] = $id;
			$transfer_otp_status = verifyOtpStatus::select('transfer_update_status')
            ->where('statusID','=',1)->get();
	        $userData=User::where('id',$id)->first();

			if($userData->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$id;
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
			}else{

		        if ($transfer_otp_status[0]->transfer_update_status == 1) {
			        $arrRules            = ['otp' => 'required|min:10|max:10'];
			        $verify_otp = verify_Otp($arrInput);
			            // dd($verify_otp);
			        if (!empty($verify_otp)) {
			            if ($verify_otp['status'] == 200) {
			            } else {
			                $arrStatus = Response::HTTP_NOT_FOUND;;
			                $arrCode = Response::$statusTexts[$arrStatus];
			                $arrMessage = 'Invalid or Otp Expired!';
			                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                    // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			            }
			        } else {
			            $arrStatus = Response::HTTP_NOT_FOUND;;
			            $arrCode = Response::$statusTexts[$arrStatus];
			            $arrMessage = 'Invalid or Otp Expired!';
			            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			        }
		        }
			}

			$from_id       = Auth::User()->id;
			$chck_transfer = User::where('user_id', $request->to_user_id)->pluck('transfer_block_by_admin')->first();

			if ($chck_transfer == "1") {
				//dd($chck_transfer);
				//dd($chck_transfer);
				$strMessage = 'Tansfer Stop By Admin';
				$intCode    = Response::HTTP_BAD_REQUEST;
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, '');
			}
			// dd($chck_transfer);
			if (!empty($from_id)) {

				$touser_id = User::where('user_id', $request->to_user_id)->pluck('id')->first();
				if (!empty($touser_id) && $from_id != $touser_id) {

					$topup_bal = Dashboard::selectRaw('roi_wallet - roi_wallet_withdraw as balance')->where('id', $from_id)->first();
					$amount    = $request->amount;
					$remark    = $request->remark;

					if ($topup_bal->balance >= $amount) {

						$updateFromData = array();

						$updateFromData['roi_wallet_withdraw'] = DB::raw('roi_wallet_withdraw + '.$amount.'');
						$updateFromData['usd']                    = DB::raw('usd - '.$amount.'');
						$updateqryfrom                            = Dashboard::where('id', $from_id)->limit(1)->update($updateFromData);

						$updateTouserData                  = array();
						$updateTouserData['roi_wallet'] = DB::raw('roi_wallet +'.$amount.'');
						$updateqryto                       = Dashboard::where('id', $touser_id)->limit(1)
						                                                 ->update($updateTouserData);

						$fundData                 = array();
						$fundData['to_user_id']   = $touser_id;
						$fundData['from_user_id'] = $from_id;
						$fundData['amount']       = $amount;
						$fundData['remark']       = 'TRANSFER';
						$fundData['wallet_type']  = 1;
						$fundData['entry_time']   = $this->today;

						$insFund = FundTransfer::create($fundData);

						$query = DB::table('tbl_templates')
							->select('title', 'subject', 'content')
							->where('title', 'Fund')
							->get();

								$project_setting = DB::table('tbl_project_settings')
								->select('project_name', 'icon_image', 'background_image', 'domain_name')
								->where('id', '1')
								->get();
							// dd($query, $project_setting);
							$pagename = "emails.fundtransfer";
							$pagename_reciever = "emails.funds_received";

							$subject_transfer='Fund Transfer Successful';
							$subject_reciever='Fund Received to your HSCC account';

							$from_id   = Auth::User()->user_id;
							$email     = Auth::User()->email;
							$fund      = $request->amount;
							$content   = $query[0]->content;

							$to_user_id = $request->to_user_id;
							$to_email = User::where('user_id', $request->to_user_id)->pluck('email')->first();
							$reciever_name = User::where('user_id', $request->to_user_id)->pluck('fullname')->first();
							$entry_time = date('d M Y', strtotime($this->today));
							$reciever_new_balance = Dashboard::selectRaw('roi_wallet - roi_wallet_withdraw as balance')->where('id', $touser_id)->first();
							$wallet_type="ROI";
							$this->add_transaction_activity(Auth::User()->id,2,'ROI Wallet Transfer',0,$amount,$topup_bal->balance,($topup_bal->balance-$amount));
							$this->add_transaction_activity($touser_id,2,'Received to ROI Wallet',$amount,0,($reciever_new_balance->balance - $amount),$reciever_new_balance->balance);

							$dd1         = ['$fund', '$touser_id', '$from_id'];
							$dd2         = [$fund, $touser_id, $from_id];
							$new_content = str_replace($dd1, $dd2, $content);


							$data_transfer = array(
								'title' => $query[0]->title,
								'subject' => $subject_transfer,
								'content' => $new_content,
								'date'=>$entry_time,
								'name'=>Auth::User()->fullname,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
							);
							$data_recieve = array(
								'title' => $query[0]->title,
								'subject' => $subject_reciever,
								'content' => $new_content,
								'date'=>$entry_time,
								'reciever_name'=>$reciever_name,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename_reciever,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
								'reciever_new_balance' => $reciever_new_balance->balance,
								'wallet_type'=>$wallet_type
							);

							$mail_transfer = sendMail($data_transfer, $email, $subject_transfer);
							$mail_recieve = sendMail($data_recieve, $to_email, $subject_reciever);

						$arrStatus  = Response::HTTP_OK;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Amount Transferred Successfully!';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');

					} else {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Insufficient balance';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Unaunthenticated User';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}




    public function transferFromWorkingWallet(Request $request)
    {
        $data['title'] = 'Transfer From Working Wallet | HSCC';
        return view('user.transfer-funds.TransferFromWorkingWallet', compact('data'));
    }

    public function WorkingToWorkingTransfer(Request $request) {
		//dd($request);
		try {
			$rules = array(
				'amount'     => 'required|numeric|min:1',
				'to_user_id' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$id=Auth::User()->id;
			$arrInput            = $request->all();
			$arrInput['user_id'] = $id;
			$transfer_otp_status = verifyOtpStatus::select('transfer_update_status')
            ->where('statusID','=',1)->get();
	        $userData=User::where('id',$id)->first();

			if($userData->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$id;
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
			}else{

		        if ($transfer_otp_status[0]->transfer_update_status == 1) {
			        $arrRules            = ['otp' => 'required|min:10|max:10'];
			        $verify_otp = verify_Otp($arrInput);
			            // dd($verify_otp);
			        if (!empty($verify_otp)) {
			            if ($verify_otp['status'] == 200) {
			            } else {
			                $arrStatus = Response::HTTP_NOT_FOUND;;
			                $arrCode = Response::$statusTexts[$arrStatus];
			                $arrMessage = 'Invalid or Otp Expired!';
			                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                    // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			            }
			        } else {
			            $arrStatus = Response::HTTP_NOT_FOUND;;
			            $arrCode = Response::$statusTexts[$arrStatus];
			            $arrMessage = 'Invalid or Otp Expired!';
			            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			        }
		        }
			}

			$from_id       = Auth::User()->id;
			$chck_transfer = User::where('user_id', $request->to_user_id)->pluck('transfer_block_by_admin')->first();

			if ($chck_transfer == "1") {
				//dd($chck_transfer);
				//dd($chck_transfer);
				$strMessage = 'Tansfer Stop By Admin';
				$intCode    = Response::HTTP_BAD_REQUEST;
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, '');
			}
			// dd($chck_transfer);
			if (!empty($from_id)) {

				$touser_id = User::where('user_id', $request->to_user_id)->pluck('id')->first();
				if (!empty($touser_id) && $from_id != $touser_id) {

					$topup_bal = Dashboard::selectRaw('working_wallet - working_wallet_withdraw as balance')->where('id', $from_id)->first();
					$amount    = $request->amount;
					$remark    = $request->remark;

					if ($topup_bal->balance >= $amount) {

						$updateFromData = array();

						$updateFromData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw + '.$amount.'');
						$updateFromData['usd']                    = DB::raw('usd - '.$amount.'');
						$updateqryfrom                            = Dashboard::where('id', $from_id)->limit(1)->update($updateFromData);

						$updateTouserData                  = array();
						$updateTouserData['working_wallet'] = DB::raw('working_wallet +'.$amount.'');
						$updateqryto                       = Dashboard::where('id', $touser_id)->limit(1)
						                                                 ->update($updateTouserData);

						$fundData                 = array();
						$fundData['to_user_id']   = $touser_id;
						$fundData['from_user_id'] = $from_id;
						$fundData['amount']       = $amount;
						$fundData['remark']       = 'TRANSFER';
						$fundData['wallet_type']  = 7;
						$fundData['entry_time']   = $this->today;

						$insFund = FundTransfer::create($fundData);

						// sssssssssssssssssssssssss

						$query = DB::table('tbl_templates')
							->select('title', 'subject', 'content')
							->where('title', 'Fund')
							->get();

								$project_setting = DB::table('tbl_project_settings')
								->select('project_name', 'icon_image', 'background_image', 'domain_name')
								->where('id', '1')
								->get();
							// dd($query, $project_setting);
							$pagename = "emails.fundtransfer";
							$pagename_reciever = "emails.funds_received";

							$subject_transfer='Fund Transfer Successful';
							$subject_reciever='Fund Received to your HSCC account';

							$from_id   = Auth::User()->user_id;
							$email     = Auth::User()->email;
							$fund      = $request->amount;
							$content   = $query[0]->content;

							$to_user_id = $request->to_user_id;
							$to_email = User::where('user_id', $request->to_user_id)->pluck('email')->first();
							$reciever_name = User::where('user_id', $request->to_user_id)->pluck('fullname')->first();
							$entry_time = date('d M Y', strtotime($this->today));
							$reciever_new_balance = Dashboard::selectRaw('working_wallet - working_wallet_withdraw as balance')->where('id', $touser_id)->first();
							$wallet_type="Working";
							$this->add_transaction_activity(Auth::User()->id,3,'Working Wallet Transfer',0,$amount,$topup_bal->balance,($topup_bal->balance-$amount));
							$this->add_transaction_activity($touser_id,3,'Received to Working Wallet',$amount,0,($reciever_new_balance->balance - $amount),$reciever_new_balance->balance);

							$dd1         = ['$fund', '$touser_id', '$from_id'];
							$dd2         = [$fund, $touser_id, $from_id];
							$new_content = str_replace($dd1, $dd2, $content);


							$data_transfer = array(
								'title' => $query[0]->title,
								'subject' => $subject_transfer,
								'content' => $new_content,
								'date'=>$entry_time,
								'name'=>Auth::User()->fullname,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
							);
							$data_recieve = array(
								'title' => $query[0]->title,
								'subject' => $subject_reciever,
								'content' => $new_content,
								'date'=>$entry_time,
								'reciever_name'=>$reciever_name,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename_reciever,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
								'reciever_new_balance' => $reciever_new_balance->balance,
								'wallet_type'=>$wallet_type
							);

							$mail_transfer = sendMail($data_transfer, $email, $subject_transfer);
							$mail_recieve = sendMail($data_recieve, $to_email, $subject_reciever);

						// ssssssssssssssssssssssssssssss

						$arrStatus  = Response::HTTP_OK;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Amount Transferred Successfully!';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');

					} else {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Insufficient balance';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Unaunthenticated User';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function hsccWalletTohsccWalletTransfer(Request $request) {
		//dd($request);
		try {
			$rules = array(
				'amount'     => 'required|numeric|min:1',
				'to_user_id' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$id=Auth::User()->id;
			$arrInput            = $request->all();
			$arrInput['user_id'] = $id;
			$transfer_otp_status = verifyOtpStatus::select('transfer_update_status')
            ->where('statusID','=',1)->get();
	        $userData=User::where('id',$id)->first();

			if($userData->google2fa_status=='enable') {
				$arrIn  = array();

				$arrIn['id']=$id;
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
			}else{

		        if ($transfer_otp_status[0]->transfer_update_status == 1) {
			        $arrRules            = ['otp' => 'required|min:10|max:10'];
			        $verify_otp = verify_Otp($arrInput);
			            // dd($verify_otp);
			        if (!empty($verify_otp)) {
			            if ($verify_otp['status'] == 200) {
			            } else {
			                $arrStatus = Response::HTTP_NOT_FOUND;;
			                $arrCode = Response::$statusTexts[$arrStatus];
			                $arrMessage = 'Invalid or Otp Expired!';
			                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                    // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			            }
			        } else {
			            $arrStatus = Response::HTTP_NOT_FOUND;;
			            $arrCode = Response::$statusTexts[$arrStatus];
			            $arrMessage = 'Invalid or Otp Expired!';
			            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			                // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			        }
		        }
			}

			$from_id       = Auth::User()->id;
			$chck_transfer = User::where('user_id', $request->to_user_id)->pluck('transfer_block_by_admin')->first();

			if ($chck_transfer == "1") {
				//dd($chck_transfer);
				//dd($chck_transfer);
				$strMessage = 'Tansfer Stop By Admin';
				$intCode    = Response::HTTP_BAD_REQUEST;
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, '');
			}
			// dd($chck_transfer);
			if (!empty($from_id)) {

				$touser_id = User::where('user_id', $request->to_user_id)->pluck('id')->first();
				if (!empty($touser_id) && $from_id != $touser_id) {

					$topup_bal = Dashboard::selectRaw('hscc_bonus_wallet - hscc_bonus_wallet_withdraw as balance')->where('id', $from_id)->first();
					$amount    = $request->amount;
					$remark    = $request->remark;

					if ($topup_bal->balance >= $amount) {

						$updateFromData = array();

						$updateFromData['hscc_bonus_wallet_withdraw'] = DB::raw('hscc_bonus_wallet_withdraw + '.$amount.'');
						$updateFromData['usd']                    = DB::raw('usd - '.$amount.'');
						$updateqryfrom                            = Dashboard::where('id', $from_id)->limit(1)->update($updateFromData);

						$updateTouserData                  = array();
						$updateTouserData['hscc_bonus_wallet'] = DB::raw('hscc_bonus_wallet +'.$amount.'');
						$updateqryto                       = Dashboard::where('id', $touser_id)->limit(1)
						                                                 ->update($updateTouserData);

						$fundData                 = array();
						$fundData['to_user_id']   = $touser_id;
						$fundData['from_user_id'] = $from_id;
						$fundData['amount']       = $amount;
						$fundData['remark']       = 'TRANSFER';
						$fundData['wallet_type']  = 8;
						$fundData['entry_time']   = $this->today;

						$insFund = FundTransfer::create($fundData);

						$query = DB::table('tbl_templates')
							->select('title', 'subject', 'content')
							->where('title', 'Fund')
							->get();

								$project_setting = DB::table('tbl_project_settings')
								->select('project_name', 'icon_image', 'background_image', 'domain_name')
								->where('id', '1')
								->get();
							// dd($query, $project_setting);
							$pagename = "emails.fundtransfer";
							$pagename_reciever = "emails.funds_received";

							$subject_transfer='Fund Transfer Successful';
							$subject_reciever='Fund Received to your HSCC account';

							$from_id   = Auth::User()->user_id;
							$email     = Auth::User()->email;
							$fund      = $request->amount;
							$content   = $query[0]->content;

							$to_user_id = $request->to_user_id;
							$to_email = User::where('user_id', $request->to_user_id)->pluck('email')->first();
							$reciever_name = User::where('user_id', $request->to_user_id)->pluck('fullname')->first();
							$entry_time = date('d M Y', strtotime($this->today));
							$reciever_new_balance = Dashboard::selectRaw('hscc_bonus_wallet - hscc_bonus_wallet_withdraw as balance')->where('id', $touser_id)->first();
							$wallet_type="HSCC Bonus";
							$this->add_transaction_activity(Auth::User()->id,4,'HSCC Bonus Wallet Transfer',0,$amount,$topup_bal->balance,($topup_bal->balance-$amount));
							$this->add_transaction_activity($touser_id,4,'Received to HSCC Bonus Wallet',$amount,0,($reciever_new_balance->balance - $amount),$reciever_new_balance->balance);

							$dd1         = ['$fund', '$touser_id', '$from_id'];
							$dd2         = [$fund, $touser_id, $from_id];
							$new_content = str_replace($dd1, $dd2, $content);


							$data_transfer = array(
								'title' => $query[0]->title,
								'subject' => $subject_transfer,
								'content' => $new_content,
								'date'=>$entry_time,
								'name'=>Auth::User()->fullname,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
							);
							$data_recieve = array(
								'title' => $query[0]->title,
								'subject' => $subject_reciever,
								'content' => $new_content,
								'date'=>$entry_time,
								'reciever_name'=>$reciever_name,
								'project_name'       => $project_setting[0]->project_name,
								'icon_image'       => $project_setting[0]->icon_image,
								'background_image' => $project_setting[0]->background_image,
								'domain_name'       => $project_setting[0]->domain_name,
								'pagename'       => $pagename_reciever,
								'from_userId'       => $from_id,
								'touserId'       => $to_user_id,
								'fund'       => $request->amount,
								'reciever_new_balance' => $reciever_new_balance->balance,
								'wallet_type'=>$wallet_type
							);

							$mail_transfer = sendMail($data_transfer, $email, $subject_transfer);
							$mail_recieve = sendMail($data_recieve, $to_email, $subject_reciever);


						$arrStatus  = Response::HTTP_OK;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Amount Transferred Successfully!';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');

					} else {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Insufficient balance';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');
					}

				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Invalid user';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Unaunthenticated User';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function WorkingToPurchaseSelfTransfer(Request $request)
	{
		// check user is from same browser or not
		$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info, Auth::user()
				->temp_info);
		if ($result == false) {
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]
				['status'], 'Invalid User Token!!!', '');
		}


		try {
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

			$check_record = whiteListIpAddress($type=3,Auth::user()->id);

            $ip_Address = getIpAddrss();
			$check_user_hits = WhiteListIpAddress::select('id', 'transfer_status', 'transfer_expire')->where([['uid',Auth::user()->id],['ip_add',$ip_Address]])->first();
			if(!empty($check_user_hits))
			{
				if($check_user_hits->transfer_status == 1){
					$today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
					if($check_user_hits->transfer_expire >= $today){
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Due to too many request hits, temporary you are block!', $this->emptyArray);
					}
				}
			}

			$id = Auth::User()->id;
			if (!empty($id)) {
				$transfer_status = verifyOtpStatus::select('transfer_update_status')
				->where('statusID','=',1)->get();
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
				// dd($otpdata['status']);
			if($otpdata['status'] == 200){
				$working_bal = Dashboard::selectRaw('working_wallet - working_wallet_withdraw as balance')->where('id', $id)->first();
				$amount      = $request->amount;
				if ($working_bal->balance >= $amount) {

					$updateCoinData = array();

					$updateCoinData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw +'.$amount.'');
					$updateCoinData['top_up_wallet']           = DB::raw('top_up_wallet +'.$amount.'');
					$updateqryto                               = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);

					$fundData                     = array();
					$fundData['to_user_id']       = $id;
					$fundData['from_user_id']     = $id;
					$fundData['amount']           = $amount;
					$fundData['status']           = 1;
					$fundData['from_wallet_type'] = 1;
					$fundData['to_wallet_type']   = 2;
					$fundData['ip_address']   = getIPAddress();;
					$fundData['entry_time']       = $this->today;

					$insFund = DexToPurchaseFundTransfer::create($fundData);

					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Amount transfer successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');

				} else {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Insufficient balance';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Unaunthenticated User';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		  }
		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
}
