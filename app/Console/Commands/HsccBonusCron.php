<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Controller;
use App\Models\WorkingToPurchaseTransfer;
use App\Models\Dashboard;
use App\Models\HsccBonus;
use App\Models\Topup;
use App\Models\TransactionActivity;
use App\Models\DirectIncome;
use App\Models\DailyBinaryIncome;
use App\Models\PayoutHistory;
use App\Models\HsccBonusSetting;
use App\Models\ProjectSettings as ProjectSettingModel;
use App\Models\UserInfo;
use DB;

class HsccBonusCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:hscc_bonus_cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'New hscc bonus on direct binary';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $date = \Carbon\Carbon::now();
        $this->today = $date->toDateTimeString();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        try {
            
            echo 'CRON started at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";
            $current_time=\Carbon\Carbon::now()->toDateTimeString();
            
            // $userData = UserInfo::select('id','ref_user_id','bonus_count')->where('topup_status','=',"1")->where('bonus_count','>=',2)->get();
            $userData = UserInfo::select('id','ref_user_id','bonus_count','topup_status','status')->where('bonus_count','>=',10)->get();
             //dd($userData);
            $all_directId_data=array();
            $insert_activity_data = array();
            $all_bonus_data=array();
            $direct_bonus_arr=array();
            $binary_bonus_arr=array();
            // $get_user_ids = array_column($userData,'id');

            foreach ($userData as $key=>$v) {
                
                list($usec, $sec) = explode(" ", microtime());
                $time_start = ((float)$usec + (float)$sec);
                // dd($key);
                $get_direct_id = UserInfo::where('ref_user_id',$v['id'])->where('topup_status','=',"1")->pluck('id');

                $get_direct_id = $get_direct_id->toArray();
                
                 
                $get_Direct_income =  DirectIncome::whereIn('toUserId',$get_direct_id)->where('bonus_status','=',0)->sum('amount');

                $get_binary_income =  PayoutHistory::whereIn('user_id',$get_direct_id)->where('bonus_status','=',0)->sum('amount');
                
                $get_bonus_setting = HsccBonusSetting::select('id','no_of_directs','code_direct','percentage')->where([['status','Active'],['code_direct','<=',$v['bonus_count']]])->orderBy('id','desc')->first();
                // dd($get_bonus_setting);
                if(!empty($get_bonus_setting)){
                    $check_records = HsccBonus::where('user_id',$v['id'])->whereDate('entry_time',\Carbon\Carbon::now()->format('Y-m-d'))->count();
                    if($check_records <= 0)
                    {
                        $total_amount = $get_Direct_income+$get_binary_income;
                        if($total_amount > 0)
                        {
                            $amount = ($total_amount * $get_bonus_setting->percentage) / 100;
                            $remark = "Bonus Income";
                            $laps_amount = 0;
                            $status = "Active";

                            $topup= Topup::where('id',$v['id'])->selectRaw("COUNT(srno) as tp_count,SUM(total_income) as capping_amount")->first();

                            $check_if_cap_ref_exist = $topup->tp_count;

                            /*if($check_if_cap_ref_exist >= 1)
                            {*/   

                                $dash = Dashboard::selectRaw('round((roi_income+direct_income+binary_income+hscc_bonus_income),3) as total_income')->where('id',$v['id'])->first();
                                $total_income = $dash->total_income;

                                $capping = $topup->capping_amount;

                                if ($v['status'] == "Inactive") 
                                {
                                  $capping_amount = $amount;
                                  $laps_amount =   $amount;
                                  $amount      =  0;
                                  $remark = "Income lapsed due to user is Blocked";
                                  $status = "Inactive";
                                }else if ($v['topup_status'] == 0) {
                                
                                  $laps_amount =   $amount;
                                  $amount      =  0;
                                  $remark = "Not having topup";
                                  $status = "Inactive";
                                
                                }else if($total_income >= $capping) {
                                  /*$capping_amount = $amount;*/
                                  $laps_amount =   $amount;
                                  $amount      =  0;
                                  // $remark = "Income lapsed due to 3X Capping Achieved";
                                  $remark = "Income lapsed due to 10X Capping Achieved";
                                  $status = "Inactive";

                                  $three_x_achieve_date= date('Y-m-d H:i:s', strtotime($current_time));
                                  UserInfo::where('id',$v['id'])->update(['capping_withdrawal_status'=>'Inactive',
                                    'three_x_achieve_status'=>'1',
                                    'three_x_achieve_date'=>$three_x_achieve_date]);


                                }else if(($total_income + $amount) >= $capping) 
                                {   
                                    /*$capping_amount = $amount;*/
                                    $laps_amount = ($total_income + $amount) - $capping;
                                    $amount = $capping - $total_income;
                                    // $remark = "3X Capping";
                                    $remark = "10X Capping";

                                    $three_x_achieve_date= date('Y-m-d H:i:s', strtotime($current_time));
                                    UserInfo::where('id',$v['id'])->update(['capping_withdrawal_status'=>'Inactive',
                                    'three_x_achieve_status'=>'1',
                                    'three_x_achieve_date'=>$three_x_achieve_date]);
                                }else{
                                    UserInfo::where('id',$v['id'])->update(['capping_withdrawal_status'=>'Active']);
                                }

                                $total_income = $total_income + $amount;

                                /*if ($v->three_x_achieve_status == 1) {
                                 
                                  $laps_amount =   $amount;
                                  $amount      =  0;
                                  $remark = "Income lapsed due to 3X Capping Achieved";
                                }*/
                                // $countbonus = 1;

                                $arrayBonus = array();
                                $arrayBonus['user_id'] = $v['id'];
                                $arrayBonus['bonus_id'] = $get_bonus_setting->id;
                                $arrayBonus['direct_amount'] = $get_Direct_income;
                                $arrayBonus['binary_amount'] = $get_binary_income;
                                $arrayBonus['amount'] = $amount;
                                $arrayBonus['laps_amount'] = $laps_amount;
                                $arrayBonus['remark'] = $remark;
                                $arrayBonus['status'] = $status;
                                $arrayBonus['entry_time'] = \Carbon\Carbon::now();
                                array_push($all_bonus_data,$arrayBonus);

                                $direct_bonus_arr = array_merge($direct_bonus_arr, $get_direct_id);
                                $binary_bonus_arr = array_merge($binary_bonus_arr, $get_direct_id);

                                $updateData = array();
                                /*$updateData['working_wallet'] = DB::raw('working_wallet + '.$amount);*/
                                $updateData['hscc_bonus_income'] = DB::raw('hscc_bonus_income + '.$amount);
                                $updateData['hscc_bonus_wallet'] = DB::raw('hscc_bonus_wallet + '.$amount);
                                /*$updateData['hscc_bonus_wallet_withdraw'] = DB::raw('hscc_bonus_wallet_withdraw + '.$amount);*/
                                // $updateData['hscc_bonus_no'] = DB::raw('hscc_bonus_no + '.$countbonus);
                                $updateDash = Dashboard::where('id',$v['id'])->update($updateData);

                                $hsccbalance = Dashboard::where('id', $v['id'])->selectRaw('round(hscc_bonus_wallet - hscc_bonus_wallet_withdraw,2) as hscc_balance')->pluck('hscc_balance')->first();

                                $TransActivityData = array();
                                if ($amount>0) {
                                    $TransActivityData['user_id'] = $v['id'];
                                    $TransActivityData['wallet_type'] = 4;
                                    $TransActivityData['narration'] = $amount > 0 ? 'HSCC Bonus Income' : 'Lapsed HSCC Bonus';
                                    $TransActivityData['credit'] = $amount;
                                    $TransActivityData['debit'] = 0;
                                    $TransActivityData['old_balance'] = $hsccbalance;
                                    $TransActivityData['new_balance'] = ($hsccbalance+$amount);
                                    $TransActivityData['entry_time'] = \Carbon\Carbon::now();
                                    
                                    array_push($insert_activity_data,$TransActivityData);
                                }
                                echo"Successful run for UserId: ".$v['id'];
                            // }
                                
                        }else{
                            echo "Amount must be greater than 0";
                        }

                       
                    }else{
                        echo"For this user cron already executed";
                        continue;
                    }
                    
                }else{
                    echo"No records found of settings";
                    continue;
                }
                list($usec, $sec) = explode(" ", microtime());
                $time_end = ((float)$usec + (float)$sec);
                $time = $time_end - $time_start;
                echo "time ".$time."\n";
            }

            $count = 1;
            $array = array_chunk($all_bonus_data, 1000);
           
            while ($count <= count($array)) {
                $key = $count - 1;
                HsccBonus::insert($array[$key]);
                $count++;
            }

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

            $count1 = 1;     
            $array1 = array_chunk($direct_bonus_arr,1000);
            while($count1 <= count($array1))
            {
               $key1 = $count1-1;
               $arrProcess = $array1[$key1];
               $id = implode(",", $arrProcess);
                
                $updt_qry = "UPDATE tbl_directincome SET bonus_status = 1 WHERE toUserId IN (".$id.")";
                $updt_user = DB::statement($updt_qry);

              echo $count1." update bonus status array ".count($array1[$key1])."\n";
              $count1 ++;
            }

            $count2 = 1;   
            $array2 = array_chunk($binary_bonus_arr,1000);
            while($count2 <= count($array2))
            {
                $key2 = $count2-1;
                $arrProcess = $array2[$key2];
                $id = implode(",", $arrProcess);
                
                $updt_qry = "UPDATE tbl_payout_history SET bonus_status = 1 WHERE user_id IN (".$id.")";
                $updt_user = DB::statement($updt_qry);

                echo $count2." update bonus status array ".count($array2[$key2])."\n";
                $count2 ++;
            }


            echo 'CRON end at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";
        } catch (Exception $e) {
            dd($e);
        }
        
    }

}
