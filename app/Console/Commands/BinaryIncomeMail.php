<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PayoutHistory;
use App\User as UserModel;
use App\Models\Topup;
use App\Models\Dashboard;
use App\Models\ProjectSetting;

class BinaryIncomeMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:binary_income_mail';

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
        try {
            $day = \Carbon\Carbon::now()->format('D');
            $project_set_data = ProjectSetting::select('binary_mail_day')->first();
            $weekday= \Carbon\Carbon::parse($project_set_data->binary_mail_day)->format('l');
            if($day == $project_set_data->binary_mail_day){
                list($usec, $sec) = explode(" ", microtime());

                $time_start1 = ((float)$usec + (float)$sec);

                $current_time = \Carbon\Carbon::now()->toDateTimeString();

                $msg = "Binary Income Weekly Cron started at " . $current_time;
                //sendSMS($mobile,$msg);
                echo $msg . "\n";
                $start_date = date('Y-m-d',strtotime("previous week Monday")); 
                $end_date = date('Y-m-d',strtotime("previous week Sunday"));
                 
                $current_time = \Carbon\Carbon::now()->toDateTimeString();

                $getUsers = UserModel::select('id','user_id','entry_time','topup_status','status','type','fullname','email')
                ->where('status', 'Active')->where('type', '')->where('topup_status', '1')
                ->orderBy('entry_time', 'asc')
                ->get();

                
                // dd($start_date,$end_date,count($usersList));
                $i=0;
                foreach ($getUsers as $user) {

                    $direct_income_info = PayoutHistory::selectRaw('SUM(amount) as total_binary_income, COUNT(id) as b_count')->where('user_id',$user->id)->whereBetween('entry_time',[$start_date,$end_date])->first();

                    $topup= Topup::where('id',$user->id)->selectRaw("COUNT(srno) as tp_count,SUM(total_income) as capping_amount")->first();

                    $dash = Dashboard::selectRaw('round((roi_income+direct_income+binary_income+hscc_bonus_income),2) as total_income')->where('id',$user->id)->first();

                    $capping = $topup->capping_amount;
                    $total_income = $dash->total_income;
                    $total_records = $direct_income_info->b_count;
                    $weekly_binary_income = $direct_income_info->total_binary_income;

                    if ($total_records > 0) {
                        // if ($total_income < $capping) {

                            echo "\nUser ID:".$user->id."\tCapping: ".$capping."\tTotal Income: ".$total_income."\tstart: ".$start_date."\tend: ".$end_date."\nWeekly Binary Income: ".$weekly_binary_income."\n";
                            
                            $subject = "Binary Income Weekly Report";
                            $pagename = "emails.binaryincome";
                            $data = array('pagename' => $pagename,'username' =>$user->fullname,'weekly_binary_income'=>$weekly_binary_income);
                            $email =$user->email;
                            $mail = sendMail($data, $email, $subject);
                            $i++;
                        // }
                    }
                    
                }
                echo "\n Cron run successfully \n";
                $current_time = \Carbon\Carbon::now()->toDateTimeString();
                $msg = "\nBinary Income weekly Cron end at ".$current_time."\nTotal records : ".$i."\n";

                echo $msg;
                list($usec, $sec) = explode(" ", microtime());
                $time_end1 = ((float)$usec + (float)$sec);
                $time = $time_end1 - $time_start1;
                echo "\n Total cron time -> " . $time . "\n";

            }else{

                echo "\n Binary weekly reports are only available on ".$weekday." \n";
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }
}
