<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\WithdrawTransactionController;
use App\User;
use App\Models\DailyBonus;
use App\Models\Topup;
use Config;

class AutoWithdrawIncome extends Command {
     

      /*public function __construct() {
       
          $this->statuscode = Config::get('constants.statuscode');
    }*/
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Auto:Withdraw';
    protected $hidden = true;





    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto withdraw working Income';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(WithdrawTransactionController $withdrawRoi) {
        parent::__construct();
        $this->withdrawRoi = $withdrawRoi;
         $this->statuscode = Config::get('constants.statuscode');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $day = \Carbon\Carbon::now()->format('D');
       
        if($day != 'Wed'){
        // dd('In');
          // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Working Wallet withdrawal only allowed on Wed', '');
            $this->info('Working Wallet withdrawal only allowed on Wed'); 
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Working Wallet withdrawal only allowed on Wed', '');
        }
        //dd('hii');
        $user = User::join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->select('tbl_users.id','tbl_users.remember_token','tbl_dashboard.working_wallet','tbl_dashboard.working_wallet_withdraw','tbl_dashboard.roi_income','tbl_dashboard.roi_income_withdraw')->where('tbl_users.status', '=', 'Active')
          // ->where('tbl_users.id',580)
           ->get();
      
        if (!empty($user)) {
            foreach ($user as $key => $value) {
                $working_wallet_balance = $user[$key]->working_wallet-$user[$key]->working_wallet_withdraw;
                $roi_income_balance = $user[$key]->roi_income-$user[$key]->roi_income_withdraw;
                $id = $user[$key]->id;
                /*26-12-2018 
                if(($working_wallet_balance) >= 0.02){*/
               // dd($working_wallet_balance);
                $check_if_topuo_exist = Topup::where('id',$id)->pluck('id');
                if(COUNT($check_if_topuo_exist) >= 1)
                {
                  if(($working_wallet_balance) >= 10){
                    
                     $checkUpdate = $this->withdrawRoi->AutoWithdrawWorkingIncome($id);
                  }else{
                   
                  }
                 }
            }
            $this->info('Weekly Working wallet withdraw successfully');
        } else {

            $this->info('User is not exist');
        }
    }

}
