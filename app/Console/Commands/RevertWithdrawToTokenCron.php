<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\WithdrawPending;
use App\Models\WithdrawalConfirmed;
use App\Models\Dashboard;
use App\Models\IcoPhases;
use App\Models\WalletTransactionLog;
use DB;
use Mail;

class RevertWithdrawToTokenCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:revert_withdraw_to_token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revert Withdraw';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

         $with_req = WithdrawPending::select(DB::raw('tbl_withdrwal_pending.amount+tbl_withdrwal_pending.deduction as total'),'tbl_withdrwal_pending.amount as net_amount','tbl_withdrwal_pending.deduction as net_amount','tbl_withdrwal_pending.sr_no','tbl_withdrwal_pending.id')
           ->where([['tbl_withdrwal_pending.status',0],['tbl_withdrwal_pending.verify',0]])->orderBy('sr_no','desc')
           ->where(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"),"2021-09-05")
           ->get();
         
         foreach($with_req as $wr){

            $amount = $wr->total;

            $todayphase = IcoPhases::select('srno','name','from_date','to_date','total_supply','sold_supply','usd_rate','status','min_coin')->where('srno','=',3)->where('status','=','Available')->first();
            $coins = round(($amount/$todayphase->usd_rate),2);
            if ($todayphase != NULL) {
                $supplyBal=$todayphase->total_supply - $todayphase->sold_supply;
                if($supplyBal <=0){
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'No Token available Sold Out', ''); 
                }
                if($coins < $todayphase->min_coin){

                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Enter greater than '.$todayphase->min_coin, '');
                }
                $status="Available";
                $sold_supply =$coins+$todayphase->sold_supply; 
                IcoPhases::where('srno', $todayphase->srno)->update(['sold_supply' => $sold_supply, 'status' => $status]);

                $transWallet = new WalletTransactionLog;

                $transWallet->from_user_id=$wr->id;
                $transWallet->to_user_id=$wr->id;
                $transWallet->amount=$amount;
                $transWallet->wallet_type=1;
                $transWallet->remark='Buy Coin ' . $coins;
                $transWallet->entry_time=\Carbon\Carbon::now();
                $transWallet->save();



                $coinpayment_id = 0;
                $arrUpdate = [
                    'status' => 2,
                    'remark' => 'Rejected and token purchased',/*$req['remark'],*/
                    'api_ref_id' => '',
                    'paid_date' =>  \Carbon\Carbon::now()
                ];
                $updatePending = WithdrawPending::where('sr_no',$wr->sr_no)->update($arrUpdate);

                Dashboard::where('id', $wr->id)->limit(1)->update(['coin' => DB::raw('coin + '.$coins)]);

            }
            

               
            echo "Withdrawal request ".$wr->sr_no." reverted successfull\n";                        
        }        
       
    }

}
