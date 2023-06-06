<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Config;
use App\User as UserModel;
use App\Dashboard;
use DB;


class ReminderCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:reminder_referral_marketing';    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reminder Cron';

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

        list($usec, $sec) = explode(" ", microtime());

        $time_start1 = ((float)$usec + (float)$sec);

        $current_time = \Carbon\Carbon::now()->toDateTimeString();

        $msg = "Reminder Cron started at " . $current_time;
        //sendSMS($mobile,$msg);
        echo $msg . "\n";

        $getUsers = UserModel::select('id','user_id','entry_time','status','type','fullname','email','reminder_mail_status')
        ->where('status', 'Active')->where('type', '')->where('reminder_mail_status', '0')
        ->orderBy('entry_time', 'asc')
        ->get(); 

        $i=0;
        foreach ($getUsers as $user) {
            $current_time = \Carbon\Carbon::now()->toDateTimeString();
            $today = date('Y-m-d H:i',strtotime($current_time));

            $user_entry_time=date('Y-m-d H:i',strtotime($user->entry_time));
            $reminder_date=date('Y-m-d H:i', strtotime($user_entry_time. ' + 24 hours'));
            // dd('reminder_date',$reminder_date);
            $domain_link=Config::get('constants.settings.domainpath-vue')."logindyucatsrabdhsfdsadsad";
            // $reminder_marketing_date=date('Y-m-d H', strtotime($entry_time. ' + 1 day'));
            if (strtotime($reminder_date) <= strtotime($today)) {
                echo "\nUser ID:".$user->id."\tentry_time: ".$user_entry_time."\tReminder date: ".$reminder_date."\tcurrent_time: ".$today;

                $subject_referral = "Here's your HSCC referral link";
                $subject_marketing = "Get yourself armed with HSCC Marketing Tools.";
                $pagename_referral = "emails.using_referral_links";
                $pagename_marketing = "emails.using_marketing_tools";
                $data_referral = array('pagename' => $pagename_referral,'username' => $user->fullname , 'domain_link' =>$domain_link);
                $data_marketing = array('pagename' => $pagename_marketing,'username' =>$user->fullname , 'domain_link' =>$domain_link);
                $email =$user->email;
                $mail_referral = sendMail($data_referral, $email, $subject_referral);
                $mail_marketing = sendMail($data_marketing, $email, $subject_marketing);

                $arr = array('reminder_mail_status'=>'1');
                $updateUser=UserModel::where('id','=',$user->id)->update($arr);
                $i++;
            }

        }
        echo "\n Cron run successfully \n";
        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "\nReminder Cron end at ".$current_time."\nTotal records : ".$i."\n";

        echo $msg;
        list($usec, $sec) = explode(" ", microtime());
        $time_end1 = ((float)$usec + (float)$sec);
        $time = $time_end1 - $time_start1;
        echo "\n Total cron time -> " . $time . "\n";
    }
}
