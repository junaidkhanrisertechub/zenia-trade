<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use DB; 
use App\Models\TheDailySummary;

class Dailysummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:Dailysummary';

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
        //
        echo 'Daily Summary  CRON started at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";
         
        $all_users = DB::table('tbl_users')->select('id','user_id','entry_time')->orderby('entry_time','asc')->where('type','=','')->get();
        $todaydetails_data = array();



        list($usec, $sec) = explode(" ", microtime());

        $time_start1 = ((float)$usec + (float)$sec);

       // dd($all_users->toArray());
        $count1 = 1;
        $array1 = array_chunk($all_users->toArray(), 1000);
      
        while ($count1 <= count($array1)) {
            $key1 = $count1-0;
            foreach($array1[$key1] as $user)
            {

                 list($usec, $sec) = explode(" ", microtime());

                $time_start_loop = ((float)$usec + (float)$sec);

                echo "For User Id : ".$user->id."\n"; 
                $today = \Carbon\Carbon::now(); 
                $today_datetime2current = date('Y-m-d', strtotime($today));
                
                $check_record_exist = TheDailySummary::select('entry_time')->where('user_id',$user->id)->orderby('entry_time','desc')->first();
               
                $entry_time = date('Y-m-d', strtotime($user->entry_time));
                if(!empty($check_record_exist))
                {
                    //$entry_time = $check_record_exist->entry_time;
                    $entry_time=date('Y-m-d', strtotime($check_record_exist->entry_time.  ' +1 days'));
                }
               
                echo "Date:".$entry_time."\n";//dd($entry_time , $today_datetime2current);
                // dd($entry_time,$today_datetime2current,$entry_time < $today_datetime2current);
                if($entry_time < $today_datetime2current)
                {
                    $sum_of_binary = DB::table('tbl_payout_history')
                                    ->where('tbl_payout_history.user_id','=',$user->id)
                                    //->where(DB::raw("DATE_FORMAT(tbl_payout_history.entry_time,'%Y-%m-%d')"),'=',$entry_time)
                                     ->where([[DB::raw("Date(tbl_payout_history.entry_time)"),$entry_time]])
                                    ->sum('tbl_payout_history.amount');
                    
                    $sum_of_roi = DB::table('tbl_dailybonus')
                                    ->where('tbl_dailybonus.id','=',$user->id)
                                    //->where(DB::raw("DATE_FORMAT(tbl_dailybonus.entry_time,'%Y-%m-%d')"),'=',$entry_time)
                                    ->where([[DB::raw("Date(tbl_dailybonus.entry_time)"),$entry_time]])
                                    ->sum('tbl_dailybonus.amount');

                    $sum_of_direct_income = DB::table('tbl_directincome')
                                    ->where('tbl_directincome.toUserId','=',$user->id)
                                    //->where(DB::raw("DATE_FORMAT(tbl_directincome.entry_time,'%Y-%m-%d')"),'=',$entry_time)
                                    ->where([[DB::raw("Date(tbl_directincome.entry_time)"),$entry_time]])
                                    ->sum('tbl_directincome.amount');  
                    
                    $sum_of_supermatching = DB::table('tbl_supper_matching_bonus_income')
                                    ->where('tbl_supper_matching_bonus_income.id','=',$user->id)
                                    //->where(DB::raw("DATE_FORMAT(tbl_supper_matching_bonus_income.entry_time,'%Y-%m-%d')"),'=',$entry_time)
                                    ->where([[DB::raw("Date(tbl_supper_matching_bonus_income.entry_time)"),$entry_time]])
                                    ->sum('tbl_supper_matching_bonus_income.amount');  

                    $sum_of_freedom_club = DB::table('tbl_freedom_club_income')
                                    ->where('tbl_freedom_club_income.user_id','=',$user->id)
                                    //->where(DB::raw("DATE_FORMAT(tbl_freedom_club_income.entry_time,'%Y-%m-%d')"),'=',$entry_time)

                                     ->where([[DB::raw("Date(tbl_freedom_club_income.entry_time)"),$entry_time]])
                                    ->sum('tbl_freedom_club_income.amount');  
                                    
                    $sum_of_coinpayment_funds = DB::table('tbl_transaction_invoices')
                                    ->where('tbl_transaction_invoices.id','=',$user->id)
                                   // ->where(DB::raw("DATE_FORMAT(tbl_transaction_invoices.entry_time,'%Y-%m-%d')"),'=',$entry_time)

                                     ->where([[DB::raw("Date(tbl_transaction_invoices.entry_time)"),$entry_time]])
                                    ->sum('tbl_transaction_invoices.price_in_usd'); 
                    
                    /*$sum_of_debit_funds = DB::table('tbl_deduction_stat')
                                    ->where([['tbl_deduction_stat.user_id','=',$user->id],['tbl_deduction_stat.wallet_name','=','Purchase Wallet'],[DB::raw('date(tbl_deduction_stat.entry_time)'),'=',$entry_time]])
                                    ->sum('tbl_deduction_stat.amount'); */
                    $sum_of_debit_funds = $sum_of_binary + $sum_of_roi + $sum_of_supermatching + $sum_of_direct_income + $sum_of_freedom_club ;

                    /*$DailySumary_foruser = new TheDailySummary;*/


                    echo "binary_income ".$sum_of_binary."\n";
                    echo "roi_income ".$sum_of_roi."\n";
                    echo "direct_income ".$sum_of_direct_income."\n";
                    echo "supermatching_income ".$sum_of_supermatching."\n";
                    echo "freedom_club_income ".$sum_of_freedom_club."\n";
                    echo "coinpayment_funds ".$sum_of_coinpayment_funds."\n";
                    echo "credit_fund ".$sum_of_coinpayment_funds."\n"; 
                    echo "debit_fund ".$sum_of_debit_funds."\n"; 

                    $DailySumary_foruser = array();
                    $DailySumary_foruser['user_id'] = $user->id;
                    $DailySumary_foruser['binary_income'] = $sum_of_binary;
                    $DailySumary_foruser['roi_income'] = $sum_of_roi;
                    $DailySumary_foruser['direct_income'] = $sum_of_direct_income;
                    $DailySumary_foruser['supermatching_income'] = $sum_of_supermatching;
                    $DailySumary_foruser['freedom_club_income'] = $sum_of_freedom_club;
                    $DailySumary_foruser['coinpayment_funds'] = $sum_of_coinpayment_funds;
                    $DailySumary_foruser['credit_fund'] = $sum_of_coinpayment_funds;
                    $DailySumary_foruser['debit_fund'] = $sum_of_debit_funds;
                    $DailySumary_foruser['entry_time'] = $entry_time;
                    //$DailySumary_foruser->save();
                    array_push($todaydetails_data, $DailySumary_foruser);


                    list($usec, $sec) = explode(" ", microtime());
                                      $time_end_loop = ((float)$usec + (float)$sec);
                                         $time_loop = $time_end_loop - $time_start_loop;
                                         echo "time loop ".$time_loop."\n";
                }else
                {
                    echo "Daily Summary not allowed future date"."\n";
                }
            }
            $count = 1;
            $array = array_chunk($todaydetails_data, 1000);
          
            while ($count <= count($array)) {
                $key = $count - 1;
                TheDailySummary::insert($array[$key]);
                $count++;
            }
        }
        echo 'Daily Summary  CRON end at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";

         list($usec, $sec) = explode(" ", microtime());
                                      $time_end = ((float)$usec + (float)$sec);
                                         $time = $time_end- $time_start;
                                         echo "time  ".$time."\n";
    }
}
