<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\EWalletController;
use App\Http\Controllers\userapi\SettingsController;
use App\User;
use App\Models\WithdrawPending;
use App\Models\WithdrawalConfirmed;
use App\Models\CronRunStatus;
use App\Models\CronStatus;
use App\Models\Names;
use Mail;

class AutoSendCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:auto_withdraw_send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Withdraw';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EWalletController $ewallet,SettingsController $settings) {
        parent::__construct();
        $this->ewallet = $ewallet;
        $this->settings = $settings;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

      $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();

      if($croncheck->status ==1)
      {
      echo "this cron is active\n";

         $with_req = WithdrawPending::select('tbl_withdrwal_pending.*','tu.user_id')->join('tbl_users as tu','tu.id','=','tbl_withdrwal_pending.id')
           ->where([['tbl_withdrwal_pending.status',0],['tbl_withdrwal_pending.verify',1]])->orderBy('sr_no','desc')
           /*->limit(1)*/
           //->where('tbl_withdrwal_pending.sr_no',21)
           ->get();
           //dd($with_req);
         $now = \Carbon\Carbon::now()->toDateTimeString();
         
         $arrInput = [];
         foreach($with_req as $wr){
               
            if($now >= $wr->entry_time){

                $arrInput['srno'] = $wr->sr_no;
                $arrInput['remark'] = $wr->remark;
                $names=Names::where('sr_no',$wr->api_sr_no)->first();
                $env = $names->subject;

                $ciphering = "AES-128-CTR"; 

                $iv_length = openssl_cipher_iv_length($ciphering); 
                $options = 0; 

                $decryption_iv = '1874654512313213'; 

                $decryption_key = "h9mnEzPXqkfkF9Eb"; 
                $decryption=openssl_decrypt ($env, $ciphering, $decryption_key, $options, $decryption_iv);
                $arrInput["admin_otp"]=$decryption;

                $checkExist = WithdrawalConfirmed::where('wp_ref_id',$wr->sr_no)->first();
                if(empty($checkExist)){

                    $req = new Request;
                    $req['address'] = $wr->to_address;
                    $req['id'] = $wr->id;
                    $req['network_type'] = 'BTC';
                    $checkvalidAddress =  $this->settings->checkAddresses($req);
                    //dd($wr->to_address,$checkvalidAddress->original['code']);
                    /*if($checkvalidAddress->original['code'] == 200){*/
                    // dd($this->settings);
                    // dd('hiii');
                    $callcron = 1;
                   $res = $this->ewallet->PaymentsSendApiNew($wr,$arrInput,'coinpayment',$callcron);

                   echo $res;

                /*}*/
                }
            }else{
            //dd('hiiii');
            }
        }        
       $cronRunEntry=new CronRunStatus();
    $cronRunEntry['cron_id'] =$croncheck->id;
    $cronRunEntry['run_status'] =1;
    $cronRunEntry['run_time'] =$now;
    $cronRunEntry->save();
    }
    else{
        echo "this cron is inactive \n";
      }
  }

}
