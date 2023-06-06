<?php
namespace App\Policies;

use App\Policies\Contracts\BinaryIncomeInterface;

use App\Models\CurrentAmountDetails;
use App\Models\QualifiedUserList;
use App\Models\AllTransaction;
use App\Models\BinaryIncome;
use App\Models\PointValue;
use App\Models\Dashboard;
use App\User;
use Config;
use DB;


/**
 * public function chkBinary();
	public function chkQualifiedBinaryIncome();
	public function chkRecordExist();
	public function findMatch();
	SELECT * FROM `tbl_users` WHERE `ref_user_id` LIKE '50' ORDER BY `amount` DESC

 */


class BinaryIncomeService implements BinaryIncomeInterface {

	/**
	 * [chkBinary description]
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	public function chkBinary($user_id) {
		$chkBinary = User::select('id As sibling_id','user_id as name','ref_user_id','amount','l_bv','r_bv','virtual_parent_id')
			->where('virtual_parent_id',$user_id);
		if($chkBinary->count() > 0) {
			return $chkBinary->get(); 
		} else {
			return FALSE;
		}	
	}	

	/**
	 * [chkQualifiedBinaryIncome description]
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	public function chkQualifiedBinaryIncome($user_id) {
		$chkQulifiedBinary = DB::table('tbl_users AS usr')
								->select('qulified_usr.user_id AS user_id','usr.ref_user_id As sponser','usr.fullname','usr.amount','usr.virtual_parent_id','usr.l_bv','usr.r_bv')
							->join('tbl_qualified_user_list AS qulified_usr', function($join) {
								$join->on('qulified_usr.user_id','usr.id');
							})
							->where('usr.amount','>=', 0)
							->where('usr.id',$user_id);
		if($chkQulifiedBinary->count() > 0) {
			return $chkQulifiedBinary->first();
		} else {
			return 0;
		}		
	}

	/**
	 * [chkRecordExist description]
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	
	public function insertBinaryIncome($user_id) {
		//echo "<br>".$user_id;

		$bIncome = [];
		$bIncome['match'] = 0;
		$bIncome['b_interest'] = Config::get('constants.settings.binary_income_per');

		$userQualified = DB::table('tbl_users AS usr')
								->select('qulified_usr.user_id AS user_id','usr.amount',
									'usr.virtual_parent_id','usr.l_bv','usr.r_bv')
							->join('tbl_qualified_user_list AS qulified_usr', function($join) {
								$join->on('qulified_usr.user_id','usr.id');
							})
							->where('usr.amount','>=', 0)
							->where('usr.id',$user_id)->first();

		if(!is_null($userQualified)) {
			/** @var [ Chk: user exist in curr_amt_details ] */
			$curr_amt_data = CurrentAmountDetails::where('user_id', 
										intval($userQualified->user_id))
										->count();
			/** @var [notExist: insert] */
			if($curr_amt_data == 0 ) {
				$CurrentAmountDetails = new CurrentAmountDetails();
				$CurrentAmountDetails->cron_id = '1';
				$CurrentAmountDetails->user_id = $user_id;
				$CurrentAmountDetails->left_bv =  $userQualified->l_bv;
				$CurrentAmountDetails->right_bv = $userQualified->r_bv;
				$CurrentAmountDetails->save();	
			} 


			/** @var [notExist: update] */
				$activeRow = DB::table('tbl_curr_amt_details AS current_amt')
				->select('current_amt.user_id','current_amt.cron_id','current_amt.left_bv','current_amt.right_bv')
				->where('current_amt.user_id',$user_id)->first();


				if(count($activeRow) > 0) { 

					

						$bIncome['l_bv'] =  $activeRow->left_bv;
					$bIncome['r_bv'] =  $activeRow->right_bv;

					$bIncome['left_bv_before'] =  $activeRow->left_bv;
					$bIncome['right_bv_before'] = $activeRow->right_bv;


					/** find Match and set carry bv left or bv right */
					if($bIncome['l_bv'] > $bIncome['r_bv']) {
						$bIncome['match'] = $bIncome['r_bv'];
						$bIncome['carry_left_bv'] =  $bIncome['l_bv'] - $bIncome['r_bv'];
						$bIncome['carry_right_bv'] = 0;				
					} else {
						$bIncome['match'] = $bIncome['l_bv'];
						$bIncome['carry_right_bv'] = $bIncome['r_bv'] - $bIncome['l_bv'];
						$bIncome['carry_left_bv'] =  0;
					}
					/****************************************************/
					/** calculate binary income */
					if( $bIncome['b_interest'] > 0 ) {
						 $bIncome['b_income'] =  ($bIncome['match'] * $bIncome['b_interest']) / 100;
					}
					$pointValue = PointValue::get();

			if($bIncome['b_income'] > 0 ) { 		
			/** @var [update: tbl_curr_amt_details ] */
			$upd_curr_amt = CurrentAmountDetails::where('user_id', $activeRow->user_id)
							->update([
										'left_bv' => $bIncome['carry_left_bv' ],
										'right_bv' => $bIncome['carry_right_bv'] 
									]);


			/** @var [insert: tbl_payout_history] */
			$ins_payout_history = new BinaryIncome;
					$ins_payout_history->user_id = $activeRow->user_id;
					$ins_payout_history->amount =  $bIncome['b_income'];
					$ins_payout_history->left_bv = $bIncome['l_bv'];
					$ins_payout_history->right_bv = $bIncome['r_bv'];
					$ins_payout_history->left_bv_carry = $bIncome['carry_left_bv'];
					$ins_payout_history->right_bv_carry = $bIncome['carry_right_bv'];
					$ins_payout_history->left_bv_before = $bIncome['left_bv_before'];
					$ins_payout_history->right_bv_before = $bIncome['right_bv_before'];
					//$ins_payout_history->withdraw_date  = '0000-00-00 00:00:00';
			$ins_payout_history->save();


			/** @var AllTransaction [insert: tbl_all_transaction] */
			$ins_all_transaction = new AllTransaction;
					$ins_all_transaction->id = $activeRow->user_id;
					$ins_all_transaction->network_type = 'BTC'; 
					$ins_all_transaction->credit = $ins_all_transaction->credit + $bIncome['b_income']; 
					$ins_all_transaction->balance = $ins_all_transaction->balance + $bIncome['b_income']; 
					$ins_all_transaction->refference = 0; 
					$ins_all_transaction->transaction_date = now();
					$ins_all_transaction->remarks = "Binary Income With ".$bIncome['b_interest']."%";
					$ins_all_transaction->entry_time = now();
			$ins_all_transaction->save();	


			/** @var [update: tbl_dashboard] */
				$upd_dashboard = Dashboard::where('id', $activeRow->user_id)->first();
				if(count($upd_dashboard) > 0 ) {
					$upd_dashboard->usd  = $upd_dashboard->usd  + number_format($bIncome['b_income']);					 
					$upd_dashboard->total_profit = $upd_dashboard->total_profit + number_format($bIncome['b_income']);
					$upd_dashboard->binary_income = $upd_dashboard->binary_income + number_format($bIncome['b_income']);
					$upd_dashboard->entry_time = now();
					$upd_dashboard->update();	
				  } else {
				  	$ins_dashboard = new Dashboard();
					$ins_dashboard->id = $activeRow->user_id;
					$ins_dashboard->usd  = $ins_dashboard->usd  + number_format($bIncome['b_income']);
					$ins_dashboard->total_profit = $ins_dashboard->total_profit + number_format($bIncome['b_income']);
					$ins_dashboard->binary_income = number_format($bIncome['b_income']);
					$ins_dashboard->entry_time = now();
					$ins_dashboard->save();
				  }

					
				}
			}
		} 
	}

}