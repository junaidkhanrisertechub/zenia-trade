<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Dashboard;
use App\User;
use App\Models\Rank;
use App\Models\TodayDetails;
use DB;



class TodaysEntryFromStart extends Command

{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:TodaysEntryFromStart';

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

        dd("Don't run cron!!! run only for leg shifting");
      
      $userDetails = User::select('id','virtual_parent_id','entry_time')->get();
      if (count($userDetails) > 0) {
           
        echo "Total ids ".count($userDetails);
        echo "\n";
        
          
        
          foreach ($userDetails as $l => $w) {
            list($usec, $sec) = explode(" ", microtime());

                            $time_start = ((float)$usec + (float)$sec);

            echo "Id ".$userDetails[$l]->id;
            echo "\n";


      $virtual_parent_id1 = $last = $userDetails[$l]->id;
      $from_user_id_for_today_count = $userDetails[$l]->id;
      $i = 0;
      //-------update user count  & binary business -------
      $loopOn1 = true;
      $todaydetails_data = array();
      $left_users = array();
      $right_users = array();
      if ($virtual_parent_id1 > 0) {
          do {
              $posDetails = User::select('id','virtual_parent_id','position')->where([['id', '=', $virtual_parent_id1]])->get();
              if (count($posDetails) <= 0) {

                  $loopOn1 = false;
              } else {

                  foreach ($posDetails as $k => $v) {

                      $virtual_parent_id1 = $posDetails[$k]->virtual_parent_id;
                      if ($virtual_parent_id1 > 0) {
                          $position = $posDetails[$k]->position;
                          if ($last != $virtual_parent_id1) {
                              if ($position == 1) {

                                  array_push($left_users, $virtual_parent_id1);

                                
                              }
                              if ($position == 2) {
                                  array_push($right_users, $virtual_parent_id1);

                                 
                              }

                               $Todaydata = array(); // new TodayDetails;
                              $Todaydata['to_user_id'] = $virtual_parent_id1;
                              $Todaydata['from_user_id'] = $from_user_id_for_today_count;
                              $Todaydata['entry_time'] = $userDetails[$l]->entry_time;
                              $Todaydata['position'] = $position;
                              $Todaydata['level'] = $i + 1;
                              array_push($todaydetails_data, $Todaydata);
                                 $i++;
                             

                          }
                      } else {
                          $loopOn1 = false;
                      }
                  }
              }
          } while ($loopOn1 == true);

          //exit;
      }
      //-------------------- bulk entery 

     // bulk todays data from today details table
/*
     $todaysDetails = TodayDetails::select('to_user_id','position','level')->where([['from_user_id', '=', $virtual_parent_id1]])->get();
              if (count($todaysDetails) > 0) {

                  foreach ($todaysDetails as $k => $v) {

                      $virtual_parent_id1 = $todaysDetails[$k]->to_user_id;
                      if ($virtual_parent_id1 > 0) {
                          $position = $todaysDetails[$k]->position;
                          if ($last != $virtual_parent_id1) {
                              if ($position == 1) {

                                  array_push($left_users, $virtual_parent_id1);

                                
                              }
                              if ($position == 2) {
                                  array_push($right_users, $virtual_parent_id1);

                                 
                              }

                               $Todaydata = array(); // new TodayDetails;
                              $Todaydata['to_user_id'] = $virtual_parent_id1;
                              $Todaydata['from_user_id'] = $from_user_id_for_today_count;
                              $Todaydata['entry_time'] = $userDetails[$l]->entry_time;
                              $Todaydata['position'] = $position;
                              $Todaydata['level'] = $todaysDetails[$k]->level + 1;
                              array_push($todaydetails_data, $Todaydata);

                          }
                      } 
                  }
              }*/



      $count = 1;
      $array = array_chunk($todaydetails_data, 1000);
    
      while ($count <= count($array)) {
           $key = $count - 1;
       
         TodayDetails::insert($array[$key]);
          $count++;

         
      }


      $updateLCountArr = array();
      $updateLCountArr['l_c_count'] = DB::raw('l_c_count + 1');
     
      

      $updateRCountArr = array();
      $updateRCountArr['r_c_count'] = DB::raw('r_c_count + 1');
     
      // Update count
      $count1 = 1;
      $array1 = array_chunk($left_users, 1000);

      while ($count1 <= count($array1)) {
          //dd($array1);
          $key1 = $count1 - 1;
          User::whereIn('id', $array1[$key1])->update($updateLCountArr);
            $count1++;
      }

      $count2 = 1;
      $array2 = array_chunk($right_users, 1000);
      while ($count2 <= count($array2)) {
          $key2 = $count2 - 1;
          User::whereIn('id', $array2[$key2])->update($updateRCountArr);
              $count2++;
      }



      list($usec, $sec) = explode(" ", microtime());
      $time_end = ((float)$usec + (float)$sec);
         $time = $time_end - $time_start;
         echo "after todays entry ".$time."\n";
   
  }

    }
    
}

}
