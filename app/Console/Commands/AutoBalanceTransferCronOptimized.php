<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\FundTransfer;
use App\Models\BalanceTransfer;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\Models\Dashboard;
use App\Models\AllTransaction;
use App\Models\Activitynotification;
use App\Models\ProjectSettings as ProjectSettingModel;
use Mail;
use DB;

class AutoBalanceTransferCronOptimized extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:auto_balance_transfer_optimized';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer wallet Balance to one ID';

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

      $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();
      
      if($croncheck != null)
      {
            
      if($croncheck->status ==1)
      {
      echo "this cron is active\n";

        $transferReq = BalanceTransfer::where('status',0)->get();
        if(sizeof($transferReq) > 0)
        {
        foreach ($transferReq as $key => $req) {
            
            /*$touser = User::select('id','user_id','email')->where('id',$req->user_id)->first();*/

            $arrPendings = User::join('tbl_dashboard as td', 'td.id', '=', 'tbl_users.id')
                    ->select('tbl_users.fullname as name','tbl_users.user_id','tbl_users.email','tbl_users.id as from_user_id',DB::raw('ROUND(td.working_wallet - td.working_wallet_withdraw , 2) as amount'))
                    ->where('tbl_users.email',$req->email)->where('tbl_users.id','!=',$req->user_id)
                    ->where('tbl_users.status','Active')
                    ->where(DB::raw('round((td.working_wallet - td.working_wallet_withdraw),2)'),'>=',20)
                   // ->limit(1)
                    ->get();
           
            $totalAmount = 0;

            if (!empty($arrPendings)) {
                $to_user_id = $req->user_id;
                $toDash = $fromDash = $insertFundData = array();
                foreach ($arrPendings as $key => $user) {         
                    $trans_bal = $user->amount;
                    $from_user_id = $user->from_user_id;
                    if ($trans_bal >= 20) {

                        list($usec, $sec) = explode(" ", microtime());

                        $time_start = ((float)$usec + (float)$sec);                

                        $updateCoinData = array();
                        $tempData = [];

                        //---------update level income wd---------//

                        $updateCoinData['id'] = $from_user_id;
                        $updateCoinData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw +'.$trans_bal.'');
                        $updateCoinData['sent_balance'] = DB::raw('sent_balance +'.$trans_bal.'');
                        
                        array_push($fromDash,$updateCoinData);

                        
                        $fundData = array();
                        $fundData['to_user_id'] = $to_user_id;
                        $fundData['from_user_id'] = $from_user_id;
                        $fundData['amount'] = $trans_bal;
                        $fundData['email'] = $req->email;
                        $fundData['balance_transfer_id'] = $req->id;
                        $fundData['wallet_type'] = 4;
                        $fundData['entry_time'] = $this->today;
                        
                        array_push($insertFundData,$fundData);

                        $email = $user->email;
                        $totalAmount = $totalAmount + $trans_bal;

                        list($usec, $sec) = explode(" ", microtime());
                        $time_end = ((float)$usec + (float)$sec);
                        $time = $time_end - $time_start;

                        echo "time ".$time."\n";
                        echo "Transfer ".$trans_bal." from ".$from_user_id." To ".$to_user_id ."\n";

                    } else {
                        echo $arrMessage  = $from_user_id." Wallet balance is less than 1\n";
                    }
                }


                $count1 = 1;     
        
                $array1 = array_chunk($fromDash,1000);

                while($count1 <= count($array1))
                {
                    $key1 = $count1-1;
                    $arrProcess = $array1[$key1];
                    $ids = implode(',', array_column($arrProcess, 'id'));

                    $working_wallet_withdraw_qry = 'working_wallet_withdraw = (CASE id';
                    $sent_balance_qry = 'sent_balance = (CASE id';

                    foreach ($arrProcess as $key => $val) {
                        $working_wallet_withdraw_qry = $working_wallet_withdraw_qry . " WHEN ".$val['id']." THEN ".$val['working_wallet_withdraw'];
                        $sent_balance_qry = $sent_balance_qry . " WHEN ".$val['id']." THEN ".$val['sent_balance'];
                    }

                    $working_wallet_withdraw_qry = $working_wallet_withdraw_qry . " END)";
                    $sent_balance_qry = $sent_balance_qry . " END)";
                    
                    $updt_qry = "UPDATE tbl_dashboard SET ".$working_wallet_withdraw_qry." , ".$sent_balance_qry." WHERE id IN (".$ids.")";
                    $updt_user = DB::statement(DB::raw($updt_qry));

                    echo $count1." update from user dash array ".count($arrProcess)."\n";
                    $count1 ++;
                }
               
                $updateTouserData = array();
                $updateTouserData['working_wallet'] = DB::raw('working_wallet +'.$totalAmount.'');
                $updateTouserData['received_balance'] = DB::raw('received_balance +'.$totalAmount.'');
                $updt_touser = Dashboard::where('id',$to_user_id)->update($updateTouserData);

                $count = 1;
                $array = array_chunk($insertFundData, 1000);
              
                while ($count <= count($array)) {
                    $key = $count - 1;
                    FundTransfer::insert($array[$key]);
                    echo $count." Insert fund transfer count array ".count($array[$key])."\n";            
                    $count++;
                }

               $updtReq = BalanceTransfer::where('id',$req->id)->update(array("transferred_amount"=>$totalAmount,'status'=>1));

                echo $arrMessage  = "Amount transfer successfully\n";
            } else {
                echo $arrMessage  = "Invalid user\n";
            }
        }

        $today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();
        //$today_datetime2 = $today->toDateString();
      
    $cronRunEntry=new CronRunStatus();
    $cronRunEntry['cron_id'] =$croncheck->id;
    $cronRunEntry['run_status'] =1;
    $cronRunEntry['run_time'] =$today_datetime;
    $cronRunEntry->save();
        }
        else{
            echo "No Data Available \n";
        }
    }
    else{
        echo "this cron is inactive \n";
      }
    }
    else{
        echo "this cron is inactive \n";
    }
  }

}
