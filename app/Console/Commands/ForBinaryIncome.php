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
use App\Models\Topup;
use App\Models\QualifiedUserList as QualifiedUsers;
use App\Traits\Users;

class ForBinaryIncome extends Command
{

    use Users;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:binary_income';

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

        dd("This is old cron please use cron:optimized_binary_income cron");
        $now = \Carbon\Carbon::now();
        $day=date('D', strtotime($now));
        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "Qualify Cron started at ".$current_time;
        //sendSMS($mobile,$msg);
        echo $msg."\n";

        echo "start ".now()."\n";
/*
          if ($day !='Thu')
            {
                 $this->info('Binary Income is Allowed only on thursday');
                 return sendResponse(404, 'HTTP_INTERNAL_SERVER_ERROR', 'NOT ALLOWED', '');
            }*/
        
        $isExistUser = User::select("tbl_users.id","tbl_users.user_id As name","tbl_users.rank")->leftjoin('tbl_curr_amt_details as tcad','tcad.user_id',"=",'tbl_users.id')->where('tbl_users.rank','!=',null)->where('tbl_users.binary_qualified_status',1)->whereNull('tcad.user_id')->get();
        if(!is_null($isExistUser)) {
            foreach($isExistUser as $user) {
                
                $user_id = $user->id;  
                /*$isUsrQualifed = $this->objBinaryIncome->chkQualifiedBinaryIncomeNew($user_id);
                if($isUsrQualifed !== false ) { */   
                $insBinaryIncome = $this->objBinaryIncome->QualifiedCurrentAmtDetailsNew($user_id);
                /*}*/ 
            }
        }

        $getUserDetails = User::join('tbl_curr_amt_details as tcad','tbl_users.id','=','tcad.user_id')->select("tcad.user_id","tcad.left_bv","tcad.right_bv","tbl_users.pan_no")
        ->where([['tcad.left_bv','>',0],['tcad.right_bv','>',0],['tbl_users.rank','!=',null]])
        ->get();
        
        if(!empty($getUserDetails))
        {

            $payout_no=PayoutHistory::max('payout_no');
            if(empty($payout_no)){
              $payout_no=1;
            }else{
              $payout_no=$payout_no+1; 
            }
            $binary_count = 0;
            foreach ($getUserDetails as $key => $value) {
                $deduction=array();
                $deduction['netAmount']=$deduction['tdsAmt']=$deduction['amt_pin']=0;

                $id=$getUserDetails[$key]['user_id'];

                $this->check_rank($id);
                
                $checkQualified = QualifiedUsers::where([['user_id',$id]])->first();
                if(!empty($checkQualified )) {   
                    $left_bv=$getUserDetails[$key]['left_bv']; 
                    $right_bv=$getUserDetails[$key]['right_bv'];
                 
                    $getVal=min($left_bv,$right_bv);
                    $getTotalPair=$getVal;
                    if($getTotalPair!=0){
                        $arrdata=array();
                        $arrdata['id']=$id;
                        $arrdata['getTotalPair']=$getTotalPair;
                        $arrdata['perPair']=1;
                        $arrdata['payout_no']=$payout_no;
                        $arrdata['left_bv']=$left_bv;
                        $arrdata['right_bv']=$right_bv;
                        $arrdata['match_bv']=$getVal;
                        $arrdata['laps_position'] = 1;
                        $this->objBinaryIncome->PerPairBinaryIncomeNew($arrdata,$deduction);
                        $binary_count = $binary_count + 1;
                    }
                }
            }
        }

        echo "end ".now()."\n";
        echo "\n Cron generated successfully \n" ;

        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "Binary Cron end at ".$current_time."\nTotal Records: ".$binary_count."\n";
        //sendSMS($mobile,$msg);
        echo $msg;

         $this->info("Binary Income Cron Run Successfully At ".date("Y-m-d H:i:s"));
    }
}