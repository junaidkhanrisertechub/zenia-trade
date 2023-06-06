<?php

namespace App\Console\Commands;
use App\Models\DailyBouns;
use App\Models\Dashboard;
//use App\Models\Topup;
use App\Models\DirectIncome;
use App\Models\PayoutHistory;
use App\Models\ProjectSettings;
use App\Models\Topup;
use App\Models\Template;
use App\Models\WithdrawalConfirmed;
use App\Models\WithdrawPending;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\Models\TransactionInvoice;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class StatisticsCron extends Command {
// 7387165720
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cron:statistics';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Statistics report';

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
	public function handle() {

	  $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();
	  if($croncheck != null)
      {
      if($croncheck->status ==1)
      {
      echo "this cron is active\n";

		$total_user_count = User::count();

		/*$total_newUser =  User::where('status', 'Active')
		                       // ->where('ref_user_id', '!=', 1)
		                        ->count();

		$total_active_users = User::leftjoin('tbl_topup', 'tbl_topup.id', '=', 'tbl_topup.id')
			->where([['tbl_users.status', 'Active']])
			//->where('tbl_users.ref_user_id', '!=', 1)
			->groupby('tbl_topup.id')
			->count();

		$total_inactive_users = $total_user_count - $total_active_users;
		//$total_inactive_users = $total_user_count;

		$total_topup_count = Topup::where('top_up_by', '!=', 1)->count();

		$total_topup_amount = Topup::where('top_up_by', '!=', 1)->sum('amount');*/

	    $total_coin_payment = TransactionInvoice::where('in_status', '=', 1)->sum('price_in_usd');
	    $temp_data = Template::where('title', '=', 'Statistic Mail')->first();
		$project_set_data = ProjectSettings::select('icon_image','domain_name')->first();
		

		/*$total_binary_income = PayoutHistory::sum('amount');
		$total_roi = DailyBouns::sum('amount');
		$total_direct_income = DirectIncome::sum('amount');

		$total_withdraw = WithdrawPending::where([['tbl_withdrwal_pending.status', 0], ['tbl_withdrwal_pending.verify', 1]])->sum('amount');

		$total_withdraw_conf = WithdrawalConfirmed::where('status', '=', 1)->sum('amount');*/

		//-----------------------yesterday -------------------------------------
		//	--------------------------------------------------------
		$yesterdayDate = Carbon::yesterday()->toDateString();

		/*$user_count = User::where('status', 'Active')
		//->where('ref_user_id', '!=', 1)
		->count();
		
		$newUser = User::where('status', 'Active')->whereDate('entry_time', '=', $yesterdayDate)
		//->where('ref_user_id', '!=', 1)
		->count();*/
		$newUser = User::whereDate('entry_time', '=', $yesterdayDate)
		//->where('ref_user_id', '!=', 1)
		->count();

		/*$active_users = User::leftjoin('tbl_topup', 'tbl_topup.id', '=', 'tbl_topup.id')
			->where([['tbl_users.status', 'Active']])
			->groupby('tbl_topup.id')
			->count();

		$inactive_users = $user_count - $active_users;

		$topup_count = Topup::whereDate('entry_time', '=', $yesterdayDate)
		->where('top_up_by', '!=', 1)
		->count();

		$topup_amount = Topup::whereDate('entry_time', '=', $yesterdayDate)
		->where('top_up_by', '!=', 1)
		->sum('amount');

		$binaryIncome = PayoutHistory::whereDate('entry_time', '=', $yesterdayDate)->sum('amount');
		$roi = DailyBouns::whereDate('entry_time', '=', $yesterdayDate)->sum('amount');
		$DirectIncome = DirectIncome::whereDate('entry_time', '=', $yesterdayDate)->sum('amount');

		$total_income = Dashboard::whereDate('entry_time', '=', $yesterdayDate)->sum('working_wallet');*/

		$yesterday_coin_payment = TransactionInvoice::where('in_status', '=', 1)->
		whereDate('entry_time', '=', $yesterdayDate)->sum('price_in_usd');

		/*$yesterdayWithdraw = WithdrawPending::whereDate('entry_time', '=', $yesterdayDate)->where([['tbl_withdrwal_pending.status', 0], ['tbl_withdrwal_pending.verify', 1]])->sum('amount');

		$yesterdayWithdrawConf = WithdrawalConfirmed::whereDate('entry_time', '=', $yesterdayDate)->where('status', '=', 1)->sum('amount');*/

		$p_setting = ProjectSettings::where([['status', '=', 1]])
			->select('project_name')
			->first();
		$project_name = $p_setting->project_name;
		//$mobile = $p_setting->notification_mobile;

		//---------------------------------------------------------------------------------------

		$Today = Carbon::now()->toDateString();

		//---------------------------------------------------------------------------------------
		/*	$msg = '';
		$msg .= "Project Name : " . $project_name . "\n" .
			"Date " . $yesterdayDate . "\n" .
			"Total Users : " . $user_count . "\n" .
			"New users: " . $newUser . "\n" .
			"User Top amount : " . $topup_amount . "\n" .
			"Binary Income : " . $binaryIncome . "\n" .
			"ROI : " . $roi . "\n" .
			"DirectIncome : " . $DirectIncome . "\n";

		//"Total Income: " . $total_income . " \n";

		/*$total_topup_amount = number_format((float) $total_topup_amount, 2, '.', '');

		$topup_amount = number_format((float) $topup_amount, 2, '.', '');

		$total_roi = number_format((float) $total_roi, 2, '.', '');

		$roi = number_format((float) $roi, 2, '.', '');*/

		/*$msg = "Project : " . $project_name . "\n\n" .
		//"INR Transaction Mode Details : \n".
		"Total Registrations : " . $total_user_count . "\n" .
		"Total Active : " . $total_inactive_users . "\n" .
		"Total Topup Count : " . $total_topup_count . "\n" .
		"Total Topup Amount : " . $total_topup_amount . "\n" .
		"Total Direct Income : " . $total_direct_income . "\n" .
		"Total ROI Income : " . $total_roi . "\n" .
		"Total Pending Withdrawal  : " . $total_withdraw . "\n" .
		"Total Conf Wr Amount : " . $total_withdraw_conf . "\n\n" .
		"Total Coin Payment : " . $total_coin_payment . "\n\n" .

		"Date : " . $yesterdayDate . "\n" .

		//"INR Transaction Mode Details : \n".
		"Registrations : " . $newUser . "\n" .
			"Topup Count : " . $topup_count . "\n" .
			"Topup Amount : " . $topup_amount . "\n" .
			"Direct Income : " . $DirectIncome . "\n" .
			"Binary Income : " . $binaryIncome . "\n" .
			"ROI Income : " . $roi . "\n" .
			"Pending Withdrawal Amount : " . $yesterdayWithdraw . "\n" .
			"Confi Wr Amount : " . $yesterdayWithdrawConf . "\n".
			"Coin Payment : " . $yesterday_coin_payment . "\n";*/
		//echo $msg;

		$message = "Project : " . $project_name . "<br>" .
		//"INR Transaction Mode Details : \n".
		"Total Registrations : " . $total_user_count . "<br>" .
		/*"Total Active : " . $total_inactive_users . "<br>" .
		"Total Topup Count : " . $total_topup_count . "<br>" .
		"Total Topup Amount : " . $total_topup_amount . "<br>" .
		"Total Direct Income : " . $total_direct_income . "<br>" .
		"Total ROI Income : " . $total_roi . "<br>" .
		"Total Pending Withdrawal  : " . $total_withdraw . "<br>" .
		"Total Confirm Withdraw Amount : " . $total_withdraw_conf . "<br>" */
		"Total Coin Payment : " . $total_coin_payment . "<br><br>" .

		"Date : " . $yesterdayDate . "<br><br>" .

		//"INR Transaction Mode Details : <br>".
		"Registrations : " . $newUser . "<br>" .
		/*"Topup Count : " . $topup_count . "<br>" .
		"Topup Amount : " . $topup_amount . "<br>" .
		"Direct Income : " . $DirectIncome . "<br>" .
		"Binary Income : " . $binaryIncome . "<br>" .
		"ROI Income : " . $roi . "<br>" .
		"Pending Withdrawal Amount : " . $yesterdayWithdraw . "<br>" .
		"Confirm Withdrawal Amount : " . $yesterdayWithdrawConf . "<br>".*/
		"Coin Payment : " . $yesterday_coin_payment . "<br>";


		//$mob = explode(',', $mobile);
		//dd($mobile);
		//		$mob = explode(',', $mobile);
		//86696 05501,
		//9765352255

		/*sendSMS('9404737203', $msg);
	 	sendSMS('8669605501', $msg);
	    sendSMS('9765352255', $msg);*/
		$subject = $temp_data->subject;
		$content = $temp_data->content;
		$domain_name = $project_set_data->domain_name;

	    $pagename = "emails.admin-emails.statistics_mail";
	    $data = array('pagename' => $pagename,'mesage'=>$message,'content'=>$content,'domain_name');
		
		
		// dd($subject);
	    $mail =sendMail($data, 'tasukeonline@gmail.com', $yesterdayDate." Statistics",$subject);
		// $ss = sendSms('9403832061', $msg);

		/*dd($mob);

			foreach ($mob as $key => $value) {
				//code...
				sendSMS($value, $msg);
		*/

		//sendSMS('9404737203', $msg);

		/*echo "\n";
			echo "Project Name-->" . $project_name . " Mobile-->" . $mobile;
			echo "\n";
			echo "Total Users-->" . $user_count . " Total Active users-->" . $active_users . "  Total Inactive users-->" . $inactive_users . " Total pending provide help count-->" . $confirmrequestpending_count
				. " Total pending provide help amount-->" . $confirmrequestpending_amount . " Total confirm provide help count-->" . $confirmrequestconfirm_count . " Total confirm provide help amount-->" . $confirmrequestconfirm_amount . " Total pending get help count-->" . $withdrawlinkpending_count . " Total pending get help amount-->" . $withdrawlinkpending_amount . " Total confirm get help count-->" . $withdrawlinkconfirm_count . " Total confirm get help amount-->" . $withdrawlinkconfirm_amount . " Total Income-->" . $total_income;
		*/
		echo "Statistics SMS Sent";
		echo "\n";
    $cronRunEntry=new CronRunStatus();
    $cronRunEntry['cron_id'] =$croncheck->id;
    $cronRunEntry['run_status'] =1;
    $cronRunEntry['run_time'] =$Today;
    $cronRunEntry->save();
	}
	else{
        echo "this cron is inactive \n";
      }
	}
	else{
        echo "this cron is inactive \n";
      }
  }

}
