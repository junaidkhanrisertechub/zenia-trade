<?php

namespAce App\Console\Commands;
use App\User;
use App\Models\TodayDetails;
use App\Models\supermatching;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use Illuminate\Console\Command;
use DB;
class DemoAssignRankCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:DemoAssignRankCron';

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

        $users = DB::table('tbl_users')
                ->select('id','l_ace','r_ace','rank')
                ->where(function($q) {
                    $q->where('l_ace_check_status', '>', 0)
                    ->orWhere('r_ace_check_status', '>', 0);
                    })
               // ->where('id', '=', 1200)
                ->where('rank', '!=', null)
                ->where('type', '!=', "Admin")
                ->where('status', '=', "Active")
                ->orderBy('id', 'ASC')
                ->get();
        
         
        foreach ($users as $value) 
        {

            list($usec, $sec) = explode(" ", microtime());

            $time_start = ((float)$usec + (float)$sec);

            $flag=0;

            echo "Rank checking for ".$value->id." At ".date('Y-m-d H:i:s')." \n";

          

            $usersleft = $value->l_ace;
            
                      
            $usersright = $value->r_ace;
            
            
             

            $invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');

          

            // for loop for rank 

            $from_rank_details= DB::table('tbl_rank')
                ->select('id')
                ->where('rank', '=', $value->rank)
                ->orderBy('id', 'ASC')
                ->limit('1')
                ->get();

           $from_rank = $from_rank_details[0]->id;

            $to_rank_details= DB::table('tbl_rank')
                ->select('id')
                ->where('left_ace_req', '<=', $value->l_ace)
                ->where('right_ace_req', '<=', $value->l_ace)
                ->orderBy('id', 'DESC')
                ->limit('1')
                ->get();

                $to_rank = $to_rank_details[0]->id;

                for ($i = $from_rank; $i <= $to_rank; $i++)
                {
                    for ($x = 0; $x <= 10; $x++)
                  $rank_details= DB::table('tbl_rank')
                   ->select('id','rank','left_ace_req','right_ace_req','left_users_tbl_col','right_users_tbl_col')
                   ->where('id', '=', $i)
                   ->get();

                   if(($usersleft >= $rank_details[0]->left_ace_req) && ($usersright >=$rank_details[0]->right_ace_req))
            {   
             
                $isExist = supermatching::select("user_id")
                ->where("rank", $rank_details[0]->rank)
                ->where('user_id', $value->id)
                ->count();


                if ($isExist == 0) {

                    $updateData = array();
                    $updateData['maching_income_status'] = "Inactive";
                    
                    supermatching::where('user_id', $value->id)->update($updateData);
          

                    $invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');

                    $Insertdata = array();
                    $Insertdata['pin'] = $invoice_id;
                    $Insertdata['rank'] = $rank_details[0]->rank;
                    $Insertdata['user_id'] = $value->id;
                    $Insertdata['rank_upline_pass_status'] = 1;
                    
                    
                    $insertAdd = supermatching::create($Insertdata);
                    $data = array();
                    $data['rank'] = $rank_details[0]->rank;


                    DB::table('tbl_users')
                        ->where('id', $value->id)
                        ->update($data);
                        $flag=1;


                    // update this ranks to upline
                    $left_rank_column=$rank_details[0]->left_users_tbl_col;
                    $right_rank_column=$rank_details[0]->right_users_tbl_col;


                // $updateLCountArr = array();
                // $updateLCountArr["${left_rank_column}"] = DB::raw("${left_rank_column}".' + 1');
               

                // DB::table('tbl_today_details as a')
                // ->join('tbl_users as b','a.to_user_id', '=','b.id')
                // ->where('a.from_user_id','=',$value->id)
                // ->where('a.position','=',1)
                // ->update($updateLCountArr);


                // $updateRCountArr = array();
                // $updateRCountArr["${right_rank_column}"] = DB::raw("${right_rank_column}".' + 1');
                

                // DB::table('tbl_today_details as a')
                // ->join('tbl_users as b','a.to_user_id', '=','b.id')
                // ->where('a.from_user_id','=',$value->id)
                // ->where('a.position','=',2)
                // ->update($updateRCountArr);


                // update rank to upline new logic
                $virtual_parent_id1 = $value->id;
                //-------update rank -------
                $loopOn1 = true;
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
                                    if ($value->id != $virtual_parent_id1) {
                                        if ($position == 1) {

                                            array_push($left_users, $virtual_parent_id1);

                                          
                                        }
                                        if ($position == 2) {
                                            array_push($right_users, $virtual_parent_id1);

                                           
                                        }

                                       

                                    }
                                } else {
                                    $loopOn1 = false;
                                }
                            }
                        }
                    } while ($loopOn1 == true);
                    //exit;
                }



                $todaysDetails = TodayDetails::select('to_user_id','position','level')->where([['from_user_id', '=', $virtual_parent_id1]])->get();
                if (count($todaysDetails) > 0) {

                    foreach ($todaysDetails as $k => $v) {

                        $virtual_parent_id1 = $todaysDetails[$k]->to_user_id;
                        if ($virtual_parent_id1 > 0) {
                            $position = $todaysDetails[$k]->position;
                            if ($id != $virtual_parent_id1) {
                                if ($position == 1) {
                                    array_push($left_users, $virtual_parent_id1);
                                } else if ($position == 2) {
                                    array_push($right_users, $virtual_parent_id1);
                                }

                            }
                        } 
                    }
                }
                /*$startDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', '2021-07-01 00:00:00');
                $endDate = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', '2021-08-31 23:59:59');
         
                $checkdate = \Carbon\Carbon::now()->between($startDate, $endDate);*/
                // bulk update

                $updateLCountArr = array();
                $updateLCountArr["${left_rank_column}"] = DB::raw("${left_rank_column}".' + 1');
                /*if($left_rank_column == "l_commander" && $checkdate){
                    $updateLCountArr["contest_lbv"] = DB::raw("contest_lbv".' + 1');
                }*/
               
             

                $updateRCountArr = array();
                $updateRCountArr["${right_rank_column}"] = DB::raw("${right_rank_column}".' + 1');
                /*if($right_rank_column == "r_commander" && $checkdate){
                    $updateLCountArr["contest_rbv"] = DB::raw("contest_rbv".' + 1');
                }*/
                


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



                }
                
            } 


            }


            if($flag=1)
            {

           $data5['l_ace_check_status'] = 0;
            $data5['r_ace_check_status'] = 0;
            DB::table('tbl_users')
            ->where('id', $value->id)
            ->update($data5);

            }
            

            list($usec, $sec) = explode(" ", microtime());
            $time_end = ((float)$usec + (float)$sec);
               $time = $time_end - $time_start;
               echo "after rank assign time ".$time."\n";
        
        }
        echo "Rank Assign Cron Run Succesfully.....";

    $today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();
        //$today_datetime2 = $today->toDateString();
      
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