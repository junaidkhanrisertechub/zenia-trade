<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use DB;
use Response;
use App\User;
use App\Models\WithdrawPending;


class WithdrawRequestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:withdraw_request_mail';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'withdraw_request_mail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
        $user_arr = array();

    	 $withdrawRequestList = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
         ->select('tu.email','tu.user_id','tbl_withdrwal_pending.sr_no','tbl_withdrwal_pending.amount','tbl_withdrwal_pending.deduction')->where('tbl_withdrwal_pending.withdraw_request_mail_status','=',0)
         ->get();

    	foreach ($withdrawRequestList as $user)
        {
            if(!empty($user))
            {
                $subject = "Withdraw request received successfully";
                $pagename = "emails.withdraw_request";
                $email = $user->email;
                $user_id = $user->user_id;
                $amount = $user->amount + $user->deduction;
                $data = array('pagename' => $pagename,'email' =>$email, 'username' =>$user_id ,'amount'=>$amount);
                $mail = sendMail($data, $email, $subject);

                array_push($user_arr, $user->sr_no);
            }
    	}

        $user_count = 1;     
        $user_array = array_chunk($user_arr,1000);
        while($user_count <= count($user_array))
        {
          $user_key = $user_count-1;
          WithdrawPending::whereIn('sr_no',$user_array[$user_key])->update(['withdraw_request_mail_status'=>'1']);
          $user_count ++;
        }                               
    }
}


                           