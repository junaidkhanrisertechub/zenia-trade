<?php
namespace App\Console\Commands;

use App\Models\DailyBinaryIncome;
use App\Models\PayoutHistory;
use App\Models\Topup;
use App\Models\UserInfo;
use App\Models\TransactionActivity;
use App\Models\Dashboard;
use DB;
use App\User;
use Illuminate\Console\Command;

class OptimizedDailyBinaryIncome extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cron:daily_optimized_binary_income';
	//  protected $hidden = true;

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
    public function handle()
    {
        try {
            list($usec, $sec) = explode(" ", microtime());

            $time_start1 = ((float)$usec + (float)$sec);
        
            DB::raw('LOCK TABLES `tbl_dashboard` WRITE');
      
            DB::raw('LOCK TABLES `tbl_dailybonus` WRITE');
       
            $day = \Carbon\Carbon::now()->format('D');
        
            $current_time = \Carbon\Carbon::now()->toDateTimeString();
            $msg = "Binary Cron started at " . $current_time;
            echo $msg . "\n";

            $date_diff = 1;

            $daliyincome = PayoutHistory::selectRaw('tbl_payout_history.user_id,tbl_payout_history.id as invoice_id,tbl_payout_history.left_bv,tbl_payout_history.amount,tbl_payout_history.created_at,tbl_payout_history.right_bv,tbl_payout_history.remark,tbl_payout_history.total_binary_count,tbl_payout_history.match_bv,tbl_payout_history.binary_distribution_days,tbl_payout_history.binary_distribution_per,tbl_payout_history.pin,tbl_payout_history.binary_status,tbl_payout_history.created_at')
                    ->join('tbl_users as tu', 'tu.id', '=', 'tbl_payout_history.user_id')
                    // ->where('tu.status', 'Active')->where('tu.type', '')
                    ->where('tbl_payout_history.binary_status', 'Active')
                    //->where('tbl_payout_history.total_binary_count', '<', 50)
                    ->where('tbl_payout_history.total_binary_count', '<', DB::raw('tbl_payout_history.binary_distribution_days'))
                    ->where('tbl_payout_history.amount','!=',0)
                    /*->where('tu.three_x_achieve_status','=',0)*/
                    // ->where(DB::raw("TIMESTAMPDIFF(DAY,DATE(tbl_topup.last_roi_entry_time),now())"), '>=', 1)
                    //->where('id',3)
                    ->get();
               // dd($daliyincome);
                $i = 1;
                $insert_dailybonus_arr = array();
                $insert_activity_data = array();
                $pin_arr = array();
                $dashArr  = array();
                $capping_arr  = array();
                $Dailydata = array();
                $TransActivityData = array();
                $updateTopupData = [];

                foreach ($daliyincome as $tp => $v) 
                {


                    $Countdata = PayoutHistory::select('total_binary_count')->where('id', $v->invoice_id)->first();
                   // dd($Countdata);
                    
                        $remaining_days_count = $v->binary_distribution_days - ($Countdata->total_binary_count + 1);
                        $pending_days = $remaining_days_count >= 0 ? $remaining_days_count : 0;
                    if($Countdata->total_binary_count >= ($v->binary_distribution_days-1))
                    {
                       PayoutHistory::where('id',$v->invoice_id)->update(['binary_status'=>'Inactive']);
                    }
                        $capping_amount  = 0;
                        $laps_amount  = 0;
                        $remark = "Binary Income";

                        list($usec, $sec) = explode(" ", microtime());

                        $time_start = ((float)$usec + (float)$sec);

                        $daliy_binary_income = ($v->amount*$v->binary_distribution_per)/100;

                        $topup= Topup::where('id',$v->user_id)->selectRaw("COUNT(srno) as tp_count,SUM(total_income) as capping_amount")->first();

                        $get_last_topup= Topup::where('id',$v->user_id)->selectRaw("entry_time")->orderBy('srno','desc')->first();
                        $user_info= UserInfo::where('id',$v->user_id)->where('topup_status','1')->selectRaw("three_x_achieve_status,three_x_achieve_date")->first();

                        $check_if_cap_ref_exist = $topup->tp_count;

                        if($check_if_cap_ref_exist >= 1)
                        { 

                            $dash = Dashboard::selectRaw('round((roi_income+direct_income+binary_income+hscc_bonus_income),3) as total_income')->where('id',$v->user_id)->first();

                            $total_income = $dash->total_income;


                            $capping = $topup->capping_amount;
                            echo "\ntotal_income: ".$total_income."\ndaliy_binary_income:".$daliy_binary_income."\nCapping:".$capping."\n";

                            if($total_income >= $capping) 
                            {
                              $capping_amount = $daliy_binary_income;
                              $laps_amount =   $daliy_binary_income;
                              $daliy_binary_income  =  0;
                              // $remark = "Income lapsed due to 3X Capping Achieved";
                              $remark = "Income lapsed due to 10X Capping Achieved";

                              array_push($capping_arr,$v->user_id);
                              $three_x_achieve_date= date('Y-m-d H:i:s', strtotime($current_time));
                                UserInfo::where('id',$v->user_id)->update(['capping_withdrawal_status'=>'Inactive']);
                                if ($user_info->three_x_achieve_status == 0) {
                                    UserInfo::where('id',$v->user_id)->update([
                                    'three_x_achieve_status'=>'1',
                                    'three_x_achieve_date'=>$three_x_achieve_date]);
                                }

                            }else if (($total_income + $daliy_binary_income) >= $capping) 
                            {   
                                $capping_amount = $daliy_binary_income;
                                $laps_amount = ($total_income + $daliy_binary_income) - $capping;
                                $daliy_binary_income = $capping - $total_income;
                                // $remark = "3X Capping";
                                $remark = "10X Capping";

                                array_push($capping_arr,$v->user_id);
                                $three_x_achieve_date= date('Y-m-d H:i:s', strtotime($current_time));
                                UserInfo::where('id',$v->user_id)->update(['capping_withdrawal_status'=>'Inactive']);
                                if ($user_info->three_x_achieve_status == 0) {
                                    UserInfo::where('id',$v->user_id)->update([
                                    'three_x_achieve_status'=>'1',
                                    'three_x_achieve_date'=>$three_x_achieve_date]);
                                }
                            }elseif ($user_info->three_x_achieve_status == 0 && !empty($user_info->three_x_achieve_date)) {
                                $last_topup_time= date('Y-m-d H:i:s',strtotime($get_last_topup->entry_time));
                                $three_x_achieve_end_date = date('Y-m-d H:i:s',strtotime($user_info->three_x_achieve_date." + 2 days"));
                                if (strtotime($last_topup_time) > strtotime($three_x_achieve_end_date)) {
                                    $binary_count_after_expire =  PayoutHistory::where('id',$v->invoice_id)->where('created_at','>',$three_x_achieve_end_date)->count('id');
                                    if (empty($binary_count_after_expire)) {
                                        
                                        $capping_amount = ($v->binary_distribution_days - $Countdata->total_binary_count) * $daliy_binary_income;
                                        $laps_amount =   $capping_amount;
                                        $daliy_binary_income  =  0;
                                        $remark = "Income lapsed due to Retopup timer expired";
                                        PayoutHistory::where('id',$v->invoice_id)->update(['binary_status'=>'Inactive']);
                                    }
                                }
                            
                            }else{
                                UserInfo::where('id',$v->user_id)->update(['capping_withdrawal_status'=>'Active']);
                            }
                            echo "\nlaps_amount:".$laps_amount."\n";

                            $total_income = $total_income + $daliy_binary_income;


                            $Dailydata['user_id'] = $v->user_id;
                            $Dailydata['daliy_binary_income'] = $daliy_binary_income;
                            $Dailydata['amount'] = $v->amount;
                            $Dailydata['daliy_percentage'] = $v->binary_distribution_per;
                            $Dailydata['left_busniess'] = $v->left_bv;
                            $Dailydata['right_busniess'] = $v->right_bv;
                            $Dailydata['daily_binary_pin'] = $v->pin;
                            $Dailydata['lapse_amount'] = $laps_amount;
                            $Dailydata['capping_amount'] = $capping_amount;
                            $Dailydata['pending_days'] = $pending_days;
                            $Dailydata['remark'] = $remark;
                            $Dailydata['entry_time'] = $current_time;


                            /*$updateCoinData = array();
                            $updateCoinData['binary_income'] = $daliy_binary_income;
                            $updateCoinData['binary_income_withdraw'] = $daliy_binary_income;
                            $updateCoinData['working_wallet'] =$daliy_binary_income;
            
                            $updateCoinData['id'] =$v->user_id;
        
        
                            array_push($dashArr,$updateCoinData);*/


                            $updateTopupData[] = array(
                                'total_binary_count' => 'total_binary_count + 1',
                                'invoice_id' => $v->invoice_id
                            );

                            $total_binary = $v->total_binary_count;

                            /*if ($total_binary >= $v->binary_distribution_days || $total_income >= $capping) {
                                array_push($pin_arr, $v->invoice_id);
                            }*/
                            if ($total_binary >= $v->binary_distribution_days) {
                                array_push($pin_arr, $v->invoice_id);
                            }

                            $updateCoinData['binary_income'] = DB::raw('binary_income + '.$daliy_binary_income);
                            $updateCoinData['binary_income_withdraw'] = DB::raw('binary_income_withdraw + '.$daliy_binary_income);
                            $updateCoinData['working_wallet'] = DB::raw('working_wallet + '.$daliy_binary_income);
                            $updateDashData = Dashboard::where('id', $v->user_id)->update($updateCoinData);

                            
                            

                            array_push($insert_dailybonus_arr, $Dailydata);

                            $workingbalance = Dashboard::where('id', $v->user_id)->selectRaw('round(working_wallet - working_wallet_withdraw,2) as working_balance')->pluck('working_balance')->first();
                            if ($daliy_binary_income > 0) {
                                // code...
                                
                                $TransActivityData['user_id'] = $v->user_id;
                                $TransActivityData['wallet_type'] = 3;
                                $TransActivityData['narration'] = $daliy_binary_income > 0 ? 'Daily Binary Income' : 'Lapsed Daily Binary';
                                $TransActivityData['credit'] = $daliy_binary_income;
                                $TransActivityData['debit'] = 0;
                                $TransActivityData['old_balance'] = $workingbalance;
                                $TransActivityData['new_balance'] = ($workingbalance+$daliy_binary_income);
                                $TransActivityData['entry_time'] = $current_time;
                                
                                array_push($insert_activity_data,$TransActivityData);
                            }
                    
                            list($usec, $sec) = explode(" ", microtime());
                            $time_end = ((float)$usec + (float)$sec);
                            $time = $time_end - $time_start;
                            echo "time " . $time . "\n";
                        }    
                    }

                  $dashCount = 1;     

                  $dasharray = array_chunk($dashArr,1000);

                  /*while($dashCount <= count($dasharray))
                  {     

                        $dashk = $dashCount-1;
                        $arrProcess = $dasharray[$dashk];
                        $mainArr = array();
                        foreach ($arrProcess as $k => $v) {

                          $mainArr[$v['id']]['id'] = $v['id'];
                         
                          if (!isset($mainArr[$v['id']]['working_wallet']) && !isset($mainArr[$v['id']]['binary_income_withdraw']) && !isset($mainArr[$v['id']]['binary_income']) ) {
                            $mainArr[$v['id']]['binary_income']=$mainArr[$v['id']]['binary_income_withdraw']=$mainArr[$v['id']]['working_wallet']=0;
                            
                          }
                          $mainArr[$v['id']]['working_wallet'] += $v['working_wallet']; 
                          $mainArr[$v['id']]['binary_income_withdraw'] += $v['binary_income_withdraw']; 
                          $mainArr[$v['id']]['binary_income'] += $v['binary_income']; 
                          
                      }
                     
                    $ids = implode(',', array_column($mainArr, 'id'));

                    $total_profit_qry = 'working_wallet = (CASE id';

                    $binary_income_qry = 'binary_income = (CASE id';
                    $binary_income_withdraw_qry = 'binary_income_withdraw = (CASE id';
                    

                    foreach ($mainArr as $key => $val) {
                      $total_profit_qry = $total_profit_qry . " WHEN ".$val['id']." THEN working_wallet + ".$val['working_wallet'];             
                     
                      $binary_income_qry = $binary_income_qry . " WHEN ".$val['id']." THEN binary_income + ".$val['binary_income'];

                      $binary_income_withdraw_qry = $binary_income_withdraw_qry . " WHEN ".$val['id']." THEN binary_income_withdraw + ".$val['binary_income_withdraw'];
                     
                    }

                    $total_profit_qry = $total_profit_qry . " END)";         
                    
                    $binary_income_qry = $binary_income_qry . " END)";

                    $binary_income_withdraw_qry = $binary_income_withdraw_qry . " END)";
                    

                    $updt_qry = "UPDATE tbl_dashboard SET  ".$total_profit_qry." , ".$binary_income_qry." , ".$binary_income_withdraw_qry."  WHERE id IN (".$ids.")";
                    
                    $updt_user = DB::statement(DB::raw($updt_qry));

                    echo $dashCount." update from user dash array ".count($mainArr)."\n";
                    $dashCount ++;
                }*/

                $countActivity = 1;
                $arrayActivity = array_chunk($insert_activity_data,1000);
                   // dd($array);
                while($countActivity <= count($arrayActivity))
                {
                  $keyActivity = $countActivity-1;
                  TransactionActivity::insert($arrayActivity[$keyActivity]);
                  echo $countActivity." insert count arrayActivity ".count($arrayActivity[$keyActivity])."\n";
                  $countActivity ++;
                }

                $stopCount = 1;
                $stopDirect = array_chunk($updateTopupData,1000);

                while($stopCount <= count($stopDirect))
                {
                    $keyx = $stopCount-1;
                    $arrProcess = $stopDirect[$keyx];
                    $pin = "'".implode("','", array_column($arrProcess, 'invoice_id'))."'";

                    $total_binary_count = "total_binary_count = (CASE id";
                    //dd($arrProcess);
                    foreach ($arrProcess as $key => $val){
                       $total_binary_count = $total_binary_count . " WHEN '".$val['invoice_id']."' THEN ".$val['total_binary_count'];
                    }
                    $total_binary_count = $total_binary_count . " END)"; 
                    echo $updt_qry = "UPDATE tbl_payout_history SET ".$total_binary_count." WHERE id IN (".$pin.")";
                    $updt_user = DB::statement($updt_qry);

                    echo $stopCount." update total binary count array ".$pin."\n";
                    $stopCount ++;
                }

                $count3 = 1;     
                $array3 = array_chunk($pin_arr,1000);
                while($count3 <= count($array3))
                {
                  $key3 = $count3-1;
                  PayoutHistory::whereIn('id',$array3[$key3])->update(['binary_status'=>'Inactive']);
                  echo $count3." update pin array ".count($array3[$key3])."\n";
                  $count3 ++;
                }

                $count4 = 1;     
                $array4 = array_chunk($capping_arr,1000);
                while($count4 <= count($array4))
                {
                  $key4 = $count4-1;
                  User::whereIn('id',$array4[$key4])->update(['capping_withdrawal_status'=>'Inactive']);
                  echo $count4." update capping array ".count($array4[$key4])."\n";
                  $count4 ++;
                }

                $count = 1;
                $array = array_chunk($insert_dailybonus_arr, 1000);
               
                while ($count <= count($array)) {
                    $key = $count - 1;
                    DailyBinaryIncome::insert($array[$key]);
                    echo $count . " insert count array " . count($array[$key]) . "\n";
                    $count++;
                }

            $current_time = \Carbon\Carbon::now()->toDateTimeString();
            $msg = "Daliy Binary Cron end at " . $current_time . "\nTotal records : " . count($insert_dailybonus_arr) . "\n";

            echo $msg;

            echo "\n";
            list($usec, $sec) = explode(" ", microtime());
            $time_end1 = ((float)$usec + (float)$sec);
            $time = $time_end1 - $time_start1;
            echo "after roi income cron " . $time . "\n";

            DB::raw('UNLOCK TABLES');
            
        } catch (Exception $e) {
            dd($e);
        }
    
}
}
