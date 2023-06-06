<?php

//namespace AyubIRZ\PerfectMoneyAPI;
namespace App\Http\Controllers\userapi;

use App\Http\Controllers\userapi\CurrencyConvertorController;
use App\Models\Dashboard;
use App\Models\Invoice;
use App\Models\PerfectMoneyMember;
use App\Models\ProjectSettings;
use Auth;
use AyubIRZ\PerfectMoneyAPI\PerfectMoneyAPI;
use Config;
use Illuminate\Http\Request;
// use PM;

use Illuminate\Http\Response as Response;

class PerfectMoneyController {
	/*
	 * @var integer AccountID: the username of your PM account.
	 */
	protected $AccountID;

	/*
	 * @var string PassPhrase: the password of your PM account.
	 */
	protected $PassPhrase;

	/**
	 * Constructor
	 *
	 */
	public function __construct(CurrencyConvertorController $CurrencyConvertorController) {
		$this->CurrencyConvertorController = $CurrencyConvertorController;
		$this->statuscode                  = Config::get('constants.statuscode');
	}

	/**
	 * Fetch the public name of another existing PerfectMoney account
	 *
	 */
	public function transferPayment(Request $request) {
		//dd('11');

		$rules = array(
			//'product_id' => 'required',
			'amount' => 'required|numeric',
			//'franchise_user_id' => 'required',
			'username' => 'required',
			'password' => 'required',
		);
		$validator = checkvalidation($request->all(), $rules, '');
		if (!empty($validator)) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, []);
		}
		//dd($request->amount);
		$PMAccountID = '1000589';// This should be replaced with your real PerfectMoney Member ID(username that you login with).

		$PMPassPhrase = 'new$CAS99';// This should be replaced with your real PerfectMoney PassPhrase(password that you login with).

		$PM = new PerfectMoneyAPI($PMAccountID, $PMPassPhrase);

		$fromAccount = $request->username;// Replace this with one of your own wallet IDs that you want to transfer currency from. from U22953769

		$toAccount = 'U22953769';// Replace this with the destination wallet ID that you want to transfer currency to. To U22384420

		$amount = $request->amount;// Replace this with the amount of currency unit(in this case 250 USD) that you want to transfer.
		//dd($amount);

		$paymentID = microtime();// Replace this with a unique payment ID that you've generated for this transaction. This can be the ID for the database stored record of this payment for example(Up to 50 characters). ***THIS PARAMETER IS OPTIONAL***

		$memo = 'Test';// Replace this with a description text that will be shown for this transaction(Up to 100 characters). ***THIS PARAMETER IS OPTIONAL***

		//dd($fromAccount,$toAccount);
		$PMtransfer = $PM->transferFund($fromAccount, $toAccount, $amount, $paymentID, $memo);
		//dd($PMtransfer);

		// https://perfectmoney.com/acct/confirm.asp?AccountID=myaccount&PassPhrase=mypassword&Payer_Account=U987654&Payee_Account=U1234567&Amount=1&PAY_IN=1&PAYMENT_ID=1223

		// An array of previously provided data will return for a valid and successful transaction. If any error happen, an array with one item with the key "ERROR" will return.
		// $currencyValue = '0.0001';
		if (array_key_exists("ERROR", $PMtransfer)) {

			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = $PMtransfer['ERROR'];
			// 'Payment not confirm. Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		} else {
			$req             = new Request;
			$req['usdvalue'] = $PMtransfer['PAYMENT_AMOUNT'];
			$convert         = $this->CurrencyConvertorController->usdTobtc($req);
			if ($convert->original['code'] == 200) {
				$currencyValue = $convert->original['data']['btc'];
			} else {
				$currencyValue = 0;
			}

			$Invoicedata['invoice_id']         = $PMtransfer['PAYMENT_BATCH_NUM'];
			$Invoicedata['hash_rate']          = $PMtransfer['PAYMENT_AMOUNT'];
			$Invoicedata['hash_unit']          = $PMtransfer['PAYMENT_AMOUNT'];
			$Invoicedata['price_unit']         = $PMtransfer['PAYMENT_AMOUNT'];
			$Invoicedata['price_in_usd']       = $PMtransfer['PAYMENT_AMOUNT'];
			$Invoicedata['payee_account_name'] = $PMtransfer['Payee_Account_Name'];
			$Invoicedata['payee_account']      = $PMtransfer['Payee_Account'];
			$Invoicedata['payer_account']      = $PMtransfer['Payer_Account'];
			$Invoicedata['payment_id']         = custom_round($PMtransfer['PAYMENT_ID'], 7);
			$Invoicedata['id']                 = Auth::user()->id;
			$Invoicedata['currency_price']     = custom_round($currencyValue, 7);
			$Invoicedata['payment_mode']       = 'PM';
			$Invoicedata['address']            = '';
			$Invoicedata['plan_id']            = '1';
			$Invoicedata['entry_time']         = \Carbon\Carbon::now();
			$Invoicedata['product_url']        = 'system';
			$Invoicedata['in_status']          = 1;

			$insertAdd  = Invoice::create($Invoicedata);
			$dash       = Dashboard::where('id', '=', Auth::user()->id)->first();
			$update     = Dashboard::where('id', '=', Auth::user()->id)->limit(1)->update(['top_up_wallet' => $dash->top_up_wallet+$request->amount]);
			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Payment Success';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}

		return json_encode($PMtransfer);
	}

	/**
	 * get the balance for the wallet or a specific account inside a wallet
	 *
	 */

	public function create_pm_transaction(Request $request) {

		$rules = array(
			'amount' => 'required|numeric',
		);
		$validator = checkvalidation($request->all(), $rules, '');
		if (!empty($validator)) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, []);
		}
		try {

			//check cross browser (check_user_authentication_browser)
			$req_temp_info = $request->header('User-Agent');
			$result        = check_user_authentication_browser($req_temp_info, Auth::user()->temp_info);
			if ($result == false) {
				return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]
					['status'], 'Invalid User Token!!!', '');
			}

			$projectSettings = ProjectSettings::where('status', 1)
				->select('add_fund_status', 'add_fund_msg')	->first();
			if ($projectSettings->add_fund_status == "off") {
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = $projectSettings->add_fund_msg;
				;
				return sendResponse($arrStatus, $arrCode, $arrMessage, array());
			} else {
				$getMember     = PerfectMoneyMember::select('receiver')->first();
				$payee_account = $getMember->receiver;

				$paymentID          = time();
				$amount             = $request->amount;
				$payee_account_name = Config::get('constants.settings.projectname');

				$Invoicedata['invoice_id']         = $paymentID;
				$Invoicedata['hash_rate']          = $amount;
				$Invoicedata['hash_unit']          = $amount;
				$Invoicedata['price_unit']         = $amount;
				$Invoicedata['price_in_usd']       = $amount;
				$Invoicedata['payee_account_name'] = $payee_account_name;
				$Invoicedata['payee_account']      = $payee_account;
				$Invoicedata['payment_id']         = $paymentID;
				$Invoicedata['id']                 = Auth::user()->id;
				$Invoicedata['currency_price']     = $amount;
				$Invoicedata['payment_mode']       = 'PM';
				$Invoicedata['address']            = '';
				$Invoicedata['plan_id']            = '1';
				$Invoicedata['entry_time']         = \Carbon\Carbon::now();
				$Invoicedata['product_url']        = 'system';
				$Invoicedata['in_status']          = 0;

				$insertAdd = Invoice::create($Invoicedata);

				$trans_arr                         = array();
				$trans_arr['PAYEE_ACCOUNT']        = $payee_account;
				$trans_arr['PAYEE_NAME']           = $payee_account_name;
				$trans_arr['PAYMENT_ID']           = $paymentID;
				$trans_arr['PAYMENT_AMOUNT']       = $amount;
				$trans_arr['PAYMENT_UNITS']        = 'USD';
				$trans_arr['PAYMENT_URL']          = Config::get('constants.settings.domainpath')."/user#/success-pm-transaction";
				$trans_arr['PAYMENT_URL_METHOD']   = "GET";
				$trans_arr['NOPAYMENT_URL']        = Config::get('constants.settings.domainpath')."/user#/failed-pm-transaction";
				$trans_arr['NOPAYMENT_URL_METHOD'] = "GET";
				$trans_arr['SUGGESTED_MEMO']       = Auth::user()->user_id." added fund of $ ".$amount;

				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Request added successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $trans_arr);
			}
		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong';
			return sendResponse($arrStatus, $arrCode, $arrMessage, array());
		}
	}
}
