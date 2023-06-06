<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\CurrencyConvertorController;
use App\Http\Controllers\userapi\SettingsController;
use App\Http\Controllers\userapi\TopupController;
use App\Models\Activitynotification;
use App\Models\AddressTransaction;
use App\Models\AllTransaction;
use App\Models\Dashboard;
use App\Models\Invoice;
use App\Models\ProjectSettings;
use App\Models\Topup;
use App\User;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;

class BlocktransactionController extends Controller {

	public function __construct(SettingsController $projectsetting, CurrencyConvertorController $currency, TopupController $topup) {

		$this->projectsettings = $projectsetting;
		$this->currency        = $currency;

		$no_of_confirmation       = ProjectSettings::where('status', 1)->pluck('no_of_confirmation')->first();
		$this->no_of_confirmation = $no_of_confirmation;
		$this->topup              = $topup;
		$date                     = \Carbon\Carbon::now();
		$this->today              = $date->toDateTimeString();
	}

	/**
	 * Insert data on tables after condirmation
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function insertTransaction($hashkey, $address, $total, $userid) {
		try {
			$invoice_id   = Invoice::where([['address', '=', $address]])->pluck('invoice_id')->first();
			$price_in_usd = Invoice::where([['invoice_id', '=', $invoice_id]])->pluck('price_in_usd')->first();
			$payment_mode = Invoice::where([['invoice_id', '=', $invoice_id]])->pluck('payment_mode')->first();
			$rec_amt      = Invoice::where([['invoice_id', '=', $invoice_id]])->pluck('rec_amt')->first();

			$trasndata               = array();
			$trasndata['trans_hash'] = $hashkey;
			$trasndata['remark']     = 'Deposit Amount: '.custom_round($total, 7).' '.$payment_mode.' , Deposit Address: '.$address.' Transaction Hash: '.$hashkey;
			$trasndata['in_status']  = 1;
			// $trasndata['rec_amt']=custom_round($rec_amt+$total,7);
			$updateOtpSta             = Invoice::where([['address', '=', $address]])->update($trasndata);
			$balance                  = AllTransaction::where('id', '=', $userid)->orderBy('srno', 'desc')->pluck('balance')->first();
			$invoice_id               = Invoice::where([['address', '=', $address]])->pluck('invoice_id')->first();
			$Trandata                 = array();// insert in transaction
			$Trandata['id']           = $userid;
			$Trandata['network_type'] = $payment_mode;
			$Trandata['refference']   = $invoice_id;
			$Trandata['credit']       = custom_round($price_in_usd, 7);
			$Trandata['balance']      = $balance+custom_round($price_in_usd, 7);
			$Trandata['type']         = 'DEPOSIT';
			$Trandata['entry_time']   = $this->today;
			$Trandata['status']       = 1;
			$Trandata['remarks']      = 'Deposit  Amount: '.custom_round($total, 7).' '.$payment_mode.' , Deposit Address: '.$address.' Transaction Hash: '.$hashkey.'';
			$TransactionDta           = AllTransaction::create($Trandata);

			$updateData     = array();
			$dashboardExist = Dashboard::where([['id', '=', $userid]])->first();
			if (empty($dashboardExist)) {
				$dashdata     = new Dashboard;
				$dashdata->id = $userid;
				$dashdata->save();
			}

			//------activity notification---
			/* $msgstatus = 'Your transaction is confirmed with amount: ' . $total . ' ' . $payment_mode . ' ,Deposite Address: ' . $address . ' Transaction Hash: ' . $hashkey . '';*/

			$msgstatus = 'Greetings on your successful confirmation of your deposit : '.$price_in_usd.' '.$payment_mode.'with order ID : '.$invoice_id;

			/* $whatsappMsg = "Greetings on your successful confirmation of your deposit ". $price_in_usd. "with order ID" . $invoice_id . "\nvisit\nFor any queries contact +919604819152";*/

			$getUser = User::where([['id', '=', $userid]])->first();

			// $countrycode = getCountryCode($getUser->country);

			//   sendSMS($getUser->mobile, $whatsappMsg);
			//sendWhatsappMsg($countrycode, $getUser->mobile, $whatsappMsg);

			/*$subject = "Deposite Confirmation";
			$pagename = "emails.deposite_confirmation";
			$data = array('pagename' => $pagename, 'amount' => $price_in_usd, 'order_id' => $invoice_id);
			$email = $getUser->email;
			$mail = sendMail($data, $email, $subject);*/

			$actdata               = array();// insert in transaction
			$actdata['id']         = $userid;
			$actdata['message']    = $msgstatus;
			$actdata['status']     = 1;
			$actdata['entry_time'] = $this->today;
			$actDta                = Activitynotification::create($actdata);

			$price_in_usd = Invoice::where([['invoice_id', '=', $invoice_id]])->pluck('price_in_usd')->first();

			// check user first product price is less or equal to current
			$getPrice = Topup::where([['id', '=', $userid]])->select('amount')->orderBy('srno', 'desc')->get();

			if (count($getPrice) > 0) {
				//if ($price_in_usd >= $getPrice[0]->amount) {
				if (!empty($invoice_id)) {
					$this->topup->selectpackages($invoice_id, $price_in_usd, $userid);
				}
				//}
			} else if (count($getPrice) == 0) {
				if (!empty($invoice_id)) {
					$this->topup->selectpackages($invoice_id, $price_in_usd, $userid);
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
	 * Blockio address transaction function
	 * update tbl_deposit_address  total received
	 * update  tbl_dashboard btc value
	 * insert tbl_deposit_address_transaction
	 * @return \Illuminate\Http\Response
	 */

	public function blockio_transaction($txsArr, $address, $userid, $dbusd, $dbrec_amt) {

		try {
			if (!empty($txsArr)) {
				$payment_mode = Invoice::where([['address', '=', $address]])->pluck('payment_mode')->first();
				$flag         = 'false';
				$total        = 0;
				foreach ($txsArr as $k => $v) {
					$hashkey       = $txsArr[$k]['txid'];
					$confirmations = $txsArr[$k]['confirmations'];

					$transaction_hash_Exist = AddressTransaction::where([['transaction_hash', '=', $hashkey], ['address', '=', $address]])->first();
					if (empty($transaction_hash_Exist)) {
						if ($confirmations > $this->no_of_confirmation) {
							$btc_val                       = custom_round($txsArr[$k]['amounts_received'][0]['amount'], 7);
							$value                         = $btc_val*100000000;
							$total                         = $total+$btc_val;
							$req1                          = new Request;
							$req1['btcvalue']              = $btc_val;
							$data                          = $this->currency->btcTousd($req1);
							$usd_amount                    = $data->original['data']['usd'];
							$trasndata                     = array();
							$trasndata['transaction_hash'] = $hashkey;
							$trasndata['address']          = trim($address);
							$trasndata['confirmation']     = $confirmations;
							$trasndata['value']            = $value;
							$trasndata['btc']              = $btc_val;
							$trasndata['usd']              = $usd_amount;
							$trasndata['id']               = $userid;
							$trasndata['remark']           = 'Approved by admin';
							$trasndata['confirm_remark']   = 'Blockio';
							$trasndata['confirm_date']     = date('Y-m-d H:i:s');
							$trasndata['ip_address']       = request()->ip();
							$trasndata['network_type']     = $payment_mode;
							$trasndata['status']           = 'confirmed';
							$trasndata['entry_time']       = $this->today;
							$insertactDta                  = AddressTransaction::create($trasndata);

							// $dash = Dashboard::where('id',$userid)->first();
							// Dashboard::where('id',$userid)->update(['top_up_wallet'=>$dash->top_up_wallet + $usd_amount]);

						}
					}
				}

				// update recievd amount
				if ($total > 0) {
					$rec_amt = $dbrec_amt+$total;// update
				} else {
					$rec_amt = $dbrec_amt;// as its is
				}

				$trasndata            = array();
				$trasndata['rec_amt'] = custom_round($rec_amt, 7);
				$updateOtpSta         = Invoice::where([['address', '=', $address]])->update($trasndata);// update rec_Amount
				//if user total balance is greater

				if ($rec_amt > 0) {

					$req             = new Request;
					$req['btcvalue'] = $rec_amt;
					$data            = $this->currency->btcTousd($req);
					//dd($data);// make btc to usd
					$total_usd_amount = $data->original['data']['usd'];

					$dbusd1 = $dbusd;

					$dbusd = $dbusd-2;// if used is less than 2 then also give confimation

					if (!empty($data->original['data'])) {
						if ($total_usd_amount >= $dbusd) {

							$dash = Dashboard::where('id', $userid)->first();
							Dashboard::where('id', $userid)->update(['top_up_wallet' => $dash->top_up_wallet+$dbusd1]);

							// status in_status = 1
							$trasndata['in_status']   = 1;
							$trasndata['top_up_date'] = \Carbon\Carbon::now();
							$updateOtpSta             = Invoice::where([['address', '=', $address]])->update($trasndata);

							// $lastTransction = end($txsArr);
							// $hashkey = $lastTransction['txid'];
							// $insertTransaction = $this->insertTransaction($hashkey, $address, $rec_amt, $userid);
						}
					}
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
	 * Block- chain address transaction function
	 * update tbl_deposit_address  total received
	 * update  tbl_dashboard btc value
	 * insert tbl_deposit_address_transaction
	 * @return \Illuminate\Http\Response
	 */

	public function blockchain_transaction($chaintxsArr, $chainaddress, $userid, $dbusd, $dbrec_amt) {

		try {
			$address = $chainaddress;
			if (!empty($chaintxsArr)) {
				$payment_mode = Invoice::where([['address', '=', $chainaddress]])->pluck('payment_mode')->first();

				$finaltotal = 0;

				foreach ($chaintxsArr as $k => $val) {
					$inputarray          = $chaintxsArr[$k]['inputs'];// input array
					$outarray            = $chaintxsArr[$k]['out'];
					$block_current_count = blockchain_confirmation();

					$block_height = $chaintxsArr[$k]['block_height'];
					$confirmation = ($block_current_count['current_block_count']-$block_height)+1;

					$input_addr = array();
					if (!empty($inputarray)) {
						foreach ($inputarray as $input => $getval) {
							$input_addr[] = $input['prev_out']['addr'];// take array of input address
						}
						$gethash = array();
						if (!empty($outarray)) {
							if (!in_array($chainaddress, $input_addr)) {
								foreach ($outarray as $output => $otval) {
									if (!empty($outarray[$output]['addr']) && $outarray[$output]['addr'] == $chainaddress) {
										$output                              = $outarray[$output]['value'];
										$gethash[$chaintxsArr[$k]['hash']][] = $output;
									}
								}
							}
						}
						if (!empty($outarray) && !empty($gethash)) {
							$getarr = array();
							foreach ($gethash as $o => $v) {
								foreach ($v as $p) {
									if (!array_key_exists($o, $getarr)) {
										$getarr[$o] = $p;
									} else {
										$getarr[$o] += $p;
									}
								}
							}

							if (!empty($outarray) && !empty($gethash) && !empty($getarr)) {// insert transaction
								foreach ($getarr as $hashkey => $total) {

									$transaction_hash_Exist = AddressTransaction::where([['transaction_hash', '=', $hashkey], ['address', '=', $chainaddress]])->first();
									if (empty($transaction_hash_Exist)) {
										if ($confirmation > $this->no_of_confirmation) {
											$btc_val                       = custom_round(($total/100000000), 7);
											$finaltotal                    = $finaltotal+$btc_val;
											$req1                          = new Request;
											$req1['btcvalue']              = $btc_val;
											$data                          = $this->currency->btcTousd($req1);
											$usd_amount                    = $data->original['data']['usd'];
											$trasndata                     = array();
											$trasndata['transaction_hash'] = $hashkey;
											$trasndata['address']          = trim($chainaddress);
											$trasndata['confirmation']     = $confirmation;
											$trasndata['value']            = $total;
											$trasndata['btc']              = $btc_val;
											$trasndata['usd']              = $usd_amount;
											$trasndata['id']               = $userid;
											$trasndata['remark']           = 'Approved by admin';
											$trasndata['confirm_remark']   = 'Blockio';
											$trasndata['confirm_date']     = date('Y-m-d H:i:s');
											$trasndata['ip_address']       = request()->ip();
											$trasndata['network_type']     = $payment_mode;
											$trasndata['status']           = 'confirmed';
											$trasndata['entry_time']       = $this->today;
											$insertactDta                  = AddressTransaction::create($trasndata);

											//              $dash = Dashboard::where('id',$userid)->first();
											// Dashboard::where('id',$userid)->update(['top_up_wallet'=>$dash->top_up_wallet + $usd_amount]);
										}
									}
								}
							}
						}
					}
				}

				// dd($total);
				// update recievd amount
				if ($finaltotal > 0) {
					$rec_amt = $dbrec_amt+$finaltotal;// update
				} else {
					$rec_amt = $dbrec_amt;// as its is
				}

				$trasndata            = array();
				$trasndata['rec_amt'] = custom_round($rec_amt, 7);
				$updateOtpSta         = Invoice::where([['address', '=', $address]])->update($trasndata);// update invoice rec_Amout
				//if user total balance is greater
				// dd($rec_amt);
				if ($rec_amt > 0) {
					$req              = new Request;
					$req['btcvalue']  = $rec_amt;
					$data             = $this->currency->btcTousd($req);// btc to usd convetior
					$total_usd_amount = $data->original['data']['usd'];
					$dbusd1           = $dbusd;
					$dbusd            = $dbusd-2;
					if (!empty($data->original['data'])) {
						if ($total_usd_amount > $dbusd) {

							$dash = Dashboard::where('id', $userid)->first();
							Dashboard::where('id', $userid)->update(['top_up_wallet' => $dash->top_up_wallet+$dbusd1]);

							// status in_status = 1
							$trasndata['in_status']   = 1;
							$trasndata['top_up_date'] = \Carbon\Carbon::now();
							$updateOtpSta             = Invoice::where([['address', '=', $address]])->update($trasndata);

							// $hashkey = (array_keys($getarr)[0]);
							// $insertTransaction = $this->insertTransaction($hashkey, $chainaddress, $rec_amt, $userid);
						}
					}
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
	 * Block- cyper address transaction function
	 * update tbl_deposit_address  total received
	 * update  tbl_dashboard btc value
	 * insert tbl_deposit_address_transaction
	 * @return \Illuminate\Http\Response
	 */
	public function blockcyper_transaction($txsArr, $address, $userid, $dbusd, $dbrec_amt) {
		$amountarr = array();

		try {
			if (!empty($txsArr)) {
				$payment_mode = Invoice::where([['address', '=', $address]])->pluck('payment_mode')->first();

				$flag  = 'false';
				$total = 0;

				foreach ($txsArr as $k => $v) {
					$hashkey                = $txsArr[$k]['tx_hash'];
					$confirmations          = $txsArr[$k]['confirmations'];
					$transaction_hash_Exist = AddressTransaction::where([['transaction_hash', '=', $hashkey], ['address', '=', $address]])->first();
					// dd($transaction_hash_Exist);
					if (empty($transaction_hash_Exist)) {
						if ($confirmations > $this->no_of_confirmation) {

							$tx_output_n = $txsArr[$k]['tx_output_n'];
							if ($tx_output_n >= 0) {
								$value                         = $txsArr[$k]['value'];
								$btc_val                       = custom_round(($value/100000000), 7);
								$total                         = $total+$btc_val;
								$req1                          = new Request;
								$req1['btcvalue']              = $btc_val;
								$data                          = $this->currency->btcTousd($req1);
								$usd_amount                    = $data->original['data']['usd'];
								$trasndata                     = array();
								$trasndata['transaction_hash'] = $hashkey;
								$trasndata['address']          = trim($address);
								$trasndata['confirmation']     = $confirmations;
								$trasndata['value']            = $value;
								$trasndata['btc']              = $btc_val;
								$trasndata['usd']              = 0;
								$trasndata['id']               = $userid;
								$trasndata['remark']           = 'Approved by admin';
								$trasndata['confirm_remark']   = 'Blockio';
								$trasndata['confirm_date']     = date('Y-m-d H:i:s');
								$trasndata['ip_address']       = request()->ip();
								$trasndata['network_type']     = $payment_mode;
								$trasndata['status']           = 'confirmed';
								$trasndata['entry_time']       = $this->today;

								$insertactDta = AddressTransaction::create($trasndata);

							}
						}
					}
				}

				// dd($total);
				// update recievd amount
				if ($total > 0) {
					$rec_amt = $dbrec_amt+$total;// update
				} else {
					$rec_amt = $dbrec_amt;// as its is
				}
				$trasndata            = array();
				$trasndata['rec_amt'] = custom_round($rec_amt, 7);
				$updateOtpSta         = Invoice::where([['address', '=', $address]])->update($trasndata);// update
				//if user total balance is greater
				//dd($rec_amt);
				if ($rec_amt > 0) {
					$req              = new Request;
					$req['btcvalue']  = $rec_amt;
					$data             = $this->currency->btcTousd($req);// btc to usd convetior
					$total_usd_amount = $data->original['data']['usd'];

					if (!empty($data->original['data'])) {
						$dbusd1 = $dbusd;
						$dbusd  = $dbusd-2;
						if ($total_usd_amount > $dbusd) {

							$dash = Dashboard::where('id', $userid)->first();
							Dashboard::where('id', $userid)->update(['top_up_wallet' => $dash->top_up_wallet+$dbusd1]);

							// status in_status = 1
							$trasndata['in_status']   = 1;
							$trasndata['top_up_date'] = \Carbon\Carbon::now();
							$updateOtpSta             = Invoice::where([['address', '=', $address]])->update($trasndata);

							// $lastTransction = end($txsArr);
							// $hashkey = $lastTransction['tx_hash'];
							// $insertTransaction = $this->insertTransaction($hashkey, $address, $rec_amt, $userid);

						}
					}
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
	 * Block- bitpas address transaction function
	 * update tbl_deposit_address  total received
	 * update  tbl_dashboard btc value
	 * insert tbl_deposit_address_transaction
	 * @return \Illuminate\Http\Response
	 */

	public function blockbitaps_transaction($txsArr, $address, $userid, $dbusd, $dbrec_amt) {
		$amountarr = array();
		try {
			if (!empty($txsArr)) {
				$payment_mode = Invoice::where([['address', '=', $address]])->pluck('payment_mode')->first();

				$flag  = 'false';
				$total = 0;
				foreach ($txsArr as $k => $v) {
					$hashkey                = $txsArr[$k]['1'];
					$confirmations          = $txsArr[$k]['5'];
					$transaction_hash_Exist = AddressTransaction::where([['transaction_hash', '=', $hashkey], ['address', '=', $address]])->first();
					if (empty($transaction_hash_Exist)) {
						if ($confirmations > $this->no_of_confirmation) {
							$value                         = $txsArr[$k]['7'];
							$btc_val                       = custom_round(($value/100000000), 7);
							$total                         = $total+$btc_val;
							$req1                          = new Request;
							$req1['btcvalue']              = $btc_val;
							$data                          = $this->currency->btcTousd($req1);
							$usd_amount                    = $data->original['data']['usd'];
							$trasndata                     = array();
							$trasndata['transaction_hash'] = $hashkey;
							$trasndata['address']          = trim($address);
							$trasndata['confirmation']     = $confirmations;
							$trasndata['value']            = $value;
							$trasndata['btc']              = $btc_val;
							$trasndata['usd']              = $usd_amount;
							$trasndata['id']               = $userid;
							$trasndata['remark']           = 'Approved by admin';
							$trasndata['confirm_remark']   = 'Blockio';
							$trasndata['confirm_date']     = date('Y-m-d H:i:s');
							$trasndata['ip_address']       = request()->ip();
							$trasndata['network_type']     = $payment_mode;
							$trasndata['status']           = 'confirmed';
							$trasndata['entry_time']       = $this->today;

							$insertactDta = AddressTransaction::create($trasndata);
							// $dash = Dashboard::where('id',$userid)->first();
							// Dashboard::where('id',$userid)->update(['top_up_wallet'=>$dash->top_up_wallet + $usd_amount]);
						}
					}
				}
				// update recievd amount
				if ($total > 0) {
					$rec_amt = $dbrec_amt+$total;// update
				} else {
					$rec_amt = $dbrec_amt;// as it is
				}
				$trasndata            = array();
				$trasndata['rec_amt'] = custom_round($rec_amt, 7);
				$updateOtpSta         = Invoice::where([['address', '=', $address]])->update($trasndata);
				//if user total balance is greater
				if ($rec_amt > 0) {
					$req              = new Request;
					$req['btcvalue']  = $rec_amt;
					$data             = $this->currency->btcTousd($req);// btc to usd convertor
					$total_usd_amount = $data->original['data']['usd'];
					if (!empty($data->original['data'])) {
						$dbusd1 = $dbusd;
						$dbusd  = $dbusd-2;
						if ($total_usd_amount > $dbusd) {

							$dash = Dashboard::where('id', $userid)->first();
							Dashboard::where('id', $userid)->update(['top_up_wallet' => $dash->top_up_wallet+$dbusd1]);

							// status in_status = 1
							$trasndata['in_status']   = 1;
							$trasndata['top_up_date'] = \Carbon\Carbon::now();
							$updateOtpSta             = Invoice::where([['address', '=', $address]])->update($trasndata);

							// $lastTransction = end($txsArr);
							// $hashkey = $lastTransction['1'];
							// $insertTransaction = $this->insertTransaction($hashkey, $address, $rec_amt, $userid);
						}
					}
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
	 * ETH  address transaction function
	 * update tbl_deposit_address  total received
	 * update  tbl_dashboard btc value
	 * insert tbl_deposit_address_transaction
	 * @return \Illuminate\Http\Response
	 */

	public function etherscanio_transaction($txsArr, $address, $userid, $dbusd, $dbrec_amt) {
		$amountarr = array();

		try {
			if (!empty($txsArr)) {
				$flag         = 'false';
				$total        = 0;
				$payment_mode = Invoice::where([['address', '=', $address]])->pluck('payment_mode')->first();
				foreach ($txsArr as $k => $v) {
					$toAddress = $txsArr[$k]['to'];

					if ($toAddress == $address) {

						$hashkey       = $txsArr[$k]['hash'];
						$confirmations = $txsArr[$k]['confirmations'];

						$transaction_hash_Exist = AddressTransaction::where([['transaction_hash', '=', $hashkey], ['address', '=', $address]])->first();
						if (empty($transaction_hash_Exist)) {
							if ($confirmations > $this->no_of_confirmation) {
								$eth_val                       = $txsArr[$k]['value'];
								$value                         = custom_round($eth_val/1000000000000000000, 7);
								$total                         = $total+$value;
								$data                          = $this->currency->Ethtousd();
								$total_eth_amount              = $data->original['data']['USD'];
								$eth_usd_amount                = $total_eth_amount*$value;
								$trasndata                     = array();
								$trasndata['transaction_hash'] = $hashkey;
								$trasndata['address']          = trim($address);
								$trasndata['confirmation']     = $confirmations;
								$trasndata['value']            = $value;
								$trasndata['btc']              = $eth_val;
								$trasndata['usd']              = $eth_usd_amount;
								$trasndata['id']               = $userid;
								$trasndata['remark']           = 'Approved by admin';
								$trasndata['confirm_remark']   = 'ETH';
								$trasndata['confirm_date']     = date('Y-m-d H:i:s');
								$trasndata['ip_address']       = request()->ip();
								$trasndata['network_type']     = $payment_mode;
								$trasndata['status']           = 'confirmed';
								$trasndata['entry_time']       = $this->today;
								$insertactDta                  = AddressTransaction::create($trasndata);
							}
						}
					}
				}

				if ($total > 0) {
					$rec_amt = $dbrec_amt+$total;
				} else {
					$rec_amt = $dbrec_amt;
				}

				$trasndata            = array();
				$trasndata['rec_amt'] = custom_round($rec_amt, 7);
				$updateOtpSta         = Invoice::where([['address', '=', $address]])->update($trasndata);
				if ($rec_amt > 0) {

					$data             = $this->currency->Ethtousd();
					$total_eth_amount = $data->original['data']['USD'];
					$eth_usd_amount   = $total_eth_amount*$rec_amt;

					if (!empty($data->original['data'])) {
						$dbusd = $dbusd-2;
						if ($eth_usd_amount > $dbusd) {

							$lastTransction = end($txsArr);

							$hashkey           = $lastTransction['hash'];
							$insertTransaction = $this->insertTransaction($hashkey, $address, $rec_amt, $userid);
						}
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

}
