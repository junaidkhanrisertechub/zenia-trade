<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Config;
use App\Models\Topup;
use App\Models\TransactionInvoice;
use App\Models\Product;
use App\User as UserModel;
use App\Dashboard;
use DB;


class ReminderInvestCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:reminder_invest';    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invest Reminder Cron';

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
    
    public function handle(){   
        try {
            list($usec, $sec) = explode(" ", microtime());

            $time_start1 = ((float)$usec + (float)$sec);

            $current_time = \Carbon\Carbon::now()->toDateTimeString();

            $msg = "Invest reminder Cron started at " . $current_time;
            //sendSMS($mobile,$msg);
            echo $msg . "\n";

            $getUsers = UserModel::select('id','user_id','entry_time','status','type','fullname','email','topup_reminder_status')
            ->where('status', 'Active')->where('type', '')->where('topup_reminder_status', '0')
            ->orderBy('entry_time', 'asc')
            ->get();

            $i=0;
            foreach ($getUsers as $user) {
                $current_time = \Carbon\Carbon::now()->toDateTimeString();
                $today = date('Y-m-d H:i',strtotime($current_time));

                $deposit_info= TransactionInvoice::select('in_status','entry_time','top_up_status')->where([['in_status','1'],['top_up_status','1'],['id',$user->id]])->orderBy('srno', 'asc')->first();
                if (!empty($deposit_info)) {
                    
                    $deposit_entry_time=date('Y-m-d H:i',strtotime($deposit_info->entry_time));
                    $reminder_date=date('Y-m-d H:i', strtotime($deposit_entry_time. ' + 24 hours'));

                    $domain_link=Config::get('constants.settings.domainpath-vue')."login";
                    if (strtotime($reminder_date) <= strtotime($today)) {
                        echo "\nUser ID:".$user->id."\tDeposit entry_time: ".$deposit_entry_time."\tReminder date: ".$reminder_date."\tCurrent_time: ".$today;

                        $subject = "Funds Available in your Fund Wallet. Invest to start getting ROI ";
                        $pagename = "emails.added_funds_not_invested";

                        $data = array('pagename' => $pagename,'username' =>$user->fullname , 'domain_link' =>$domain_link);
                        $email =$user->email;
                        $mail = sendMail($data, $email, $subject);

                        $arr = array('topup_reminder_status'=>'1');
                        $updateUser=UserModel::where('id','=',$user->id)->update($arr);
                        $i++;
                    }
                }

            }
            echo "\n Cron run successfully \n";
            $current_time = \Carbon\Carbon::now()->toDateTimeString();
            $msg = "\nInvest Reminder Cron end at ".$current_time."\nTotal records : ".$i."\n";

            echo $msg;
            list($usec, $sec) = explode(" ", microtime());
            $time_end1 = ((float)$usec + (float)$sec);
            $time = $time_end1 - $time_start1;
            echo "\n Total cron time -> " . $time . "\n";
            
        } catch (Exception $e) {
            dd($e);
        }

    }
}
