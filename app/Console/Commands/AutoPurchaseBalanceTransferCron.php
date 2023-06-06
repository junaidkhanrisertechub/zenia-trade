<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\FundTransfer;
use App\Models\PurchaseBalanceTransfer;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\Models\Dashboard;
use App\Models\AllTransaction;
use App\Models\Activitynotification;
use App\Models\ProjectSettings as ProjectSettingModel;
use Mail;
use DB;

class AutoPurchaseBalanceTransferCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:auto_purchase_balance_transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer purchase wallet Balance to one ID';

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

        $transferReq = PurchaseBalanceTransfer::where('status',0)->get();
        foreach ($transferReq as $key => $req) {
            
            /*$touser = User::select('id','user_id','email')->where('id',$req->user_id)->first();*/

            $arrPendings = User::join('tbl_dashboard as td', 'td.id', '=', 'tbl_users.id')
                    ->select('tbl_users.fullname as name','tbl_users.user_id','tbl_users.email','tbl_users.id as from_user_id',DB::raw('ROUND(td.top_up_wallet - td.top_up_wallet_withdraw , 6) as amount'))
                    ->where('tbl_users.email',$req->email)->where('tbl_users.id','!=',$req->user_id)
                    ->where('tbl_users.status','Active')
                    ->where(DB::raw('round((td.top_up_wallet - td.top_up_wallet_withdraw),2)'),'>=',1)->get();
           
            $totalAmount = 0;

            if (!empty($arrPendings)) {
                $to_user_id = $req->user_id;
                $toDash = $fromDash = $insertFundData = array();
                foreach ($arrPendings as $key => $user) {         
                    $trans_bal = $user->amount;
                    $from_user_id = $user->from_user_id;
                    if ($trans_bal >= 1) {

                        list($usec, $sec) = explode(" ", microtime());

                        $time_start = ((float)$usec + (float)$sec);                

                        $updateCoinData = array();
                        $tempData = [];
                        //---------update level income wd---------//
                        $updateCoinData['id'] = $from_user_id;
                        $updateCoinData['top_up_wallet_withdraw'] = DB::raw('top_up_wallet_withdraw +'.$trans_bal.'');
                        $updateCoinData['sent_purchase_balance'] = DB::raw('sent_purchase_balance +'.$trans_bal.'');
                        
                        array_push($fromDash,$updateCoinData);

                        /*$updateTouserData = array();
                        $updateTouserData['id'] = $to_user_id;
                        $updateTouserData['top_up_wallet'] = DB::raw('top_up_wallet +'.$trans_bal);
                        $updateTouserData['received_purchase_balance'] = DB::raw('received_purchase_balance +'.$trans_bal);
                        array_push($toDash,$updateTouserData);*/


                        $fundData = array();
                        $fundData['to_user_id'] = $to_user_id;
                        $fundData['from_user_id'] = $from_user_id;
                        $fundData['amount'] = $trans_bal;
                        $fundData['email'] = $req->email;
                        $fundData['balance_transfer_id'] = $req->id;
                        $fundData['wallet_type'] = 7;
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
                        echo $arrMessage  = $from_user_id." Purchase wallet balance is less than 1\n";
                    }
                }


                $count1 = 1;     
        
                $array1 = array_chunk($fromDash,1000);

                while($count1 <= count($array1))
                {
                    $key1 = $count1-1;
                    $arrProcess = $array1[$key1];
                    $ids = implode(',', array_column($arrProcess, 'id'));

                    $top_up_wallet_withdraw_qry = 'top_up_wallet_withdraw = (CASE id';
                    $sent_purchase_balance_qry = 'sent_purchase_balance = (CASE id';

                    foreach ($arrProcess as $key => $val) {
                        $top_up_wallet_withdraw_qry = $top_up_wallet_withdraw_qry . " WHEN ".$val['id']." THEN ".$val['top_up_wallet_withdraw'];
                        $sent_purchase_balance_qry = $sent_purchase_balance_qry . " WHEN ".$val['id']." THEN ".$val['sent_purchase_balance'];
                    }

                    $top_up_wallet_withdraw_qry = $top_up_wallet_withdraw_qry . " END)";
                    $sent_purchase_balance_qry = $sent_purchase_balance_qry . " END)";
                    
                    $updt_qry = "UPDATE tbl_dashboard SET ".$top_up_wallet_withdraw_qry." , ".$sent_purchase_balance_qry." WHERE id IN (".$ids.")";
                    $updt_user = DB::statement(DB::raw($updt_qry));

                    echo $count1." update from user dash array ".count($arrProcess)."\n";
                    $count1 ++;
                }
                /*$count2 = 1;     
        
                $array2 = array_chunk($toDash,1000);

                while($count2 <= count($array2))
                {
                    $key2 = $count2-1;
                    $arrProcess = $array2[$key2];
                    $ids = implode(',', array_column($arrProcess, 'id'));

                    $top_up_wallet_qry = 'top_up_wallet = (CASE id';
                    $received_purchase_balance_qry = 'received_purchase_balance = (CASE id';

                    foreach ($arrProcess as $key => $val) {
                        $top_up_wallet_qry = $top_up_wallet_qry . " WHEN ".$val['id']." THEN ".$val['top_up_wallet'];
                        $received_purchase_balance_qry = $received_purchase_balance_qry . " WHEN ".$val['id']." THEN ".$val['received_purchase_balance'];
                    }

                    $top_up_wallet_qry = $top_up_wallet_qry . " END)";
                    $received_purchase_balance_qry = $received_purchase_balance_qry . " END)";
                    
                    $updt_qry = "UPDATE tbl_dashboard SET ".$top_up_wallet_qry." , ".$received_purchase_balance_qry."  WHERE id IN (".$ids.")";
                    $updt_user = DB::statement(DB::raw($updt_qry));

                    echo $count2." update to user dash array ".count($arrProcess)."\n";
                    $count2 ++;
                }*/
                $updateTouserData = array();
                $updateTouserData['top_up_wallet'] = DB::raw('top_up_wallet +'.$totalAmount.'');
                $updateTouserData['received_purchase_balance'] = DB::raw('received_purchase_balance +'.$totalAmount.'');
                $updt_touser = Dashboard::where('id',$to_user_id)->update($updateTouserData);

                $count = 1;
                $array = array_chunk($insertFundData, 1000);
              
                while ($count <= count($array)) {
                    $key = $count - 1;
                    FundTransfer::insert($array[$key]);
                    echo $count." insert fund transfer count array ".count($array[$key])."\n";            
                    $count++;
                }

                $updtReq = PurchaseBalanceTransfer::where('id',$req->id)->update(array("transferred_amount"=>$totalAmount,'status'=>1));
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
        echo "this cron is inactive \n";
      }
    }
    else{
        echo "this cron is inactive\n";
    }


  }

}
