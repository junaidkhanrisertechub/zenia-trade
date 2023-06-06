<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Events\MessageSent;
use DB;
use Response;
use App\User as UserModel;
//use App\Http\Controllers\userapi\AwardRewardController;
use App\Models\DailyBonus;
use App\Models\Dashboard;
use App\Models\Topup;
use App\Models\ProjectSetting;

class RoiIncomeMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:roi_income_mail';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'roi_income_mail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    // public function __construct(AwardRewardController $assignaward)
    public function __construct()
    {
        parent::__construct();
       // $this->assignaward = $assignaward; 
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
            $project_set_data = ProjectSetting::select('roi_mail_day')->first();
            $weekday= \Carbon\Carbon::parse($project_set_data->roi_mail_day)->format('l');
            if($day == $project_set_data->roi_mail_day){
                list($usec, $sec) = explode(" ", microtime());

                $time_start1 = ((float)$usec + (float)$sec);

                $current_time = \Carbon\Carbon::now()->toDateTimeString();

                $msg = "ROI Income Weekly Cron started at " . $current_time;
                //sendSMS($mobile,$msg);
                echo $msg . "\n";
                $start_date = date('Y-m-d',strtotime("last Monday")); 
                $end_date = date('Y-m-d',strtotime("Yesterday"));
                // dd($start_date,$end_date);
                 
                $current_time = \Carbon\Carbon::now()->toDateTimeString();

                $getTopup = Topup::select('id','amount','pin','amount_roi','percentage','total_income','last_roi_entry_time','duration','total_roi_count')
                ->orderBy('srno', 'asc')
                ->get();

                
                // dd($start_date,$end_date,count($usersList));
                $i=0;
                foreach ($getTopup as $tp) {
                    $roi_income_info = DailyBonus::selectRaw('COALESCE(SUM(amount), 0) as total_roi_income, COUNT(sr_no) as roi_count')->where('pin',$tp->pin)->whereBetween('entry_time',[$start_date,$end_date])->first();

                    $topup= Topup::where('id',$tp->id)->selectRaw("COUNT(srno) as tp_count,SUM(total_income) as capping_amount")->first();

                    $dash = Dashboard::selectRaw('round((roi_income+direct_income+binary_income+hscc_bonus_income),2) as total_income')->where('id',$tp->id)->first();

                    $user_info= UserModel::select('id','user_id','fullname','email')->where([['status','Active'],['topup_status','1'],['type',''],['id',$tp->id]])->first();

                    $capping = $topup->capping_amount;
                    $total_income = $dash->total_income;
                    $total_records = $roi_income_info->roi_count;
                    $weekly_roi_income = round($roi_income_info->total_roi_income,2);
                    if ($total_records>0) {
                        // if ($total_income < $capping) {

                            echo "\nUser ID:".$tp->id."\tPIN: ".$tp->pin."\tstart-date: ".$start_date."\tend-date: ".$end_date."\nWeekly ROI Income: ".$weekly_roi_income."\ncount:".$total_records."\n";

                            $start = date('d/m/Y',strtotime($start_date)); 
                            $end = date('d/m/Y',strtotime($end_date));

                            $subject = "ROI Weekly Report";
                            $pagename = "emails.roi_weekely_report";
                            $data = array('pagename' => $pagename,'username' =>$user_info->fullname,'user_id'=>$user_info->user_id,'start_date'=>$start,'end_date'=>$end,'pin'=>$tp->pin,'weekly_roi_income'=>$weekly_roi_income);
                            $email =$user_info->email;
                            $mail = sendMail($data, $user_info->email, $subject);
                            // echo "\nmail status: ".$mail;
                            $i++;
                        // }
                    }
                    
                }
                echo "\n Cron run successfully \n";
                $current_time = \Carbon\Carbon::now()->toDateTimeString();
                $msg = "\nROI Income weekly Cron end at ".$current_time."\nTotal records : ".$i."\n";

                echo $msg;
                list($usec, $sec) = explode(" ", microtime());
                $time_end1 = ((float)$usec + (float)$sec);
                $time = $time_end1 - $time_start1;
                echo "\n Total cron time -> " . $time . "\n";

            }else{

                echo "\n ROI weekly reports are only available on ".$weekday."\n";
            }
        } catch (Exception $e) {
            dd($e);
        }
                            
    }
}


                           