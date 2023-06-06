<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use DB;
use Response;
use App\User;
use App\Models\Topup;


class TopupMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:topup_mail';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'topup_mail';

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

    	 $topupUsersList = Topup::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
         ->select('tu.email','tu.user_id','tbl_topup.pin','tbl_topup.amount','tbl_topup.topupfrom','tbl_topup.product_name')->where('tbl_topup.topup_mail_status','=',0)
         ->get();

    	foreach ($topupUsersList as $user)
        {
            if($user->topupfrom == "New Fund Wallet")
            {
                $subject = "Topup Done Successfully";
                $pagename = "emails.topup";
                $email = $user->email;
                $user_id = $user->user_id;
                $amount = $user->amount;
                $data = array('pagename' => $pagename,'email' =>$email, 'username' =>$user_id ,'amount'=>$amount);
                $mail = sendMail($data, $email, $subject);

                array_push($user_arr, $user->pin);

            }else{

                $subject = "Package Activated";
                $pagename = "emails.deposit";
                $email = $user->email;
                $user_id = $user->user_id;
                $amount = $user->amount;
                $package = $user->product_name;
                $data = array('pagename' => $pagename,'email' =>$email, 'username' =>$user_id ,'amount'=>$amount, 'Package'=>$package);
                $mail = sendMail($data, $email, $subject);

                array_push($user_arr, $user->pin);
            }
    	}

        $user_count = 1;     
        $user_array = array_chunk($user_arr,1000);
        while($user_count <= count($user_array))
        {
          $user_key = $user_count-1;
          Topup::whereIn('pin',$user_array[$user_key])->update(['topup_mail_status'=>'1']);
          $user_count ++;
        }                               
    }
}


                           