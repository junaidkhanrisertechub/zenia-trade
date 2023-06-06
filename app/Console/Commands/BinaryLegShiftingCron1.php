<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\TodayDetails;
use App\Models\LegShift;
//use App\Traits\Users;
use Config;
use DB;

class BinaryLegShiftingCron1 extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
   // use Users;
    protected $signature = 'cron:binary_leg_shifting1';
    //protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manual Tree shifting';

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

        $current_time = \Carbon\Carbon::now()->toDateTimeString();
        $msg = "ROI Cron started at ".$current_time;
        echo $msg."\n";

        $getdetails = LegShift::join('tbl_users as tu', 'tbl_leg_shift.user_id','=','tu.id')
            ->join('tbl_users as tu1', 'tbl_leg_shift.new_vpid','=','tu1.id')
            ->where('tbl_leg_shift.status', '0')->orderBy('tbl_leg_shift.entry_time', 'ASC')
            ->select('tbl_leg_shift.*','tu.virtual_parent_id','tu.position','tu.ref_user_id','tbl_leg_shift.id as srno')
            // ->where('tbl_leg_shift.user_id', 505)
            // ->where('status', '1')
            ->get();
        // dd($getdetails);
        // $users = User::select('id','virtual_parent_id','position','entry_time')->where('type', '=', '')->orderBy('id','ASC')->get();
        if (!empty($getdetails)) 
        {
            foreach ($getdetails as $key => $user) {
                $ifavailable = User::where('virtual_parent_id', $user->new_vpid)->where('position', $user->leg)->count();
                // dd($ifavailable);
                if($ifavailable == 0)
                {
                    if ($user->virtual_parent_id != '0') {
                        $this->createstructure($user->user_id,$user->position,$user->virtual_parent_id,$user->new_vpid,$user->leg,0);

                        $downlineids = TodayDetails::join('tbl_users as tu', 'tbl_today_details.from_user_id','=','tu.id')
                                ->where('tbl_today_details.to_user_id',$user->user_id)->orderBy('tbl_today_details.entry_time','ASC')
                                ->select('tbl_today_details.*','tu.virtual_parent_id','tu.position')
                                ->get();
                        // dd($downlineids);
                        foreach($downlineids as $e)
                        {
                            echo 'position' . $e->position;
                            $this->createstructure($e->from_user_id,$e->position,$e->virtual_parent_id,$e->virtual_parent_id,$e->position,1);
                        }                        
                    }

                    // $this->updateRCcount();
                    LegShift::where('id', $user->srno)->update(['status' => '1']);
                    // dd($todaydetails_data, $left_users,$right_users); 
                    
                    $current_time1 = \Carbon\Carbon::now()->toDateTimeString();
                    $msg = "ROI Cron end at ".$current_time1 ."\n";                    
                    echo $msg;            
                    // echo "\n";
                }
                else { 
                    LegShift::where('id', $user->srno)->update(['status' => '1']);
                    echo "Position Already Filled" ;
                }

            }                
        }
        else 
        {
            $this->info('User is not exist');
        }
        
    }


    public function findpositionid($idno,$i)
    {
        // dd($idno,$i);
        // echo $idno.'----'.$i;        
        if($i >= 0 && $idno != 1)
        {
            $result_new = User::where('id', $idno)->select('virtual_parent_id as vpid1','position')->first();            
            // dd($result_new->vpid1);
            if(empty($result_new))
            {
                // dd(1);
                return false;
            }
            else{  
                // dd(2);              
                return $result_new;
            }
        }
        else {
            return false;
        }
    }

    public function createstructure($user_id,$position,$virtual_parent_id,$new_vpid,$leg,$flag)
    {
        echo 'position - ' . $leg ;
        echo "\n";
        if($flag == 0)
        {
            User::where('id', $user_id)->update([   'virtual_parent_id' => $new_vpid,
                                                    'position'         => $leg]);
        }
       
        $old_left_users = array();
        $old_right_users = array();
        
        $old_left = TodayDetails::where('from_user_id',$user_id)->where('position', 1)->select('to_user_id')->get();
        foreach($old_left as $e)
        {
            array_push($old_left_users,$e->to_user_id);
        }

        $old_right = TodayDetails::where('from_user_id',$user_id)->where('position', 2)->select('to_user_id')->get();
        foreach($old_right as $e)
        {
            array_push($old_right_users,$e->to_user_id);
        }

        TodayDetails::where('from_user_id',$user_id)->delete();

                        $userid = $user_id;
                        $userid1 = $user_id;
                        $virtual_parent_id = $virtual_parent_id;                        
                        $i = 1;
                        // $loopOn1 = true;     
                        $todaydetails_data = array();
                        $left_users = array();
                        $right_users = array();
                        // $cronstatus = array();
                        if ($virtual_parent_id > 0) {                        
                            $i = 0;                            
                            $leg = $leg;   
                            // echo $i;
                        // if($i > 0){
                        for($i=0;$i<=100000;$i++){
                            // dd($virtual_parent_id1);
                            $result1 = $this->findpositionid($userid,$i);                            
                        //    dd($result1);
                            
                            if($result1 !== false && !empty($result1))
                            {
                                echo $result1->vpid1 .'---'.$result1->position .'---'. $i .'---'. $userid ;
                                echo "\n";
                                // echo $userid;
                                // echo "\n";

                                $Todaydata = array(); // new TodayDetails;
                                $Todaydata['to_user_id'] = $result1->vpid1;
                                $Todaydata['from_user_id'] = $userid1;
                                $Todaydata['entry_time'] = date("Y-m-d H:i:s");
                                $Todaydata['position'] = $result1->position;
                                $Todaydata['level'] = $i+1;
                                array_push($todaydetails_data, $Todaydata);
                               
                                if($result1->position == 1 )
                                {
                                    array_push($left_users, $result1->vpid1);
                                }
                                else if($result1->position == 2 )
                                {
                                    array_push($right_users, $result1->vpid1);
                                }
                                $userid = $result1->vpid1;
                                // echo $userid;
                                // $i++;
                                // echo $i;
                            }
                            else{
                                break;
                                echo "break";
                            }
                            echo "\n";
                        }  
                            //exit;
                        }

                        
                        $count = 1;
                        $array = array_chunk($todaydetails_data, 1000);
                        //dd($array);
                        while ($count <= count($array)) {
                            $key = $count - 1;
                            TodayDetails::insert($array[$key]);
                            // echo $count." count array ".count($array[$key])."\n";
                            $count++;
                        }
                        // dd($left_users,$right_users);

                        $updateLCountArr = array();
                        $updateLCountArr['l_c_count'] = DB::raw('l_c_count + 1');

                        $updateRCountArr = array();
                        $updateRCountArr['r_c_count'] = DB::raw('r_c_count + 1');

                        $updateoldLCountArr = array();
                        $updateoldLCountArr['l_c_count'] = DB::raw('l_c_count - 1');

                        $updateoldRCountArr = array();
                        $updateoldRCountArr['r_c_count'] = DB::raw('r_c_count - 1');

                        // Update count
                        $count1 = 1;
                        $array1 = array_chunk($left_users, 1000);

                        while ($count1 <= count($array1)) {
                            //dd($array1);
                            $key1 = $count1 - 1;
                            User::whereIn('id', $array1[$key1])->update($updateLCountArr);
                            // echo $count." count array1 ".count($array1[$key1])."\n";
                            $count1++;
                        }

                        $count2 = 1;
                        $array2 = array_chunk($right_users, 1000);
                        while ($count2 <= count($array2)) {
                            $key2 = $count2 - 1;
                            User::whereIn('id', $array2[$key2])->update($updateRCountArr);
                            // echo $count2." count array ".count($array2[$key2])."\n";
                            $count2++;
                        }

                        $count3 = 1;
                        $array3 = array_chunk($old_left_users, 1000);

                        while ($count3 <= count($array3)) {
                            //dd($array1);
                            $key3 = $count3 - 1;
                            User::whereIn('id', $array3[$key3])->update($updateoldLCountArr);
                            // echo $count." count array1 ".count($array1[$key1])."\n";
                            $count3++;
                        }

                        $count4 = 1;
                        $array4 = array_chunk($old_right_users, 1000);
                        while ($count4 <= count($array4)) {
                            $key4 = $count4 - 1;
                            User::whereIn('id', $array4[$key4])->update($updateoldRCountArr);
                            // echo $count2." count array ".count($array2[$key2])."\n";
                            $count4++;
                        }

    }

    // public function updateRCcount()
    // {
    //     $tobeupdt = User::select('id')->orderBy('entry_time', 'ASC')->get();
    //     $LCupdate = array();
    //     $RCupdate = array();
    //     foreach($tobeupdt as $updtuser)
    //     {
    //         $lccount = TodayDetails::where('position', 1)->where('to_user_id', $updtuser->id)->count();
    //         $rccount = TodayDetails::where('position', 2)->where('to_user_id', $updtuser->id)->count();
            
    //         $LRarray = array();
    //         $Data = array();
    //         $Data['l_c_count'] = $lccount;
    //         $Data['r_c_count'] = $rcocunt;
    //         $data['id']        = $$updtuser->id;

    //         array_push($LRarray, $data);            
            
    //         $count1 = 1;
    //         $array1 = array_chunk($LRarray, 1000);

    //         while ($count1 <= count($array1)) {
    //         // dd($array1);
    //         $key1 = $count1 - 1;
    //         User::whereIn('id', $array1[$key1])->update(['l_c_count' => $array1[]]);
    //         // echo $count." count array1 ".count($array1[$key1])."\n";
    //         $count1++;
    //         }
    //     }
    // }

   

}
