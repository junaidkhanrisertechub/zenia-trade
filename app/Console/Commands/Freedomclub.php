<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dashboard;
use App\User;
use App\Models\Rank;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\Models\supermatching;
use App\Models\freedomclubincome;
use App\Models\dailybusiness;
use DB;



class Freedomclub extends Command

{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:Freedomclub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

      $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();
      if($croncheck != null)
      {
      if($croncheck->status ==1)
      {
      echo "this cron is active\n";

      $today = \Carbon\Carbon::now();
      $today_datetime = $today->toDateTimeString();
      $today_datetime2current = $today->toDateString();
      // need dynamic
      $last_daily_business_entry = DB::table('tbl_daily_business')->select('entry_time')->orderBy('entry_time','desc')->first(); 
     
      if(empty($last_daily_business_entry))
      {

        $last_top_up_entry = DB::table('tbl_topup')->select('entry_time')->orderBy('entry_time','asc')->first(); 
     
        if(empty($last_top_up_entry))
        {
            dd("No topup yet");
        }
        else{
            $today_datetime2=$last_top_up_entry->entry_time;
        }

        
      }
      else{
        $today_datetime2=date('Y-m-d', strtotime($last_daily_business_entry->entry_time.  ' +1 days'));
      }

      $today_datetime2current=date('Y-m-d', strtotime($today_datetime2current));
     
      if($today_datetime2 >= $today_datetime2current)
      {
          dd("Freedom club not allowed future date");

      }

      echo "Today date ".$today_datetime2;
      echo "\n";

      $PriviousEntrydate=date('Y-m-d', strtotime($today_datetime2)); 

      echo "Yesterday date ".$PriviousEntrydate;
      echo "\n";
     
      $teambusiness = DB::table('tbl_topup')->where([[DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"),$PriviousEntrydate]])->sum('amount'); 
      // dd($teambusiness);
      //$bussiness_per_to_rank_user = $teambusiness * 0.4 / 100;
     // where([[DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"),$PriviousEntrydate],['user_id',$value2->user_id]])
     // dd($bussiness_per_to_rank_user);
     echo "Yesterday business  ".$teambusiness;
      echo "\n";

      $Dailybusinessdata = array();
      $Dailybusinessdata['total_business'] = $teambusiness;
      $Dailybusinessdata['entry_time'] = $PriviousEntrydate;
      $Dailybusinessdata = dailybusiness::create($Dailybusinessdata);

     if($teambusiness >0)
     {
         // update tbl users status 


         $updateStatusData = array();
         $updateStatusData['tbl_super_matching.user_status'] = DB::raw('tbl_users.status');
         
         supermatching::join('tbl_users', 'tbl_users.id', '=', 'tbl_super_matching.user_id')
        ->update($updateStatusData);                
      
      $getRanks = Rank::select('*')->orderBy('id','asc')->get();
    
        foreach ($getRanks as $value) {

             if($value->rank != null )
             {
                $rank_name = $value->rank;
                echo "for rank  ".$rank_name;
                echo "\n";

                $max_amount = $value->max_amount;
                
               $user_rank_count =DB::table('tbl_super_matching')->where('rank',$rank_name)->where('user_status',"Active")->where('freedom_club_capping_status',0)->where(DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"),'<=', $PriviousEntrydate)->count();
               // $user_rank_count =DB::table('tbl_super_matching')->where('rank',$rank_name)->count();
                echo "rank achivers  ".$user_rank_count;
                echo "\n";
                if($user_rank_count > 0)
                {

                $income_amt = round((($teambusiness * 0.00833 / 100) / $user_rank_count),6);
                
                echo "rank income   ".$income_amt;
                echo "\n";

                $get_all_user_rank = DB::table('tbl_super_matching')->select('user_id')->where('rank',$rank_name)->where('user_status',"Active")->where('freedom_club_capping_status',0)->where(DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"),'<=', $PriviousEntrydate)->get();
               //$get_all_user_rank = DB::table('tbl_super_matching')->select('user_id')->where('rank',$rank_name)->get();
                 foreach ($get_all_user_rank as $value2) {
                        $check_record_exist = freedomclubincome::where([[DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"),$PriviousEntrydate],['user_id',$value2->user_id],['rank',$rank_name]])->first(); 
                        
                        echo "user id    ".$value2->user_id;
                        echo "\n";
                        if(empty($check_record_exist))
                        {
                        $old_freedom_club_income = freedomclubincome::where([['user_id',$value2->user_id],['rank',$rank_name]])->sum('amount'); 
                       // $dashborad_data =Dashboard::where([['id',$value2->user_id]])->get();
                        $freedom_club_income=$income_amt;
                        $laps_amount=0;
                        echo "old freedom club income    ".$old_freedom_club_income;
                        echo "\n";
                        echo "initial laps amount    ".$laps_amount;
                        echo "\n";
                        if(($old_freedom_club_income+$freedom_club_income) > $max_amount)
                        {
                            
                            $laps_amount = $freedom_club_income - ($max_amount-$old_freedom_club_income);
                            $freedom_club_income = $max_amount-$old_freedom_club_income;

                        }
                        echo "freedom club income    ".$freedom_club_income;
                        echo "\n";

                        echo "laps amount    ".$laps_amount;
                        echo "\n";

                        if($freedom_club_income > 0)
                        {

                            $Dailydata = array();
                            $Dailydata['amount'] = $freedom_club_income;
                            $Dailydata['user_id'] = $value2->user_id;
                            $Dailydata['laps_amount'] = $laps_amount;
                            $Dailydata['rank'] = $rank_name;
                            $Dailydata['remark'] = "Freedom income generated";
                            $Dailydata['entry_time'] = $PriviousEntrydate;
                            $Dailydata = freedomclubincome::create($Dailydata);

                            $updateData = array();
                            $updateData['total_profit'] = DB::raw('total_profit + '.$freedom_club_income);//$dashborad_data[0]->total_profit + $income_amt;
                              $updateData['freedom_club_income'] = DB::RAW('freedom_club_income + '.$income_amt);
                            $updateData['freedom_club_income_withdraw'] = DB::RAW('freedom_club_income_withdraw + '.$income_amt);
                            $updateData['working_wallet'] = DB::raw('working_wallet + '.$freedom_club_income);//$dashborad_data[0]->working_wallet + $income_amt;
                            $updateData['dex_wallet'] = DB::raw('dex_wallet + '.$freedom_club_income);//$dashborad_data[0]->dex_wallet + $income_amt;
                            $updateOtpSta = Dashboard::where('id', $value2->user_id)->limit(1)->update($updateData);
                        }

                        // if capping 

                            if($laps_amount > 0)
                            {
                                $updateCapping = array();
                                $updateCapping['freedom_club_capping_status'] = DB::raw(1);//$dashborad_data[0]->total_profit + $income_amt;

                                    $updateCapping = supermatching::where('user_id', $value2->user_id)->where('rank',$rank_name)->limit(1)->update($updateCapping);
                            }
                        
                         }
                         else
                         {
                            echo " income  given  for date ".$PriviousEntrydate;
                            echo "\n";

                         }
                      }
                 }
                 
                  
             }
         }
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
    }else{
      echo "this cron is inactive \n";
    }
  }
    
}
