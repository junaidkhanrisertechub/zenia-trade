<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\CurrencyConvertorController;

use Config;

use Illuminate\Http\Request;

//=================generate address===================================

class CryptocurrencyController extends Controller {

	public function __construct(CurrencyConvertorController $currency) {

		$this->statuscode = Config::get('constants.statuscode');
		$this->currency   = $currency;
	}

	//===========================================================
	public function getCryptoCurrency() {

		$url = "https://api.coinmarketcap.com/v1/ticker/";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$getDtata = curl_exec($ch);
		$getDtata = json_decode($getDtata, true);
		if (!empty($getDtata)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Currency data found successfully', $getDtata);
		} else {

			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Something went wrong,Please try again', $getDtata);
		}
	}

	//===========================================================
	public function CryptoExchange(Request $request) {

		$rules = array(
			'currency' => 'required|',
		);

		$validator = checkvalidation($request->all(), $rules, '');
		if (!empty($validator)) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
		}

		$arrData1 = array();
		$arrData2 = array();
		$arrData3 = array();
		$arrData4 = array();
		$arrData5 = array();
		$arrData6 = array();
		$arrData7 = array();
		$arrData8 = array();
		$arrData9 = array();

		//=============yobit==================================
		$Yogdata = $this->getCurrencyData("https://yobit.net/api/3/ticker/".strtolower($request->input('currency'))."_usd");

		if ($Yogdata['msg'] == 'success') {
			$yogArr            = array();
			$yogArr['high']    = $Yogdata['data'][strtolower($request->input('currency'))."_usd"]['high'];
			$yogArr['low']     = $Yogdata['data'][strtolower($request->input('currency'))."_usd"]['low'];
			$yogArr['avg']     = $Yogdata['data'][strtolower($request->input('currency'))."_usd"]['avg'];
			$yogArr['last']    = $Yogdata['data'][strtolower($request->input('currency'))."_usd"]['last'];
			$yogArr['volume']  = $Yogdata['data'][strtolower($request->input('currency'))."_usd"]['vol'];
			$yogArr['updated'] = 'Not Available';
			$arrData1['Yobit'] = $yogArr;
		} else if ($Yogdata['msg'] == 'failed') {

			$yogArr['high']    = 'Not Available';
			$yogArr['low']     = 'Not Available';
			$yogArr['avg']     = 'Not Available';
			$yogArr['updated'] = 'Not Available';
			$yogArr['last']    = 'Not Available';
			$yogArr['volume']  = 'Not Available';
			$arrData1['Yobit'] = $yogArr;
		}
		//====================hitbtc=======================================
		$HitbtcData = $this->getCurrencyData("https://api.hitbtc.com/api/2/public/ticker/".$request->input('currency')."USD");

		if ($HitbtcData['msg'] == 'success') {
			$hitArr             = array();
			$hitArr['high']     = $HitbtcData['data']['high'];
			$hitArr['low']      = $HitbtcData['data']['low'];
			$hitArr['avg']      = ($HitbtcData['data']['high']+$HitbtcData['data']['low']/2);
			$hitArr['updated']  = $HitbtcData['data']['timestamp'];
			$hitArr['last']     = $HitbtcData['data']['last'];
			$hitArr['volume']   = $HitbtcData['data']['volume'];
			$arrData2['HitBTC'] = $hitArr;
		} else if ($HitbtcData['msg'] == 'failed') {
			$hitArr['high']     = 'Not Available';
			$hitArr['low']      = 'Not Available';
			$hitArr['avg']      = 'Not Available';
			$hitArr['updated']  = 'Not Available';
			$hitArr['last']     = 'Not Available';
			$hitArr['volume']   = 'Not Available';
			$arrData2['HitBTC'] = $hitArr;
		}

		//=============cryptopia========================================
		$CryptopiaData = $this->getCurrencyData("https://www.cryptopia.co.nz/api/GetMarket/".$request->input('currency')."_btc");

		if ($CryptopiaData['msg'] == 'success') {
			$CryptArr = array();

			$request->request->add(['btcvalue' => $CryptopiaData['data']['Data']['High']]);
			$usdhighvalue = $this->currency->btc_to_usd($request);// btc value of current rate
			$request->request->add(['btcvalue' => $CryptopiaData['data']['Data']['Low']]);
			$usdlowvalue = $this->currency->btc_to_usd($request);// btc value of current rate
			if ($usdhighvalue->original['status'] == 'OK') {
				$CryptArr['high'] = custom_round(json_encode($usdhighvalue->original['data']['usd']), 7);
			}
			if ($usdlowvalue->original['status'] == 'OK') {
				$CryptArr['low'] = custom_round(json_encode($usdlowvalue->original['data']['usd']), 7);
			}
			if ($usdhighvalue->original['status'] == 'OK' && $usdlowvalue->original['status'] == 'OK') {
				$CryptArr['avg'] = ($CryptArr['high']+$CryptArr['low']/2);
			}

			$request->request->add(['btcvalue' => $CryptopiaData['data']['Data']['LastPrice']]);
			$lastvalue = $this->currency->btc_to_usd($request);// btc value of current rate

			if ($lastvalue->original['status'] == 'OK' && $lastvalue->original['status'] == 'OK') {
				$CryptArr['last'] = custom_round(json_encode($lastvalue->original['data']['usd']), 7);
			}

			$CryptArr['volume'] = $CryptopiaData['data']['Data']['Volume'];

			$CryptArr['updated']   = 'Not Available';
			$arrData3['Cryptopia'] = $CryptArr;
		} else if ($CryptopiaData['msg'] == 'failed') {

			$CryptArr['high']      = 'Not Available';
			$CryptArr['low']       = 'Not Available';
			$CryptArr['avg']       = 'Not Available';
			$CryptArr['updated']   = 'Not Available';
			$CryptArr['last']      = 'Not Available';
			$CryptArr['volume']    = 'Not Available';
			$arrData3['Cryptopia'] = $CryptArr;
		}

		//=============poloniex===============================
		$PoloniexArr            = array();
		$PoloniexArr['high']    = 'Not Available';
		$PoloniexArr['low']     = 'Not Available';
		$PoloniexArr['avg']     = 'Not Available';
		$PoloniexArr['updated'] = 'Not Available';
		$PoloniexArr['last']    = 'Not Available';
		$PoloniexArr['volume']  = 'Not Available';
		$arrData4['Poloniex']   = $PoloniexArr;

		//============coinone==============
		$Coinone = $this->getCurrencyData("https://api.coinone.co.kr/ticker/?currency=".$request->input('currency'));

		if ($Coinone['msg'] == 'success' && !array_key_exists('errorCode', $Coinone['data'])) {
			$coinArr           = array();
			$coinArr['high']   = $Coinone['data']['high'];
			$coinArr['low']    = $Coinone['data']['low'];
			$coinArr['avg']    = ($coinArr['high']+$coinArr['low']/2);
			$coinArr['last']   = $Coinone['data']['last'];
			$coinArr['volume'] = $Coinone['data']['volume'];

			$coinArr['updated']  = 'Not Available';
			$arrData5['Coinone'] = $coinArr;
		} else if ($Coinone['msg'] == 'failed') {

			$coinArr['high']     = 'Not Available';
			$coinArr['low']      = 'Not Available';
			$coinArr['avg']      = 'Not Available';
			$coinArr['updated']  = 'Not Available';
			$coinArr['last']     = 'Not Available';
			$coinArr['volume']   = 'Not Available';
			$arrData5['Coinone'] = $coinArr;
		}

		//============CEX==============

		$Cex = $this->getCurrencyData("https://cex.io/api/ticker/".strtoupper($request->input('currency'))."/USD");

		if ($Cex['msg'] == 'success') {
			$CexArr           = array();
			$CexArr['high']   = $Cex['data']['high'];
			$CexArr['low']    = $Cex['data']['low'];
			$CexArr['avg']    = ($CexArr['high']+$CexArr['low']/2);
			$CexArr['last']   = $Cex['data']['last'];
			$CexArr['volume'] = $Cex['data']['volume'];

			$CexArr['updated'] = 'Not Available';
			$arrData6['Cex']   = $CexArr;
		} else if ($Cex['msg'] == 'failed') {

			$CexArr['high']    = 'Not Available';
			$CexArr['low']     = 'Not Available';
			$CexArr['avg']     = 'Not Available';
			$CexArr['updated'] = 'Not Available';
			$CexArr['last']    = 'Not Available';
			$CexArr['volume']  = 'Not Available';
			$CexArr['updated'] = 'Not Available';
			$arrData6['Cex']   = $CexArr;
		}

		//============Vebitcoin==============

		$Vebitcoin = $this->getCurrencyData("https://www.vebitcoin.com/Ticker/".$request->input('currency'));
		if ($Vebitcoin['msg'] == 'success') {
			$VebitcoinArr           = array();
			$VebitcoinArr['high']   = $Vebitcoin['data']['High'];
			$VebitcoinArr['low']    = $Vebitcoin['data']['Low'];
			$VebitcoinArr['avg']    = ($VebitcoinArr['high']+$VebitcoinArr['low']/2);
			$VebitcoinArr['last']   = $Vebitcoin['data']['Last'];
			$VebitcoinArr['volume'] = $Vebitcoin['data']['Volume'];

			$VebitcoinArr['updated'] = $Vebitcoin['data']['Time'];
			$arrData7['Vebitcoin']   = $VebitcoinArr;
		} else if ($Vebitcoin['msg'] == 'failed') {

			$VebitcoinArr['high']    = 'Not Available';
			$VebitcoinArr['low']     = 'Not Available';
			$VebitcoinArr['avg']     = 'Not Available';
			$VebitcoinArr['updated'] = 'Not Available';
			$VebitcoinArr['last']    = 'Not Available';
			$VebitcoinArr['volume']  = 'Not Available';
			$VebitcoinArr['updated'] = 'Not Available';
			$arrData7['Vebitcoin']   = $VebitcoinArr;
		}

		//============Buyucoin==============

		$Buyucoin = $this->getCurrencyData("https://www.buyucoin.com/api/v1.2/currency/".strtolower($request->input('currency')));

		if ($Buyucoin['msg'] == 'success' && !empty($Buyucoin['data']['data'])) {
			$BuyucoinArr           = array();
			$BuyucoinArr['high']   = $Buyucoin['data']['data']['high_24'];
			$BuyucoinArr['low']    = $Buyucoin['data']['data']['low_24'];
			$BuyucoinArr['avg']    = ($BuyucoinArr['high']+$BuyucoinArr['low']/2);
			$BuyucoinArr['last']   = $Buyucoin['data']['data']['last_trade'];
			$BuyucoinArr['volume'] = $Buyucoin['data']['data']['vol'];

			$BuyucoinArr['updated'] = 'Not Available';
			$arrData8['Buyucoin']   = $BuyucoinArr;
		} else if ($Buyucoin['msg'] == 'success' && empty($Buyucoin['data']['data'])) {

			$BuyucoinArr['high']    = 'Not Available';
			$BuyucoinArr['low']     = 'Not Available';
			$BuyucoinArr['avg']     = 'Not Available';
			$BuyucoinArr['updated'] = 'Not Available';
			$BuyucoinArr['last']    = 'Not Available';
			$BuyucoinArr['volume']  = 'Not Available';
			$BuyucoinArr['updated'] = 'Not Available';
			$arrData8['Buyucoin']   = $BuyucoinArr;
		} else if ($Buyucoin['msg'] == 'failed') {

			$BuyucoinArr['high']    = 'Not Available';
			$BuyucoinArr['low']     = 'Not Available';
			$BuyucoinArr['avg']     = 'Not Available';
			$BuyucoinArr['updated'] = 'Not Available';
			$BuyucoinArr['last']    = 'Not Available';
			$BuyucoinArr['volume']  = 'Not Available';
			$BuyucoinArr['updated'] = 'Not Available';
			$arrData8['Buyucoin']   = $BuyucoinArr;
		}

		//============Cryptonator==============

		$Cryptonator = $this->getCurrencyData("https://api.cryptonator.com/api/ticker/".$request->input('currency')."-usd");
		if ($Cryptonator['msg'] == 'success') {
			$CryptonatorArr           = array();
			$CryptonatorArr['high']   = 'Not Available';
			$CryptonatorArr['low']    = 'Not Available';
			$CryptonatorArr['avg']    = $Cryptonator['data']['ticker']['price'];
			$CryptonatorArr['last']   = 'Not Available';
			$CryptonatorArr['volume'] = $Cryptonator['data']['ticker']['volume'];

			$CryptonatorArr['updated'] = 'Not Available';
			$arrData9['Cryptonator']   = $CryptonatorArr;
		} else if ($Cryptonator['msg'] == 'failed') {

			$CryptonatorArr['high']    = 'Not Available';
			$CryptonatorArr['low']     = 'Not Available';
			$CryptonatorArr['avg']     = 'Not Available';
			$CryptonatorArr['updated'] = 'Not Available';
			$CryptonatorArr['last']    = 'Not Available';
			$CryptonatorArr['volume']  = 'Not Available';
			$CryptonatorArr['updated'] = 'Not Available';
			$arrData9['Cryptonator']   = $CryptonatorArr;
		}

		if (!empty($arrData1) && !empty($arrData2) && !empty($arrData3)) {
			$firstArray   = array_merge($arrData1, $arrData2);
			$secondArray  = array_merge($firstArray, $arrData3);
			$thirdArray   = array_merge($secondArray, $arrData4);
			$fourArray    = array_merge($thirdArray, $arrData5);
			$fifthArray   = array_merge($fourArray, $arrData6);
			$sixthArray   = array_merge($fifthArray, $arrData7);
			$seventhArray = array_merge($sixthArray, $arrData8);
			$eightArray   = array_merge($seventhArray, $arrData9);

			if (!empty($eightArray)) {

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'USD value get successfully', $eightArray);
			} else {

				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
			}
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
		}
	}

	//============================
	public function getCurrencyData($url) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$getDtata = curl_exec($ch);
		$getDtata = json_decode($getDtata, true);
		$arr      = array();
		if (!empty($getDtata)) {
			if (array_key_exists('error', $getDtata) && $getDtata['error']) {
				$arr['data'] = $getDtata;
				$arr['msg']  = 'failed';
			} else if (array_key_exists('Error', $getDtata) && $getDtata['Error']) {
				$arr['data'] = $getDtata;
				$arr['msg']  = 'failed';
			} else {

				$arr['data'] = $getDtata;
				$arr['msg']  = 'success';
			}
		} else {

			$arr['data'] = '';
			$arr['msg']  = 'failed';
		}
		return $arr;
	}

}
