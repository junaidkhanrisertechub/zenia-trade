<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\FundTransfer;
use App\Models\BalanceTransfer;
use App\Models\Dashboard;
use App\Models\AllTransaction;
use App\Models\Activitynotification;
use App\Models\ProjectSettings as ProjectSettingModel;
use Mail;
use DB;

class AutoBalanceTransferCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:auto_balance_transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer Balance to one ID';

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

        $transferReq = BalanceTransfer::where('status',0)->get();
        foreach ($transferReq as $key => $req) {
            

            $arrPendings = User::join('tbl_dashboard as td', 'td.id', '=', 'tbl_users.id')->select('tbl_users.fullname as name','tbl_users.user_id','tbl_users.email','tbl_users.id as from_user_id',DB::raw('ROUND(td.working_wallet - td.working_wallet_withdraw , 2) as amount'))
                    ->where('tbl_users.email',$req->email)->where('tbl_users.id','!=',$req->user_id)
                    ->where('tbl_users.status','Active')
                    ->whereRaw('(td.working_wallet - td.working_wallet_withdraw) >=20')->get();
           
            $totalAmount = 0;

            if (!empty($arrPendings)) {
                $to_user_id = $req->user_id;
                foreach ($arrPendings as $key => $user) {                    
                    $trans_bal = $user->amount;
                    $from_user_id = $user->from_user_id;
                    if ($trans_bal >= 20) {
                        $updateCoinData = array();
                        $tempData = [];
                        //---------update level income wd---------//
                        $updateCoinData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw +'.$trans_bal.'');
                        $updateCoinData['sent_balance'] = DB::raw('sent_balance +'.$trans_bal.'');
                        $updateCoinData['usd'] = DB::raw('usd -'.$trans_bal.'');
                        $updateCoinData = Dashboard::where('id', $from_user_id)->limit(1)->update($updateCoinData);


                        $updateTouserData = array();
                        $updateTouserData['working_wallet'] = DB::raw('working_wallet +'.$trans_bal.'');
                        $updateTouserData['received_balance'] = DB::raw('received_balance +'.$trans_bal.'');
                        $updateCoinData = Dashboard::where('id', $to_user_id)->limit(1)->update($updateTouserData);

                        $fundData = array();
                        $fundData['to_user_id'] = $to_user_id;
                        $fundData['from_user_id'] = $from_user_id;
                        $fundData['amount'] = $trans_bal;
                        $fundData['email'] = $req->email;
                        $fundData['balance_transfer_id'] = $req->id;
                        $fundData['wallet_type'] = 4;
                        $fundData['entry_time'] = $this->today;
                        
                        $insFund = FundTransfer::create($fundData);
                        $email = $user->email;
                        $totalAmount = $totalAmount + $trans_bal;
                        echo $arrMessage  = $user->user_id." working wallet transferred ".$trans_bal." to ".$to_user_id."\n";
                    } else {
                        echo $arrMessage  = $user->user_id." working wallet balance is less than 20\n";
                    }
                }                   
                $updtReq = BalanceTransfer::where('id',$req->id)->update(array("transferred_amount"=>$totalAmount,'status'=>1));
                echo $arrMessage  = "Amount transfer successfully\n";
            } else {
                echo $arrMessage  = "Invalid user\n";
            }
        }
    }

}
