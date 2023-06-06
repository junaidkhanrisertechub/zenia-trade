<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\WithdrawPending;
use App\Models\WithdrawalConfirmed;
use App\Models\Dashboard;
use DB;
use Mail;

class RevertWithdrawCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:revert_withdraw';

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
           ->where(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"),"2021-08-28")
           ->get();
         
         foreach($with_req as $wr){
            
            $checkExist = WithdrawalConfirmed::where('wp_ref_id',$wr->sr_no)->first();
            if(empty($checkExist)){

                $coinpayment_id = 0;
                $arrUpdate = [
                    'status' => 2,
                    'remark' => 'Rejected and reverted to dex wallet',/*$req['remark'],*/
                    'api_ref_id' => '',
                    'paid_date' =>  \Carbon\Carbon::now()
                ];
                $updatePending = WithdrawPending::where('sr_no',$wr->sr_no)->update($arrUpdate);
                $dashArr=array();
                $dash['working_wallet']=DB::RAW('working_wallet +'.($wr->total));
                $updtDash = Dashboard::where('id',$wr->id)->update($dash);
                   
                echo "Withdrawal request ".$wr->sr_no." reverted successfull\n";                        
                
            }
        }        
       
    }

}
