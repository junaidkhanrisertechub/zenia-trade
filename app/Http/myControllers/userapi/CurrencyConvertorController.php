<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Currencyrate;
use App\User;
use Config;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;

class CurrencyConvertorController extends Controller {

	public function __construct() {
		$this->linkexpire = Config::get('constants.settings.linkexpire');
		$this->statuscode = Config::get('constants.statuscode');
		$this->emptyArray = (object) array();
	}

	/**
	 * Get all currency
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function getAllwitrhdrawCurrency(Request $request) {
		try {

			// if (!empty($request->withdrwal_status)) {

				$arrCurrency = Currency::where([['withdrwal_status', '=', '1']])->get();

			// } else {

			// 	$arrCurrency = Currency::where([['status', '=', '1']])->get();
			// }
			if (count($arrCurrency) > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Currency data found successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrCurrency);
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Currency data found successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function getAllCurrency(Request $request) {
		try {

			if (!empty($request->withdrwal_status)) {

				$arrCurrency = Currency::where([['withdrwal_status', '=', '1']])->get();

			} else {

				$arrCurrency = Currency::where([['status', '=', '1']])->get();
			}
			if (count($arrCurrency) > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Currency data found successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrCurrency);
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Currency data found successfully';
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
	 * btc to usd
	 *
	 * @return \Illuminate\Http\Response
	 */

	// public function btcTousd(Request $request) {
	//     if (!empty($request->input('btcvalue'))) {

	//         $price_in_btc = $request->input('btcvalue');
	//         $url = "https://api.coinmarketcap.com/v1/ticker/?convert=USD";

	//         $ch = curl_init();
	//         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//         curl_setopt($ch, CURLOPT_URL, $url);
	//         $all_data = curl_exec($ch);

	//         $json_feed = json_decode($all_data, true);
	//         dd($json_feed);

	//         $btc_to_usd = $json_feed['0']['price_usd'];

	//         //$usdvalue['usd'] = $btc_to_usd * $price_in_btc;
	//         $usdvalue['usd'] = custom_round($btc_to_usd * $price_in_btc, 7);

	//         return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'USD value get successfully', $usdvalue);
	//     } else {

	//         return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'btcvalue should not be null', $this->emptyArray);
	//     }
	// }

	public function btcTousd(Request $request) {
		if (!empty($request->input('btcvalue'))) {
			$p1           = 'BTC';
			$p2           = 'USD';
			$price_in_btc = $request->input('btcvalue');
			// $url = "https://api.coinmarketcap.com/v1/ticker/?convert=USD";
			$url = "https://min-api.cryptocompare.com/data/price?fsym=".$p1."&tsyms=".$p2;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			$all_data = curl_exec($ch);

			$json_feed = json_decode($all_data, true);
			// dd($json_feed);
			// $btc_to_usd = $json_feed['0']['price_usd'];
			$btc_to_usd = $json_feed;
			// dd($btc_to_usd);
			//$usdvalue['usd'] = $btc_to_usd * $price_in_btc;
			$usdvalue['usd'] = custom_round($btc_to_usd['USD']*$price_in_btc, 7);

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'USD value get successfully', $usdvalue);
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'btcvalue should not be null', $this->emptyArray);
		}
	}

	/**
	 * usd to btc
	 *
	 * @return \Illuminate\Http\Response
	 */

	function usdTobtc(Request $request) {

		if (!empty($request->input('usdvalue'))) {

			$price_in_usd = $request->input('usdvalue');
			$url          = "https://api.coinbase.com/v2/prices/spot?currency=USD";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			$all_data = curl_exec($ch);

			$json_feed = json_decode($all_data, true);
			$btc_rate  = $json_feed['data']['amount'];
			if (!empty($btc_rate)) {
				$price_in_btc    = number_format($price_in_usd/$btc_rate, 6);
				$btcvalue['btc'] = custom_round($price_in_btc, 7);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'BTC value get successfully', $btcvalue);
			} else {

				$request->request->add(['btcvalue' => 1]);
				$usdvalue = $this->btc_to_usd($request);
				if ($usdvalue->original['status'] == 'OK') {
					$usd_value = custom_round(json_encode($usdvalue->original['data']['usd']), 7);
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong ,Please try again', $this->emptyArray);
				}
				$price_in_btc    = number_format($price_in_usd/$usd_value, 6);
				$btcvalue['btc'] = custom_round($price_in_btc, 7);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'BTC value get successfully', $btcvalue);
			}
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'usdvalue should not be null', $this->emptyArray);
		}
	}

	/**
	 * price per erv
	 *
	 * @return \Illuminate\Http\Response
	 */

	function pricepercoin(Request $request) {

		$remember_token   = trim($request->input('remember_token'));
		$check_useractive = User::where([['remember_token', '=', $remember_token],
				['status', '=', 'Active']])->first();
		if (!empty($check_useractive)) {

			$getCurrencyDetails = Currencyrate::where([['status', '=', '1']])->first();
			$price_in_usd       = $getCurrencyDetails->usd;

			$url = "https://api.coinbase.com/v2/prices/spot?currency=USD";
			$ch  = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			$all_data = curl_exec($ch);

			$json_feed                     = json_decode($all_data, true);
			$btc_rate                      = $json_feed['data']['amount'];
			$price_in_btc                  = number_format($price_in_usd/$btc_rate, 6);
			$priceperearv['price_per_erv'] = custom_round($price_in_btc, 7);
			$priceperearv['Fee']           = $getCurrencyDetails->transaction_fee;
			$priceperearv['bonus']         = $getCurrencyDetails->bonus;
			if ($check_useractive->google2fa_status == 'enable') {

				$priceperearv['google2faauth'] = 'TRUE';
			} else {
				$priceperearv['google2faauth'] = 'FALSE';
			}

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Price per ERV get successfully', $priceperearv);
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User is not active ', $this->emptyArray);
		}
	}

	/**
	 * btc to usd
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function btc_to_usd(Request $request) {
		if (!empty($request->input('btcvalue'))) {

			$price_in_btc = $request->input('btcvalue');

			$url = "https://blockchain.info/ticker";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			$all_data = curl_exec($ch);

			$json_feed = json_decode($all_data, true);
			if (!empty($json_feed)) {

				$btc_to_usd = $json_feed['USD']['last'];

				$usdvalue['usd'] = custom_round($btc_to_usd*$price_in_btc, 7);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'USD value get successfully', $usdvalue);
			}//if not empty json feed
			 elseif (empty($json_feed)) {
				$url = "https://api.coindesk.com/v1/bpi/currentprice.json";

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, $url);
				$all_data = curl_exec($ch);

				$json_feed = json_decode($all_data, true);
				//dd($json_feed['bpi']['USD']);
				if (!empty($json_feed)) {

					$btc_to_usd = floor($json_feed['bpi']['USD']['rate']);

					$usdvalue['usd'] = custom_round($btc_to_usd*$price_in_btc, 7);

					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'USD value get successfully', $usdvalue);
				}//elseif empty json feed
				 elseif (empty($json_feed)) {
					$url = "https://www.bitstamp.net/api/v2/ticker/btcusd";

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_URL, $url);
					$all_data = curl_exec($ch);

					$json_feed = json_decode($all_data, true);
					//dd($json_feed);
					if (!empty($json_feed)) {

						$btc_to_usd = $json_feed['last'];

						//$usdvalue['usd'] = $btc_to_usd * $price_in_btc;
						$usdvalue['usd'] = custom_round($btc_to_usd*$price_in_btc, 7);

						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'USD value get successfully', $usdvalue);
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'something went wrong', $this->emptyArray);
					}
				}
			}
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'btcvalue should not be null', $this->emptyArray);
		}
	}

	/**
	 * usd to btc block
	 *
	 * @return \Illuminate\Http\Response
	 */
	function usdTobtcBlock($usdvalue) {

		if (!empty($usdvalue)) {

			$price_in_usd = $usdvalue;
			$url          = "https://api.coinbase.com/v2/prices/spot?currency=USD";

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			$all_data = curl_exec($ch);

			$json_feed    = json_decode($all_data, true);
			$btc_rate     = $json_feed['data']['amount'];
			$price_in_btc = number_format($price_in_usd/$btc_rate, 6);
			if (!empty($btc_rate)) {
				$price_in_btc    = number_format($price_in_usd/$btc_rate, 6);
				$btcvalue['btc'] = custom_round($price_in_btc, 7);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'BTC value get successfully', $btcvalue);
			} else {

				$request->request->add(['btcvalue' => 1]);
				$usdvalue = $this->btc_to_usd($request);
				if ($usdvalue->original['status'] == 'OK') {
					$usd_value = custom_round(json_encode($usdvalue->original['data']['usd']), 7);
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong ,Please try again', $this->emptyArray);
				}
				$price_in_btc    = number_format($price_in_usd/$usd_value, 6);
				$btcvalue['btc'] = custom_round($price_in_btc, 7);

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'BTC value get successfully', $btcvalue);
			}
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'usdvalue should not be null', $this->emptyArray);
		}
	}

	/**
	 * usd to eth
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function usdToEth() {

		$url = "https://min-api.cryptocompare.com/data/price?fsym=USD&tsyms=ETH";
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$currency_rate = curl_exec($ch);
		$json          = json_decode($currency_rate, true);
		$ethvalue      = $json;

		if (count($ethvalue) > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Eth value get successfully', $ethvalue);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'something went wrong with eth value', '');
		}
	}

	/**
	 * eth to usd     *
	 * @return \Illuminate\Http\Response
	 */
	public function Ethtousd() {

		$url = "https://min-api.cryptocompare.com/data/price?fsym=ETH&tsyms=USD";
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$currency_rate = curl_exec($ch);
		$json          = json_decode($currency_rate, true);
		$usdvalue      = $json;

		if (count($usdvalue) > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Eth value get successfully', $usdvalue);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong with usd value from eth', '');
		}
	}

	/**
	 * Currency convertor function    *
	 * @return \Illuminate\Http\Response
	 */
	public function currenyConverter(Request $request) {
		$rules = array(
			'usd' => 'required|regex:/^\d*(\.\d{1,4})?$/',
		);

		$validator = checkvalidation($request->all(), $rules, '');
		if (!empty($validator)) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
		}

		if ($request->usd <= 0) {
			$arr = ['USD' => 0, 'BTC' => 0, 'ETH' => 0];
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arr);
		}
		$usd = $request->usd;

		$ethvalue  = $this->usdToEth();
		$eth_value = $ethvalue->original['data']['ETH'];
		$eth       = custom_round($usd*$eth_value, 7);

		$request->request->add(['usdvalue' => $usd]);
		$btcvalue = $this->usdTobtc($request);// btc value of current rate
		if ($btcvalue->original['status'] == 'OK') {
			$btc_value = custom_round(json_encode($btcvalue->original['data']['btc']), 7);
		}

		$arr = ['USD' => $usd, 'BTC' => custom_round($btc_value, 7), 'ETH' => $eth];
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arr);
	}

	public function getAllActivationCurrency(Request $request) {
		try {

			if (!empty($request->withdrwal_status)) {

				$arrCurrency = Currency::where([['withdrwal_status', '=', '1']])->get();
			} else {

				$arrCurrency = Currency::where([['activation_status', '=', '1']])->get();
			}
			if (count($arrCurrency) > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Currency data found successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrCurrency);
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Currency data found successfully';
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
