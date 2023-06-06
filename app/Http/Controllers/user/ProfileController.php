<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Config;
use Exception;
use Illuminate\Http\Request;
use App\Models\collectusd;
use App\Models\Topup;
use App\Models\UserWithdrwalSetting;
use App\Models\Currency;
use Illuminate\Http\Response as Response;
use DB;
use Intervention\Image\Facades\Image;




class ProfileController extends Controller {

	/**
	 * Get User profile data
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getusddata(Request $request){
		$id = Auth::user()->id;
		$usddata = Topup::select('entry_time')->where('id' , $id)->first();
		$entry_time = $usddata->entry_time;
		$to = \Carbon\Carbon::now();
		// $from = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $entry_time);
		$diff = $to->diffInMinutes($entry_time);
		$arrData['minutes']= $diff;
		$dexd = $diff *0.001;
		$dexd_half = $diff *0.0005;
		//dd($diff, $dexd, $dexd_half);
		if(strtotime($entry_time) >= strtotime("2021-08-01 00:00:00"))
		{
			$arrData['usddata'] = custom_round($diff*0.00025);
		}else if(strtotime($entry_time) >= strtotime("2021-06-11 00:00:00"))
		{
			$arrData['usddata'] = custom_round($dexd_half);
		}
		else{
			$arrData['usddata'] =custom_round($dexd);
		}

		$arrStatus = Response::HTTP_OK;
		$arrCode = Response::$statusTexts[$arrStatus];
		$arrMessage = ' data found successfully';
		return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);

	}

	
	public function getprofileinfo(Request $request) {
		
		try {

			$id = Auth::user()->id;
			//--------------------------------check id exist-----------------------------
			$users = User::leftjoin('tbl_country_new', 'tbl_country_new.iso_code', '=', 'tbl_users.country')->where([['id', '=', $id], ['status', '=', 'Active']])->first();
			
			// $users = User::leftjoin('tbl_country_new', 'tbl_country_new.iso_code', '=', 'tbl_users.country')->where([['id', '=', $id]])->first();


			if (empty($users)) {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {

				$ref_user_id = $users->ref_user_id;
				if ($users->ref_user_id != '') {

					$getsponser = User::where([['id', '=', $users->ref_user_id], ['status', '=', 'Active']])->first();
					
					//$getsponser = User::where([['id','=',$users->ref_user_id]])->first();
					if (!empty($getsponser)) {
						$arrData['sponser'] = $getsponser->user_id;
						$arrData['sponser_fullname'] = $getsponser->fullname;
						$arrData['sponser_country'] = $getsponser->country;
					} else {

						$arrData['sponser'] = '';
						$arrData['sponser_fullname'] = '';
						$arrData['sponser_country'] = '';
					}
				}
				$arrData['doge_address'] = $users->doge_address;
            $arrData['ltc_address'] = $users->ltc_address;
            $arrData['sol_address'] = $users->sol_address;
            $arrData['usdt_trc20_address'] = $users->usdt_trc20_address;
            $arrData['usdt_erc20_address'] = $users->usdt_erc20_address;
				$arrData['btc_address'] = $users->btc_address;
				// $arrData['trn_address'] = $users->trn_address;
				$arrData['ethereum'] = $users->ethereum;
				$arrData['bnb_address'] = $users->bnb_address;

				$btcadd = UserWithdrwalSetting::where('id', $id)->where('currency', 'BTC')->select('currency_address')->first();
				$ethadd = UserWithdrwalSetting::where('id', $id)->where('currency', 'ETH')->select('currency_address')->first();
				$bnbadd = UserWithdrwalSetting::where('id', $id)->where('currency', 'BNB')->select('currency_address')->first();
				$trxadd = UserWithdrwalSetting::where('id', $id)->where('currency', 'TRX')->select('currency_address')->first();
				$usdtadd = UserWithdrwalSetting::where('id', $id)->where('currency', 'USDT-TRC20')->select('currency_address')->first();
				$usdt_erc_add = UserWithdrwalSetting::where('id', $id)->where('currency', 'USDT-ERC20')->select('currency_address')->first();
				$ltcadd = UserWithdrwalSetting::where('id', $id)->where('currency', 'LTC')->select('currency_address')->first();
				$soladd = UserWithdrwalSetting::where('id', $id)->where('currency', 'SOL')->select('currency_address')->first();
				$dogeadd = UserWithdrwalSetting::where('id', $id)->where('currency', 'DOGE')->select('currency_address')->first();
				
				
				if(empty($btcadd) || $btcadd == null  || $btcadd == "null" )	{
					$arrData['btc_address'] = '';
				}
				else {
					$arrData['btc_address'] = $btcadd->currency_address;
				}

				if(empty($trxadd) || $trxadd == null  || $trxadd == "null" )	{
					$arrData['trn_address'] = '';
				}
				else {
					$arrData['trn_address'] = $trxadd->currency_address;
				}
				if(empty($ethadd) || $ethadd == null  || $ethadd == "null" )	{
					$arrData['ethereum'] = '';
				}
				else {
					$arrData['ethereum'] = $ethadd->currency_address;
				}

				if(empty($bnbadd) || $bnbadd == null  || $bnbadd == "null" )	{
					$arrData['bnb_address'] = '';
				}
				else {
					$arrData['bnb_address'] = $bnbadd->currency_address;
				}

				if(empty($usdtadd) || $usdtadd == null  || $usdtadd == "null" )	{
					$arrData['usdt_trc20_address'] = '';
				}
				else {
					$arrData['usdt_trc20_address'] = $usdtadd->currency_address;
				}
				if(empty($usdt_erc_add) || $usdt_erc_add == null  || $usdt_erc_add == "null" )	{
					$arrData['usdt_erc20_address'] = '';
				}
				else {
					$arrData['usdt_erc20_address'] = $usdt_erc_add->currency_address;
				}
				if(empty($ltcadd) || $ltcadd == null  || $ltcadd == "null" )	{
					$arrData['ltc_address'] = '';
				}
				else {
					$arrData['ltc_address'] = $ltcadd->currency_address;
				}

				if(empty($soladd) || $soladd == null  || $soladd == "null" )	{
					$arrData['sol_address'] = '';
				}
				else {
					$arrData['sol_address'] = $soladd->currency_address;
				}

				if(empty($dogeadd) || $dogeadd == null  || $dogeadd == "null" )	{
					$arrData['doge_address'] = '';
				}
				else {
					$arrData['doge_address'] = $dogeadd->currency_address;
				}
				

				

				$arrData['user_image'] = $users->user_image;
				$arrData['user_id'] = $users->user_id;
				$arrData['fullname'] = $users->fullname;
				$arrData['entry_time'] = $users->entry_time;
				$arrData['email'] = $users->email;
				$arrData['country'] = $users->iso_code;
				$arrData['mobile'] = $users->mobile;
				$arrData['address'] = $users->address;
				$arrData['ref_user_id'] = $ref_user_id;
				$arrData['account_no'] = $users->account_no;
				$arrData['holder_name'] = $users->holder_name;
				$arrData['pan_no'] = $users->pan_no;
				$arrData['bank_name'] = $users->bank_name;
				$arrData['ifsc_code'] = $users->ifsc_code;
				$arrData['branch_name'] = $users->branch_name;
				$arrData['city'] = $users->city;
				$arrData['code'] = $users->code;
				$arrData['is_franchise'] = $users->is_franchise;
				$arrData['facebook_link'] = $users->facebook_link;
				$arrData['twitter_link'] = $users->twitter_link;
				$arrData['linkedin_link'] = $users->linkedin_link;
				$arrData['instagram_link'] = $users->instagram_link;
				$arrData['perfect_money_address'] = $users->perfect_money_address;
				$arrData['paypal_address'] = $users->paypal_address;
				$arrData['topup_status'] = $users->topup_status;
				$arrData['user_profile'] = $users->user_profile;            
				$google2fa = app('pragmarx.google2fa');
				


				// $withdrawal_currency = Currency::where('tbl_currency.withdrwal_status','1')->get();
				//  // dd($withdrawal_currency);
				// foreach ($withdrawal_currency as $key) {
				// 	$curr_address = UserWithdrwalSetting::where([['id',$id], ['currency',$key['currency_code']],['status',1]])->pluck('currency_address')->first();
				// 	if(!empty($curr_address)){
				// 		$arrData[''.str_replace(".","_",strtolower($key['currency_code'])).'_address'] = $curr_address;
				// 	}else{
				// 		$arrData[''.str_replace(".","_",strtolower($key['currency_code'])).'_address'] = "";
				// 	}
				// 	// dd($curr_address);
				// }

				$secret = $google2fa->generateSecretKey();
				
		
				$QR_Image = $google2fa->getQRCodeInline(
					config('app.name'),
					$users->user_id,
					$secret
				);
				
				
				 $QR_Imagearr = explode("<svg", $QR_Image);
				 $QR_Image = "<svg".$QR_Imagearr[1];

				 $QR_Imagearr1 = explode("</svg>", $QR_Image);
				 $QR_Image = $QR_Imagearr1[0]."</svg>";
				
				//dd($QR_Image);
				//$png = Image::make($QR_Image)->encode('png');
				
				$arrData['QR_Image'] = $QR_Image;
				$arrData['secret'] = $secret;
				
				$arrData['google2fa_status'] = $users->google2fa_status;
				$arrData['image'] = $users->image;
				$arrStatus = Response::HTTP_OK;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'User profile data found successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
			}
		} catch (Exception $e) {
			dd($e);
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

		/**
	 * Get User profile data
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getuserrank(Request $request){
		$id = Auth::user()->id;
		$userrank = User::select('rank')->where('id', $id)->first();

		if(!empty($userrank->rank))
		{
			$arrData['rank'] =$userrank->rank;
		}
		else{
			$arrData['rank'] = 0;
		}
		$arrStatus = Response::HTTP_OK;
		$arrCode = Response::$statusTexts[$arrStatus];
		$arrMessage = ' data found successfully';
		return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);

	}
	public function checkUserCountryAvoided(){	
		$usercountry = Auth::User()->country;
		$country =DB::table('tbl_country_new')->where([['iso_code','=',$usercountry],['avoid_con',1]])->first();
        if($country!= null){

           $arrStatus   = Response::HTTP_OK;
           $arrCode     = Response::$statusTexts[$arrStatus];
           $arrMessage  = '123';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }else{
        	$arrStatus   = Response::HTTP_NOT_FOUND;
        	$arrCode     = Response::$statusTexts[$arrStatus];
        	$arrMessage  = '';
           	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
	}
}