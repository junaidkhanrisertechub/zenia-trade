<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\SettingsController;
use App\Models\Activitynotification;
use App\Models\AllTransaction;
use App\Models\DailyBonus;
use App\Models\Dashboard;
use App\Models\Packages;
use App\Models\Rank;

use App\Models\supermatching;
use App\Models\SupperMatchingIncome;
use App\Models\Topup;
use App\Traits\Income;

use DB;
use Exception;

use Illuminate\Http\Response as Response;

class GenerateRoiController extends Controller {
	use Income;
	public function __construct(SettingsController $projectsetting) {

		$this->projectsettings = $projectsetting;
		$date                  = \Carbon\Carbon::now();
		$this->today           = $date->toDateTimeString();
		$this->emptyArray      = (object) array();
	}

	/**
	 * Generate roi cron
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function generateroi($invest_amount, $id, $pin, $type, $entry_time, $email, $user_id, $country, $mobile, $withdraw, $old_status, $rank) {

		try {

			$original_entry_time = $entry_time;

			$lastDateExist = DailyBonus::select('entry_time')->where([['pin', '=', $pin]])->orderBy('entry_time', 'desc')->first();
			if (!empty($lastDateExist)) {
				$entry_time = $lastDateExist->entry_time;
			}

			$date    = \Carbon\Carbon::parse($entry_time);
			$now     = \Carbon\Carbon::now();
			$diff    = $date->diffInDays($now);
			$getDate = $now->toDateString();

			$packageExist = Packages::where([['id', '=', $type]])->limit(1)->first();

			$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + '.$packageExist->date_diff.'days'));

			$checkWeekend = $now->isWeekend();

			$DailyBounsExist = DailyBonus::select('entry_time')->where([[DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"), '=', $nextEntrydate], ['pin', '=', $pin]])->first();

			if (empty($DailyBounsExist && $diff >= $packageExist->date_diff) && strtotime($nextEntrydate) <= strtotime($getDate)) {

				// check user invetment is greater than 0

				if ($invest_amount > 0) {

					if (!empty($packageExist)) {

						$ProductRoi = $packageExist->roi;

						// $ProductRoi=0;

						// $TopupNo=Topup::where([['entry_time','<=',$original_entry_time],['id','=',$id]])->count();

						// $TopupNo=Topup::where('entry_time','<=',$original_entry_time)->where('id',$id)->count();

						// dd($pin,$original_entry_time,$id,$TopupNo);
						/*
						if($TopupNo == 1)
						{
						$ProductRoi = 0.35;
						}
						else if($TopupNo == 2)
						{
						$ProductRoi = 0.5;
						}
						else if($TopupNo == 3)
						{
						$ProductRoi = 0.75;
						}
						else if($TopupNo == 4)
						{
						$ProductRoi = 1;
						}
						else if($TopupNo >= 5)
						{
						$ProductRoi = 1.25;
						} */

						// calculate product percentage
						$daily_intrest = (($invest_amount*$ProductRoi)/100);// value in usd
						// total roi per day
						$Total_ROI = $daily_intrest;
						// $Total_ROI=$software_intrest+$daily_intrest;

						$userDash = Dashboard::select('usd', 'roi_income', 'total_profit', 'level_income', 'roi_income_withdraw', 'working_wallet', 'dex_wallet')->where([['id', '=', $id], ])->first();

						$user_usd   = $userDash->usd;
						$roi_income = $userDash->roi_income;

						$total_profit = $userDash->total_profit;
						$level_income = $userDash->level_income;

						$roi_income_withdraw = $userDash->roi_income_withdraw;

						$working_wallet = $userDash->working_wallet;

						/*$user_usd = Dashboard::where([['id', '=', $id],])->pluck('usd')->first();
						$roi_income = Dashboard::where([['id', '=', $id],])->pluck('roi_income')->first();

						$total_profit = Dashboard::where([['id', '=', $id],])->pluck('total_profit')->first();
						$level_income = Dashboard::where([['id', '=', $id],])->pluck('level_income')->first();

						$roi_income_withdraw = Dashboard::where([['id', '=', $id],])->pluck('roi_income_withdraw')->first();

						$working_wallet = Dashboard::where([['id', '=', $id],])->pluck('working_wallet')->first();*/

						$remarks = "ROI profit: ".$Total_ROI." Deposit ID: ".$pin." interest ".$ProductRoi."% amount ".$daily_intrest.")";

						//updat daily bonus
						//  $countPin = DailyBonus::where('pin', '=', $pin)->sum('daily_percentage');
						$countPin   = DailyBonus::where('pin', '=', $pin)->count();
						$amount_got = DailyBonus::where('pin', '=', $pin)->sum('amount');

						$amount_got = $amount_got+$withdraw;

						$amount_to_give = $invest_amount*2;

						//# $checkEntryExist = DailyBonus::where('pin', '=', $pin)->orderBy('entry_time', 'desc')->limit(1)->first();

						$Productduration = $packageExist->duration;
						if ($countPin < $Productduration) {

							// if ($amount_got < $amount_to_give) {

							/* if (!empty($checkEntryExist)) {

							$nextEntrydate = date('Y-m-d', strtotime($checkEntryExist->entry_time . ' + ' . $packageExist->date_diff . 'days'));
							} else {
							$nextEntrydate = date('Y-m-d', strtotime($entry_time . ' + ' . $packageExist->date_diff . 'days'));
							}*/

							$getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');
							if ($getDay == 'Sat') {
								$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate.' + 2 days'));
							}
							if ($getDay == 'Sun') {
								$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate.' + 1 day'));
							}

							/* $getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');

							if($getDay == 'Sun'){
							$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate. ' + 1 day'));
							}*/

							if ($Total_ROI > 0 && strtotime($nextEntrydate) <= strtotime($getDate)) {

								$finalarr                        = array();
								$Dailydata                       = array();
								$Dailydata['amount']             = $Total_ROI;
								$Dailydata['id']                 = $id;
								$Dailydata['pin']                = $pin;
								$Dailydata['status']             = 'Paid';
								$Dailydata['remark']             = $remarks;
								$Dailydata['software_perentage'] = 0;
								$Dailydata['daily_percentage']   = $ProductRoi;
								$Dailydata['software_amount']    = 0;
								$Dailydata['daily_amount']       = $daily_intrest;
								$Dailydata['entry_time']         = $nextEntrydate;
								$Dailydata['type']               = $type;
								//$Dailydata = DailyBonus::create($Dailydata);

								//======all transaction
								// update user usd and roi income
								$updateCoinData                 = array();
								$updateCoinData['usd']          = custom_round(($user_usd+$Total_ROI), 7);
								$updateCoinData['total_profit'] = custom_round(($total_profit+$Total_ROI), 7);
								$updateCoinData['roi_income']   = custom_round(($roi_income+$Total_ROI), 7);

								$updateCoinData['roi_income_withdraw'] = custom_round(($roi_income_withdraw+$Total_ROI), 7);

								$updateCoinData['working_wallet'] = custom_round(($working_wallet+$Total_ROI), 7);
								$updateCoinData['id']             = $id;

								$updateCoinData = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);

								$balance                  = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
								$Trandata                 = array();// insert in transaction
								$Trandata['id']           = $id;
								$Trandata['network_type'] = 'USD';
								$Trandata['refference']   = $id;
								//$Dailydata->id;
								$Trandata['balance']    = $balance+$Total_ROI;
								$Trandata['credit']     = $Total_ROI;
								$Trandata['type']       = 'ROI INCOME';
								$Trandata['status']     = 1;
								$Trandata['remarks']    = $remarks;
								$Trandata['entry_time'] = $nextEntrydate;
								// $TransactionDta = AllTransaction::create($Trandata);

								$actdata               = array();// insert in transaction
								$actdata['id']         = $id;
								$actdata['message']    = $remarks;
								$actdata['status']     = 1;
								$actdata['entry_time'] = $nextEntrydate;
								//  $actDta = Activitynotification::create($actdata);

								/*****************Level roi***************/
								// if($old_status!=1){
								//    $this->pay_level_roi($id, $Total_ROI, $type, $pin, $nextEntrydate);
								// }

								$subject  = " ROI Generated!";
								$pagename = "emails.roi";
								$data     = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id, 'amount' => $daily_intrest);
								$email    = $email;
								// $mail = sendMail($data, $email, $subject);
								//dd($email);

								$whatsappMsg = "Greetings\n, Your daily ROI of $ ".$daily_intrest." has been generated in your account having user id -: ".$user_id;

								//$countrycode = getCountryCode($country);

								// sendSMS($mobile, $whatsappMsg);
								//sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

								$finalarr['dailydata']      = $Dailydata;
								$finalarr['updateCoinData'] = $updateCoinData;
								$finalarr['trandata']       = $Trandata;
								$finalarr['actdata']        = $actdata;

								return $finalarr;
								//return array('finalarr' =>$finalarr);

								$arrStatus  = Response::HTTP_OK;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'ROI generated successfully';
								return sendResponse($arrStatus, $arrCode, $arrMessage, $finalarr);
							}

						} else {
							return 404;
							/*$arrStatus   = Response::HTTP_NOT_FOUND;
						$arrCode     = Response::$statusTexts[$arrStatus];
						$arrMessage  = 'Pin count is ' . $countPin . '';
						return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
						}
					} else {

						return 404;
						/*$minCost = Packages::orderBy('id', 'asc')->min('cost');
					$maxCost = Packages::orderBy('id', 'asc')->max('cost');

					$arrStatus   = Response::HTTP_NOT_FOUND;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'Investment not matched with package amount.It should be min ' . $minCost . ' and max ' . $maxCost . '';
					return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
					}

				} else {
					return 404;
					/*   $arrStatus   = Response::HTTP_NOT_FOUND;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = 'Investment amount must be greater than 0';
				return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/

				}// end of $invest_amount>0
			} else {
				return 404;
				/*$arrStatus   = Response::HTTP_NOT_FOUND;
			$arrCode     = Response::$statusTexts[$arrStatus];
			$arrMessage  = 'ROI already generated';
			return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
			}
		} catch (Exception $e) {
			return 404;
			dd($e);

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
		// end of daily bonus with exist with id
	}

	/**
	 * Generate generatesuppermatchingincome cron
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function generatesuppermatchingincome($id, $pin, $type, $entry_time, $email, $user_id, $country, $mobile, $withdraw, $old_status, $rank) {

		try {

			$original_entry_time = $entry_time;
			/*$nextEntrydate=date('Y-m-d', strtotime($entry_time));*/

			/* $packageExist=Rank::where([['rank','=',$rank]])->limit(1)->first();*/

			$lastDateExist = SupperMatchingIncome::select('entry_time')->where([['pin', '=', $pin], ['rank', '=', $rank], ['id', '=', $id]])->orderBy('entry_time', 'desc')->first();

			$packageExist = Rank::where([['rank', '=', $rank]])->limit(1)->first();

			if (!empty($lastDateExist)) {
				$entry_time = $lastDateExist->entry_time;

				$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + '.$packageExist->date_diff.' days'));

			} else {

				if ($rank == 'Ace') {
					$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + '.$packageExist->date_diff.'days'));
				} else {

					$last_entry_in_supermatching = supermatching::select('entry_time', 'rank')->where([['user_id', '=', $id], [DB::raw('date(entry_time)'), '=', date('Y-m-d', strtotime($original_entry_time))], ['rank', '=', $rank]])->orderBy('entry_time', 'desc')->first();
					//  dd($last_entry_in_supermatching);
					$rank = $last_entry_in_supermatching['rank'];
					//  $rank_entry_date = date('Y-m-d', strtotime($last_entry_in_supermatching['entry_time']));
					$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + 7 days'));

				}
			}
			// echo $rank."\n";
			$packageExist = Rank::where([['rank', '=', $rank]])->limit(1)->first();
			$date         = \Carbon\Carbon::parse($entry_time);
			$now          = \Carbon\Carbon::now();
			$diff         = $date->diffInDays($now);
			//dd($diff);
			$getDate = $now->toDateString();

			if ($rank == null) {
				$roi = 1;

			} else {
				$roidata = Rank::where([['rank', '=', $rank]])->limit(1)->first();
				$roi     = $roidata->income_percentage;
			}

			//dd($roi);

			$checkWeekend = $now->isWeekend();

			//dd($nextEntrydate);
			$DailyBounsExist = SupperMatchingIncome::select('entry_time')->where([[DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"), '=', $nextEntrydate], ['pin', '=', $pin]])->first();
			/*dd($DailyBounsExist);*/

			/*if(empty($DailyBounsExist && $diff>=$packageExist->date_diff) && strtotime($nextEntrydate)<= strtotime($getDate))*/
			if (empty($DailyBounsExist && $diff <= $packageExist->date_diff) && strtotime($nextEntrydate) <= strtotime($getDate)) {

				/*dd('$DailyBounsExist');*/
				// check user invetment is greater than 0
				$invest_amount = $packageExist->bonus_percentage;

				/*dd($invest_amount);*/
				if ($invest_amount > 0) {

					if (!empty($packageExist)) {

						//$ProductRoi = $packageExist->roi;

						// calculate product percentage
						$daily_intrest = $invest_amount;// value in usd
						// total roi per day
						$Total_ROI = $daily_intrest;
						/*dd($Total_ROI);*/
						// $Total_ROI=$software_intrest+$daily_intrest;

						$userDash = Dashboard::select('usd', 'total_profit', 'working_wallet', 'dex_wallet', 'supper_maching_income')->where([['id', '=', $id], ])->first();

						$user_usd = $userDash->usd;
						//$roi_income = $userDash->roi_income;

						$total_profit          = $userDash->total_profit;
						$supper_maching_income = $userDash->supper_maching_income;
						/*$level_income =$userDash->level_income;

						$roi_income_withdraw = $userDash->roi_income_withdraw;*/

						$working_wallet = $userDash->working_wallet;
						$dex_wallet     = $userDash->dex_wallet;

						$remarks = "Supper Matching Bonus profit: ".$Total_ROI." Deposit ID: ".$pin."% amount ".$daily_intrest.")";
						/*  dd($remarks);*/

						//updat daily bonus
						//  $countPin = DailyBonus::where('pin', '=', $pin)->sum('daily_percentage');
						$countPin   = SupperMatchingIncome::where('pin', '=', $pin)->count();
						$amount_got = SupperMatchingIncome::where('pin', '=', $pin)->sum('amount');
						$amount_got = $amount_got+$withdraw;

						$amount_to_give = $invest_amount;
						/* dd($amount_to_give);*/

						//# $checkEntryExist = DailyBonus::where('pin', '=', $pin)->orderBy('entry_time', 'desc')->limit(1)->first();

						$Productduration = $packageExist->duration;

						if ($countPin < $Productduration) {

							$getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');
							/*  if($getDay == 'Sat'){
							$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate. ' + 2 days'));
							}
							if($getDay == 'Sun'){
							$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate. ' + 1 day'));
							}*/

							/* $getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');

							if($getDay == 'Sun'){
							$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate. ' + 1 day'));
							}*/

							if ($Total_ROI > 0 && strtotime($nextEntrydate) <= strtotime($getDate)) {

								$finalarr                        = array();
								$Dailydata                       = array();
								$Dailydata['amount']             = $Total_ROI;
								$Dailydata['id']                 = $id;
								$Dailydata['pin']                = $pin;
								$Dailydata['status']             = 'Paid';
								$Dailydata['remark']             = $remarks;
								$Dailydata['software_perentage'] = 0;
								//$Dailydata['daily_percentage'] = $ProductRoi;
								$Dailydata['software_amount'] = 0;
								$Dailydata['daily_amount']    = $daily_intrest;
								$Dailydata['entry_time']      = $nextEntrydate;
								$Dailydata['rank']            = $rank;
								/*$Dailydata['type'] = $type;*/
								$Dailydata = SupperMatchingIncome::create($Dailydata);
								//dd($pin);
								/*if (($countPin+1) >= $Productduration)
								{
								$pindata = (int)$pin;

								$update_roi_status = SupperMatchingIncome::where('pin',$pindata)->update(['maching_income_status'=>'Inactive']);

								}*/

								//======all transaction
								// update user usd and roi income
								$updateCoinData                 = array();
								$updateCoinData['usd']          = custom_round(($user_usd+$Total_ROI), 7);
								$updateCoinData['total_profit'] = custom_round(($total_profit+$Total_ROI), 7);
								/*$updateCoinData['roi_income'] = custom_round(($roi_income + $Total_ROI), 7);

								$updateCoinData['roi_income_withdraw'] = custom_round(($roi_income_withdraw + $Total_ROI), 7);*/

								$updateCoinData['working_wallet'] = custom_round(($working_wallet+$Total_ROI), 7);
								$updateCoinData['dex_wallet']     = custom_round(($dex_wallet+$Total_ROI), 7);

								$updateCoinData['supper_maching_income'] = custom_round(($supper_maching_income+$Total_ROI), 7);

								/* $updateCoinData['id'] =$id;*/

								$updateCoinData = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);

								$balance                  = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
								$Trandata                 = array();// insert in transaction
								$Trandata['id']           = $id;
								$Trandata['network_type'] = 'USD';
								$Trandata['refference']   = $id;
								//$Dailydata->id;
								$Trandata['balance']    = $balance+$Total_ROI;
								$Trandata['credit']     = $Total_ROI;
								$Trandata['type']       = 'supper matching INCOME';
								$Trandata['status']     = 1;
								$Trandata['remarks']    = $remarks;
								$Trandata['entry_time'] = $nextEntrydate;
								$TransactionDta         = AllTransaction::create($Trandata);

								$actdata               = array();// insert in transaction
								$actdata['id']         = $id;
								$actdata['message']    = $remarks;
								$actdata['status']     = 1;
								$actdata['entry_time'] = $nextEntrydate;
								$actDta                = Activitynotification::create($actdata);

								/*****************Level roi***************/
								// if($old_status!=1){
								//    $this->pay_level_roi($id, $Total_ROI, $type, $pin, $nextEntrydate);
								// }

								$subject  = " ROI Generated!";
								$pagename = "emails.roi";
								$data     = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id, 'amount' => $daily_intrest);
								$email    = $email;
								// $mail = sendMail($data, $email, $subject);
								//dd($email);
								/*dd();*/

								$whatsappMsg = "Greetings\n, Your daily ROI of $ ".$daily_intrest." has been generated in your account having user id -: ".$user_id;

								//$countrycode = getCountryCode($country);

								// sendSMS($mobile, $whatsappMsg);
								//sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);
								/*if($countPin+1 == $Productduration){
								$updatepin['maching_income_status'] = 'Inactive';
								$updatedata = SupperMatchingIncome::where('pin', $pin)->update($updatepin);
								}
								dd($updatedata);*/
								$finalarr['dailydata']      = $Dailydata;
								$finalarr['updateCoinData'] = $updateCoinData;
								$finalarr['trandata']       = $Trandata;
								$finalarr['actdata']        = $actdata;

								return $finalarr;
								//return array('finalarr' =>$finalarr);

								$arrStatus  = Response::HTTP_OK;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'ROI generated successfully';
								return sendResponse($arrStatus, $arrCode, $arrMessage, $finalarr);
							}

						} else {
							$updateData['maching_income_status'] = 'Inactive';

							$updateCoinData = supermatching::where('pin', $pin)->limit(1)->update($updateData);
							/*$arrStatus   = Response::HTTP_NOT_FOUND;
						$arrCode     = Response::$statusTexts[$arrStatus];
						$arrMessage  = 'Pin count is ' . $countPin . '';
						return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
						}
					} else {

						return 404;
						/*$minCost = Packages::orderBy('id', 'asc')->min('cost');
					$maxCost = Packages::orderBy('id', 'asc')->max('cost');

					$arrStatus   = Response::HTTP_NOT_FOUND;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'Investment not matched with package amount.It should be min ' . $minCost . ' and max ' . $maxCost . '';
					return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
					}

				} else {
					return 404;
					/*   $arrStatus   = Response::HTTP_NOT_FOUND;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = 'Investment amount must be greater than 0';
				return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/

				}// end of $invest_amount>0
			} else {
				return 404;
				/*$arrStatus   = Response::HTTP_NOT_FOUND;
			$arrCode     = Response::$statusTexts[$arrStatus];
			$arrMessage  = 'ROI already generated';
			return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
			}
		} catch (Exception $e) {
			return 404;
			dd($e);

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
		// end of daily bonus with exist with id
	}
	public function generateroidynamic($invest_amount, $id, $pin, $type, $entry_time, $email = "", $user_id = "", $country = "", $mobile = "", $withdraw = 0, $old_status = "", $rank = "") {

		try {

			$original_entry_time = $entry_time;

			$lastDateExist = DailyBonus::select('entry_time')->where([['pin', '=', $pin]])->orderBy('entry_time', 'desc')->first();if (!empty($lastDateExist)) {
				$entry_time   = $lastDateExist->entry_time;
			}

			$date    = \Carbon\Carbon::parse($entry_time);
			$now     = \Carbon\Carbon::now();
			$diff    = $date->diffInDays($now);
			$getDate = $now->toDateString();
			
			$packageExist = Packages::where([['id', '=', $type]])->limit(1)->first();
			// dd($packageExist->date_diff);
			$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + '.$packageExist->date_diff.'days'));

			$checkWeekend = $now->isWeekend();

			$DailyBounsExist = DailyBonus::select('entry_time')->where([[DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"), '=', $nextEntrydate], ['pin', '=', $pin]])->first();

			if (empty($DailyBounsExist && $diff >= $packageExist->date_diff) && strtotime($nextEntrydate) <= strtotime($getDate)) {

				// check user invetment is greater than 0

				if ($invest_amount > 0) {

					if (!empty($packageExist)) {

						$ProductRoi = $packageExist->roi;

						// $ProductRoi=0;

						// $TopupNo=Topup::where([['entry_time','<=',$original_entry_time],['id','=',$id]])->count();

						// $TopupNo=Topup::where('entry_time','<=',$original_entry_time)->where('id',$id)->count();

						// dd($pin,$original_entry_time,$id,$TopupNo);
						/*
						if($TopupNo == 1)
						{
						$ProductRoi = 0.35;
						}
						else if($TopupNo == 2)
						{
						$ProductRoi = 0.5;
						}
						else if($TopupNo == 3)
						{
						$ProductRoi = 0.75;
						}
						else if($TopupNo == 4)
						{
						$ProductRoi = 1;
						}
						else if($TopupNo >= 5)
						{
						$ProductRoi = 1.25;
						} */

						// calculate product percentage
						$daily_intrest = (($invest_amount*$ProductRoi)/100);// value in usd
						// total roi per day
						$Total_ROI = $daily_intrest;

						//$remarks = "ROI profit: ".$Total_ROI." Deposit ID: ".$pin." interest ".$ProductRoi."% amount ".$daily_intrest.")";
						$remarks = "ROI Income";

						//updat daily bonus
						//  $countPin = DailyBonus::where('pin', '=', $pin)->sum('daily_percentage');
						$countPin = DailyBonus::where('pin', '=', $pin)->count();
						/*$amount_got = DailyBonus::where('pin', '=', $pin)->sum('amount');*/

						/*$amount_got=$amount_got+$withdraw;*/

						$amount_to_give = $invest_amount*2;

						//# $checkEntryExist = DailyBonus::where('pin', '=', $pin)->orderBy('entry_time', 'desc')->limit(1)->first();

						$Productduration = $packageExist->duration;
						if ($countPin < $Productduration) {

							// if ($amount_got < $amount_to_give) {

							/* if (!empty($checkEntryExist)) {

							$nextEntrydate = date('Y-m-d', strtotime($checkEntryExist->entry_time . ' + ' . $packageExist->date_diff . 'days'));
							} else {
							$nextEntrydate = date('Y-m-d', strtotime($entry_time . ' + ' . $packageExist->date_diff . 'days'));
							}*/

							$getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');
							if ($getDay == 'Sat') {
								$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate.' + 2 days'));
							}
							if ($getDay == 'Sun') {
								$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate.' + 1 day'));
							}

							/* $getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');

							if($getDay == 'Sun'){
							$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate. ' + 1 day'));
							}*/

							if ($Total_ROI > 0 && strtotime($nextEntrydate) <= strtotime($getDate)) {

								$finalarr                        = array();
								$Dailydata                       = array();
								$Dailydata['amount']             = $Total_ROI;
								$Dailydata['id']                 = $id;
								$Dailydata['pin']                = $pin;
								$Dailydata['status']             = 'Paid';
								$Dailydata['remark']             = $remarks;
								$Dailydata['software_perentage'] = 0;
								$Dailydata['daily_percentage']   = $ProductRoi;
								$Dailydata['software_amount']    = 0;
								$Dailydata['daily_amount']       = $daily_intrest;
								$Dailydata['entry_time']         = $nextEntrydate;
								$Dailydata['type']               = $type;
								//$Dailydata = DailyBonus::create($Dailydata);

								//======all transaction
								// update user usd and roi income
								$updateCoinData = array();
								$updateCoinData['usd'] = DB::raw('usd +'.$Total_ROI);
								$updateCoinData['total_profit'] = DB::raw('total_profit +'. $Total_ROI);
								$updateCoinData['roi_income'] = DB::raw('roi_income + '. $Total_ROI);
								// $updateCoinData['roi_income_withdraw'] = DB::raw('roi_income_withdraw + '. $Total_ROI);
								$updateCoinData['working_wallet'] = DB::raw('working_wallet + '.$Total_ROI);
								$updateCoinData['id'] = $id;

								// $updateCoinData = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);

								/*$balance = AllTransaction::where('id', '=',$id)->orderBy('srno','desc')->pluck('balance')->first();
								$Trandata = array();      // insert in transaction
								$Trandata['id'] = $id;
								$Trandata['network_type'] = 'USD';
								$Trandata['refference'] = $id;
								//$Dailydata->id;
								$Trandata['balance'] =$balance+$Total_ROI;
								$Trandata['credit'] = $Total_ROI;
								$Trandata['type'] = 'ROI INCOME';
								$Trandata['status'] = 1;
								$Trandata['remarks'] = $remarks;
								$Trandata['entry_time'] = $nextEntrydate;*/
								// $TransactionDta = AllTransaction::create($Trandata);

								/*$actdata = array();      // insert in transaction
								$actdata['id'] = $id;
								$actdata['message'] = $remarks;
								$actdata['status'] = 1;
								$actdata['entry_time'] = $nextEntrydate;*/
								//  $actDta = Activitynotification::create($actdata);

								/*****************Level roi***************/
								// if($old_status!=1){
								//    $this->pay_level_roi($id, $Total_ROI, $type, $pin, $nextEntrydate);
								// }

								/*$subject = " ROI Generated!";
								$pagename = "emails.roi";
								$data = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id,'amount' => $daily_intrest);
								$email = $email;*/
								// $mail = sendMail($data, $email, $subject);
								//dd($email);

								/*$whatsappMsg = "Greetings\n, Your daily ROI of $ ".$daily_intrest." has been generated in your account having user id -: " . $user_id;*/

								//$countrycode = getCountryCode($country);

								// sendSMS($mobile, $whatsappMsg);
								//sendWhatsappMsg($countrycode, $mobile, $whatsappMsg);

								$finalarr['dailydata'] = $Dailydata;
								$finalarr['updateCoinData']=$updateCoinData;
								/*$finalarr['trandata']=$Trandata;
								$finalarr['actdata']=$actdata;*/

								return $finalarr;
								//return array('finalarr' =>$finalarr);

								$arrStatus  = Response::HTTP_OK;
								$arrCode    = Response::$statusTexts[$arrStatus];
								$arrMessage = 'ROI generated successfully';
								return sendResponse($arrStatus, $arrCode, $arrMessage, $finalarr);
							}

						} else {
							return 404;
							/*$arrStatus   = Response::HTTP_NOT_FOUND;
						$arrCode     = Response::$statusTexts[$arrStatus];
						$arrMessage  = 'Pin count is ' . $countPin . '';
						return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
						}
					} else {

						return 404;
						/*$minCost = Packages::orderBy('id', 'asc')->min('cost');
					$maxCost = Packages::orderBy('id', 'asc')->max('cost');

					$arrStatus   = Response::HTTP_NOT_FOUND;
					$arrCode     = Response::$statusTexts[$arrStatus];
					$arrMessage  = 'Investment not matched with package amount.It should be min ' . $minCost . ' and max ' . $maxCost . '';
					return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
					}

				} else {
					return 404;
					/*   $arrStatus   = Response::HTTP_NOT_FOUND;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = 'Investment amount must be greater than 0';
				return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/

				}// end of $invest_amount>0
			} else {
				return 404;
				/*$arrStatus   = Response::HTTP_NOT_FOUND;
			$arrCode     = Response::$statusTexts[$arrStatus];
			$arrMessage  = 'ROI already generated';
			return sendResponse($arrStatus,$arrCode,$arrMessage,'');*/
			}
		} catch (Exception $e) {
			//return 404;
			dd($e);

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
		// end of daily bonus with exist with id
	}

	public function generateroistatic($roi_amount, $id, $pin, $type, $entry_time, $roi_per, $date_diff, $start_roi_days, $product_amount) {
		// dd($lastDateExist);
		// $time_start = microtime_float();
		$lastDateExist = DailyBonus::select('entry_time')->where('pin', '=', $pin)->orderBy('entry_time', 'desc')->limit(1)->first();
		if (!empty($lastDateExist)) {
			$entry_time = $lastDateExist->entry_time;
		} else {
			$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + '.$start_roi_days.'days'));
		}

		/*$time_end = microtime_float();
		$time = $time_end - $time_start;
		echo "dailybonus date ".$time."\n";*/

		$date = \Carbon\Carbon::parse($entry_time)->toDateString();

		$now = \Carbon\Carbon::now()->toDateString();

		$date1   = \Carbon\Carbon::parse($date);
		$now1    = \Carbon\Carbon::parse($now);
		$diff    = $date1->diffInDays($now1);
		$getDate = $now;
		//$now->toDateString();
		// $packageExist=Product::where([['id','=',$type]])->limit(1)->first();
		// dd($packageExist->date_diff);

		/*     if(empty($lastDateExist)){

		$addDays = 3;
		$nextEntrydate=date('Y-m-d', strtotime($entry_time. ' + '.$addDays. 'days'));
		}else{*/
		if (!empty($lastDateExist)) {
			$nextEntrydate = date('Y-m-d', strtotime($entry_time.' + '.$date_diff.'days'));
		}
		// }

		// dd($entry_time,$nextEntrydate);

		//dd($getDay);

		$DailyBounsExist = DailyBonus::select('pin')->where([[DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"), '=', $nextEntrydate], ['pin', '=', $pin]])->first();

		//$currentDiff = $packageExist->date_diff;

		/*$settings = ProjectSettings::select("tds","admin_charges")->first();*/
		/*$tds_per= 0;
		//$settings->tds;

		$admin_charges = 0;
		//$settings->admin_charges;
		 */
		//dd($diff,$currentDiff);
		if (((empty($DailyBounsExist)) && ($diff >= $date_diff)) && strtotime($nextEntrydate) <= strtotime($getDate)) {
			if ($roi_amount > 0) {
				// $nextEntrydate = $getDate;
				// if (!empty($packageExist)) {
				/*$ProductRoi = $roi_per;
				//$packageExist->roi;
				 */
				// calculate product percentage
				// $daily_intrest = (($roi_amount * $ProductRoi) / 100); // value in usd
				// $daily_intrest =  $ProductRoi;
				// total roi per day
				// $Total_ROI = $roi_amount;

				// $Total_ROI=$software_intrest+$daily_intrest;
				// $remarks = "( ROI profit: " . $Total_ROI . " Deposit Id : " . $pin . " interest " . $ProductRoi . " amount " . ")";

				//updat daily bonus
				$roi_data       = DailyBonus::selectRaw('COUNT(pin) as count, COALESCE(SUM(amount),0) as total_roi_amount')->where('pin', '=', $pin)->limit(1)->first();
				$countPin       = $roi_data->count;
				$totalRoiAmount = $roi_data->total_roi_amount;
				// $checkEntryExist = DailyBonus::where('pin', '=', $pin)->orderBy('entry_time', 'desc')->limit(1)->first();

				$Productduration = 200;
				//365;
				//$packageExist->duration;

				// dd($roi_data,$product_amount);
				if ($countPin < $Productduration && $totalRoiAmount < $product_amount) {
					// if(1){
					/*    if (!empty($checkEntryExist)) {

					$nextEntrydate = date('Y-m-d', strtotime($checkEntryExist->entry_time . ' + ' . $currentDiff . 'days'));
					} else {
					$nextEntrydate = date('Y-m-d', strtotime($entry_time . ' + ' . $currentDiff . 'days'));
					}*/
					$getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');
					if ($getDay == 'Sat') {
						$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate.' + 2 days'));
					}
					if ($getDay == 'Sun') {
						$nextEntrydate = date('Y-m-d', strtotime($nextEntrydate.' + 1 day'));
					}
					//dd($nextEntrydate);
					// $time_start = microtime_float();

					$Dailydata           = array();
					$Dailydata['amount'] = $roi_amount;
					$Dailydata['id']     = $id;
					$Dailydata['pin']    = $pin;
					$Dailydata['status'] = 'Paid';
					// $Dailydata['remark'] = $remarks;
					$Dailydata['software_perentage'] = 0;
					$Dailydata['daily_percentage']   = $roi_per;
					$Dailydata['software_amount']    = 0;
					$Dailydata['daily_amount']       = $roi_amount;
					$Dailydata['entry_time']         = $nextEntrydate;
					$Dailydata['type']               = $type;
					$Dailydata['tax_amount']         = 0;
					/*$Dailydata['net_amount'] = $roi_amount;*/

					// $Dailydata = DailyBonus::create($Dailydata);

					/*$time_end = microtime_float();
					$time = $time_end - $time_start;
					echo "insert dailybonus ".$time."\n";*/

					//dd('fsdfsdfsdff');
					//======all transaction
					// update user usd and roi income
					// $userDetails = Dashboard::select('usd','roi_income','total_profit','roi_income_withdraw','working_wallet')->where([['id', '=', $id]])->first();
					//dd($roi_amount);

					// $time_start = microtime_float();

					//$day = \Carbon\Carbon::parse($nextEntrydate)->format('D');

					/*$updateCoinData = array();
					$updateCoinData['usd'] = DB::RAW('usd + '.$roi_amount);
					$updateCoinData['total_profit'] = DB::RAW('total_profit + '.$roi_amount);
					$updateCoinData['roi_income'] = DB::RAW('roi_income + '.$roi_amount);
					// echo $day."\n";
					if($day == 'Sun')
					{
					$updateCoinData['roi_income_withdraw'] = DB::RAW('roi_income_withdraw + '.$roi_amount);
					$updateCoinData['ludo_wallet'] = DB::RAW('ludo_wallet + '.$roi_amount);
					}*/
					// $updateCoinData['roi_income_withdraw'] = custom_round(($userDetails->roi_income_withdraw + $Total_ROI), 7);
					//$updateCoinData['working_wallet'] = custom_round(($userDetails->working_wallet + $Total_ROI), 7);

					//   $updateCoinData['ewallet'] = custom_round(($userDetails->ewallet + $Total_ROI), 7);
					// $updateCoinData = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);

					// Update last roi date
					// $update_prev_roi_entry_time = Topup::where('pin',$pin)->limit(1)->update(['prev_roi_entry_time' => $nextEntrydate]);

					/*$balance = AllTransaction::where('id', '=',$id)->orderBy('srno','desc')->pluck('balance')->first();

					$Trandata = array();      // insert in transaction
					$Trandata['id'] = $id;
					$Trandata['network_type'] = 'USD';
					$Trandata['refference'] = $Dailydata->id;
					$Trandata['credit'] = $Total_ROI;
					$Trandata['balance'] =$balance+$Total_ROI;
					$Trandata['type'] = 'ROI INCOME';
					$Trandata['status'] = 1;
					$Trandata['remarks'] = $remarks;
					$Trandata['entry_time'] = $nextEntrydate;
					$TransactionDta = AllTransaction::create($Trandata);

					$actdata = array();      // insert in transaction
					$actdata['id'] = $id;
					$actdata['message'] = $remarks;
					$actdata['status'] = 1;
					$actdata['entry_time'] = $nextEntrydate;
					$actDta = Activitynotification::create($actdata);*/

					/*$time_end = microtime_float();
					$time = $time_end - $time_start;
					echo "update dashboard ".$time."\n";*/

					//echo "User ID ".$id."\n";

					$outputArr               = array();
					$outputArr['dailybonus'] = $Dailydata;
					//$outputArr['day'] = $day;
					return $outputArr;
				} else {

					$update_roi_status = Topup::where('pin', $pin)->update(['roi_status' => 'Inactive']);
					// return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Pin count is ' . $countPin . '', $this->emptyArray);
				}
				/*} else {

			$minCost = Product::orderBy('id', 'asc')->min('cost');
			$maxCost = Product::orderBy('id', 'asc')->max('cost');
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Investment not matched with package amount.It should be min ' . $minCost . ' and max ' . $maxCost . '', $this->emptyArray);
			}*/

			} else {

				// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Investment amount must be greater than 0', $this->emptyArray);
			}// end of $invest_amount>0
		} else {

			// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ROI already generated', $this->emptyArray);
		}

		// end of daily bonus with exist with id
	}

}
