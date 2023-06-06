<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Controllers\user\TransactionConfiController;
use App\Http\Controllers\user\LevelController;

use App\Models\ContestPrizeSetting;
use App\Models\Dashboard;
use App\Models\PayoutHistory;

use App\Models\ProjectSetting;

use App\Models\Topup;
use App\Models\UserContestAchievement;
use App\Models\WithdrawalConfirmed;
use App\Models\UserSettingFund;
use App\Models\HsccBonus;
use App\Models\HsccBonusSetting;
use App\Models\Contact;
use App\Models\DailyBonus;
use App\Models\DailyBinaryIncome;
use App\Models\WithdrawPending;
use DateTime;
use App\Models\User;
use Auth;
use Config;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;

class DashboardController extends Controller
{
	protected $projects;

	public function __construct(TransactionConfiController $transaction, Request $request)
	{
		$this->homepageDate = Config::get('constants.settings.homepageDate');
		$date               = \Carbon\Carbon::now();
		$this->today        = $date->toDateTimeString();
		$this->transaction  = $transaction;
		$this->statuscode   = Config::get('constants.statuscode');

		$this->middleware(function ($request, $next) {
			//$this->projects = Auth::user()->temp_info; temp close

			$this->projects = 'c3fcd9e52fd775c43c9553a961bfc52c';

			return $next($request);
		});
		// $req_temp_info = $request->header('User-Agent');
		// $result        = check_user_authentication_browser($req_temp_info,  $this->projects);
		// if ($result == false) {
		// 	return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
		// }
	}

	public function index_old(){
		$responseData =  $this->getUserDashboardDetails();

		print_r($responseData);
		//return view('user.dashboard.dashboard');

	}

	public function getUserBonusInfo(){
		try {
			$id = Auth::user()->id;
		 	$get_bonus_setting = HsccBonusSetting::get();
			$arrBonusData=[];
			foreach ($get_bonus_setting as $key => $value) {
				$bonusStr = 'bonus_percentage_'.$value->percentage.'_amt';
				$bonus_amt= HsccBonus::selectRaw('COALESCE(SUM(amount),0) as bonus_amount')->where('bonus_id', $value->id)->where('user_id', $id)->pluck('bonus_amount')->first();
                $arrBonusData[$bonusStr] = custom_round($bonus_amt,2);
            }
            return $arrBonusData;
			/*
			if (count($arrBonusData)>0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrBonusData);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			*/

		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function getUserBonusInfo_old(){
		try {
			//$id = Auth::user()->id; temp close
		 $id = 10819;
			$get_bonus_setting = HsccBonusSetting::get();
			$arrBonusData=array();
			foreach ($get_bonus_setting as $key => $value) {
				$bonusStr = 'bonus_percentage_'.$value->percentage.'_amt';
				$bonus_amt= HsccBonus::selectRaw('COALESCE(SUM(amount),0) as bonus_amount')->where('bonus_id', $value->id)->where('user_id', $id)->pluck('bonus_amount')->first();
				$arrBonusData[$bonusStr] = custom_round($bonus_amt,2);
			}
			/*
			if (count($arrBonusData)>0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrBonusData);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
			*/

		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/**
	 * Get USER Dashboard currecy  details
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getUserDashboardDetails(Request $request)
	{

		// check user is from same browser or not
		/*$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info, Auth::user()
			->temp_info);
		if ($result == false) {
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
		}
*/

			$id = Auth::user()->id;

			// get Dashboard Details
			$getDetails = User::select('tbl_users.l_ace','tbl_users.l_c_count','tbl_users.r_c_count', 'tbl_users.r_ace', 'tbl_users.l_herald', 'tbl_users.r_herald', 'tbl_users.l_crusader', 'tbl_users.r_crusader', 'tbl_users.l_guardian', 'tbl_users.r_guardian', 'tbl_users.l_commander', 'tbl_users.r_commander', 'tbl_users.l_valorant', 'tbl_users.r_valorant', 'tbl_users.l_legend', 'tbl_users.r_legend', 'tbl_users.l_relic', 'tbl_users.r_relic', 'tbl_users.contest_lbv', 'tbl_users.contest_rbv', 'tbl_users.l_almighty', 'tbl_users.r_almighty', 'tbl_users.l_conqueror', 'tbl_users.r_conqueror', 'tbl_users.l_titan', 'tbl_users.r_titan', 'tbl_users.l_lmmortal', 'tbl_users.r_immortal', 'tbl_users.is_franchise','tbl_users.topup_status', 'tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.rank', 'tbl_users.btc_address', 'tbl_dashboard.*')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.status', '=', 'Active'], ['tbl_users.type', '=', ''], ['tbl_dashboard.id', '=', $id]])->get();


			if (!empty($getDetails) && count($getDetails) > 0) {


				$supermatching_income = custom_round($getDetails[0]->supper_maching_income, 2);
				/*$freedom_income	= freedomclubincome::where('user_id', $id)->sum('amount');*/
				$freedom_income = custom_round($getDetails[0]->freedom_club_income, 2);

				$direct_list = User::where('ref_user_id', $id)->where("topup_status","=","1")->count('id');

				$topupsum = Topup::where('id', $id)->sum('amount');

				$withdraw_amount       = WithdrawalConfirmed::where('id', $id)->sum('amount');
				$total_withdraw_amount = WithdrawPending::where('id', $id)->whereIn('status', array(1, 0))->sum('amount');


				//need to ask
				$topup_rec = Topup::selectRaw('SUM(amount) as total_deposit,SUM(total_income) as capping,percentage,last_roi_entry_time, pin, amount_roi, amount,total_income,duration, SUM(amount*percentage*duration/100) as total_roi')->where('id', $id)->where('roi_status','=', '"Active"')->orderBy('srno', 'desc')->first();
				$total_income_sum=Topup::where('id', $id)->sum('total_income');

				// dd($topup_rec);
				$percentage3x = 0;
				$total_income = 0;
				$total_roi = 0;
				$roisum = 0;
				$capping3x = 0;
				if (!empty($topup_rec))
				{
					$roisum = DailyBonus::where('id', $id)->sum('amount');
					// $total_roi = ($topup_rec->percentage/100) * $topup_rec->duration * $topup_rec->total_deposit;
					$total_roi=$topup_rec->total_roi;
					/*dd($roisum,$topup_rec->amount_roi,$topup_rec->duration,$topup_rec->total_deposit);*/
					$capping3x = $total_income_sum;

					$total_income = ($getDetails[0]->roi_income) + ($getDetails[0]->binary_income) + ($getDetails[0]->direct_income)+ ($getDetails[0]->hscc_bonus_income);

				// $percentage3x = ($total_income * 100) / $capping3x;


				if($total_income < $capping3x)
				{
					$arrData['acpercentage3x'] = round(($total_income * 100)/$capping3x,2);
				}elseif($total_income >= $capping3x)
				{
					$arrData['acpercentage3x']=100;
				}
				if($total_income == 0 && $capping3x == 0)
				{
					$arrData['acpercentage3x']=0;
				}

				$arrData['last_topup'] = custom_round($topup_rec->amount, 2);
				}else{
				$arrData['last_topup'] = custom_round('0', 2);
				}
				$arrData['capping3x'] = custom_round($capping3x, 2);
				/*$arrData['acpercentage3x'] = custom_round($percentage3x, 2);*/
				$arrData['total_income'] = ($total_income);
				$hscc_bonus_income=$getDetails[0]->hscc_bonus_income;
				$hscc_bonus_wallet=$getDetails[0]->hscc_bonus_wallet;
				$hscc_bonus_wallet_withdraw=$getDetails[0]->hscc_bonus_wallet_withdraw;
				$hscc_bonus_wallet_balance=$hscc_bonus_wallet-$hscc_bonus_wallet_withdraw;



				$arrData['hscc_bonus'] = custom_round($hscc_bonus_income, 2);
				$arrData['hscc_bonus_wallet'] = custom_round($hscc_bonus_wallet, 2);
				$arrData['hscc_bonus_wallet_withdraw'] = custom_round($hscc_bonus_wallet_withdraw, 2);
				$arrData['hscc_bonus_balance'] = custom_round($hscc_bonus_wallet_balance, 2);
				$arrData['hscc_bonus_new'] = $hscc_bonus_income;

				$arrData['received_roi'] = round($roisum, 2);
				$arrData['remain_roi'] = round($total_roi - $roisum, 2);
				// $arrData['topup_amount'] = round($topupsum , 7);
				$arrData['direct_list'] = $direct_list;
				//$topupsuminves = Topup::where('id', $id)->sum('amount');
				$arrData['supermatching_income'] = custom_round($supermatching_income, 2);
				$arrData['freedom_income']       = custom_round($freedom_income, 2);
				/*$arrData['total_investment_dashboard'] = round($topupsum, 2);*/
				$arrData['total_deposit']          = custom_round($topupsum, 2);
				$arrData['total_left_bv']          = $getDetails[0]->l_bv;
				$arrData['total_right_bv']         = $getDetails[0]->r_bv;
				$arrData['topup_status']           = $getDetails[0]->topup_status;
				$arrData['coin']                   = $getDetails[0]->coin;
				$arrData['coin_withdrawal']        = $getDetails[0]->coin_withdrawal;
				$arrData['total_coin']             = $getDetails[0]->coin - $getDetails[0]->coin_withdrawal;
				$arrData['btc']                    = custom_round($getDetails[0]->btc, 2);
				$arrData['usd']                    = custom_round($getDetails[0]->usd, 2);
				$arrData['total_withdrawl_amount'] = custom_round($withdraw_amount, 2);
				$arrData['total_withdraw_amount']  = custom_round($total_withdraw_amount, 2);
				$arrData['is_franchise']           = $getDetails[0]->is_franchise;

				// $arrData['total_investment'] = round($getDetails[0]->total_investment, 2);
				$arrData['active_investment'] = custom_round($getDetails[0]->active_investment, 2);
				$arrData['total_withdraw']    = custom_round($getDetails[0]->total_withdraw, 2);
				$arrData['total_profit']      = custom_round($getDetails[0]->total_profit, 2);
				// $arrData['total_investment'] = round($getDetails[0]->total_investment, 2);
				$arrData['active_investment']      = custom_round($getDetails[0]->active_investment, 2);
				$arrData['franchise_income']       = custom_round($getDetails[0]->franchise_income, 2);
				$arrData['direct_income']          = custom_round($getDetails[0]->direct_income, 2);
				$arrData['direct_income_withdraw'] = custom_round($getDetails[0]->direct_income_withdraw, 2);
				$arrData['direct_wallet_balance']  = ($arrData['direct_income'] - $arrData['direct_income_withdraw']);

				$arrData['level_income']          = custom_round($getDetails[0]->level_income, 2);
				$arrData['level_income_roi']      = custom_round($getDetails[0]->level_income_roi, 2);
				$arrData['level_income_withdraw'] = custom_round($getDetails[0]->level_income_withdraw, 2);

				$arrData['level_income_balance'] = ($arrData['level_income'] - $arrData['level_income_withdraw']);

				$arrData['roi_income']          = ($getDetails[0]->roi_income);
				$arrData['roi_income_withdraw'] = ($getDetails[0]->roi_income_withdraw);
				$arrData['roi_wallet']          = ($getDetails[0]->roi_wallet);
				$arrData['roi_wallet_withdraw'] = ($getDetails[0]->roi_wallet_withdraw);

				$arrData['roi_wallet_balance'] = ($arrData['roi_wallet'] - $arrData['roi_wallet_withdraw']);


				$arrData['roi_wallet_balance_new'] = ($arrData['roi_income'] - $arrData['roi_income_withdraw']);
				$arrData['binary_income']          = ($getDetails[0]->binary_income);
				$arrData['binary_income_withdraw'] = ($getDetails[0]->binary_income_withdraw);
				$pre_binary_income = PayoutHistory::where('user_id', $id)->sum('amount');
				$arrData['pre_binary_income'] = custom_round($pre_binary_income,2);

				$arrData['binary_income_balance'] = ($arrData['binary_income'] - $arrData['binary_income_withdraw']);

				$arrData['direct_income_balance'] = ($arrData['direct_income'] - $arrData['direct_income_withdraw']);

				$arrData['top_up_wallet']          = custom_round($getDetails[0]->top_up_wallet, 2);
				$arrData['top_up_wallet_withdraw'] = custom_round($getDetails[0]->top_up_wallet_withdraw, 2);

				$arrData['top_up_Wallet_balance'] = custom_round(($arrData['top_up_wallet'] - $arrData['top_up_wallet_withdraw']), 2);

				$arrData['fund_wallet']          = custom_round($getDetails[0]->fund_wallet, 2);



				$arrData['fund_wallet_withdraw'] = custom_round($getDetails[0]->fund_wallet_withdraw, 2);




				$arrData['fund_Wallet_balance'] = custom_round(($arrData['fund_wallet'] - $arrData['fund_wallet_withdraw']), 2);

				$arrData['transfer_wallet']          = custom_round($getDetails[0]->transfer_wallet, 2);
				$arrData['transfer_wallet_withdraw'] = custom_round($getDetails[0]->transfer_wallet_withdraw, 2);




				$arrData['chain_income_wallet_wihdrwal']          = custom_round($getDetails[0]->chain_income_wallet_wihdrwal, 2);

				$arrData['chain_bonus_wallet']          = custom_round($getDetails[0]->chain_bonus_wallet, 2);

				$arrData['chain_income_wallet']          = custom_round($getDetails[0]->chain_income_wallet, 2);
				$arrData['chain_bonus_wallet_withdrwal']          = custom_round($getDetails[0]->chain_bonus_wallet_withdrwal, 2);

				$arrData['chain_bonus_wallet_balance'] = custom_round(($arrData['chain_bonus_wallet'] - $arrData['chain_bonus_wallet_withdrwal']), 2);

				$arrData['transfer_Wallet_balance'] = custom_round(($arrData['transfer_wallet'] - $arrData['transfer_wallet_withdraw']), 2);

				$arrData['working_wallet']          = ($getDetails[0]->working_wallet);
				$arrData['working_wallet_withdraw'] = ($getDetails[0]->working_wallet_withdraw);

				$work = ($arrData['working_wallet'] - $arrData['working_wallet_withdraw']);
				$arrData['total_wallet_balance'] = ($hscc_bonus_wallet_balance + $work + $arrData['roi_wallet_balance']);

				$arrData['working_wallet'] = ($work);

				$arrData['working_wallet_balance'] = $arrData['direct_income_balance']-$arrData['binary_income_balance'];
				$arrData['total_earnings']          = custom_round($getDetails[0]->working_wallet, 2);

				$arrData['leadership_income']          = custom_round($getDetails[0]->leadership_income, 2);
				$arrData['leadership_income_withdraw'] = custom_round($getDetails[0]->leadership_income_withdraw, 2);

				$arrData['leadership_Wallet_balance'] = ($arrData['leadership_income'] - $arrData['leadership_income_withdraw']);

				$arrData['level_income_roi']          = custom_round($getDetails[0]->level_income_roi, 2);
				$arrData['level_income_roi_withdraw'] = custom_round($getDetails[0]->level_income_roi_withdraw, 2);

				$arrData['level_income_roi_balance'] = ($arrData['level_income_roi'] - $arrData['level_income_roi_withdraw']);

				$arrData['upline_income']          = custom_round($getDetails[0]->upline_income, 2);
				$arrData['upline_income_withdraw'] = custom_round($getDetails[0]->upline_income_withdraw, 2);

				$arrData['upline_balance'] = ($arrData['upline_income'] - $arrData['upline_income_withdraw']);

				$arrData['award_income']          = custom_round($getDetails[0]->award_income, 2);
				$arrData['award_income_withdraw'] = custom_round($getDetails[0]->award_income_withdraw, 2);

				$arrData['award_balance'] = ($arrData['award_income'] - $arrData['award_income_withdraw']);

				$arrData['promotional_income']      = custom_round($getDetails[0]->promotional_income, 2);
				$arrData['passive_income']          = custom_round($getDetails[0]->passive_income, 2);
				$arrData['passive_income_withdraw'] = custom_round($getDetails[0]->passive_income_withdraw, 2);
				$arrData['passive_income_balance']  = custom_round(($arrData['passive_income'] - $arrData['passive_income_withdraw']), 2);

				//$arrData['toatl_binary_income_new'] =

				//$arrData['roi_wallet_balance_new']+($arrData['fund_wallet'] - $arrData['fund_wallet_withdraw'])+$arrData['hscc_bonus']+$arrData['working_wallet_balance']+$arrData['direct_income'];

				$arrData['server_time']  = \Carbon\Carbon::now()->format('H:i:s');
				$arrData['joining_date'] = $getDetails[0]->entry_time;
				$arrData['user_id']      = $getDetails[0]->user_id;
				$arrData['fullname']     = $getDetails[0]->fullname;

				$arrData['l_ace']       = $getDetails[0]->l_ace;
				$arrData['r_ace']       = $getDetails[0]->r_ace;
				$arrData['l_herald']    = $getDetails[0]->l_herald;
				$arrData['r_herald']    = $getDetails[0]->r_herald;
				$arrData['l_crusader']  = $getDetails[0]->l_crusader;
				$arrData['r_crusader']  = $getDetails[0]->r_crusader;
				$arrData['l_guardian']  = $getDetails[0]->l_guardian;
				$arrData['r_guardian']  = $getDetails[0]->r_guardian;
				$arrData['l_commander'] = $getDetails[0]->l_commander;
				$arrData['r_commander'] = $getDetails[0]->r_commander;
				$arrData['l_valorant']  = $getDetails[0]->l_valorant;
				$arrData['r_valorant']  = $getDetails[0]->r_valorant;
				$arrData['l_legend']    = $getDetails[0]->l_legend;
				$arrData['r_legend']    = $getDetails[0]->r_legend;
				$arrData['l_relic']     = $getDetails[0]->l_relic;
				$arrData['r_relic']     = $getDetails[0]->r_relic;
				$arrData['l_almighty']  = $getDetails[0]->l_almighty;
				$arrData['r_almighty']  = $getDetails[0]->r_almighty;
				$arrData['l_conqueror'] = $getDetails[0]->l_conqueror;
				$arrData['r_conqueror'] = $getDetails[0]->r_conqueror;
				$arrData['l_titan']     = $getDetails[0]->l_titan;
				$arrData['r_titan']     = $getDetails[0]->r_titan;
				$arrData['l_lmmortal']  = $getDetails[0]->l_lmmortal;
				$arrData['r_immortal']  = $getDetails[0]->r_immortal;

				$arrData['total_left_rank_count']  = $getDetails[0]->l_ace + $getDetails[0]->l_herald + $getDetails[0]->l_crusader + $getDetails[0]->l_guardian + $getDetails[0]->l_commander + $getDetails[0]->l_valorant + $getDetails[0]->l_legend + $getDetails[0]->l_relic + $getDetails[0]->l_almighty + $getDetails[0]->l_conqueror + $getDetails[0]->l_titan + $getDetails[0]->l_lmmortal;
				$arrData['total_right_rank_count'] = $getDetails[0]->r_ace + $getDetails[0]->r_herald + $getDetails[0]->r_crusader + $getDetails[0]->r_guardian + $getDetails[0]->r_commander + $getDetails[0]->r_valorant + $getDetails[0]->r_legend + $getDetails[0]->r_relic + $getDetails[0]->r_almighty + $getDetails[0]->r_conqueror + $getDetails[0]->r_titan + $getDetails[0]->r_immortal;

				$getRank = PayoutHistory::where('user_id',$id)->orderBy('id','desc')->pluck('designation')->first();

				if(empty($getRank))
				{
					$arrData['rank'] = "You dont have Rank Yet";
				}
				else{
					$arrData['rank'] = $getRank;
				}


				$arrData['lid']         = $getDetails[0]->l_c_count;
				$arrData['rid']         = $getDetails[0]->r_c_count;
				$arrData['contest_rbv'] = $getDetails[0]->contest_rbv;
				$arrData['contest_lbv'] = $getDetails[0]->contest_lbv;

				$arrData['sent_balance']     = custom_round($getDetails[0]->sent_balance, 2);
				$arrData['received_balance'] = custom_round($getDetails[0]->received_balance, 2);

				$arrData['ip_address'] = $_SERVER['REMOTE_ADDR'];

				$current_time            = getTimeZoneByIP($arrData['ip_address']);
				$arrData['current_time'] = $current_time;
				$arrData['total_income'] = $total_income;
				//$arrData['total_income_new'] =  $arrData['roi_wallet_balance_new'] + $arrData['direct_income_balance'] + $arrData['binary_income_balance']+ $getDetails[0]->hscc_bonus_income;

				$arrData['total_income_new'] =   $arrData['direct_income_balance'] + $arrData['binary_income_balance'];

				$date = new DateTime('next monday');
				$thisMonth = $date->format('Y-m-d H:i:s');

				$arrData['current_date'] = $thisMonth;
				$arrData['current_day'] = date('D');
				$path    = Config::get('constants.settings.domainpath-vue');
				$dataArr = array();

				//$arrData['link'] = $path . 'register?ref_id=' . Auth::user()->unique_user_id/*.'&' .'position='.


				$arrData['link'] = $path . 'register?ref_id=' . 'HSCC7544835'/*.'&' .'position='.


				Auth::user()->position*/;


	             $bonusData =  $this->getUserBonusInfo();

				//$dummy = ['status'=>1];

				$request = new \Illuminate\Http\Request();
				$request->replace(['status' => 1]);

				$result = (new LevelController)->getTeamStatus($request);


				$request = new \Illuminate\Http\Request();
				$request->replace(['status' => 0]);

				$result1= (new LevelController)->getTeamStatus($request);
				$arrData['active'] = $result;
				$arrData['inactive'] = $result1;
				return view('user.dashboard.dashboard')->with(compact('arrData', 'bonusData'));

			}

	}

	public function getTimerDetails() {
		try {
			$user = Auth::user();
			// get Dashboard Details

			if (!empty($user)) {
				/*$getRecord = BinaryCapping::where('user_id',$user->id)->where('status',0)->orderBy('entry_time','desc')->first();*/
				$FinalArray = array();
				/*if(!empty($getRecord))
				{*/


				$FinalArray['firstDateofcycle'] = $nextEntrydate=date('Y-m-d H:i:s', strtotime($this->today));
				if (Auth::user()->three_x_achieve_status == 1) {

					$FinalArray['lastDayofcycle'] = date('Y-m-d H:i:s', strtotime(Auth::user()->three_x_achieve_date. ' + 2 days'));
				}else{
					$FinalArray['lastDayofcycle'] = date('Y-m-d H:i:s', strtotime($this->today));
				}
				$FinalArray['three_x_achieve_status'] = Auth::user()->three_x_achieve_status;
				// $FinalArray['totalDayCount'] = $getRecord->cron_run_count;
				// dd($FinalArray);
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $FinalArray);
				// }


			}
				else
				{
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data not found';
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
	 * Get PerfectMoney
	 **/

	public function getUserHeader(Request $request)
	{

		// check user is from same browser or not
		$req_temp_info = $request->header('User-Agent');
		$result        = check_user_authentication_browser($req_temp_info, Auth::user()
			->temp_info);
		if ($result == false) {
			return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
		}

		try {
			$id = Auth::user()->id;

			$getDetails = User::select('tbl_users.user_id', 'tbl_dashboard.total_withdraw', 'tbl_dashboard.active_investment', 'tbl_dashboard.roi_income', 'tbl_dashboard.roi_income_withdraw', 'tbl_dashboard.direct_income', 'tbl_dashboard.direct_income_withdraw', 'tbl_dashboard.working_wallet', 'tbl_dashboard.working_wallet_withdraw', 'tbl_dashboard.binary_income', 'tbl_dashboard.binary_income_withdraw')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.status', '=', 'Active'], ['tbl_users.type', '=', ''], ['tbl_dashboard.id', '=', $id]])->get();

			if (!empty($getDetails) && count($getDetails) > 0) {

				$directincome = $getDetails[0]->direct_income - $getDetails[0]->direct_income_withdraw;
				$roiincome = $getDetails[0]->roi_income - $getDetails[0]->roi_income_withdraw;
				$binaryincome = $getDetails[0]->binary_income - $getDetails[0]->binary_income_withdraw;
				$working_wallet = $getDetails[0]->working_wallet - $getDetails[0]->working_wallet_withdraw;


				$arrData['total_withdraw']    = custom_round($getDetails[0]->total_withdraw, 2);
				$arrData['active_investment']    = custom_round($getDetails[0]->active_investment, 2);

				$arrData['direct_income']     = custom_round($directincome, 2);
				$arrData['roi_income']        = custom_round($roiincome, 2);
				$arrData['binary_income']     = custom_round($binaryincome, 2);
				$arrData['working_wallet']     = custom_round($working_wallet, 2);



				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}


	public function getPerfectMoneyCred()
	{
		try {
			$user = Auth::user();
			// get Dashboard Details

			if (!empty($user)) {
				$getDetails = Config::get('constants.perfectmoney_credential');
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $getDetails);
			}
		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	/**
	 * Get PerfectMoney
	 **/
	public function getManualPayInfo()
	{
		try {
			dd('Not in use');
			$user = DB::table('tbl_manual_pay')->where('status', 1)->get();

			if (!empty($user)) {
				$getDetails = $user;
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $getDetails);
			}
		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	/************* WEBSITE ADMIN AREA TO PUT PERFECT MONEY CREDENTIALS ********/
	// PM = PERFECT MONEY
	/*************************************************************************/
	//Check Valid PerfectMoney Account
	/*  public function getNewPerfectmoney(){
	try{

	$pm_member_id = "1000589";
	$pm_phrase = "U78bD6Fd5P5pOTopbziIyaCUI";
	$pm_usd_account = "U22384420";
	$account_id="U17876799";
	$pmamount="1";
	$payment_id="PAYMENT ID GENERATED BY WEBSITE";

	$f=fopen('https://perfectmoney.is/acct/acc_name.asp?AccountID='.$pm_member_id.'&PassPhrase='.$pm_phrase.'&Account='.$account_id, 'rb');
	if($f===false)
	{
	echo 'Invalid url parameter';
	}
	$out="";
	while(!feof($f)) $out.=fgets($f);
	fclose($f);
	$error = explode(":",$out);
	if($error[0] == "ERROR")
	{
	echo "Perfect Money Account ID not Valid.";
	}

	}catch(Exception $e){
	//dd($e);

	}
	 */

	/**
	 * Get Working Balance
	 **/

	public function getWROIBalance()
	{
		try {
			$id = Auth::user()->id;
			// get Dashboard Details
			$getDetails = Dashboard::where('id', $id)->select('roi_wallet', 'roi_wallet_withdraw')->first();
			$bal        = (($getDetails->roi_wallet - $getDetails->roi_wallet_withdraw));
			if ($bal > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			}
		} catch (Exception $e) {
			$bal        = 0;
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
		}
	}

	/**
	 * Get HSCC Bonus Balance
	 **/

	public function getHBonusBalance()
	{
		try {
			$id = Auth::user()->id;
			// get Dashboard Details
			$getDetails = Dashboard::where('id', $id)->select('hscc_bonus_wallet', 'hscc_bonus_wallet_withdraw')->first();
			$bal        = (($getDetails->hscc_bonus_wallet - $getDetails->hscc_bonus_wallet_withdraw));
			if ($bal > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			}
		} catch (Exception $e) {
			$bal        = 0;
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
		}
	}


	public function getWorkingBalance()
	{

		try {
			$id = Auth::user()->id;
			// get Dashboard Details
			$getDetails = Dashboard::where('id', $id)->select('working_wallet', 'working_wallet_withdraw')->first();
			$bal        = (($getDetails->working_wallet - $getDetails->working_wallet_withdraw));
			if ($bal > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			}
		} catch (Exception $e) {
			//dd($e);
			$bal        = 0;
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
		}
	}

	public function getFundBalance()
	{
		try {
			$id = Auth::user()->id;
			// get Dashboard Details
			$getDetails = Dashboard::where('id', $id)->select('fund_wallet', 'fund_wallet_withdraw')->first();
			$bal        = (($getDetails->fund_wallet - $getDetails->fund_wallet_withdraw));
			if ($bal > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			}
		} catch (Exception $e) {
			$bal        = 0;
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
		}
	}

	/**
	 * Get Working Balance
	 **/
	public function getTopupBalance()
	{
		try {
			$id = Auth::user()->id;
			// get Dashboard Details
			$getDetails = Dashboard::where('id', $id)->select('top_up_wallet', 'top_up_wallet_withdraw', 'fund_wallet', 'fund_wallet_withdraw')->first();
			if (!empty($getDetails)) {
				$bal['purchase_wallet'] = ($getDetails->top_up_wallet - $getDetails->top_up_wallet_withdraw);
				$bal['fund_wallet']     = ($getDetails->fund_wallet - $getDetails->fund_wallet_withdraw);
				$arrStatus              = Response::HTTP_OK;
				$arrCode                = Response::$statusTexts[$arrStatus];
				$arrMessage             = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
			}
		} catch (Exception $e) {
			$bal        = 0;
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
		}
	}
	public function getWalletBalance(Request $request)
	{
		try {
			$rules = array(
				'wallet_type' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				return sendresponse(Response::HTTP_NOT_FOUND, Response::$statusTexts[404], $validator, []);
			}
			$id          = Auth::user()->id;
			$wallet_type = $request->wallet_type;
			$balance     = 0;
			if ($wallet_type == "working_wallet") {
				$balance = Dashboard::where('id', $id)->selectRaw('round(working_wallet-working_wallet_withdraw,2) as balance')->pluck('balance')->first();
			} elseif ($wallet_type == "top_up_wallet") {
				$balance = Dashboard::where('id', $id)->selectRaw('round(top_up_wallet-top_up_wallet_withdraw,2) as balance')->pluck('balance')->first();
			} elseif ($wallet_type == "roi_wallet") {
				$balance = Dashboard::where('id', $id)->selectRaw('round(roi_wallet-roi_wallet_withdraw,2) as balance')->pluck('balance')->first();
			} elseif ($wallet_type == "hscc_bonus_wallet") {
				$balance = Dashboard::where('id', $id)->selectRaw('round(hscc_bonus_wallet-hscc_bonus_wallet_withdraw,2) as balance')->pluck('balance')->first();
			} elseif ($wallet_type == "fund_wallet") {
				$balance = Dashboard::where('id', $id)->selectRaw('round(fund_wallet-fund_wallet_withdraw,2) as balance')->pluck('balance')->first();
			} elseif ($wallet_type == "passive_income") {
				$balance = Dashboard::where('id', $id)->selectRaw('round(passive_income-passive_income_withdraw,2) as balance')->pluck('balance')->first();
			} elseif ($wallet_type == "passive_income_withdraw") {
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
			}

			if ($balance > 0) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $balance);
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $balance);
			}
		} catch (Exception $e) {
			//dd($e);
			$bal        = 0;
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
		}
	}
	/**
	 * show homepage data
	 * @param  Request $request [description]
	 * @return [type]           [Json Array]
	 */
	public function homepageUserdata()
	{
		try {
			$date = \Carbon\Carbon::parse($this->homepageDate);
			$now  = \Carbon\Carbon::now();

			$getDetails = User::join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->selectRaw('sum(active_investment) as Total_deposit,sum(total_withdraw) as Total_withdraw,count(*) as Total_users')->where([['tbl_users.type', '=', '']])->get();
			if (!empty($getDetails)) {
				$diff = $date->diffInDays($now);

				$arr                   = array();
				$arr['Datediff']       = $diff;
				$arr['Total_deposit']  = $getDetails[0]->Total_deposit;
				$arr['Total_withdraw'] = $getDetails[0]->Total_withdraw;
				$arr['Total_users']    = $getDetails[0]->Total_users;

				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arr);
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
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
	 * Get USER Dashboard currecy  details
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function getUsernavigationDetails(Request $request)
	{

		try {
			$id = Auth::user()->id;
			// get Dashboard Details
			$getDetails = User::select('tbl_users.user_id', 'tbl_users.rank')

				->where([['tbl_users.status', '=', 'Active'], ['tbl_users.type', '=', ''], ['tbl_users.id', '=', $id]])
				->get();
			$proSetting = ProjectSetting::select('ico_status', 'ico_admin_error_msg')->first();

			if (!empty($getDetails) && count($getDetails) > 0) {

				$direct_list            = User::where('ref_user_id', $id)->count();
				$arrData['server_time'] = \Carbon\Carbon::now()->format('H:i:s');
				$arrData['user_id']     = $getDetails[0]->user_id;

				$arrData['rank'] = $getDetails[0]->rank;

				$arrData['ip_address'] = $_SERVER['REMOTE_ADDR'];

				$current_time                   = getTimeZoneByIP($arrData['ip_address']);
				$arrData['current_time']        = $current_time;
				$arrData['ico_status']          = $proSetting->ico_status;
				$arrData['ico_admin_error_msg'] = $proSetting->ico_admin_error_msg;

				// dd($arrData['current_time']);
				$dataArr = array();

				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
			} else {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function getIcoStatus()
	{

		try {

			$arrData = ProjectSetting::select('ico_status', 'ico_admin_error_msg')->first();

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function addContact(Request $request)
	{
		//  dd($request);
		try {
			$messsages = array(
				'fullname.max' => 'Fullname should be max 20 char',
				'fullname.email' => 'Email should be in format abc@abc.com',
				'content.max' => 'Content should be max 200 char',
				'message.max' => 'Message should be max 1000 char',

			);
			$rules = array(
				'fullname' => 'required|max:20',
				'email' => 'required|email',
				'mobile' => 'required|max:20',

			);


			$validator = checkvalidation($request->all(), $rules, $messsages);

			if (!empty($validator)) {

				$arrStatus   = Response::HTTP_NOT_FOUND;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = $validator;
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$data = array();
			$data['fullname'] = $request->fullname;
			$data['email'] = $request->email;
			$data['mobile'] = $request->mobile;
			$data['content'] = $request->content;
			$data['message'] = $request->message;
			// $data['entry_time'] = $this->today;
			// dd($data);
			// $request->request->add(['status' => '1']);
			$ContactData = Contact::create($data);
			if (!empty($ContactData)) {

				$arrStatus   = Response::HTTP_OK;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = '"Contact Details has been submitted successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			} else {

				$arrStatus   = Response::HTTP_NOT_FOUND;
				$arrCode     = Response::$statusTexts[$arrStatus];
				$arrMessage  = 'Problem with submitting contact data ';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			//dd($e);
			$arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode     = Response::$statusTexts[$arrStatus];
			$arrMessage  = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function getUserContestData()
	{

		try {
			$id = Auth::user()->id;

			$arrData = ContestPrizeSetting::select('uca.id', 'tbl_contest_setttings.required_left_commanders', 'tbl_contest_setttings.required_right_commanders', 'tbl_contest_setttings.contest_prize', 'uca.claim_status')
				->leftjoin('tbl_user_contest_achievment as uca', function ($arrData) use ($id) {
					$arrData->on('uca.contest_id', '=', 'tbl_contest_setttings.id')
						->where('uca.user_id', $id);
				})
				->get();

			$arrStatus  = Response::HTTP_OK;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Data found';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function claimPrize(Request $request)
	{

		try {

			$rules = array(
				'id' => 'required',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				return sendresponse(Response::HTTP_NOT_FOUND, Response::$statusTexts[404], $validator, []);
			}
			$user_id = Auth::user()->id;
			$updt    = UserContestAchievement::where('user_id', $user_id)->where('id', $request->id)->update(['claim_status' => 1]);
			UserContestAchievement::where('user_id', $user_id)->where('id', '!=', $request->id)->update(['claim_status'      => 3]);
			if ($updt) {
				$arrStatus  = Response::HTTP_OK;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Contest prize claimed successfully';
				return sendResponse($arrStatus, $arrCode, $arrMessage, []);
			} else {
				$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Something went wrong,Please try again';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			//dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}
	public function GetCalculatedAmount(Request $request)
	{
		try {
			$rules = array(
				'packege_amount' => 'required|numeric',
			);
			$validator = checkvalidation($request->all(), $rules, '');
			if (!empty($validator)) {
				return sendresponse(Response::HTTP_NOT_FOUND, Response::$statusTexts[404], $validator, []);
			}
			if (!empty($request->packege_amount)) {
				$result = DB::table('tbl_product')
					->select('*')
					->whereRaw("'$request->packege_amount' BETWEEN min_hash AND max_hash")
					->where('status', 'Active')
					->orderBy('entry_time', 'desc')
					->first();

				if (!empty($result)) {

					$finalData['package_name']              = $result->package_name;
					$finalData['min_hash']                  = $result->min_hash;
					$finalData['max_hash']                  = $result->max_hash;
					$finalData['duration']                  = $result->duration;
					$finalData['roi']                       = $result->roi;
					$finalData['capping']                   = $result->capping;
					$finalData['roi_amount']                = ($request->packege_amount * $result->roi) / 100;
					$finalData['roi_amount_after_duration'] = $finalData['roi_amount'] * $finalData['duration'];
					$arrStatus                              = Response::HTTP_OK;
					$arrCode                                = Response::$statusTexts[$arrStatus];
					$arrMessage                             = 'Packge Found Succesfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $finalData);
				} else {
					$strMessage = 'Please Select Valid Amount For Package';
					$intCode    = Response::HTTP_BAD_REQUEST;
					$strStatus  = Response::$statusTexts[$intCode];
					return sendResponse($intCode, $strStatus, $strMessage, '');
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

	public function getSettingBalance()
	{
		try {
			$id = Auth::user()->id;
			$fund = UserSettingFund::where('user_id', $id)->orderBy('entry_time', 'desc')->first();
			if (!empty($fund)) {
				// get Dashboard Details
				$getDetails = Dashboard::where('id', $id)->select('top_up_wallet', 'top_up_wallet_withdraw',/* 'fund_wallet', 'fund_wallet_withdraw',*/ 'setting_fund_wallet', 'setting_fund_wallet_withdraw')->first();
				if (!empty($getDetails)) {
					/*$bal['purchase_wallet'] = custom_round($getDetails->top_up_wallet - $getDetails->top_up_wallet_withdraw, 2);*/
					/*$bal['fund_wallet'] = custom_round($getDetails->fund_wallet - $getDetails->fund_wallet_withdraw, 2);*/
					$bal['setting_wallet'] = (float)((int)(($getDetails->setting_fund_wallet - $getDetails->setting_fund_wallet_withdraw) * pow(10, 3)) / pow(10, 3));
					$bal['show_wallet'] = 1;
					$bal['topup_percentage'] = $fund->topup_percentage;
					$arrStatus = Response::HTTP_OK;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Data found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
				} else {
					$arrStatus = Response::HTTP_NOT_FOUND;
					$arrCode = Response::$statusTexts[$arrStatus];
					$arrMessage = 'User not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, []);
				}
			} else {
				$arrStatus = Response::HTTP_NOT_FOUND;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Data not found';
				return sendResponse($arrStatus, $arrCode, $arrMessage, []);
			}
		} catch (Exception $e) {
			//dd($e);
			$bal = 0;
			$arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, $bal);
		}
	}
}
