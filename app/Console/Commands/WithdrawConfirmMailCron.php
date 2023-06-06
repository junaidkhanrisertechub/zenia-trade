<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WithdrawalConfirmed;
use App\Models\Template;
use App\Models\ProjectSetting;
use DB;

class WithdrawConfirmMailCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:withdrawal_conform_mail';

    /**
     * The console command description.
     *
     * 
     * @var string
     */
    protected $description = 'Withdraw Confirmation Mail';

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
        $get_all_withdrawal = WithdrawalConfirmed::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_confirmed.id')
            ->select('tu.user_id', 'tu.email', 'tbl_withdrwal_confirmed.to_address', 'tbl_withdrwal_confirmed.sr_no')
            ->where('tbl_withdrwal_confirmed.mail_status', '=', 0)
            ->get();

        $temp_data = Template::where('title', '=', 'Withdraw Confirm')->first();
        $project_set_data = ProjectSetting::select('icon_image','domain_name')->first();

        // dd($get_all_withdrawal);
        foreach ($get_all_withdrawal as $user) {
            // $subject = "Withdrawal";
            $subject = $temp_data->subject;
            $content = $temp_data->content;
            $domain_name = $project_set_data->domain_name;
            $acount = $user['to_address'];
            $username = $user['user_id'];
            $pagename = "emails.withdraw_confirm";

            $dd1 = ['$acount', '$username'];
            $dd2 = [$acount, $username];
            $new_content = str_replace($dd1, $dd2, $content);

            $data = array('pagename' => $pagename, 'username' => $username, 'acount' => $acount, 'content' => $new_content,'domain_name' =>$domain_name);
            $mail = sendMail($data, $user['email'], $subject);

            $updateData = array();
            $updateData['mail_status'] = 1;
            $updateOtpSta = WithdrawalConfirmed::where('sr_no', $user['sr_no'])->update($updateData);
        }

        echo "Conform Withdrawal Mail Sent";
        echo "\n";
    }
}
