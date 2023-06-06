<?php

namespace App\Http\Controllers\adminapi;

use App\Dashboard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\GenerateRoiController;
use App\models\Carrybv;
use App\Models\CronRunStatus;
use App\Models\CronStatus;
use App\Models\DailyBonus;
use App\Models\PayoutHistory;
use App\Models\PowerBV;
use App\Models\QualifiedUserList;
use App\Models\Topup;
use App\Models\AddPowerToParticularId;

use App\Traits\Users;
use App\User;

use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\DB;

class ManageCronController extends Controller
{
	use Users;
	public function __construct(GenerateRoiController $generateRoi)
	{
		//parent::__construct();
		$this->generateRoi = $generateRoi;
	}

	function RunCronRoiDynamic()
	{
		$signature = "cron:optimized_roi_dynamic";
		$croncheck = CronStatus::select('*')->where('name', $signature)->first();
		//dd($croncheck);
		if ($croncheck->status == 1) {

			//  echo "this cron is active\n";

			$Runcheck = CronStatus::select('running_status')->where('name', $signature)->first();
			if ($Runcheck->running_status == 1) {
				// echo "Now the cron is running\n";

			}
			// CronStatus::where('name', $signature)->update(array('running_status' => '1'));

			DB::raw('LOCK TABLES `tbl_dashboard` WRITE');

			DB::raw('LOCK TABLES `tbl_dailybonus` WRITE');

			$day = \Carbon\Carbon::now()->format('D');
			//dd($day);
			if ($day == 'Sun' || $day == 'Sat') {
				// dd('In');
				//	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ROI is not allowed on this day', '');
			}

			/*$user = User::join('tbl_topup', 'tbl_topup.id', '=', 'tbl_users.id')
			->select('tbl_topup.amount','tbl_users.rank','tbl_users.id','tbl_users.mobile','tbl_users.country','tbl_users.user_id','tbl_users.email','tbl_topup.pin','tbl_topup.type','tbl_topup.entry_time','tbl_topup.withdraw','tbl_topup.old_status')->where([['tbl_users.status', '=', 'Active'],['tbl_users.type', '=', ''],['tbl_topup.roi_status', '=', 'Active']])
			->get();*/

			$user = Topup::select('tbl_topup.id', 'tbl_topup.pin', 'tbl_topup.type', 'tbl_topup.amount', 'tbl_topup.entry_time')
				->where('tbl_topup.roi_status', 'Active')
				->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
				->where('tu.status', 'Active')->where('tu.type', '')
				// ->where('tbl_topup.id', 10095)
				// ->whereBetween('tbl_topup.type', [1,5])
				->get();
			// dd($user);
			$today           = \Carbon\Carbon::now();
			$today_datetime  = $today->toDateTimeString();
			$today_datetime2 = $today->toDateString();

			//echo $msg = 'ROI CRON started at' . $today_datetime . "\n" ;

			$dashArr = array();
			/*$traArr=array();
			$actArr=array();*/
			$roiArr = array();
			/*$finalArr= array();*/
			$arr_id = array();
			if (!empty($user)) {
				foreach ($user as $key => $val) {
					$invest_amount = $val->amount;
					$id            = $val->id;
					$pin           = $val->pin;
					$type          = $val->type;
					$entry_time    = $val->entry_time;
					/* $email = $user[$key]->email;
					$user_id = $user[$key]->user_id;
					$country = $user[$key]->country;
					$mobile = $user[$key]->mobile;
					$withdraw = $user[$key]->withdraw;
					$old_status = $user[$key]->old_status;
					$rank = $user[$key]->rank;*/

					$checkUpdate = $this->generateRoi->generateroidynamic($invest_amount, $id, $pin, $type, $entry_time);
					// dd($checkUpdate);
					if ($checkUpdate != 404) {
						$roiArr[] = $checkUpdate['dailydata'];
						$dashArr[] = $checkUpdate['updateCoinData'];
						$arr_id[] = $id;
						/*$traArr[]=$checkUpdate['trandata'];*/
						/*$actArr[]=$checkUpdate['actdata'];*/
					}
				}

				$count = 1;
				$array = array_chunk($roiArr, 1000);
				//dd(count($array,1));
				while ($count <= count($array)) {
					$a   = 0;
					$key = $count - 1;
					$a += $key;
					DailyBonus::insert($array[$key]);
					//echo $count." count array ".count($array[$key])."\n";
					$count++;
				}
				//----------------------------------
				/* $count1 = 1;
				$array_traArr = array_chunk($traArr,1000);
				while($count1 <= count($array_traArr))
				{
				$key = $count1-1;
				AllTransaction::insert($array_traArr[$key]);
				echo $count1." count array ".count($array_traArr[$key])."\n";
				$count1 ++;
				}
				//----------------------------------
				$count2 = 1;
				$array_actArr = array_chunk($actArr,1000);
				while($count2 <= count($array_actArr))
				{
				$key = $count2-1;
				Activitynotification::insert($array_actArr[$key]);
				echo $count2." count array ".count($array_actArr[$key])."\n";
				$count2 ++;
				}*/

				//-----------------
				//dd($dashArr);
				foreach ($dashArr as $value) {
					# code...
					Dashboard::where('id', $value['id'])->limit(1)->update($value);
				}
				// $Total_ROI = 0.05;

				// $updateCoinData                        = array();
				// $updateCoinData['usd']                 = DB::raw('usd +'.$Total_ROI);
				// $updateCoinData['total_profit']        = DB::raw('total_profit +'.$Total_ROI);
				// $updateCoinData['roi_income']          = DB::raw('roi_income + '.$Total_ROI);
				// $updateCoinData['roi_income_withdraw'] = DB::raw('roi_income_withdraw + '.$Total_ROI);
				// $updateCoinData['working_wallet']      = DB::raw('working_wallet + '.$Total_ROI);

				// $count2 = 1;
				// $array2 = array_chunk($arr_id, 1000);
				// while ($count2 <= count($array2)) {
				// 	$key2 = $count2-1;
				// 	Dashboard::whereIn('id', $array2[$key2])->update($updateCoinData);
				// 	//echo $count2." update count array ".count($array2[$key2])."\n";
				// 	$count2++;
				// }

				// $this->info('ROI generated successfully');
				$today           = \Carbon\Carbon::now();
				$today_datetime  = $today->toDateTimeString();
				$today_datetime2 = $today->toDateString();

				// dd($today);
				/*$payoutHistory =DailyBonus::select('entry_time')->whereDate('entry_time', '=', $today_datetime2)->count();*/

				//  echo $msg = 'ROI CRON end at' . $today_datetime . "\n" ;

				DB::raw('UNLOCK TABLES');
			} else {

				//$this->info('User is not exist');
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User is not exist', '');
			}

			CronStatus::where('name', $signature)->update(array('running_status' => '0'));

			$Runcheck = CronStatus::select('running_status')->where('name', $signature)->first();
			if ($Runcheck->running_status == 0) {
				//  echo "Now the cron is Idle \n";
			}
			$cronRunEntry               = new CronRunStatus();
			$cronRunEntry['cron_id']    = $croncheck->id;
			$cronRunEntry['run_status'] = 1;
			//$cronRunEntry['remark']     = 'Total '.$a.' ROI generated';
			$cronRunEntry['run_time'] = $today_datetime;
			$cronRunEntry->save();

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Cron Run Successfully';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		} else {
			// echo "this cron is inactive \n";
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		}

		// if($croncheck->running_status ==0)
		// {
		// echo "this cron is Running";
		// }
		// else{
		//   echo "this cron is Idle";
		//  }

	}

	function RunBinaryQualifyCron($signature = NULL, $run_from = 'function')
	{
		$croncheck = CronStatus::select('*')->where('name', $signature)->first();
		//dd($croncheck);
		if ($croncheck->status == 1) {
			$current_time = \Carbon\Carbon::now()->toDateTimeString();
			$msg          = "Qualify Cron started at " . $current_time;
			//sendSMS($mobile,$msg);
			//echo $msg."\n";

			//echo "start ".now()."\n";
			$user = User::select('id')
				->where('type', "")->where('status', "Active")->where('binary_qualified_status', 0)
				->where('power_l_bv', '>', 0)->where('power_r_bv', '>', 0)->get();

			$qualified_count = 0;
			//dd($user);
			if (!empty($user) && count($user) > 0) {
				$insert_qualified_arr = array();
				$user_id_arr          = array();
				foreach ($user as $rowUser) {
					list($usec, $sec) = explode(" ", microtime());

					$time_start = ((float) $usec + (float) $sec);

					$QualifiedData            = array();
					$QualifiedData['user_id'] = $rowUser->id;
					array_push($insert_qualified_arr, $QualifiedData);
					array_push($user_id_arr, $rowUser->id);

					$qualified_count = $qualified_count + 1;

					// echo  "\n User ID--> ".$rowUser->id." -> ";

					list($usec, $sec) = explode(" ", microtime());
					$time_end         = ((float) $usec + (float) $sec);
					$time             = $time_end - $time_start;
					//    echo "time ".$time."\n";
				}
				$count = 1;
				$array = array_chunk($insert_qualified_arr, 1000);
				// dd($array);
				while ($count <= count($array)) {
					$key = $count - 1;
					QualifiedUserList::insert($array[$key]);
					// echo $count." insert count array ".count($array[$key])."\n";
					$count++;
				}

				$updateUserData                            = array();
				$updateUserData['binary_qualified_status'] = 1;

				$count2 = 1;
				$array2 = array_chunk($user_id_arr, 1000);
				while ($count2 <= count($array2)) {
					$key2 = $count2 - 1;
					User::whereIn('id', $array2[$key2])->update($updateUserData);
					// echo $count2." update user array ".count($array2[$key2])."\n";
					$count2++;
				}
			}
			#echo "end ".now()."\n";
			//echo "\n Cron run successfully \n";

			$current_time = \Carbon\Carbon::now()->toDateTimeString();
			$msg          = "Qualify Cron end at " . $current_time . "\nTotal qualified ids : " . $qualified_count . "\n";
			//sendSMS($mobile,$msg);
			//echo $msg;

			$cronRunEntry               = new CronRunStatus();
			$cronRunEntry['cron_id']    = $croncheck->id;
			$cronRunEntry['run_status'] = 1;
			$cronRunEntry['run_time']   = $current_time;
			$cronRunEntry->save();
			if ($run_from == 'function') {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully from ' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} else {
			//echo "this cron is inactive \n";
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		}
	}

	function RunBinaryIncomeCron($signature = NULL, $run_from = 'function')
	{
		$croncheck = CronStatus::select('*')->where('name', $signature)->first();
		//dd($croncheck);
		if ($croncheck->status == 1) {
			list($usec, $sec) = explode(" ", microtime());

			$time_start1  = ((float) $usec + (float) $sec);
			$now          = \Carbon\Carbon::now();
			$day          = date('D', strtotime($now));
			$current_time = \Carbon\Carbon::now()->toDateTimeString();

			$msg = "Binary Cron started at " . $current_time;
			//sendSMS($mobile,$msg);
			//echo $msg."\n";

			//echo "start ".now()."\n";

			$getUserDetails = User::select("id", "curr_l_bv", "curr_r_bv", "rank", "l_ace_check_status", "r_ace_check_status")
				->where([['curr_l_bv', '>', 0], ['curr_r_bv', '>', 0], ['rank', '!=', null], ['status', '=', "Active"], ['type', '!=', "Admin"], ['binary_qualified_status', '=', 1], ['topup_status', '=', "1"]])
				//->limit(1)
				->get();

			//dd($getUserDetails[0]->curr_r_bv);

			$binary_count = 0;
			if (!empty($getUserDetails)) {

				$payout_no = PayoutHistory::max('payout_no');
				if (empty($payout_no)) {
					$payout_no = 1;
				} else {
					$payout_no = $payout_no + 1;
				}

				// get common rank data
				$rankdata_arr = array();
				$rankdata     = DB::table('tbl_rank')
					->select('income_percentage', 'capping', 'rank')
					->get();

				foreach ($rankdata as $key => $value) {
					array_push($rankdata_arr, $rankdata[$key]);
				}

				$insert_carrybv_arr = array();
				$insert_payout_arr  = array();
				$update_currbv_arr  = array();
				$update_dash_arr    = array();
				foreach ($getUserDetails as $key => $value) {

					list($usec, $sec) = explode(" ", microtime());

					$time_start = ((float) $usec + (float) $sec);

					$deduction              = array();
					$deduction['netAmount'] = $deduction['tdsAmt'] = $deduction['amt_pin'] = 0;

					$id = $getUserDetails[$key]['id'];

					if ($getUserDetails[$key]['l_ace_check_status'] > 0 || $getUserDetails[$key]['r_ace_check_status'] > 0) {
						$this->check_rank($id);
					}

					$left_bv  = $getUserDetails[$key]['curr_l_bv'];
					$right_bv = $getUserDetails[$key]['curr_r_bv'];

					$getVal       = min($left_bv, $right_bv);
					$getTotalPair = $getVal;
					if ($getTotalPair != 0) {

						//  $this->objBinaryIncome->PerPairBinaryIncomeNew($arrdata,$deduction);

						$perPair       = 1;
						$match_bv      = $getVal;
						$laps_position = 1;
						$before_l_bv   = $left_bv;
						$before_r_bv   = $right_bv;

						$up_left_bv  = custom_round($left_bv - $match_bv, 10);
						$up_right_bv = custom_round($right_bv - $match_bv, 10);

						$carry_l_bv = $up_left_bv;
						$carry_r_bv = $up_right_bv;

						//******add carry *************
						$carrybvArr1                = array();
						$carrybvArr1['user_id']     = $id;
						$carrybvArr1['payout_no']   = $payout_no;
						$carrybvArr1['before_l_bv'] = $left_bv;
						$carrybvArr1['before_r_bv'] = $right_bv;
						$carrybvArr1['match_bv']    = $match_bv;
						$carrybvArr1['carry_l_bv']  = $carry_l_bv;
						$carrybvArr1['carry_r_bv']  = $carry_r_bv;

						array_push($insert_carrybv_arr, $carrybvArr1);

						$dateTime = \Carbon\Carbon::now()->toDateTimeString();

						//*******Update CurrentAmountDetails **********

						$updateCuurentData              = array();
						$updateCuurentData['curr_l_bv'] = $up_left_bv;
						$updateCuurentData['curr_r_bv'] = $up_right_bv;
						$updateCuurentData['id']        = $id;

						array_push($update_currbv_arr, $updateCuurentData);

						/* $updateLeftBv = User::where('id', $id)->update($updateCuurentData);


						*/

						$netAmount = $deduction['netAmount'];
						$tdsAmt    = $deduction['tdsAmt'];
						$amt_pin   = $deduction['amt_pin'];

						$laps_amount = 0;

						$userrankdata = $getUserDetails[$key]['rank'];
						if ($getUserDetails[$key]['l_ace_check_status'] > 0 || $getUserDetails[$key]['r_ace_check_status'] > 0) {
							$this->check_rank($id);
							// get fresh rank
							$getUserRankDetails = User::select("rank")
								->where([['id', '=', $id]])
								//->limit(100000)
								->first();
							$userrankdata = $getUserRankDetails->rank;
						}

						if ($userrankdata != null) {
							/* $rankdata = DB::table('tbl_rank')
							->select('income_percentage','capping')
							->where('rank', '=', $userrankdata)
							->get();
							$binary_per = $rankdata[0]->income_percentage;
							$my_capping = $rankdata[0]->capping;*/

							$search_value = $userrankdata;
							$search_key   = 'rank';
							$result_key   = array_search($search_value, array_column($rankdata_arr, $search_key));
							$binary_per   = $rankdata_arr[$result_key]->income_percentage;
							$my_capping   = $rankdata_arr[$result_key]->capping;

							$amount = ($match_bv * $binary_per) / 100;

							$capping_amount = 0;
							if ($amount > $my_capping && $my_capping > 0) {
								$laps_amount = ($amount - $my_capping);
								$amount      = $my_capping;
							}
							$netAmount = $amount - $amt_pin;

							$payoutArr                = array();
							$payoutArr['user_id']     = $id;
							$payoutArr['amount']      = $amount;
							$payoutArr['net_amount']  = $netAmount;
							$payoutArr['tax_amount']  = 0;
							$payoutArr['amt_pin']     = $amt_pin;
							$payoutArr['left_bv']     = $left_bv;
							$payoutArr['right_bv']    = $right_bv;
							$payoutArr['match_bv']    = $match_bv;
							$payoutArr['laps_bv']     = 0;
							$payoutArr['laps_amount'] = $laps_amount;
							//$payoutArr['product_id'] = $product_id;
							$payoutArr['entry_time']      = $dateTime;
							$payoutArr['left_bv_before']  = $before_l_bv;
							$payoutArr['right_bv_before'] = $before_r_bv;
							$payoutArr['left_bv_carry']   = $carry_l_bv;
							$payoutArr['right_bv_carry']  = $carry_r_bv;
							$payoutArr['payout_no']       = $payout_no;
							/*$payoutArr['capping_amount']=$capping_amount;*/
							$payoutArr['rank']       = $userrankdata;
							$payoutArr['percentage'] = $binary_per;

							array_push($insert_payout_arr, $payoutArr);

							$binary_count++;

							//echo " Srno --> ".$binary_count." --> User Id --> ".$id." --> Binary Income --> ".$amount." --> Match --> ".$match_bv." --> Laps --> ".$laps_amount;

							$updateDashArr                   = array();
							$updateDashArr['binary_income']  = 'binary_income + ' . $amount . '';
							$updateDashArr['working_wallet'] = 'working_wallet + ' . $amount . '';
							$updateDashArr['id']             = $id;

							array_push($update_dash_arr, $updateDashArr);

							list($usec, $sec) = explode(" ", microtime());
							$time_end         = ((float) $usec + (float) $sec);
							$time             = $time_end - $time_start;
							//echo " --> time ".$time."\n";

						}
					}
				}

				// bulk operation

				// carry bv insert
				$count = 1;
				$array = array_chunk($insert_carrybv_arr, 1000);
				// dd($array);
				while ($count <= count($array)) {
					$key = $count - 1;
					Carrybv::insert($array[$key]);
					//echo $count." insert carry bv array ".count($array[$key])."\n";
					$count++;
				}

				// payout insert
				$count1 = 1;
				$array1 = array_chunk($insert_payout_arr, 1000);
				// dd($array1);
				while ($count1 <= count($array1)) {
					$key1 = $count1 - 1;
					PayoutHistory::insert($array1[$key1]);
					//echo $count1." insert payout array ".count($array1[$key1])."\n";
					$count1++;
				}

				$count2 = 1;

				$array2 = array_chunk($update_currbv_arr, 1000);

				while ($count2 <= count($array2)) {
					$key2       = $count2 - 1;
					$arrProcess = $array2[$key2];
					$ids        = implode(',', array_column($arrProcess, 'id'));
					$rbv_qry    = 'curr_r_bv = (CASE id';
					$lbv_qry    = 'curr_l_bv = (CASE id';
					foreach ($arrProcess as $key => $val) {
						$rbv_qry = $rbv_qry . " WHEN " . $val['id'] . " THEN " . $val['curr_r_bv'];
						$lbv_qry = $lbv_qry . " WHEN " . $val['id'] . " THEN " . $val['curr_l_bv'];
					}
					$rbv_qry   = $rbv_qry . " END)";
					$lbv_qry   = $lbv_qry . " END)";
					$updt_qry  = "UPDATE tbl_users SET " . $rbv_qry . " , " . $lbv_qry . " WHERE id IN (" . $ids . ")";
					$updt_user = DB::statement(DB::raw($updt_qry));

					//echo $count2." update user array ".count($arrProcess)."\n";
					$count2++;
				}

				$count3 = 1;

				$array3 = array_chunk($update_dash_arr, 1000);

				while ($count3 <= count($array3)) {
					$key3               = $count3 - 1;
					$arrProcess         = $array3[$key3];
					$ids                = implode(',', array_column($arrProcess, 'id'));
					$binary_income_qry  = 'binary_income = (CASE id';
					$working_wallet_qry = 'working_wallet = (CASE id';
					foreach ($arrProcess as $key => $val) {
						$binary_income_qry  = $binary_income_qry . " WHEN " . $val['id'] . " THEN " . $val['binary_income'];
						$working_wallet_qry = $working_wallet_qry . " WHEN " . $val['id'] . " THEN " . $val['working_wallet'];
					}
					$binary_income_qry  = $binary_income_qry . " END)";
					$working_wallet_qry = $working_wallet_qry . " END)";
					$updt_qry           = "UPDATE tbl_dashboard SET " . $binary_income_qry . " , " . $working_wallet_qry . " WHERE id IN (" . $ids . ")";
					$updt_user          = DB::statement(DB::raw($updt_qry));

					//echo $count3." update dash array ".count($arrProcess)."\n";
					$count3++;
				}
			}

			//echo "end ".now()."\n";
			//echo "\n Cron run successfully \n";

			$cronRunEntry               = new CronRunStatus();
			$cronRunEntry['cron_id']    = $croncheck->id;
			$cronRunEntry['run_status'] = 1;
			$cronRunEntry['run_time']   = $current_time;
			$cronRunEntry->save();
			if ($run_from == 'function') {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully from ' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		}
		// $current_time = \Carbon\Carbon::now()->toDateTimeString();
		// $msg          = "Binary Cron end at ".$current_time."\nTotal binary ids : ".$binary_count."\n";
		// //sendSMS($mobile,$msg);
		// //echo $msg;
		// list($usec, $sec) = explode(" ", microtime());
		// $time_end1        = ((float) $usec+(float) $sec);
		// $time             = $time_end1-$time_start1;
		//echo "\n Total cron time -> ".$time."\n";

	}

	function AddPowerUptoAdmin($signature = NULL, $run_from = 'function')
	{
		$croncheck = CronStatus::select('*')->where('name', $signature)->first();
		//dd($croncheck->status);

		if ($croncheck->status == 1) {
			$getPower = PowerBV::where([['cron_status', '0'], ['type', '2']])->orderBy('srno', 'ASC')->get();
			// dd($getPower);

			foreach ($getPower as $key => $value) {
				$srno     = $value->srno;
				$user_id  = $value->user_id;
				$position = $value->position;
				$power_bv = $value->power_bv;

				$amount   = $power_bv;

				$update_status = PowerBV::where([['cron_status', '0'], ['srno', $srno]])->limit(1)->update(['cron_status' => '1']);

				// echo "\n";
				// echo "Id -->".$srno."-->User Id-->".$user_id."-->Position-->".$position."-->Add Power-->".$power_bv;
				// echo "\n";
				//  dd(11);
				$virtual_parent_id1 = $user_id;
				$from_user_id_for_today_count = $user_id;

				$loopOn1 = true;
				if ($virtual_parent_id1 > 0) {
					do {
						$posDetails = User::where([['id', '=', $virtual_parent_id1]])->get();
						if (count($posDetails) <= 0) {

							$loopOn1 = false;
						} else {

							foreach ($posDetails as $k => $v) {
								$virtual_parent_id1 = $posDetails[$k]->virtual_parent_id;
								if ($virtual_parent_id1 > 0) {
									$position = $posDetails[$k]->position;
									if ($user_id != $virtual_parent_id1) {

										// $userExist = CurrentAmountDetails::where([['user_id', '=', $virtual_parent_id1]])->first();
										if ($position == 1) {
											$update = array();
											$update['l_bv'] = DB::raw('l_bv + ' . $amount);
											$update['power_l_bv'] = DB::raw('power_l_bv + ' . $amount);
											$update['curr_l_bv'] = DB::raw('curr_l_bv + ' . $amount);
											$updateOtpSta1 = User::where('id', $virtual_parent_id1)->update($update);
											// $updatePower1 = User::where('id', $virtual_parent_id1)->update(array('power_l_bv' => DB::raw('power_l_bv + ' . $amount . '')));
											// $updateCurr_l_bv = User::where('id', $virtual_parent_id1)->update(array('curr_l_bv' => DB::raw('curr_l_bv + ' . $amount . '')));
											// if (!empty($userExist)) {
											//     $updateLeftBv = CurrentAmountDetails::where('user_id', $virtual_parent_id1)->update(
											//             array('left_bv' => DB::raw('left_bv + ' . $amount . '')));
											// }
										} else if ($position == 2) {
											$update2 = array();
											$update2['r_bv'] = DB::raw('r_bv + ' . $amount);
											$update2['power_r_bv'] = DB::raw('power_r_bv + ' . $amount);
											$update2['curr_r_bv'] = DB::raw('curr_r_bv + ' . $amount);
											$updateOtpSta2 = User::where('id', $virtual_parent_id1)->update($update2);
											// $updatePower2 = User::where('id', $virtual_parent_id1)->update(array('power_r_bv' => DB::raw('power_r_bv + ' . $amount . '')));
											// $updateCurr_l_bv = User::where('id', $virtual_parent_id1)->update(array('curr_l_bv' => DB::raw('curr_l_bv + ' . $amount . '')));

											// if (!empty($userExist)) {
											//     $updateLeftBv = CurrentAmountDetails::where('user_id', $virtual_parent_id1)->update(
											//             array('right_bv' => DB::raw('right_bv + ' . $amount . '')));
											// }
										}
									}
								} else {
									$loopOn1 = false;
								}
							}
						}
					} while ($loopOn1 == true);
				}
			}
			
			$cronRunEntry               = new CronRunStatus();
			$cronRunEntry['cron_id']    = $croncheck->id;
			$cronRunEntry['run_status'] = 1;
			$cronRunEntry['run_time']   = \Carbon\Carbon::now()->toDateTimeString();;
			$cronRunEntry->save();
			
			if ($run_from == 'function') {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully from ' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} else {

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		}
	}

	function AddPowerUptoParticularLevel($signature = NULL, $run_from = 'function')
	{
		//   $croncheck = CronStatus::select('*')->where('name', $signature)->first();
		//dd($croncheck);
		//   if ($croncheck->status == 1) {
		$getPower = AddPowerToParticularId::where([['cron_status', '0']])->orderBy('id', 'ASC')->get();

		foreach ($getPower as $key => $value) {
			$srno     = $value->id;
			$user_id  = $value->from_power_id;
			$position = $value->position;
			$power_bv = $value->amount;
			$amount   = $power_bv;

			//   $update_status = AddPowerToParticularId::where([['cron_status','0'],['id',$srno]])->limit(1)->update(['cron_status'=>'1']);

			$update_status = DB::table('tbl_addPower_to_levels')
				->where([['cron_status', '0'], ['id', $srno]])
				->limit(1)->update(['cron_status' => '1']);

			$uptoId = DB::table('tbl_users')
				->select('virtual_parent_id')
				->where('id', $value->up_to_id)
				->first();

			$virtual_parent_id1 = $user_id;
			$from_user_id_for_today_count = $user_id;

			//   $uptoId= up to id == virtual parent id
			$loopOn1 = true;
			if ($virtual_parent_id1 > $uptoId->virtual_parent_id) {
				do {
					$posDetails = User::where([['id', '=', $virtual_parent_id1]])->get();
					if (count($posDetails) <= 0) {

						$loopOn1 = false;
					} else {

						foreach ($posDetails as $k => $v) {
							$virtual_parent_id1 = $posDetails[$k]->virtual_parent_id;
							if ($virtual_parent_id1 > $uptoId->virtual_parent_id) {
								$position = $posDetails[$k]->position;
								if ($user_id != $virtual_parent_id1) {

									if ($position == 1) {
										$update = array();
										$update['l_bv'] = DB::raw('l_bv + ' . $amount);
										$update['power_l_bv'] = DB::raw('power_l_bv + ' . $amount);
										$update['curr_l_bv'] = DB::raw('curr_l_bv + ' . $amount);
										$updateOtpSta1 = User::where('id', $virtual_parent_id1)->update($update);
									} else if ($position == 2) {
										$update2 = array();
										$update2['r_bv'] = DB::raw('r_bv + ' . $amount);
										$update2['power_r_bv'] = DB::raw('power_r_bv + ' . $amount);
										$update2['curr_r_bv'] = DB::raw('curr_r_bv + ' . $amount);
										$updateOtpSta2 = User::where('id', $virtual_parent_id1)->update($update2);
									}
								}
							} else {
								$loopOn1 = false;
							}
						}
					}
				} while ($loopOn1 == true);
			}
		}
		$cronRunEntry               = new CronRunStatus();
		//   $cronRunEntry['cron_id']    = $croncheck->id;
		$cronRunEntry['run_status'] = 1;
		$cronRunEntry['run_time']   = \Carbon\Carbon::now()->toDateTimeString();
		$cronRunEntry->save();
		if ($run_from == 'function') {
			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Cron Run Successfully from ' . $run_from;
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		} else {
			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Cron Run Successfully' . $run_from;
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
		//   }else{
		// 	  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		//   }

	}

	function RemovePowerUptoAdmin($signature = NULL, $run_from = 'function')
	{
		$croncheck = CronStatus::select('*')->where('name', $signature)->first();
		//dd($croncheck);
		if ($croncheck->status == 1) {
			$getPower = PowerBV::where([['cron_status', '0'], ['type', '4']])->orderBy('srno', 'ASC')->get();

			foreach ($getPower as $key => $value) {
				$srno     = $value->srno;
				$user_id  = $value->user_id;
				$position = $value->position;
				$power_bv = $value->power_bv;

				$amount   = $power_bv;


				$update_status = PowerBV::where([['cron_status', '0'], ['srno', $srno]])->limit(1)->update(['cron_status' => '1']);

				//echo "\n";

				//echo "Id -->".$srno."-->User Id-->".$user_id."-->Position-->".$position."-->Add Power-->".$power_bv;

				//echo "\n";

				$virtual_parent_id1 = $user_id;
				$from_user_id_for_today_count = $user_id;

				$loopOn1 = true;
				if ($virtual_parent_id1 > 0) {
					do {
						$posDetails = User::where([['id', '=', $virtual_parent_id1]])->get();
						if (count($posDetails) <= 0) {

							$loopOn1 = false;
						} else {

							foreach ($posDetails as $k => $v) {
								$virtual_parent_id1 = $posDetails[$k]->virtual_parent_id;
								if ($virtual_parent_id1 > 0) {
									$position = $posDetails[$k]->position;
									if ($user_id != $virtual_parent_id1) {


										// $userExist = CurrentAmountDetails::where([['user_id', '=', $virtual_parent_id1]])->first();
										if ($position == 1) {
											$update['l_bv'] = DB::raw('l_bv - ' . $amount);
											$update['power_l_bv'] = DB::raw('power_l_bv - ' . $amount);
											$update['curr_l_bv'] = DB::raw('curr_l_bv - ' . $amount);
											$updateOtpSta1 = User::where('id', $virtual_parent_id1)->update($update);
											// $updatePower1 = User::where('id', $virtual_parent_id1)->update(array('power_l_bv' => DB::raw('power_l_bv - ' . $amount . '')));
											// if (!empty($userExist)) {
											//     $updateLeftBv = CurrentAmountDetails::where('user_id', $virtual_parent_id1)->update(
											//             array('left_bv' => DB::raw('left_bv - ' . $amount . '')));
											// }
										} else if ($position == 2) {
											$update['r_bv'] = DB::raw('r_bv - ' . $amount);
											$update['power_r_bv'] = DB::raw('power_r_bv - ' . $amount);
											$update['curr_r_bv'] = DB::raw('curr_r_bv - ' . $amount);
											$updateOtpSta1 = User::where('id', $virtual_parent_id1)->update($update);
											// $updatePower2 = User::where('id', $virtual_parent_id1)->update(array('power_r_bv' => DB::raw('power_r_bv - ' . $amount . '')));

											// if (!empty($userExist)) {
											//     $updateLeftBv = CurrentAmountDetails::where('user_id', $virtual_parent_id1)->update(
											//             array('right_bv' => DB::raw('right_bv - ' . $amount . '')));
											// }
										}
									}
								} else {
									$loopOn1 = false;
								}
							}
						}
					} while ($loopOn1 == true);
				}
			}
			$cronRunEntry               = new CronRunStatus();
			$cronRunEntry['cron_id']    = $croncheck->id;
			$cronRunEntry['run_status'] = 1;
			$cronRunEntry['run_time']   = \Carbon\Carbon::now()->toDateTimeString();;
			$cronRunEntry->save();
			if ($run_from == 'function') {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully from ' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		}
	}

	function PassBvuptospecificUser($signature = NULL, $run_from = 'function')
	{

		$croncheck = CronStatus::select('*')->where('name', $signature)->first();
		//dd($croncheck);
		if($croncheck != null)
		{
		if ($croncheck->status == 1) {
			$getPower = PowerBV::where([['cron_status', '0'], ['type', '2']])->orderBy('srno', 'ASC')->get();

			foreach ($getPower as $key => $value) {
				// $srno     = $value->srno;
				$fromuser = 10061;
				$position = 1;
				$power_bv = 15000;

				$amount   = $power_bv;
				$uptouser = 10055;

				$update_status = PowerBV::where([['cron_status', '0'], ['srno', $srno]])->limit(1)->update(['cron_status' => '1']);


				if ($fromuser > 0 && $uptouser > 0) {

					$posDetails = User::where('id', '>=', $uptouser)->where('id', '<=', $fromuser)->orderBy('id', 'DESC')->get();


					foreach ($posDetails as $k => $v) {

						$virtual_parent_id1 = $posDetails[$k]->virtual_parent_id;
						$virtual_parent_id12 = $posDetails[$k]->id;
						if ($virtual_parent_id1 > 0 && $virtual_parent_id12 <= $uptouser) {
							$position = $posDetails[$k]->position;
							// dd($position);
							if ($fromuser != $virtual_parent_id1) {

								// $userExist = CurrentAmountDetails::where([['user_id', '=', $virtual_parent_id1]])->first();
								if ($position == 1) {
									$update = array();
									$update['l_bv'] = DB::raw('l_bv + ' . $amount);
									$update['power_l_bv'] = DB::raw('power_l_bv + ' . $amount);
									$update['curr_l_bv'] = DB::raw('curr_l_bv + ' . $amount);
									// dd($update);
									$updateOtpSta1 = User::where('id', $virtual_parent_id1)->update($update);
								} else if ($position == 2) {

									$update2 = array();
									$update2['r_bv'] = DB::raw('r_bv + ' . $amount);
									$update2['power_r_bv'] = DB::raw('power_r_bv + ' . $amount);
									$update2['curr_r_bv'] = DB::raw('curr_r_bv + ' . $amount);
									$updateOtpSta2 = User::where('id', $virtual_parent_id1)->update($update2);
								}
							}
						}
						// else {
						// 	$loopOn1 = false;
						// }
					}
				}
			}
			$cronRunEntry               = new CronRunStatus();
			$cronRunEntry['cron_id']    = $croncheck->id;
			$cronRunEntry['run_status'] = 1;
			$cronRunEntry['run_time']   = \Carbon\Carbon::now()->toDateTimeString();;
			$cronRunEntry->save();
			if ($run_from == 'function') {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully from ' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Cron Run Successfully' . $run_from;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		}
		}
		else{
			$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[404];
				$arrMessage = 'This Cron is Inactive';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This Cron is Inactive', '');
		}
	}

	function getCurrentDateTime()
	{
		$date = \Carbon\Carbon::now();
		return $date->toDateTimeString();
	}
}
