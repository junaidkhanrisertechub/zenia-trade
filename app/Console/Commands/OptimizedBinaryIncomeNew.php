<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Policies\BinaryIncomeClass;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Config;
use DB;
use App\User;
use App\Models\ProjectSettings;
use App\Models\PayoutHistory;
use App\Models\Product;
use App\Models\DailyBonus;
use App\Models\Packages;
use App\Models\Topup;
use App\Models\QualifiedUserList as QualifiedUsers;
use App\Traits\Users;
use App\Models\Dashboard;
use App\Models\Carrybv;
use App\Models\CurrentAmountDetails;

class OptimizedBinaryIncomeNew extends Command
{

    use Users;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:optimized_binary_income_new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Binary income on amount';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(BinaryIncomeClass $objBinaryIncome){
        parent::__construct();
        $this->objBinaryIncome = $objBinaryIncome;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    /**
     * Execute the console command.
     *
     * @return mixed
     */
  public function handle()
    {
        /*dd('Please run queries first');*/
        list($usec, $sec) = explode(" ", microtime());

        $time_start1 = ((float)$usec + (float)$sec);
        $now = \Carbon\Carbon::now();
        $day=date('D', strtotime($now));
        $current_time = \Carbon\Carbon::now()->toDateTimeString();

        $msg = "Binary Cron started at ".$current_time;
        //sendSMS($mobile,$msg);
        echo $msg."\n";

        echo "start ".now()."\n";

        

        $updatetopupArr= array();

        $getUserDetails = User::select("id","curr_l_bv","curr_r_bv","binary_qualified_status","topup_status"
        /*,"l_ace_check_status","r_ace_check_status"*/)
        ->where([['curr_l_bv','>',0],['curr_r_bv','>',0],['status','=',"Active"],['type','!=',"Admin"],['binary_qualified_status',1]])
        //->limit(1)
        ->get();

       // ['binary_qualified_status','=',1],['topup_status','=',"1"];
       //dd($getUserDetails);
        
        $binary_count = 0;
        if(!empty($getUserDetails))
        {
            $insert_carrybv_arr = array();
            $insert_payout_arr = array();
            $update_currbv_arr = array();
            $update_dash_arr = array();
            foreach ($getUserDetails as $key => $value) 
            {


              $topup_type = Topup::select('type')
                    ->where('id',$value->id)
                    ->orderBy('entry_time','desc')->first(); 
               if(!empty($topup_type))
               {
                $packageExist = Packages::select('duration','binary','capping')->where('id',$topup_type->type)->orderby('entry_time', 'DESC')->first();

                $duration = $packageExist->duration;
                $binary_per = $packageExist->binary;
              }else{
                 $packageExist = Packages::select('duration','binary')->where('id',1)->first();

                $duration = $packageExist->duration;
                $binary_per = $packageExist->binary;
              }
                

                list($usec, $sec) = explode(" ", microtime());

                $time_start = ((float)$usec + (float)$sec);

                $deduction=array();
                $deduction['netAmount']=$deduction['tdsAmt']=$deduction['amt_pin']=0;

                $id=$getUserDetails[$key]['id'];
                
                $left_bv=$getUserDetails[$key]['curr_l_bv']; 
                $right_bv=$getUserDetails[$key]['curr_r_bv'];
                 
                $getVal=min($left_bv,$right_bv);
                $getTotalPair=$getVal;

              $check_if_ref_exist = Topup::select('tbl_product.direct_income','tbl_topup.pin','tbl_product.duration')->join('tbl_product','tbl_topup.type','tbl_product.id')
             ->where('tbl_topup.id',$id)->orderBy('tbl_topup.amount','desc')->first();
            if(!empty($check_if_ref_exist)){
              $check_if_duration = DailyBonus::select('id')->where('id', $id)->where('pin', $check_if_ref_exist->pin)->count('id');
            }else{
              $check_if_duration = 0;
            }

                if($getTotalPair!=0 && $check_if_duration < $duration  && !empty($check_if_ref_exist))
                {    
                    //  $this->objBinaryIncome->PerPairBinaryIncomeNew($arrdata,$deduction);
                     
                    $perPair=1;
                    $match_bv=$getVal;
                    $laps_position=1;
                    $before_l_bv=$left_bv;
                    $before_r_bv=$right_bv;

                    $up_left_bv   = round($left_bv-$match_bv, 10);
                    $up_right_bv  = round($right_bv-$match_bv, 10);
            
                    $carry_l_bv=$up_left_bv;
                    $carry_r_bv=$up_right_bv;

                    //******add carry *************
                    $carrybvArr1= array();
                    $carrybvArr1['user_id']=$id;
                    $carrybvArr1['before_l_bv']=$left_bv;
                    $carrybvArr1['before_r_bv']=$right_bv;
                    $carrybvArr1['match_bv']=$match_bv;  
                    $carrybvArr1['carry_l_bv']=$carry_l_bv;
                    $carrybvArr1['carry_r_bv']=$carry_r_bv;

                    array_push($insert_carrybv_arr,$carrybvArr1);

                    $dateTime= \Carbon\Carbon::now()->toDateTimeString(); 

                    //*******Update CurrentAmountDetails **********

                    $updateCuurentData = array();
                    $updateCuurentData['curr_l_bv'] = $up_left_bv;
                    $updateCuurentData['curr_r_bv'] = $up_right_bv;
                    $updateCuurentData['id'] = $id;

                    array_push($update_currbv_arr,$updateCuurentData);

                    $netAmount=$deduction['netAmount'];
                    $tdsAmt= $deduction['tdsAmt'];
                    $amt_pin=$deduction['amt_pin'];

                    $laps_amount  = 0;

                    $my_capping = $packageExist->capping;
                    $amount = ($match_bv * $binary_per)/100;//(100*18)/100
                    $capping_amount  = 0;
                    // if($amount > $my_capping && $my_capping > 0)//100>0 && 0 > 0
                    if($amount > $my_capping )//100>0 && 0 > 0
                    {   
                        $capping_amount  = $my_capping;
                        $laps_amount    =   ($amount - $my_capping);
                        $amount      =  $my_capping;
                    }
                    else{
                      $laps_amount = $amount; 
                    }

                    $netAmount=$amount - $amt_pin;	

                    // $check_if_topup_exist = Topup::select('tbl_topup.id')->where('tbl_topup.roi_status','Active')->where('tbl_topup.id',$id)->count('tbl_topup.srno');

                  $binary_qualified_status = $value->binary_qualified_status;
                  $topup_status = $value->topup_status;

                   $laps_bv =0;
                   $remark = "Binary Income";
                  // if ($check_if_topup_exist == 0 || $binary_qualified_status==0 || $topup_status == 0) {


                  //     // $laps_amount = $amount; 
                  //     $amount = 0;
                  //     if($binary_qualified_status==0 ){
                  //       $remark = "Binary not qualified";
                  //     }else if($topup_status == 0){
                  //       $remark = "No topup";
                  //     }else{
                  //       $remark = "Not having active topup";
                  //     }
                      
                  //     $laps_bv = $left_bv;
                  //     $netAmount = 0;
                  //     $left_bv = 0;
                  //     $right_bv = 0;
                  //     $laps_status =1;
                  // }else{
                  //   $laps_status =0;
                  // }
                  if ($binary_qualified_status==1) {
                    $payoutArr= array();
                    $payoutArr['user_id']= $id;
                    $payoutArr['amount']= $amount;//3200
                    // $payoutArr['roi']= round(($amount/$duration),4);//4.4444
                    // $payoutArr['duration']= $duration;
                    $payoutArr['net_amount']= $netAmount;
                    $payoutArr['tax_amount']= 0;
                    $payoutArr['amt_pin']= $amt_pin;
                    $payoutArr['left_bv']= $left_bv;
                    $payoutArr['right_bv']= $right_bv;
                    $payoutArr['match_bv']= $match_bv;
                    $payoutArr['laps_bv']= $laps_bv;
                    $payoutArr['laps_status']= 1;
                    // $payoutArr['invoice_id']= substr(number_format(time() * rand(), 0, '', ''), 0, '15');
                    $payoutArr['laps_amount']= $laps_amount;
                      //$payoutArr['product_id'] = $product_id;
                    $payoutArr['entry_time']= $dateTime;
                    $payoutArr['left_bv_before']= $before_l_bv;
                    $payoutArr['right_bv_before']= $before_r_bv;
                    $payoutArr['left_bv_carry']= $carry_l_bv;
                    $payoutArr['right_bv_carry']= $carry_r_bv;        	       
                    $payoutArr['capping_amount']=$capping_amount;
                    $payoutArr['remark']="Binary Income Generated";
                    $payoutArr['percentage']=$binary_per;
                    $payoutArr['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                    // $payoutArr['last_roi_entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
  
                    array_push($insert_payout_arr,$payoutArr);
  
                    $binary_count++;
  
                      echo " Srno --> ".$binary_count." --> User Id --> ".$id." --> Binary Income --> ".$amount." --> Match --> ".$match_bv." --> Laps --> ".$laps_amount;
  
                      $updateDashArr = array();
                      $updateDashArr['binary_income'] = 'binary_income + ' . $amount . '';
                      $updateDashArr['working_wallet'] = 'working_wallet + ' . $amount . '';
                      $updateDashArr['id'] = $id;
  
                    array_push($update_dash_arr,$updateDashArr);
                    

              
                }else{
                  $payoutArr= array();
                  $payoutArr['user_id']= $id;
                  $payoutArr['amount']= 0;//3200
                  // $payoutArr['roi']= round(($amount/$duration),4);//4.4444
                  // $payoutArr['duration']= $duration;
                  $payoutArr['net_amount']= 0;
                  $payoutArr['tax_amount']= 0;
                  $payoutArr['amt_pin']= $amt_pin;
                  $payoutArr['left_bv']= $left_bv;
                  $payoutArr['right_bv']= $right_bv;
                  $payoutArr['match_bv']= $match_bv;
                  $payoutArr['laps_bv']= 0;
                  $payoutArr['laps_status']= 0;
                  // $payoutArr['invoice_id']= substr(number_format(time() * rand(), 0, '', ''), 0, '15');
                  $payoutArr['laps_amount']= $laps_amount;
                    //$payoutArr['product_id'] = $product_id;
                  $payoutArr['entry_time']= $dateTime;
                  $payoutArr['left_bv_before']= $before_l_bv;
                  $payoutArr['right_bv_before']= $before_r_bv;
                  $payoutArr['left_bv_carry']= $carry_l_bv;
                  $payoutArr['right_bv_carry']= $carry_r_bv;        	       
                  $payoutArr['capping_amount']=$capping_amount;
                  $payoutArr['remark']="Amount lapse due to no active topup Found";
                  $payoutArr['percentage']=$binary_per;
                  $payoutArr['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                  // $payoutArr['last_roi_entry_time'] = \Carbon\Carbon::now()->toDateTimeString();

                  array_push($insert_payout_arr,$payoutArr);
                  
                }

               

                      list($usec, $sec) = explode(" ", microtime());
                      $time_end = ((float)$usec + (float)$sec);
                         $time = $time_end - $time_start;
                         echo " --> time ".$time."\n";

                        // }//if
                }
            }//end foreach
          // bulk operation
        
          // carry bv insert
            $count = 1;
            $array = array_chunk($insert_carrybv_arr,1000);
           // dd($array);
            while($count <= count($array))
            {
              $key = $count-1;
              Carrybv::insert($array[$key]);
              echo $count." insert carry bv array ".count($array[$key])."\n";
              $count ++;
            }

            // payout insert
            $count1 = 1;
            $array1 = array_chunk($insert_payout_arr,1000);
          // dd($array1);
            while($count1 <= count($array1))
            {
              $key1 = $count1-1;
              PayoutHistory::insert($array1[$key1]);
              echo $count1." insert payout array ".count($array1[$key1])."\n";
              $count1 ++;
            }

            $count2 = 1;
            $array2 = array_chunk($update_currbv_arr,1000);
  
            while($count2 <= count($array2))
            {
              $key2 = $count2-1;
              $arrProcess = $array2[$key2];
              $ids = implode(',', array_column($arrProcess, 'id'));
              $rbv_qry = 'curr_r_bv = (CASE id';
              $lbv_qry = 'curr_l_bv = (CASE id';
              foreach ($arrProcess as $key => $val) {
                $rbv_qry = $rbv_qry . " WHEN ".$val['id']." THEN ".$val['curr_r_bv'];
                $lbv_qry = $lbv_qry . " WHEN ".$val['id']." THEN ".$val['curr_l_bv'];
              }
              $rbv_qry = $rbv_qry . " END)";
              $lbv_qry = $lbv_qry . " END)";
              $updt_qry = "UPDATE tbl_users SET ".$rbv_qry." , ".$lbv_qry." WHERE id IN (".$ids.")";
              $updt_user = DB::statement($updt_qry);
              
              echo $count2." update user array ".count($arrProcess)."\n";
              $count2 ++;
            }

            $count3 = 1; 
            $array3 = array_chunk($update_dash_arr,1000);
            while($count3 <= count($array3))
            {
              $key3 = $count3-1;
              $arrProcess = $array3[$key3];
              $ids = implode(',', array_column($arrProcess, 'id'));
              $binary_income_qry = 'binary_income = (CASE id';
              $working_wallet_qry = 'working_wallet = (CASE id';
              foreach ($arrProcess as $key => $val) {
                $binary_income_qry = $binary_income_qry . " WHEN ".$val['id']." THEN ".$val['binary_income'];
                $working_wallet_qry = $working_wallet_qry . " WHEN ".$val['id']." THEN ".$val['working_wallet'];
              }
              $binary_income_qry = $binary_income_qry . " END)";
              $working_wallet_qry = $working_wallet_qry . " END)";
              $updt_qry = "UPDATE tbl_dashboard SET ".$binary_income_qry." , ".$working_wallet_qry." WHERE id IN (".$ids.")";
              $updt_user = DB::statement($updt_qry);
              
              echo $count3." update dash array ".count($arrProcess)."\n";
              $count3 ++;
            }
       
    }//if
        echo "end ".now()."\n";
        echo "\n Cron run successfully \n" ;

        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "Binary Cron end at ".$current_time."\nTotal binary ids : ".$binary_count."\n";
        //sendSMS($mobile,$msg);
        echo $msg;

        echo "\n";
        list($usec, $sec) = explode(" ", microtime());
        $time_end1 = ((float)$usec + (float)$sec);
        $time = $time_end1 - $time_start1;
        echo "Total cron time " . $time . "\n";

    }//handle function end
}//class end