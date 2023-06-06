<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PayoutHistory;
use App\Models\Template;
use App\Models\ProjectSetting;

class BinaryIncomeMail_old extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:binary_income_mail_old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'binary_income_mail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dd("Run binary_income_mail cron");
        try {
            // $usersList=PayoutHistory::select('u.email','u.id','u.user_id','u.mobile','tbl_payout_history.amount','tbl_payout_history.id as pid','n_email.roi')
            //     ->where([['tbl_payout_history.mail_status','=','0'],['n_email.direct','=','1']])
            //     ->join('tbl_users as u','u.id','=','tbl_payout_history.user_id')
            //     ->join('tbl_notification_email_settings as n_email','n_email.user_id','=','u.id')
            //     ->get();

            $usersList = PayoutHistory::select('u.email', 'u.id', 'u.user_id', 'u.mobile', 'tbl_payout_history.amount', 'tbl_payout_history.id as pid')
                ->where([['tbl_payout_history.mail_status', '=', '0']])
                ->join('tbl_users as u', 'u.id', '=', 'tbl_payout_history.user_id')
                //->join('tbl_notification_email_settings as n_email','n_email.user_id','=','u.id')
                ->get();

            $temp_data = Template::where('title', '=', 'Binary Income Email')->first();
            $project_set_data = ProjectSetting::select('icon_image','domain_name')->first();
            foreach ($usersList as $user) {

                $subject = $temp_data->subject;
                $pagename = "emails.binaryincome";
                $content = $temp_data->content;
                $domain_name = $project_set_data->domain_name;
                $amount = $user->amount;
                $username = $user->user_id;

                $dd1 = ['$amount', '$username'];
                $dd2 = [$amount, $username];
                $new_content = str_replace($dd1, $dd2, $content);

                $data = array('pagename' => $pagename, 'email' => $user->email, 'username' => $username, 'content' => $new_content, 'amount' => $amount,'domain_name' =>$domain_name);
                $email = $user->email;
                $mail = sendMail($data, $email, $subject);

                $arr = array('mail_status' => '1');

                PayoutHistory::where('id', '=', $user->pid)->update($arr);
                echo "\n Mail Send to : $user->user_id \n";

                //  broadcast 
                // $buy_user = ['username' => $user->fullname, 'user_id' => $user->user_id];
                // $msg = $user->user_id . ' has Generated Binary Revenue ' . $user->amount . ' successfully!! ';
                // $url = '';
                // $msg1 = array('message' => $msg, 'notification' => 'true', 'url' => $url);

                // $BinaryIncomeNotification = new Activitynotification;
                // $BinaryIncomeNotification->id  = $user->id;
                // $BinaryIncomeNotification->message   = $msg;
                // $BinaryIncomeNotification->status =  0;
                // $BinaryIncomeNotification->save();

                // broadcast(new MessageSent($buy_user, $msg1));
                // usleep(1000);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }
}
