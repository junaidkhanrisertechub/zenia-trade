<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Events\MessageSent;
use DB;
use Response;
use App\User;
//use App\Http\Controllers\userapi\AwardRewardController;
use App\Models\DailyBonus;
use App\Models\ProjectSetting;
use App\Models\Activitynotification;
use App\Models\Template;

class RoiIncomeMail_old extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:roi_income_mail_old';
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
        dd("Run roi_income_mail");
        $usersList = DailyBonus::select('u.email','u.id','u.user_id','u.mobile','tbl_dailybonus.amount','tbl_dailybonus.pin')
            ->where([['tbl_dailybonus.mail_status','=','0']])
            ->join('tbl_users as u','u.id','=','tbl_dailybonus.id')
           // ->join('tbl_notification_email_settings as n_email','n_email.user_id','=','u.id')
            ->get();
            $temp_data = Template::where('title', '=', 'Roi Email')->first();
            $project_set_data = ProjectSetting::select('icon_image','domain_name')->first();
            // dd($project_set_data);
    	foreach ($usersList as $user) {

            // $subject = " ROI Revenue Generated. ";
            $icon_image = $project_set_data->icon_image;
            $domain_name = $project_set_data->domain_name;
            // dd($domain_name);
            $subject = $temp_data->subject;
            $pagename = "emails.roiincome";
            // $content = "ROI Revenue";
            $content = $temp_data->content;
            $amount = $user->amount;
            $username = $user->user_id;
            // dd($icon_image);

            $dd1=['$amount','$username'];
                    $dd2=[$amount,$username];
                    $new_content=str_replace($dd1,$dd2,$content);

            $data = array('pagename' => $pagename,'email' =>$user->email, 'username' =>$username , 'content'=>$new_content, 'amount' =>$amount, 'logo1' =>$icon_image,'domain_name' =>$domain_name);
            $email =$user->email;
           $mail = sendMail($data, $email, $subject);
            $arr = array('mail_status'=>'1');
            DailyBonus::where('pin','=',$user->pin)->update($arr);
            echo"\n Mail Send to : $user->user_id \n";


            //  broadcast 
            // $buy_user = ['username'=>$user->fullname,'user_id'=>$user->user_id];
            // $msg=$user->user_id .' has Generated ROI Revenue '.$user->amount.' successfully!! ';
            // $url='';
            // $msg1=array('message'=>$msg,'notification'=>'true','url'=>$url);
            
            // echo $msg."\n ";
            // $RoiIncomeNotification = new Activitynotification;
            // $RoiIncomeNotification->id  = $user->id;
            // $RoiIncomeNotification->message   = $msg;
            // $RoiIncomeNotification->status =  0;  
            // $RoiIncomeNotification->save();

            // broadcast(new MessageSent($buy_user, $msg1));
            // usleep(1000);
    	}
                            
    }
}


                           