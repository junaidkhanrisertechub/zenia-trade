<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use DB;
use Response;
use App\User;
use App\Models\TodayDetails;
use App\Models\PowerBV;
use App\Models\AddRemoveBusinessUpline;

//use App\Http\Controllers\userapi\AwardRewardController;


class AddBusinessUplineCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:add_business_upline';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add_business_upline';

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
       /* dd("STOP");*/
       $start_time = \Carbon\Carbon::now();
        
        $getPower = AddRemoveBusinessUpline::where([['cron_status',"0"],['type',"2"]])->orderBy('srno','ASC')->get();
        $insertArr = array();
        foreach ($getPower as $key => $value) {
           $srno     = $value->srno;
           $user_id  = $value->user_id;
           $position = $value->position;
           $power_bv = $value->power_bv;
           $upline_id = (isset($value->upline_id))?$value->upline_id:1;
           $amount   = $power_bv;
           
        	$user = User::where('id', $user_id)->count('id');

            if ($user == 1) {

                $pos="";
                if($position == 1){
                    $pos = '1';
                    $col = "l_bv";
                    $ins =array();
                    $ins['user_id'] = $user_id;
                    $ins['from_user_id'] = $user_id;
                    $ins['power_bv'] = $power_bv;
                    $ins['position'] = 1;
                    $ins['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                    array_push($insertArr,$ins);
                }
                else{

                    $pos = '2';
                    $col = "r_bv";

                    $ins =array();
                    $ins['user_id'] = $user_id;
                    $ins['from_user_id'] = $user_id;
                    $ins['power_bv'] = $power_bv;
                    $ins['position'] = 2;
                    $ins['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                    array_push($insertArr,$ins);
                }

                $bussiness = $power_bv;
            
                $updateLCountArr = array();
                $updateLCountArr['l_bv'] = DB::raw('l_bv + '.$bussiness);
                $updateLCountArr['curr_l_bv'] = DB::raw('curr_l_bv + '.$bussiness);


                $l_qry = DB::table('tbl_today_details as a')
                ->select('b.id')
                ->join('tbl_users as b','a.to_user_id', '=','b.id')
                ->where('a.from_user_id','=',$user_id)
                ->where('a.position','=',1)
                ->where('a.today_id','<=',function ($l_qry) use ($upline_id,$user_id){
                    $l_qry->select('today_id')
                        ->from(with(new TodayDetails)->getTable())
                        ->where('from_user_id',$user_id)->where('to_user_id',$upline_id);
                });
                $l_users = $l_qry->get();               
                foreach ($l_users as $lu) {
                    $ins =array();
                    $ins['user_id'] = $lu->id;
                    $ins['from_user_id'] = $user_id;
                    $ins['power_bv'] = $bussiness;
                    $ins['position'] = 1;
                    $ins['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                    array_push($insertArr,$ins);
                }
                $l_qry->update($updateLCountArr);

                $updateRCountArr = array();
                $updateRCountArr['r_bv'] = DB::raw('r_bv + '.$bussiness);
                $updateRCountArr['curr_r_bv'] = DB::raw('curr_r_bv + '.$bussiness);


                $r_qry = DB::table('tbl_today_details as a')
                ->select('b.id')
                ->join('tbl_users as b','a.to_user_id', '=','b.id')
                ->where('a.from_user_id','=',$user_id)
                ->where('a.position','=',2)
                ->where('a.today_id','<=',function ($r_qry) use ($upline_id,$user_id){
                    $r_qry->select('today_id')
                        ->from(with(new TodayDetails)->getTable())
                        ->where('from_user_id',$user_id)->where('to_user_id',$upline_id);
                });
                $r_users = $r_qry->get();
                foreach ($r_users as $ru) {
                    $ins =array();
                    $ins['user_id'] = $ru->id;
                    $ins['from_user_id'] = $user_id;
                    $ins['power_bv'] = $bussiness;
                    $ins['position'] = 2;
                    $ins['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                    array_push($insertArr,$ins);                    
                }
                $r_qry->update($updateRCountArr);

                $updateData = array();
                $updateData[$col] = DB::raw($col.' + '.$bussiness);
                $updateData["curr_".$col] = DB::raw("curr_".$col.' + '.$bussiness);
                $userupdate =User::where('id', $user_id)->update($updateData);

                $update_status = AddRemoveBusinessUpline::where([['cron_status','0'],['srno',$srno]])->limit(1)->update(['cron_status'=>'1']);
                echo "Add power = ".$bussiness." ====> To Userid =".$user_id." ====> Upto ID = ".$upline_id."\n";
                                
            }
        }

        $count = 1;
        $array = array_chunk($insertArr,1000);
       // dd($array);
        while($count <= count($array))
        {
          $key = $count-1;
          DB::table('tbl_manual_bv_add')->insert($array[$key]);
          echo $count." insert count array ".count($array[$key])."\n";
          $count ++;
        }

        /*cron_run_status_update('add_business_upline',$start_time,count($getPower));*/
    }
}


                           