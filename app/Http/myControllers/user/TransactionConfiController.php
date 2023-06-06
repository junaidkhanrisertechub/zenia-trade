<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Controllers\user\BlocktransactionController;
use App\Models\Dashboard;
use App\Models\FundTransactionInvoice;
use App\Models\Invoice;
use App\Models\TransactionInvoice;
use App\Traits\Income;
use App\Traits\Users;
use App\User;
use Config;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;

class TransactionConfiController extends Controller {
	use Income;
	use Users;

	public function __construct(BlocktransactionController $blockio, CommonController $commonController) {

		$this->commonController = $commonController;
		$this->statuscode       = Config::get('constants.statuscode');
		$this->blockio          = $blockio;
		$date                   = \Carbon\Carbon::now();
		$this->today            = $date->toDateTimeString();
	}

	/**
	 *  BTC Transaction confirmation on address
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function transactionconfirmation(Request $request) {
		try {
			$rules = array(
				'address' => 'required|',
			);

			//  echo $request->input('address');
			echo "\n";

			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, '');
			}

			//--------------Check adress exist with received-----------------------
			$UserTreceived = Invoice::where([['address', '=', trim($request->input('address'))], ['payment_mode', '=', 'BTC']])->first();// get address from deposit mst

			if (!empty($UserTreceived)) {
				//===============using blockio=======================================

				$AddTrecived = blockio_address(trim($request->input('address')));

				// echo "blockio";

				echo "\n";

				// print_r($AddTrecived);
				echo "\n";

				if (!empty($AddTrecived) && $AddTrecived['msg'] == 'success' && !empty($AddTrecived['data']['txs'])) {

					$txsArr = $AddTrecived['data']['txs'];

					if (!empty($txsArr)) {

						$retndata = $this->blockio->blockio_transaction($txsArr, $request->input('address'), $UserTreceived->id, $UserTreceived->price_in_usd, $UserTreceived->rec_amt);

						// return response()->json($retndata->original);
					}
				} else if (1) {
					//===============using blockchain=======================================
					$chainTrecived = blockchain_address(trim($request->input('address')));

					//  echo "blockchain";

					echo "\n";

					echo "\n";

					// dd($chainTrecived);
					// $chainTrecived['msg'] = 'failed';

					if ($chainTrecived['msg'] == 'success' && !empty($chainTrecived['data']['hash160'])) {

						$hash160 = $chainTrecived['data']['hash160'];// check hash is not empty
						if (!empty($hash160)) {

							$chaintxsArr = $chainTrecived['data']['txs'];

							// dd($chaintxsArr);

							if (!empty($chaintxsArr)) {

								$retndata = $this->blockio->blockchain_transaction($chaintxsArr, trim($request->input('address')), $UserTreceived->id, $UserTreceived->price_in_usd, $UserTreceived->rec_amt);

								//  return response()->json($retndata->original);
							}
						}// hash is not empty
						// total received is less
					} else if (1) {
						//===============using blockcyper=======================================
						$cyperTrecived = blockcyper_address(trim($request->input('address')));

						//  echo "blockcyper";

						echo "\n";
						// print_r($cyperTrecived);
						echo "\n";

						//  dd($cyperTrecived);

						//echo($request->input('address'));
						if ($cyperTrecived['msg'] == 'success' && !empty($cyperTrecived['data']['txrefs'])) {
							$txrefs = $cyperTrecived['data']['txrefs'];
							if (!empty($txrefs)) {

								$cyperndata = $this->blockio->blockcyper_transaction($txrefs, $request->input('address'), $UserTreceived->id, $UserTreceived->price_in_usd, $UserTreceived->rec_amt);
							}
						} else if (1) {
							//===============using blockbitaps===
							$bitapsrecived = blockbitaps_address(trim($request->input('address')));

							//    echo "blockbitaps";
							//  print_r($bitapsrecived);
							echo "\n";
							//  dd($bitapsrecived);
							echo "\n";
							// dd($bitapsrecived);
							if ($bitapsrecived['msg'] == 'success' && !empty($bitapsrecived['data'])) {

								$bitapsdata = $this->blockio->blockbitaps_transaction($bitapsrecived['data'], $request->input('address'), $UserTreceived->id, $UserTreceived->price_in_usd, $UserTreceived->rec_amt);
							}
						}
						//===========================================================================
					}
				}
			}
		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 *  BTC Transaction confirmation on address
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function confirmTransaction(Request $request) {
		try {
			$arr         = $request->all();
			$txn['txid'] = $arr['txid'];
			$trans_data  = get_trans_status('get_tx_info', $txn);

			$trans_status = $trans_data['data'];
			//dd($trans_status);
			if (isset($trans_status['error'])) {
					if ($trans_status['error'] == "ok") {
						if ($trans_status['result']['status'] == 1 || $trans_status['result']['status'] == 100) {

								$check = TransactionInvoice::where('srno', '=', $arr['srno'])
								->where('in_status', '=', 0)
								->where('top_up_status', '=', 0)
								->get();

								/*$check_topup_exist = Topup::select('id')->where('pin', $arr['invoice_id'])->count('id');*/

								if (count($check) == 1) {

								// $this->storeActivationTopup($arr['id'], $arr['plan_id'], $arr['hash_unit'], $arr['invoice_id']);
								 $dash['fund_wallet'] = DB::RAW('fund_wallet + ' . $arr['price_in_usd']);
								Dashboard::where('id', $arr['id'])->update($dash);

								$updateOtpSta = TransactionInvoice::where('srno', $arr['srno'])->update(array('in_status' => 1, 'top_up_status' => 1, 'top_up_date' => $this->today));
								$subject  = "Successful deposit to HSCC account";
								$pagename = "emails.deposit_funds";
								$user_info = User::where('id', $arr['id'])->first();
								$data     = array('pagename' => $pagename, 'username' => $user_info->user_id,
									'name'=>$user_info->fullname,'invoice_id'=>$txn['txid']);
								$email    = $user_info->email;
								$mail     = sendMail($data, $email, $subject);
							}
						} elseif ($trans_status['result']['status'] == -1) {

							$updateOtpSta = TransactionInvoice::where('srno', $arr['srno'])->update(array('in_status' => 2));
						}
					}//dd($trans_data);
		   }
		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function confirmFundTransaction(Request $request) {
		try {
			$arr         = $request->all();
			$txn['txid'] = $arr['txid'];
			$trans_data  = get_trans_status('get_tx_info', $txn);

			$trans_status = $trans_data['data'];
			//dd($trans_status);
			if ($trans_status['error'] == "ok") {
				if ($trans_status['result']['status'] == 1 || $trans_status['result']['status'] == 100) {

					$check = FundTransactionInvoice::where('srno', '=', $arr['srno'])
						->where('in_status', '=', 0)
						->where('top_up_status', '=', 0)
						->get();

					if (count($check) == 1) {

						$dash['fund_wallet'] = DB::RAW('fund_wallet + ' . $arr['price_in_usd']);
						Dashboard::where('id', $arr['id'])->update($dash);

						$updateOtpSta = FundTransactionInvoice::where('srno', $arr['srno'])->update(array('in_status' => 1, 'top_up_status' => 1, 'top_up_date' => $this->today));
					}
				} elseif ($trans_status['result']['status'] == -1) {

					$updateOtpSta = FundTransactionInvoice::where('srno', $arr['srno'])->update(array('in_status' => 2));
				}
			}//dd($trans_data);
		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * ETH Transaction confirmation on address
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function ETHtransaction(Request $request) {

		try {
			$rules = array(
				'address' => 'required|',
			);

			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			//--------------Check adress exist with received-----------------------
			$UserTreceived = Invoice::where([['address', '=', trim($request->input('address'))], ['payment_mode', '=', 'ETH']])->first();// get address from deposit mst

			if (!empty($UserTreceived)) {
				//===============using blockio=======================================

				$Transaction = ETHConfirmation(trim($request->input('address')));

				if (!empty($Transaction) && $Transaction['msg'] == 'success') {

					$txsArr = $Transaction['data'];

					if (!empty($txsArr)) {

						$retndata = $this->blockio->etherscanio_transaction($txsArr, $request->input('address'), $UserTreceived->id, $UserTreceived->price_in_usd, $UserTreceived->rec_amt);
					}
				}
			}
		} catch (Exception $e) {

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function storeActivationTopup($id, $plan_id, $hash_unit, $tran_invoice_id) {
		// dd($hash_unit);
		try {

			// $arrInput = $request->all();

			// $rules = array(
			// 	'id' => 'required',
			// 	'product_id' => 'required',
			// 	/*'franchise_user_id' => 'required',
			// 'masterfranchise_user_id' => 'required',*/
			// 	'hash_unit' => 'required',
			// );
			// $validator = Validator::make($arrInput, $rules);
			// if ($validator->fails()) {
			// 	$message = $validator->errors();
			// 	return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
			// } else {
			$top_up_by = $id;

			$objProduct = $this->commonController->getAllProducts(['id' => $plan_id]);
			// dd($objProduct[0]['min_hash']);
			// dd($objProduct);
			if ($hash_unit < $objProduct[0]['min_hash'] && $hash_unit > $objProduct[0]['max_hash']) {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Amount range should be min'.$objProduct[0]['min_hash'].'max'.$objProduct[0]['max_hash'], '');
			}

			$touser     = User::select('id', 'user_id', 'virtual_parent_id', 'ref_user_id', 'position')->where('id', $id)->first();
			$getPrice   = Topup::select('entry_time')->where([['id', '=', $id], ['amount', '>', 0]])->select('entry_time', 'id')->orderBy('srno', 'desc')->first();
			$checktopup = Topup::where([['id', $id]])->count();
			// dd($checktopup);
			if ($checktopup > 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one topup allowed for one user', '');
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
			}

			/*if($objProduct->package_type=='Franchise')*/

			if ($hash_unit >= 10000) {
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
				$amount = $hash_unit;
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
				$amount = $hash_unit;
			}
			/*$franchise_user = User::where('id', $request->franchise_user_id)->first();

			$masterfranchise_user = User::where('id', $request->masterfranchise_user_id)->first();
			 */
			if (!empty($objProduct)) {
				$invoice_id = substr(number_format(time()*rand(), 0, '', ''), 0, '15');
				$arrInsert  = [
					'id'         => $id,
					'pin'        => $invoice_id,
					'amount'     => $amount,
					'percentage' => $objProduct[0]['roi']*$objProduct[0]['duration_month'],
					'type'       => $objProduct[0]['id'],
					/*'payment_type' => $arrInput['payment_type'],*/
					'top_up_by'   => $top_up_by,
					'top_up_type' => 2, //admin topup type
					/*'franchise_id' => $franchise_user->id,
					'master_franchise_id' => $masterfranchise_user->id,*/
					'binary_pass_status' => '1',
					'usd_rate'           => '0',
					'roi_status'         => 'Active',
					'total_usd'          => 0.001,
					'entry_time'         => now(),
				];
				$storeId = Topup::insertGetId($arrInsert);

				if (!empty($storeId)) {

					$price_in_usd = $hash_unit;//$objProduct->cost *

					$direct_income = $objProduct[0]['direct_income'];
					$settings      = $this->commonController->getProjectSettings();
					/*
					if($objProduct->package_type!='Franchise')
					{*/
					//update dahboatd value
					$udash = Dashboard::select('active_investment', 'total_investment')->where('id', $id)->first();

					$total_investment  = $udash->total_investment;
					$active_investment = $udash->total_investment;

					//$total_investment = Dashboard::where('id', $arrInput['id'])->pluck('total_investment')->first();
					//$active_investment = Dashboard::where('id', $arrInput['id'])->pluck('active_investment')->first();
					$updateCoinData['total_investment']  = custom_round(($total_investment+$price_in_usd), 7);
					$updateCoinData['active_investment'] = custom_round(($active_investment+$price_in_usd), 7);
					$updateCoinData                      = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);

					$arrtransactionInsert = [
						'in_status'     => '1',
						'top_up_status' => '1',
					];
					$updateTransactionData = TransactionInvoice::where('id', $id)->limit(1)->update($arrtransactionInsert);

					if ($settings->level_plan == 'on') {
						$getlevel = $this->pay_level($id, $price_in_usd, $plan_id);
						/* $getlevel = $this->uplineIncome($arrInput['id'],$price_in_usd,$arrInput['plan_id']);*/
					}

					if ($settings->binary_plan == 'on') {

						// update direct business

						$updateLCountArrDirectBusiness               = array();
						$updateLCountArrDirectBusiness['power_l_bv'] = DB::raw('power_l_bv + '.$price_in_usd.'');

						$updateRCountArrDirectBusiness               = array();
						$updateRCountArrDirectBusiness['power_r_bv'] = DB::raw('power_r_bv + '.$price_in_usd.'');

						if ($touser->position == 1) {
							User::where('id', $touser->ref_user_id)->update($updateLCountArrDirectBusiness);
						} else if ($touser->position == 2) {
							User::where('id', $touser->ref_user_id)->update($updateRCountArrDirectBusiness);
						}

						$usertopup = array('amount' => DB::raw('amount + '.$price_in_usd), 'topup_status' => "1");
						User::where('id', $touser->id)->update($usertopup);

						$getlevel = $this->pay_binary($id, $price_in_usd);
						// check rank of vpid

						//$this->check_rank_vpid($touser->virtual_parent_id);
						/* $this->check_rank_vpid($touser->id);
					$this->check_rank_vpid($touser->ref_user_id); */
					}
					if ($settings->direct_plan == 'on' && $direct_income > 0) {

						$plan = TransactionInvoice::join('tbl_product as pd', 'pd.id', '=', 'tbl_transaction_invoices.plan_id')->select('pd.name', 'pd.direct_income')->where([['tbl_transaction_invoices.id', '=', $touser->id], ['tbl_transaction_invoices.invoice_id', '=', $tran_invoice_id]])->first();
						//	$getlevel = $this->pay_direct($arrInput['id'], $price_in_usd, $direct_income, $invoice_id);
						// check rank for direct user to give direct income

						/* $this->check_rank($touser->ref_user_id); */

						//$getlevel = $this->pay_direct($users->id, $Productcost, $direct_income, $random);
						$this->pay_directbulk($touser->id, $price_in_usd, $direct_income, $invoice_id, $touser->ref_user_id, $touser->user_id, $plan->direct_income);
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

					$subject  = "Package Activated!";
					$pagename = "emails.deposit";
					$data     = array('pagename' => $pagename, 'email' => $touser->email, 'amount' => $amount, 'username' => $touser->user_id, 'Package' => $objProduct[0]['name']);
					$email    = $touser->email;
					$mail     = sendMail($data, $email, $subject);

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
			// }
		} catch (Exception $e) {
			dd($e);
		}
	}
}
