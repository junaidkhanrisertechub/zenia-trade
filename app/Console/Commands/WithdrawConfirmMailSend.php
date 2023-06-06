<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use DB;
use Response;
use App\User;
use App\Models\WithdrawalConfirmed;


class WithdrawConfirmMailSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:withdraw_confirm_mail_send';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'withdraw_confirm_mail';

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

    	 $withdrawConfirmList = WithdrawalConfirmed::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_confirmed.id')
         ->select('tu.email','tu.user_id','tbl_withdrwal_confirmed.sr_no','tbl_withdrwal_confirmed.amount','tbl_withdrwal_confirmed.deduction')
         ->where('tbl_withdrwal_confirmed.withdraw_confirm_mail_status','=',0)
         ->get();

    	foreach ($withdrawConfirmList as $user)
        {
            if(!empty($user))
            {
                $subject = "Withdrawal paid successfully";
                $pagename = "emails.withdraw_approved";
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
          WithdrawalConfirmed::whereIn('sr_no',$user_array[$user_key])->update(['withdraw_confirm_mail_status'=>'1']);
          $user_count ++;
        }                               
    }
}                         