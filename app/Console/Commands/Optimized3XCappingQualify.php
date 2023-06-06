<?php

namespace App\Console\Commands;

use App\Http\Controllers\adminapi\ManageCronController;
use Illuminate\Console\Command;
use App\Models\Topup;
use App\Models\ThreeXachieversList;
use App\Models\TodayDetails;
use App\Models\Dashboard;
use App\Models\CronRunStatus;
use App\User;
use DB;
use Response;
use Config;


class Optimized3XCappingQualify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:optimized_3x_capping_qualify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimized 3x qualify users cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
  

    /**
     * Execute the console command.
     *
     * @return mixed
     */
      
    public function handle()
    {   
       list($usec, $sec) = explode(" ", microtime());

        $time_start1 = ((float)$usec + (float)$sec);
        // dd('end');
        DB::raw('LOCK TABLES `tbl_dashboard` WRITE');
        // DB::raw('LOCK TABLES `tbl_topup` WRITE');
        DB::raw('LOCK TABLES `tbl_dailybonus` WRITE');
        //cron here
     
        // $time_start1 = microtime_float();
        
        $day = \Carbon\Carbon::now()->format('D');
       
        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "3x Cron started at ".$current_time;
        echo $msg."\n";

        $userArr = array();
        $insert_data_arr = array();
      
        $allEntryTime = Dashboard::select('tbl_dashboard.id','tbl_dashboard.binary_income','tbl_dashboard.direct_income','tbl_dashboard.roi_income','tbl_dashboard.hscc_bonus_income')
        ->join('tbl_users as tu', 'tu.id', '=', 'tbl_dashboard.id')
        ->where('tu.three_x_achieve_status','0')
        ->get();
        
        foreach ($allEntryTime as $tpdet) 
        {
            
        
                    
                $allTopus = Topup::select(DB::raw("SUM(amount) as amount"))
                ->where('tbl_topup.roi_status','Active')
                ->where('tbl_topup.id',$tpdet->id)
                ->first();

                $totalIncome = ($tpdet->binary_income+$tpdet->direct_income+$tpdet->roi_income+$tpdet->hscc_bonus_income);

                if($allTopus->amount < $totalIncome)
                {
                    $insertData = array();
                    $insertData['user_id'] = $tpdet->id;
                    $insertData['achieve_status'] = 1;
                    $insertData['achieve_date'] = $current_time;
                    array_push($insert_data_arr,$insertData);


                    $updateCoinData = array();
                    $updateCoinData['three_x_achieve_status'] = 1;
                    $updateCoinData['three_x_achieve_date'] = $current_time;
                    $updateCoinData['id'] =$tpdet->id;
                    array_push($userArr,$updateCoinData);
                }        
        }
        $count = 1;
        $array = array_chunk($insert_data_arr,1000);
       // dd($array);
        while($count <= count($array))
        {
          $key = $count-1;
          ThreeXachieversList::insert($array[$key]);
          echo $count." insert count array ".count($array[$key])."\n";
          $count ++;
        }

      $count2 = 1;

      $array2 = array_chunk($userArr, 1000);

      while ($count2 <= count($array2)) {
        $key2 = $count2 - 1;
        $arrProcess = $array2[$key2];
        $ids = implode(',', array_column($arrProcess, 'id'));
        $rbv_qry = 'three_x_achieve_status = (CASE id';
        $lbv_qry = 'three_x_achieve_date = (CASE id';
        foreach ($arrProcess as $key => $val) {
          $rbv_qry = $rbv_qry . " WHEN " . $val['id'] . " THEN " . $val['three_x_achieve_status'];
          $lbv_qry = $lbv_qry . " WHEN " . $val['id'] . " THEN '".$val['three_x_achieve_date']."'" ;
        }
        $rbv_qry = $rbv_qry . " END)";
        $lbv_qry = $lbv_qry . " END)";
        $updt_qry = "UPDATE tbl_users SET " . $rbv_qry . " , " . $lbv_qry . " WHERE id IN (" . $ids . ")";
        $updt_user = DB::statement(DB::raw($updt_qry));

        echo $count2 . " update user array " . count($arrProcess) . "\n";
        $count2++;
      }

     


    
        $current_time = \Carbon\Carbon::now()->toDateTimeString();
    
        echo $msg;

        echo "\n";
            list($usec, $sec) = explode(" ", microtime());
                                $time_end1 = ((float)$usec + (float)$sec);
                                   $time = $time_end1 - $time_start1;
                                   echo "after 3x income cron ".$time."\n"; 

        

        DB::raw('UNLOCK TABLES');
    
    }


}
