<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Events\MessageSent;
use DB;
use Response;
use App\User;
//use App\Http\Controllers\userapi\AwardRewardController;
use App\Models\DirectIncome;
use App\Models\Activitynotification;
use App\Models\UserFcmDetails;
use App\Models\FcmUserNotification;
class DirectIncomeMail_old extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:direct_income_mail_old';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'direct_income_mail';

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
    	$usersList=DirectIncome::select('u.email','u.id','u.user_id','u.mobile','tbl_directincome.amount','tbl_directincome.id as pid','frm_user.user_id as frm_user_id')
            ->where('tbl_directincome.mail_status','=', '0')
            ->join('tbl_users as u','u.id','=','tbl_directincome.toUserId')
            ->join('tbl_users as frm_user','frm_user.id','=','tbl_directincome.fromUserId')
        //     ->join('tbl_notification_email_settings as n_email','n_email.user_id','=','u.id')
            ->get();

    	foreach ($usersList as $user) {

            $subject = " Direct Revenue Generated. ";
            $pagename = "emails.IncomeEmail";
            $content = "Direct Revenue";
            $data = array('pagename' => $pagename,'email' =>$user->email, 'username' =>$user->user_id , 'content' => $content);
            $email =$user->email;
            $mail = sendMail($data, $email, $subject);

            $arr = array('mail_status'=>'1');

            DirectIncome::where('id','=',$user->pid)->update($arr);
            echo"\n Mail Send to : $user->user_id \n";

            //  broadcast 
            $buy_user = ['username'=>$user->fullname,'user_id'=>$user->user_id];
            $msg=$user->user_id .' has Generated Direct Revenue '.$user->amount.' successfully!! ';
            $url='';
            $msg1=array('message'=>$msg,'notification'=>'true','url'=>$url, 'type' => 'user_id');
                
            $msg_notification = "You received referral income from User ID – ".$user->frm_user_id;
            $DirectIncomeNotification = new Activitynotification;
            $DirectIncomeNotification->id  = $user->id;
            $DirectIncomeNotification->message   = $msg_notification;
            $DirectIncomeNotification->type =  'direct_income';  
            $DirectIncomeNotification->status =  0;  
            $DirectIncomeNotification->save();

            // broadcast(new MessageSent($buy_user, $msg1));
            // usleep(1000);

            // firebase notification
            $user_fcm_details = UserFcmDetails::select('device_token')->where('user_id',$user->id)->first();
            $noti_user_id = $user->id;
            if(!empty($user_fcm_details)){
                $device_token = array($user_fcm_details['device_token']);
                $data['title'] = 'Referral Income';
                $data['message'] = $msg_notification;

                $add_FCM_noti['id'] = $noti_user_id;
                $add_FCM_noti['title'] = $data['title'];
                $add_FCM_noti['message'] = $data['message'];
                $result = FcmUserNotification::insert($add_FCM_noti);
                
                $notiData['noti_type'] = 'adminalert';
                $output = send_FCM_notification($data, $device_token, $notiData['noti_type']);
                
            }
    	}
                            
    }
}