<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Config;
use App\Models\Topup;
use App\Models\DailyBonus;
use App\Models\AllTransaction;
use App\Models\TransactionActivity;
use App\Models\Product;
use App\User as UserModel;
use App\Dashboard;
use App\Http\Controllers\userapi\GenerateRoiController;
use DB;


class OptimizedRoiStatic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:optimized_roi_static';    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Roi Cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GenerateRoiController $generateRoi) {
        parent::__construct();
        $this->generateRoi = $generateRoi;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {   
     list($usec, $sec) = explode(" ", microtime());

     $time_start1 = ((float)$usec + (float)$sec);
        // dd('end');
     DB::raw('LOCK TABLES `tbl_dashboard` WRITE');
        // DB::raw('LOCK TABLES `tbl_topup` WRITE');
     DB::raw('LOCK TABLES `tbl_dailybonus` WRITE');
        //cron here
     
        // $time_start1 = microtime_float();
     $day = \Carbon\Carbon::now()->format('D');

        if($day == 'Sun' || $day == 'Sat'){
            // dd('In');
           dd("ROI is not allowed on saturday and sunday");  
        }
       
        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "ROI Cron started at ".$current_time;
        echo $msg."\n";

        $insert_dailybonus_arr = array();
        $insert_activity_data = array();
        
        $update_dash_arr = array();
        $user_id_arr = $user_id_arr1 = $user_id_arr2 = array();
        $pin_arr = array();
        $capping_arr = array();
        $capping_total = array();
        $updateTopupData = [];

        $allTopusDistinctEntryTime = Topup::select(DB::raw("Date(tbl_topup.last_roi_entry_time) as last_roi_entry_time"))
        ->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
        ->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
        ->join('tbl_dashboard as td', 'td.id', '=', 'tbl_topup.id')
        ->where('tu.status', 'Active')->where('tu.type', '')
        ->where('tbl_topup.roi_status','Active')->where('tbl_topup.amount','>',0)
        ->where('tbl_topup.total_roi_count','<',DB::raw('tbl_topup.duration'))
        ->where(DB::raw("TIMESTAMPDIFF(DAY,DATE(tbl_topup.last_roi_entry_time),now())"), '>=', DB::raw('tp.date_diff'))
        ->groupby(DB::raw("Date(tbl_topup.last_roi_entry_time)"))
        //->limit(1)
        ->get();
        //dd($allTopusDistinctEntryTime);
        
        foreach ($allTopusDistinctEntryTime as $tpdet) 
        {

            echo "Last ROI Date -> ".$last_roi_entry_time=$tpdet->last_roi_entry_time;
            echo "\n";
            
            $allTopus = Topup::selectRaw('tbl_topup.id,tbl_topup.pin,tbl_topup.type,tbl_topup.amount,tbl_topup.entry_time,tbl_topup.roi_stop_status,tbl_topup.percentage,tbl_topup.total_roi_count,tbl_topup.duration,tp.date_diff,tbl_topup.amount_roi,tbl_topup.last_roi_entry_time,tu.entry_time as register_date,round((td.roi_income+td.direct_income+td.binary_income+td.hscc_bonus_wallet),3) as dash_total_income ,(select SUM(total_income) from tbl_topup as t1 where t1.id = tu.id) as capping_amount')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
            ->join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
            ->join('tbl_dashboard as td', 'td.id', '=', 'tbl_topup.id')
            ->where('tu.status', 'Active')->where('tu.type', '')
            ->where('tbl_topup.roi_status','Active')
            ->where('tbl_topup.total_roi_count','<',DB::raw('tbl_topup.duration'))
            ->where(DB::raw("TIMESTAMPDIFF(DAY,DATE(tbl_topup.last_roi_entry_time),now())"), '>=', DB::raw('tp.date_diff'))
            ->where(DB::raw("Date(tbl_topup.last_roi_entry_time)"),$last_roi_entry_time)
       // ->limit(100000)
            ->get();

            $i=1;

            echo "last roi date -> ".$last_roi_entry_time;
            echo "\n";


            foreach ($allTopus as $tp)
            {   

                $date_diff = $tp->date_diff;
                $getDate = \Carbon\Carbon::now()->toDateString();
                $nextEntrydate=date('Y-m-d', strtotime($last_roi_entry_time. ' + '.$date_diff. 'days'));
                $getDay = \Carbon\Carbon::parse($nextEntrydate)->format('D');

                if($getDay == 'Sat'){
                   $nextEntrydate = date('Y-m-d', strtotime($nextEntrydate. ' + 2 days')); 
                }
                if($getDay == 'Sun'){
                   $nextEntrydate = date('Y-m-d', strtotime($nextEntrydate. ' + 1 day')); 
                }
                $user_info= UserModel::where('id',$tp->id)->where('topup_status','1')->selectRaw("three_x_achieve_status,three_x_achieve_date")->first();
                

                if(strtotime($nextEntrydate)<= strtotime($getDate))
                {    
                    $on_amount = $tp->amount;
                    $roi_amt_per = $tp->percentage;
                    $roi_amt = $tp->amount_roi;
                    $working_amt = $roi_amt;
                    $userId = $tp->id;
                    $laps_amount = 0;
                    $remark = "ROI Income";
                    $status = "Paid";
                    
                    if (!isset($capping_total[$userId])) 
                    {
                        $capping_total[$userId] = $tp->dash_total_income;
                    }
                    if ($capping_total[$userId] < $tp->capping_amount) 
                    {

                        if (($capping_total[$userId] + $working_amt) >= $tp->capping_amount) 
                        {
                            $laps_amount = custom_round(($tp->dash_total_income + $roi_amt),3) - $tp->capping_amount;
                            $roi_amt = $tp->capping_amount - $tp->dash_total_income;
                            $working_amt = $roi_amt;
                            // $remark = "3X Capping";
                            $remark = "10X Capping";

                            $three_x_achieve_date= date('Y-m-d H:i:s', strtotime($current_time));
                            if ($user_info->three_x_achieve_status == 0) {
                                
                                $update_3x_status= UserModel::where('id',$tp->id)->update(['three_x_achieve_status'=>'1','three_x_achieve_date'=>$three_x_achieve_date]);
                            }
                        }elseif (!empty($user_info->three_x_achieve_date)) {

                                $topup_count_after_3x =  Topup::where('id',$tp->id)->where('pin',$tp->pin)->where('entry_time','<',$user_info->three_x_achieve_date)->count('srno');
                                if (!empty($topup_count_after_3x)) {

                                    $laps_amount = custom_round($roi_amt * ($tp->duration - $tp->total_roi_count),3);
                                    $roi_amt = 0;
                                    $working_amt = 0;
                                    $status = "Unpaid";
                                    // $remark = "Income lapsed due to 3X Capping Achieved";
                                    $remark = "Income lapsed due to 10X Capping Achieved";
                                    Topup::where('id',$tp->id)->where('pin',$tp->pin)->update(['roi_status'=>'Inactive']);
                                }
                            
                        }
                    }else{
                        $laps_amount = custom_round($roi_amt * ($tp->duration - $tp->total_roi_count),3);
                        $roi_amt = 0;
                        $working_amt = 0;
                        $status = "Unpaid";
                                    // $remark = "Income lapsed due to 3X Capping Achieved";
                        $remark = "Income lapsed due to 10X Capping Achieved";
                        Topup::where('id',$tp->id)->where('pin',$tp->pin)->update(['roi_status'=>'Inactive']);
                    }

                        $capping_total[$userId] = $capping_total[$userId] + $working_amt;

                        list($usec, $sec) = explode(" ", microtime());

                        $time_start = ((float)$usec + (float)$sec);

                        if($tp->roi_stop_status == 1)
                        { 
                            $Trandata = array(); // insert in transaction
                            $Trandata['id'] = $tp->id;
                            /*$Trandata['network_type'] = $getCoin->original["data"]["coin_name"];*/
                            $Trandata['refference'] = $tp->id;
                            $Trandata['debit'] = $roi_amt;
                            $Trandata['type'] = $tp->type;
                            $Trandata['status'] = 1;
                            $Trandata['remarks'] = 'ROI Income';
                            $TransactionDta = AllTransaction::create($Trandata);

                            $Dailydata = array();
                            $Dailydata['amount'] = $roi_amt;
                            $Dailydata['id'] = $tp->id;
                            $Dailydata['pin'] = $tp->pin;
                           $Dailydata['status'] = $status;
                           // $Dailydata['software_perentage'] = 0;
                            $Dailydata['daily_percentage'] = $roi_amt_per;
                            // $Dailydata['software_amount'] = 0;
                            $Dailydata['daily_amount'] = $roi_amt;
                            $Dailydata['entry_time'] = $nextEntrydate;
                            $Dailydata['on_amount'] = $on_amount;
                            $Dailydata['laps_amount'] = $laps_amount;
                            $Dailydata['remark'] = $remark;
                            $Dailydata['type'] = $tp->type;
                            // $Dailydata['tax_amount'] = 0;
                            array_push($insert_dailybonus_arr,$Dailydata);

                            $updateCoinData = array();
                            $updateCoinData['id'] = $tp->id;
                            $updateCoinData['usd'] = $roi_amt;
                            $updateCoinData['total_profit'] = $roi_amt;
                            $updateCoinData['roi_income'] = $working_amt;
                            $updateCoinData['roi_wallet'] = $working_amt;
                            /*$updateCoinData['roi_income_withdraw'] = $working_amt;
                            $updateCoinData['working_wallet'] = $working_amt; */

                            array_push($update_dash_arr,$updateCoinData);

                            $roibalance = Dashboard::where('id', $tp->id)->selectRaw('round(roi_wallet - roi_wallet_withdraw,2) as roi_balance')->pluck('roi_balance')->first();
                            $TransActivityData = array();
                            if ($working_amt>0) {
                                $TransActivityData['user_id'] = $tp->id;
                                $TransActivityData['wallet_type'] = 2;
                                $TransActivityData['narration'] = $working_amt > 0 ? 'ROI Income' : 'Lapsed ROI Income';
                                $TransActivityData['credit'] = $working_amt;
                                $TransActivityData['debit'] = 0;
                                $TransActivityData['old_balance'] = $roibalance;
                                $TransActivityData['new_balance'] = ($roibalance+$working_amt);
                                $TransActivityData['entry_time'] = $nextEntrydate;
                                
                                array_push($insert_activity_data,$TransActivityData);
                            }
                        }
                            // topup update
                            
                        $updateTopupData[] = array(
                            'total_roi_count' => 'total_roi_count + 1',
                            'last_roi_entry_time' => $nextEntrydate,
                            'pin' => $tp->pin
                        );

                        $total_roi_count = $tp->total_roi_count + 1;

                        echo " -> srno -> ".$i++." -> id -> ".$tp->id." ->  roi date -> ".$nextEntrydate." -> ";

                        /*if ($total_roi_count >= $tp->duration || $capping_total[$userId] >= $tp->capping_amount) {
                            array_push($pin_arr,$tp->pin);
                        }*/
                        if ($total_roi_count >= $tp->duration) {
                            array_push($pin_arr,$tp->pin);
                        }
                        if($capping_total[$userId] >= $tp->capping_amount)
                        {
                            array_push($capping_arr,$tp->id);
                        }



                        list($usec, $sec) = explode(" ", microtime());
                        $time_end = ((float)$usec + (float)$sec);
                        $time = $time_end - $time_start;
                        echo "time ".$time."\n";

                        

                }
            }

            echo "\n roi date ".$nextEntrydate."\n";
        }
    $count = 1;
    $array = array_chunk($insert_dailybonus_arr,1000);
       // dd($array);
    while($count <= count($array))
    {
      $key = $count-1;
      DailyBonus::insert($array[$key]);
      echo $count." insert count array ".count($array[$key])."\n";
      $count ++;
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

  /*Update ROI Income array*/
  $count1 = 1;
  $array1 = array_chunk($update_dash_arr,1000);
  while($count1 <= count($array1))
  {
    $key1 = $count1-1;
    $arrProcess = $array1[$key1];
    $mainArr = array();
    foreach ($arrProcess as $k => $v) {
        $mainArr[$v['id']]['id'] = $v['id'];
        
    if (!isset($mainArr[$v['id']]['total_profit']) && !isset($mainArr[$v['id']]['roi_income']) /*&& !isset($mainArr[$v['id']]['roi_income_withdraw']) && !isset($mainArr[$v['id']]['working_wallet'])*/) 
    {

        $mainArr[$v['id']]['roi_income']=$mainArr[$v['id']]['roi_wallet']=$mainArr[$v['id']]['total_profit']=0;
        /*$mainArr[$v['id']]['roi_income_withdraw']=$mainArr[$v['id']]['working_wallet']=0;*/

    }
     $mainArr[$v['id']]['total_profit'] += $v['total_profit']; 
     $mainArr[$v['id']]['roi_income'] += $v['roi_income']; 
     $mainArr[$v['id']]['roi_wallet'] += $v['roi_wallet']; 

                /* $mainArr[$v['id']]['roi_income_withdraw'] += $v['roi_income_withdraw']; 
                $mainArr[$v['id']]['working_wallet'] += $v['working_wallet']; */
                
            }

            $ids = implode(',', array_column($mainArr, 'id'));
            $total_profit_qry = 'total_profit = (CASE id';
            /*$working_wallet_qry = 'working_wallet = (CASE id';*/
            $roi_income_qry = 'roi_income = (CASE id';
            $roi_wallet_qry = 'roi_wallet = (CASE id';
            /*$roi_income_withdraw_qry = 'roi_income_withdraw = (CASE id';*/



            foreach ($mainArr as $key => $val) {
                $total_profit_qry = $total_profit_qry . " WHEN ".$val['id']." THEN total_profit + ".$val['total_profit'];             
                /*$working_wallet_qry = $working_wallet_qry . " WHEN ".$val['id']." THEN working_wallet + ".$val['working_wallet'];*/

                $roi_income_qry = $roi_income_qry . " WHEN ".$val['id']." THEN roi_income + ".$val['roi_income'];
                $roi_wallet_qry = $roi_wallet_qry . " WHEN ".$val['id']." THEN roi_wallet + ".$val['roi_wallet'];
                
                /*$roi_income_withdraw_qry = $roi_income_withdraw_qry . " WHEN ".$val['id']." THEN roi_income_withdraw + ".$val['roi_income_withdraw'];*/
            }

            $total_profit_qry = $total_profit_qry . " END)";         
            /*$working_wallet_qry = $working_wallet_qry . " END)";*/
            $roi_income_qry = $roi_income_qry . " END)";
            $roi_wallet_qry = $roi_wallet_qry . " END)";
            /*$roi_income_withdraw_qry = $roi_income_withdraw_qry . " END)";*/
            //$updatedataquery = DB::table('tbl_dashboard')
            //->whereIn('id', array_column($mainArr, 'id'))
            //->update(["total_profit" => $total_profit_qry, "roi_income" => $roi_income_qry, "roi_wallet" =>$roi_wallet_qry]);
            $updt_qry = "UPDATE tbl_dashboard SET ".$total_profit_qry." , ".$roi_income_qry.", ".$roi_wallet_qry." WHERE id IN (".$ids.")";
            //dd($updatedataquery);
            
           // $q = DB::raw($updt_qry);
            
            $updt_user = DB::statement($updt_qry);

            echo $count1." update from user dash array ".count($mainArr)."\n";
            $count1 ++;
        }

        $stopCount = 1;
        $stopDirect = array_chunk($updateTopupData,1000);

        while($stopCount <= count($stopDirect))
        {
          $keyx = $stopCount-1;
          $arrProcess = $stopDirect[$keyx];
          $pin = "'".implode("','", array_column($arrProcess, 'pin'))."'";

          $total_roi_count = 'total_roi_count = (CASE pin';
          $last_roi_entry_time = 'last_roi_entry_time = (CASE pin';
          //
          foreach ($arrProcess as $key => $val){
           $total_roi_count = $total_roi_count . " WHEN '".$val['pin']."' THEN ".$val['total_roi_count'];
           $last_roi_entry_time = $last_roi_entry_time . " WHEN '".$val['pin']."' THEN '".$val['last_roi_entry_time']."'";
       }
       $total_roi_count = $total_roi_count . " END)"; 
       $last_roi_entry_time = $last_roi_entry_time . " END)"; 
       $updt_qry = "UPDATE tbl_topup SET ".$total_roi_count." , ".$last_roi_entry_time." WHERE pin IN (".$pin.")";
       
       $updt_user = DB::statement($updt_qry);
       echo $stopCount." update direct status array ".count($arrProcess)."\n";
       $stopCount ++;
   }


   $count3 = 1;     
   $array3 = array_chunk($pin_arr,1000);
   while($count3 <= count($array3))
   {
      $key3 = $count3-1;
      Topup::whereIn('pin',$array3[$key3])->update(['roi_status'=>'Inactive']);
      echo $count3." update pin array ".count($array3[$key3])."\n";
      $count3 ++;
   }

  $count4 = 1;     
   $array4 = array_chunk($capping_arr,1000);
   while($count4 <= count($array4))
   {
      $key4 = $count4-1;
      UserModel::whereIn('id',$array4[$key4])->update(['capping_withdrawal_status'=>'Inactive']);
      echo $count4." update capping array ".count($array4[$key4])."\n";
      $count4 ++;
  }


  $current_time = \Carbon\Carbon::now()->toDateTimeString();
  $msg = "ROI Cron end at ".$current_time."\nTotal records : ".count($insert_dailybonus_arr)."\n";

  echo $msg;

  echo "\n";
  list($usec, $sec) = explode(" ", microtime());
  $time_end1 = ((float)$usec + (float)$sec);
  $time = $time_end1 - $time_start1;
  echo "after roi income cron ".$time."\n"; 



  DB::raw('UNLOCK TABLES');

}
}
