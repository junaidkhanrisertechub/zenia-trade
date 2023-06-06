<?php

namespace App\Policies;

use App\Models\CurrentAmountDetails;
use App\Models\QualifiedUserList;
use App\Models\AllTransaction;
use App\Models\BinaryIncome;
use App\Models\PointValue;
use App\Models\Dashboard;
use App\Models\User;
use Config;
use DB;
use App\Models\Topup;
use App\Models\ProjectSettings;
use App\Models\Activitynotification;
use App\Models\Product;
use App\Models\PayoutHistory;
use App\Models\Carrybv;


class BinaryIncomeClass {
	

	public function DemoOne() {
		echo "****** TEST ************";
	}

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



	public function insertBinaryIncome($user_id) {
		$bIncome = [];
		$bIncome['match'] = 0;
		$cappingamt = 0;
		/* Get Latest Top of the user */
		$lasttop = Topup::join('tbl_product as tp','tp.id','=','tbl_topup.type')->select('tp.binary')->where('tbl_topup.id',$user_id)->orderBy('tbl_topup.entry_time','desc')->first();
		
		$bIncome['b_interest'] = Config::get('constants.settings.binary_income_per');
	/*	if(!empty($lasttop )){
             $bIncome['b_interest'] = $lasttop->binary;
		}else{
             $bIncome['b_interest'] = 7;
		}*/
		
       //dd($bIncome['b_interest']);
		$userQualified = DB::table('tbl_users AS usr')
								->select('usr.id AS user_id','usr.amount',
									'usr.virtual_parent_id','usr.l_bv','usr.r_bv')
								     ->where('usr.id',$user_id)->first();
							/*->join('tbl_qualified_user_list AS qulified_usr', function($join) {
								$join->on('qulified_usr.user_id','usr.id');
							})*/
							

       
		if(!is_null($userQualified)) {
			/** @var [ Chk: user exist in curr_amt_details ] */
			$curr_amt_data = CurrentAmountDetails::where('user_id', 
										intval($userQualified->user_id))
										->count();
			/** @var [notExist: insert] */
			if($curr_amt_data == 0 ) {
				$CurrentAmountDetails = new CurrentAmountDetails();
				//$CurrentAmountDetails->id = '1';
				$CurrentAmountDetails->user_id = $user_id;
				$CurrentAmountDetails->left_bv =  $userQualified->l_bv;
				$CurrentAmountDetails->right_bv = $userQualified->r_bv;
				$CurrentAmountDetails->save();	
			} 

               
			/** @var [notExist: update] */
				$activeRow = DB::table('tbl_curr_amt_details AS current_amt')
				->select('current_amt.user_id','current_amt.user_id','current_amt.left_bv','current_amt.right_bv')
				->where('current_amt.user_id',$user_id)->first();
               // dd(count($activeRow));

				if(!empty($activeRow)) { 

					

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
					//$pointValue = PointValue::get();
            // dd($bIncome['b_income']);
			if($bIncome['b_income'] > 0 ) { 		
			/** @var [update: tbl_curr_amt_details ] */
			
			$upd_curr_amt = CurrentAmountDetails::where('user_id', $activeRow->user_id)
							->update([
										'left_bv' => $bIncome['carry_left_bv' ],
										'right_bv' => $bIncome['carry_right_bv'] 
									]);
         
         
          /**----------------------- Code for capping ------------------*/
          
            /*$cappingamt = Topup::where('user_id',$user_id)->max('amount');*/

          	$topup_data = DB::table('tbl_topup')->select('type')->where('amount', \DB::raw("(select max(`amount`) from tbl_topup where id = '".$user_id."')"))->where('id',$user_id)->first();  
          	if(!empty($topup_data)){
            
               $max_prod_id = $topup_data->type;

               $product_data = DB::table('tbl_product AS P')
				->select('P.capping')
				->where('P.id',$max_prod_id)->first();

			$cappingamt = $product_data->capping;

            }else{
            	$cappingamt = 2000;
            	$max_prod_id = 1;
            }

            
			
            if($bIncome['b_income']<= $cappingamt){
           	 $amt=$bIncome['b_income'];
           	 $laps = 0;

           }else{
           	 $amt=$cappingamt;
           	 $laps = $bIncome['b_income'] - $cappingamt;  
           }
             
          // dd($amt);
         //$amt=$bIncome['b_income'];
         //$laps = 0;
          /*$latest_investment_by_user = TopupIco::orderBy('entry_time','desc')->where('id',$activeRow->user_id)->first();

          $latest_investment_amount  = $latest_investment_by_user->amount;

           if($bIncome['b_income']<=$latest_investment_amount){
           	 $amt=$bIncome['b_income'];
           	 $laps = 0;

           }else{
           	 $amt=$latest_investment_amount;
           	 $laps = $bIncome['b_income'] - $latest_investment_amount;  
           }*/
            //   $amt=$bIncome['b_income'];
         	  // $laps = 0;

           /** -----------------------   */
            $checkEntryInQualify = QualifiedUserList::where('user_id',$activeRow->user_id)->first();
            if(!empty($checkEntryInQualify)){
            //dd('hiis');
            
			/** @var [insert: tbl_payout_history] */
			$ins_payout_history = new BinaryIncome;
					$ins_payout_history->user_id = $activeRow->user_id;
					$ins_payout_history->amount =  $amt;//$bIncome['b_income'];
					$ins_payout_history->left_bv = $bIncome['l_bv'];
					$ins_payout_history->right_bv = $bIncome['r_bv'];
					$ins_payout_history->left_bv_carry = $bIncome['carry_left_bv'];
					$ins_payout_history->right_bv_carry = $bIncome['carry_right_bv'];
					$ins_payout_history->left_bv_before = $bIncome['left_bv_before'];
					$ins_payout_history->right_bv_before = $bIncome['right_bv_before'];
					$ins_payout_history->laps_bv = $laps;
					//$ins_payout_history->laps_bv = 'ico';
            
					//$ins_payout_history->withdraw_date  = '0000-00-00 00:00:00';
			$refid = $ins_payout_history->save();
  
/*

            
            $coin_name=ProjectSettings::orderBy('id', 'desc')->where('status',1)->pluck('coin_name')->first();
			
			$ins_all_transaction = new AllTransaction;
					$ins_all_transaction->id = $activeRow->user_id;
					$ins_all_transaction->network_type = $coin_name; 
					$ins_all_transaction->credit = $ins_all_transaction->credit + $amt;
					$ins_all_transaction->refference = $refid; 
					$ins_all_transaction->transaction_date = now();
					$ins_all_transaction->remarks = "Binary Income Generated with ".$bIncome['b_interest']."% Amount ".$amt." .  Match Left : ".$bIncome['l_bv']." .  Match Right : ".$bIncome['r_bv']." .  Carry Left : ".$bIncome['carry_left_bv']." .  Carry Right : ".$bIncome['carry_right_bv']." .  Laps : ".$laps;
					$ins_all_transaction->entry_time = now();
			$ins_all_transaction->save();	

			$activity=new Activitynotification;
                  $activity->id=$activeRow->user_id;
                  $activity->message="Binary Income Generated with ".$bIncome['b_interest']."% Amount ".$amt." .  Left : ".$bIncome['l_bv']." .  Right : ".$bIncome['r_bv']." .  Carry Left : ".$bIncome['carry_left_bv']." .  Carry Right : ".$bIncome['carry_right_bv']." .  Laps : ".$laps;
                  $activity->entry_time=now();
                  $activity->status=1;
              $activity->save();*/


			/** @var [update: tbl_dashboard] */
				$upd_dashboard = Dashboard::where('id', $activeRow->user_id)->first();
                    //   dd($upd_dashboard,$activeRow->user_id);
               // dd($upd_dashboard);

				//select('id','usd','working_wallet','binary_income_withdraw','binary_income','working_wallet_withdraw')->
				if(!empty($upd_dashboard)) {
				$upd_dashboard->usd = $upd_dashboard->usd + $amt;
				$upd_dashboard->binary_income = $upd_dashboard->binary_income + $amt;
				$upd_dashboard->binary_income_withdraw = $upd_dashboard->binary_income_withdraw +$amt;
				$upd_dashboard->working_wallet = $upd_dashboard->working_wallet + $amt;
					$upd_dashboard->entry_time = now();
					$upd_dashboard->update();	
				  } else {
				  	$ins_dashboard = new Dashboard();
					$ins_dashboard->id = $activeRow->user_id;
					$upd_dashboard->binary_income = $upd_dashboard->binary_income + $amt;
					
					$upd_dashboard->usd = $upd_dashboard->usd + $amt;
				
					$upd_dashboard->binary_income_withdraw = $upd_dashboard->binary_income_withdraw + $amt;

					$upd_dashboard->working_wallet = $upd_dashboard->working_wallet + $amt;
					//$upd_dashboard->binary_income = $upd_dashboard->binary_income + $amt;
					$upd_dashboard->entry_time = now();
					$ins_dashboard->save();
				  }

				}else{
					$ins_payout_history = new BinaryIncome;
					$ins_payout_history->user_id = $activeRow->user_id;
					$ins_payout_history->amount =  0;
					$ins_payout_history->left_bv = $bIncome['l_bv'];
					$ins_payout_history->right_bv = $bIncome['r_bv'];
					$ins_payout_history->left_bv_carry = $bIncome['carry_left_bv'];
					$ins_payout_history->right_bv_carry = $bIncome['carry_right_bv'];
					$ins_payout_history->left_bv_before = $bIncome['left_bv_before'];
					$ins_payout_history->right_bv_before = $bIncome['right_bv_before'];
					$ins_payout_history->laps_bv = $laps + $amt;
					//$ins_payout_history->laps_bv = 'ico';
            
					//$ins_payout_history->withdraw_date  = '0000-00-00 00:00:00';
			        $refid = $ins_payout_history->save();
				}

				 /* $now = \Carbon\Carbon::now();
                  $curntDate = $now->toDateString();
				  $domain_name = ProjectSettings::where('status', '=', 1)->pluck('domain_name')->first();
                  $sms_msg ='Dear '.$userQualified->user_id.',
                     Your Binary Income $'.$amt.' generated on '.$curntDate.'. '.$domain_name;
                  if(!empty($userQualified->mobile)){   
                  	//sendSMS($userQualified->mobile, $sms_msg);
                  }*/

				}	
				
			}
		} 
	}

	public function PerPairBinaryIncomeNew($arrdata,$deduction){
    /*DB::beginTransaction();*/
    try{


        $id=$arrdata['id'];
        $getTotalPair=$arrdata['getTotalPair'];
        $perPair=$arrdata['perPair'];
        $payout_no=$arrdata['payout_no'];
        $left_bv=$arrdata['left_bv'];
        $right_bv=$arrdata['right_bv'];
        $match_bv=$arrdata['match_bv'];
        $laps_position=$arrdata['laps_position'];
        
        $before_l_bv=$left_bv;
        $before_r_bv=$right_bv;

        if($match_bv > 0)
        {
          $up_left_bv      = round($left_bv-$match_bv, 10);
          $up_right_bv     = round($right_bv-$match_bv, 10);
 
          $carry_l_bv=$up_left_bv;
          $carry_r_bv=$up_right_bv;

          //******add carry *************
          $carrybvArr1= new Carrybv();
          $carrybvArr1->user_id=$id;
          $carrybvArr1->payout_no=$payout_no;
          $carrybvArr1->before_l_bv=$left_bv;
          $carrybvArr1->before_r_bv=$right_bv;
          $carrybvArr1->match_bv=$match_bv;  
          $carrybvArr1->carry_l_bv=$carry_l_bv;
          $carrybvArr1->carry_r_bv=$carry_r_bv;
          $carrybvArr1->save(); 

          $dateTime=$this->getCurrentDateTime(); 
         
          //*******Update CurrentAmountDetails **********

          $updateLeftBv = CurrentAmountDetails::where('user_id', $id)->update(array('left_bv' => $up_left_bv));
          $updateRightBv = CurrentAmountDetails::where('user_id', $id)->update(array('right_bv' =>  $up_right_bv));

          /*$settings = ProjectSettings::select("tds","tds_invalidpancard","admin_charges")->first();*/

          /* new plan capping changes */

          $product_id=Topup::join('tbl_product as tp','tp.id','=','tbl_topup.type')->where('tbl_topup.id',$id)->orderBy('tbl_topup.entry_time','desc')->pluck('tp.id')->first();

          $binary_per =Product::where('id', $product_id)->pluck('binary')->first();

          //$amount = ($match_bv * $binary_per)/100;

          $my_capping =Product::where('id', $product_id)->pluck('capping')->first();

          /*$capping_amount  = 0;

          if(!empty($my_capping)){

              if($amount > $my_capping && $my_capping>0)
              {
                $capping_amount  = ($amount - $my_capping);
                $amount          = $my_capping;

               }
          }*/

           /* new plan capping changes */

          $netAmount=$deduction['netAmount'];
          $tdsAmt= 0;
          $amt_pin=$deduction['amt_pin'];

          /*$my_capping=User::where('id',$id)->pluck('capping')->first();
          $capping_pair = 0;
        
          $capping_amount  = 0;
          if($amount > $my_capping && $my_capping>0)
          {
            $capping_amount  = ($amount - $my_capping);
            $amount          = $my_capping;
          }*/

          /* user topup roi active or not */
                
          /*$check_topup_exist = Topup::where([['user_id', '=', $id],['roi_status', '=', 'Active']])->first();*/

        /*  $checkQualified = QualifiedUsers::where([['user_id',$id]])->first();
          if(!empty($checkQualified )) {*/

              $laps_amount    = 0;
              /*$amt_pin=($amount*$settings->admin_charges)/100;*/
              /*$netAmount=$amount - $amt_pin;*/
              $tdsAmt= 0;
              
         /* }
          else
          {
             $laps_amount = $amount;
             $amount = 0;
          }*/


          /*$binary_status='0';*/
          
        $userrank = DB::table('tbl_users AS user')
   						->select('user.rank')
                        ->where('user.id', '=', $id)
                     	->get();
        $userrankdata = $userrank[0]->rank;

         if($userrankdata != null){
         	$rankdata = DB::table('tbl_rank')
   						->select('income_percentage','capping')
                        ->where('rank', '=', $userrankdata)
                     	->get();
         	/*dd($rankdata[0]->income_percentage);*/
         	$binary_per = $rankdata[0]->income_percentage;
         	$amount = ($match_bv * $binary_per)/100;

         	$capping_amount  = 0;
         	$my_capping = $rankdata[0]->capping;
          	if($amount > $my_capping && $my_capping>0)
          	{
            	$laps_amount  	= 	($amount - $my_capping);
            	$amount      =	$my_capping;
            	/*$capping_amount =	$my_capping;*/

           	}
            $netAmount=$amount - $amt_pin;		          

         	  $payoutArr= new PayoutHistory();
	          $payoutArr->user_id= $id;
	          $payoutArr->amount= $amount;
	          $payoutArr->net_amount= $netAmount;
	          $payoutArr->tax_amount= 0;
	          $payoutArr->amt_pin= $amt_pin;
	          $payoutArr->left_bv= $left_bv;
	          $payoutArr->right_bv= $right_bv;
	          $payoutArr->match_bv= $match_bv;
	          $payoutArr->laps_bv= 0;
	          $payoutArr->laps_amount= $laps_amount;
	          $payoutArr->product_id = $product_id;
	          $payoutArr->entry_time= $dateTime;
	          $payoutArr->left_bv_before= $before_l_bv;
	          $payoutArr->right_bv_before= $before_r_bv;
	          $payoutArr->left_bv_carry= $carry_l_bv;
	          $payoutArr->right_bv_carry= $carry_r_bv;
	          $payoutArr->payout_no=$payout_no;
	          /*$payoutArr->capping_amount=$capping_amount;*/
			  $payoutArr->rank=$userrankdata;
			  $payoutArr->percentage=$binary_per;
	          $payoutArr->save();



          echo "\n";

          echo "User Id -->".$id."--> Binary Income -->".$amount."-->Match-->".$match_bv."--> Laps-->".$laps_amount;

          echo "\n";

          $upd_dashboard = Dashboard::where('id', $id)->first();
          if(!empty($upd_dashboard)){
            $upd_dashboard->binary_income = $upd_dashboard->binary_income + $amount;
           	$upd_dashboard->working_wallet = $upd_dashboard->working_wallet + $amount;
            /*$upd_dashboard->ewallet = $upd_dashboard->ewallet + $amount;*/
            $upd_dashboard->total_profit=$upd_dashboard->total_profit + $amount;
            $upd_dashboard->update(); 

          }

       
        }

         }
          
        /* Code For Level Income On Binary */

        /*if($getTotalPair > $capping){
           $match = $capping;
           $amt1 = $per_pair_amount * $match; 
         }else{
           $match = $getTotalPair;
           $amt1 = $per_pair_amount * $match;
         }*/

       /* if($amount > 0){
        $this->pay_direct_binary($id,$amount);
        }*/

      /*DB::commit();*/
      }catch(Exception $e){
          dd($e);
            /*DB::rollback();*/
            $intCode          = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strStatus        = Response::$statusTexts[$intCode];
            $strMessage     = 'Something went wrong,Please try again';
            return sendResponse($intCode, $strStatus, $strMessage,'');
      } 
  }


  public function chkQualifiedBinaryIncomeNew($user_id) {

		$chkQulifiedBinary = DB::table('tbl_users AS usr')
								->select('qulified_usr.user_id AS user_id','usr.ref_user_id As sponser','usr.fullname','usr.amount','usr.virtual_parent_id','usr.l_bv','usr.r_bv')
							->join('tbl_qualified_user_list AS qulified_usr', function($join) {
								$join->on('qulified_usr.user_id','usr.id');
							})
							->where('usr.amount','>=', 0)
							->where('usr.id',$user_id)
							->where('rank','!=',null);

		if($chkQulifiedBinary->count() > 0) {
			return $chkQulifiedBinary->first();
		} else {
			return 0;
		}		
	}

	public function QualifiedCurrentAmtDetailsNew($id){
    /*$userQualified = DB::table('tbl_users AS usr')
               ->select('qulified_usr.user_id AS user_id','usr.amount','usr.virtual_parent_id','usr.l_bv','usr.r_bv','usr.l_bv_rep','usr.r_bv_rep')
                ->join('tbl_qualified_user_list AS qulified_usr', 'qulified_usr.user_id','usr.id') 
                ->where([['usr.id',$id],['usr.type', '!=', 'Admin']])
                ->where(function ($query) {
                    $query->where([['usr.l_bv','>',0],['usr.r_bv','>',0]])
                    ->orWhere([['usr.l_bv','>',0],['usr.r_bv','>',0]]);
                })*/

    $userQualified = DB::table('tbl_users AS usr')
               ->select('usr.id','usr.amount','usr.virtual_parent_id','usr.l_bv','usr.r_bv')
                /*->join('tbl_qualified_user_list AS qulified_usr', 'qulified_usr.user_id','usr.id') */
                ->where([['usr.id',$id],['usr.type', '!=', 'Admin']])
                ->where('rank','!=',null)
                ->where(function ($query) {
                    $query->where([['usr.l_bv','>',0],['usr.r_bv','>',0]])
                    ->orWhere([['usr.l_bv','>',0],['usr.r_bv','>',0]]);
                })

                /*->where([['usr.l_bv','>=',2],['usr.r_bv','>=',1]])
                ->orWhere([['usr.l_bv','>=',1],['usr.r_bv','>=',2]])  */ 
                ->first();
    if(!is_null($userQualified)) {
      $laps_position  = 1;
      if($userQualified->l_bv > $userQualified->r_bv){
            $userQualified->l_bv = $userQualified->l_bv-0;
            $laps_position  = 1;
      }else{
            $userQualified->r_bv= $userQualified->r_bv-0;
            $laps_position = 2;
      }
      $curr_amt_data = CurrentAmountDetails::where('user_id', 
      intval($userQualified->id))
      ->count('cron_id');
            
      if($curr_amt_data == 0 ) {
          $CurrentAmountDetails = new CurrentAmountDetails();
          //$CurrentAmountDetails->id = '1';
          $CurrentAmountDetails->user_id = $id;
          $CurrentAmountDetails->left_bv =  $userQualified->l_bv;
          $CurrentAmountDetails->right_bv = $userQualified->r_bv;
          $CurrentAmountDetails->save();  
      } 
    }    
  }

  /**
   * Common current date function
   * 
   * @return \Illuminate\Http\Response
   */
  function getCurrentDateTime(){

        $date = \Carbon\Carbon::now();
        return $date->toDateTimeString();
  }

}
