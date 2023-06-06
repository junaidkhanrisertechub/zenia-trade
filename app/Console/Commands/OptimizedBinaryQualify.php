<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Topup;
use App\Models\QualifiedUserList;
use App\Models\TodayDetails;
use App\User;
use DB;
use Response;
use Config;


class OptimizedBinaryQualify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:optimized_binary_qualify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimized binary qualify users cron';

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
        // dd('end');
        //$mobile = 1234567890;
        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "Qualify Cron started at ".$current_time;
        //sendSMS($mobile,$msg);
        echo $msg."\n";

        echo "start ".now()."\n";
        $user = User::select('id')/*->where('topup_status',"1")*/
                        ->where('type',"")->where('status',"Active")->where('binary_qualified_status',0)
                        ->where(function ($user) {
            $user->where('power_l_bv','>',0)->orWhere('power_r_bv','>',0);
        })->get();

        $qualified_count = 0;
        // dd($user);
        if(!empty($user) && count($user) > 0)
        {
            $insert_qualified_arr = array();
            $user_id_arr = array();
            foreach ($user as $rowUser) 
            {   

               /* $directCount = User::where('ref_user_id',$rowUser->id)->where('amount' , '>', 0)->where('topup_status','=',"1")->count('id');
                if ($directCount >= 2){*/

                    list($usec, $sec) = explode(" ", microtime());

                    $time_start = ((float)$usec + (float)$sec);
                   

                        $QualifiedData = array();
                        $QualifiedData['user_id'] = $rowUser->id;
                        array_push($insert_qualified_arr,$QualifiedData);
                      array_push($user_id_arr,$rowUser->id);

                        $qualified_count = $qualified_count+1;

                        echo  "\n User ID--> ".$rowUser->id." -> ";
                

                    list($usec, $sec) = explode(" ", microtime());
                                          $time_end = ((float)$usec + (float)$sec);
                                             $time = $time_end - $time_start;
                                             echo "time ".$time."\n";

               /* }  */                       
            }
            $count = 1;
            $array = array_chunk($insert_qualified_arr,1000);
           // dd($array);
            while($count <= count($array))
            {
              $key = $count-1;
              QualifiedUserList::insert($array[$key]);
              echo $count." insert count array ".count($array[$key])."\n";
              $count ++;
            }


        $updateUserData = array();
        $updateUserData['binary_qualified_status'] = 1;
       
        $count2 = 1;     
        $array2 = array_chunk($user_id_arr,1000);
        while($count2 <= count($array2))
        {
          $key2 = $count2-1;
          User::whereIn('id',$array2[$key2])->update($updateUserData);
          echo $count2." update user array ".count($array2[$key2])."\n";
          $count2 ++;
        }

        } 
        echo "end ".now()."\n";
        echo "\n Cron run successfully \n" ;

        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "Qualify Cron end at ".$current_time."\nTotal qualified ids : ".$qualified_count."\n";
        //sendSMS($mobile,$msg);
        echo $msg;
    }

}
