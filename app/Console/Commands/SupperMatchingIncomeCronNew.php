<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\GenerateRoiController;
use App\User;
use App\Models\DailyBonus;
use App\Models\SupperMatchingIncome;
use App\Models\Activitynotification;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\Models\AllTransaction;
use App\Models\Dashboard;
use App\Models\supermatching;
use DB;
use App\Models\Rank;

class SupperMatchingIncomeCronNew extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:supper_matching_income_new';
    //protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Every three hours roi generation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GenerateRoiController $generatesuppermatchingincome) {
        parent::__construct();
        $this->generatesuppermatchingincome = $generatesuppermatchingincome;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //echo "hellooo";

        //echo "hellooo";

         $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();

         if($croncheck != null)
         {
      if($croncheck->status ==1)
      {
      echo "this cron is active\n";

        $day = \Carbon\Carbon::now()->format('D');
        //dd($day);

        $user = User::join('tbl_super_matching', 'tbl_super_matching.user_id', '=', 'tbl_users.id')
        ->select('tbl_users.id','tbl_users.mobile','tbl_users.country','tbl_users.user_id','tbl_users.email','tbl_users.entry_time as register_date','tbl_super_matching.pin','tbl_super_matching.rank','tbl_super_matching.entry_time')
        ->where([['tbl_users.status', '=', 'Active'],['tbl_users.type', '=', '']])
        ->where('tbl_super_matching.rank','Ace')
              //  ->skip(500)->take(500)
               // ->limit(1)
        ->get();
      //  dd($user);
        $today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();
        $today_datetime2 = $today->toDateString();

        $payoutHistory =SupperMatchingIncome::select('entry_time')->whereDate('entry_time', '=', $today_datetime2)->count();

        $msg = 'Supper Matching Bonus CRON started at' . $today_datetime . "\n" .
        "Total records:: " . $payoutHistory . "\n";

        if (!empty($user)) {
            foreach ($user as $key => $value) {
                $id = $user[$key]->id;
                $pin = $user[$key]->pin;
                $type = $user[$key]->type;
                $entry_time = $user[$key]->entry_time;
                $email = $user[$key]->email;
                $user_id = $user[$key]->user_id;
                $country = $user[$key]->country;
                $mobile = $user[$key]->mobile;
                $withdraw = $user[$key]->withdraw;
                $old_status = $user[$key]->old_status;
                $rank = $user[$key]->rank; 
                $register_date = User::select('entry_time')->where('id',$id)->pluck('entry_time')->first();
                $datex = \Carbon\Carbon::parse($register_date);
                $nowx = \Carbon\Carbon::now();
                $diffx = $datex->diffInDays($nowx);
                
                //except package 1 (trial $100)
                //   if($user[$key]->srno == '18'){
                echo "For User Id ".$id." pin ".$pin." \n";
                /*$checkUpdate = $this->generatesuppermatchingincome->generatesuppermatchingincomenew($id, $pin, $type, $entry_time,$email,$user_id,$country,$mobile,$withdraw,$old_status,$rank);*/
               // }

                $lastDateExist=SupperMatchingIncome::select('entry_time','rank')->where([['id','=',$id]])->orderBy('entry_time','desc')->first(); 

                if(!empty($lastDateExist)){
                    // A
                  $entry_time=$lastDateExist->entry_time;

                  

                  $nextEntrydate=date('Y-m-d', strtotime($entry_time. ' + 7 days'));

                  if(strtotime($nextEntrydate)<= strtotime($today))   
                  {
                    $packageExist1=Rank::where([['rank','=',$lastDateExist->rank]])->limit(1)->first();
                    $bonus_percentage = $packageExist1->bonus_percentage;
                    $entry_in_supermatching = supermatching::select('rank','pin','entry_time')
                    ->where([['user_id','=',$id],['rank','=',$lastDateExist->rank]])
                    ->first(); 
                    $pin = $entry_in_supermatching->pin;
                    $qualify_date_time = $entry_in_supermatching->entry_time;
                    $rank = $lastDateExist->rank;
                    $entry_in_supermatching1 = supermatching::select('rank','pin','entry_time')
                    ->where([['user_id','=',$id],['entry_time','>',$qualify_date_time]])
                    ->get(); 


                    if(count($entry_in_supermatching1) > 0){
                        foreach ($entry_in_supermatching1 as $key => $value) {
                            if(date('Y-m-d', strtotime($entry_in_supermatching1[$key]->entry_time. ' + 15 days'))  <= $nextEntrydate){

                                $pin = $entry_in_supermatching1[$key]->pin;
                                $rank = $entry_in_supermatching1[$key]->rank;

                                $packageExist1=Rank::where([['rank','=',$rank]])->limit(1)->first();
                                $bonus_percentage = $packageExist1->bonus_percentage;

                            }


                        }
                    }
                    $supermatching = $bonus_percentage;

                    $finalarr=array(); 
                    $Dailydata = array();
                    $Dailydata['amount'] = $supermatching;
                    $Dailydata['id'] = $id;
                    $Dailydata['pin'] = $pin;
                    $Dailydata['daily_amount'] = $supermatching;
                    $Dailydata['entry_time'] = $nextEntrydate;
                    $Dailydata['rank'] = $rank;
                    $Dailydata = SupperMatchingIncome::create($Dailydata);

                    $updateCoinData = array();
                    $updateCoinData['usd'] = DB::raw('usd +'.$supermatching);
                    $updateCoinData['supper_maching_income'] = DB::raw('supper_maching_income + '. $supermatching);
                    
                    if ($diffx <= 90) {
                        $updateCoinData['working_wallet'] = DB::raw('working_wallet + '.$supermatching);
                    }else{
                        if(strtotime($nextEntrydate)>=strtotime("2021-08-23")){
                            $updateCoinData['passive_income'] = DB::raw('passive_income + '.$supermatching);
                        }else{
                            $updateCoinData['working_wallet'] = DB::raw('working_wallet + '.$supermatching);
                        }
                    }

                    $updateCoinData = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);




                }

            }else
            {
                // B
                /*  $entry_time=$entry_time;*/

                $entry_time=date('Y-m-d', strtotime($entry_time));

                $last_entry_in_supermatching = supermatching::select('rank','pin')
                ->where([['user_id','=',$id]])
                ->where([[DB::raw("(Date(entry_time))"),'=',$entry_time]])
                ->orderBy('entry_time','desc')
                ->orderBy('id','desc')
                ->first(); 
                /*dd($last_entry_in_supermatching->rank);*/
                $nextEntrydate=date('Y-m-d', strtotime($entry_time. ' + 7 days'));

                if(strtotime($nextEntrydate)<= strtotime($today)){
                    $packageExist=Rank::where([['rank','=',$last_entry_in_supermatching->rank]])->limit(1)->first();
                    $supermatching = $packageExist->bonus_percentage;

                    $finalarr=array(); 
                    $Dailydata = array();
                    $Dailydata['amount'] = $supermatching;
                    $Dailydata['id'] = $id;
                    $Dailydata['pin'] = $last_entry_in_supermatching->pin;
                    $Dailydata['daily_amount'] = $supermatching;
                    $Dailydata['entry_time'] = $nextEntrydate;
                    $Dailydata['rank'] = $last_entry_in_supermatching->rank;
                    $Dailydata = SupperMatchingIncome::create($Dailydata);

                    $updateCoinData = array();
                    $updateCoinData['usd'] = DB::raw('usd +'.$supermatching);
                    $updateCoinData['supper_maching_income'] = DB::raw('supper_maching_income + '. $supermatching);
                    if ($diffx <= 90) {
                        $updateCoinData['working_wallet'] = DB::raw('working_wallet + '.$supermatching);
                    }else{
                        if(strtotime($nextEntrydate)>=strtotime("2021-08-23")){
                            $updateCoinData['passive_income'] = DB::raw('passive_income + '.$supermatching);
                        }else{
                            $updateCoinData['working_wallet'] = DB::raw('working_wallet + '.$supermatching);
                        }
                    }

                    $updateCoinData = Dashboard::where('id', $id)->limit(1)->update($updateCoinData);
                }
            }
        }
        $this->info('Supper Matching Bonus generated successfully');
    } else {

        $this->info('User is not exist');
    }
    $cronRunEntry=new CronRunStatus();
    $cronRunEntry['cron_id'] =$croncheck->id;
    $cronRunEntry['run_status'] =1;
    $cronRunEntry['run_time'] =$today_datetime;
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
