<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Models\QualifiedUserList;
use App\Models\TodayDetails;
use App\Models\Topup;
use DB;
use Response;
use Config;


class ForBinaryQualified extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:qualified_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Qualified users inserted succcessfully';

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

      dd("This is old cron please use cron:optimized_binary_qualify cron");

      // $users = User::with('topupico')->get();
      $users = DB::table("tbl_users")->select('tbl_users.id','tbl_users.user_id')
            ->join('tbl_topup as tt','tt.id','=','tbl_users.id')
            ->join('tbl_product as tp','tp.id','=','tt.type')
            ->whereNotIn('tbl_users.id',function($query){
               $query->select('user_id')->from('tbl_qualified_user_list');
            })->get();

       
      $flag=0;
      $array=[];
      foreach($users as $k => $user)
      {
        $flag=0;
        $array=[];
        
        $left_direct_users =User::join('tbl_today_details as ttd','ttd.from_user_id','=','tbl_users.id')->where([['tbl_users.ref_user_id',$user->id],['ttd.to_user_id',$user->id],['ttd.position',1]])->pluck('ttd.from_user_id');

        $right_direct_users =User::join('tbl_today_details as ttd','ttd.from_user_id','=','tbl_users.id')->where([['tbl_users.ref_user_id',$user->id],['ttd.to_user_id',$user->id],['ttd.position',2]])->pluck('ttd.from_user_id');

        $topup = DB::table('tbl_topup as tt')
                  ->join('tbl_product as tp','tp.id','=','tt.type')
                  ->pluck('tt.id'); 

        $topup_users = DB::table('tbl_topup as tt')
                  ->join('tbl_product as tp','tp.id','=','tt.type')
                  /*->where('tt.status', '!=', 'zeropin')*/
                  ->pluck('tt.id');
      
        $top_entry_user = json_decode($topup_users, true);
        $counter = 0;
        $counter1 = 0;

        $userexistinTopup = Topup::where('tbl_topup.id',$user->id)
                            ->join('tbl_product as tp','tp.id','=','tbl_topup.type')->first();
        if(!empty($userexistinTopup)){
          $flag = $flag+1;
        }
        foreach($left_direct_users as $left_direct_user){
          if(in_array($left_direct_user,$top_entry_user)){
            if(!in_array($left_direct_user,$array))
            { 
              if($counter == 0){
                  $flag=$flag+1;
                 
                } 
              array_push($array,$left_direct_user);
              $counter++;
            }
          }
        }
        foreach($right_direct_users as $right_direct_user){
          if(in_array($right_direct_user,$top_entry_user)){
            if(!in_array($right_direct_user,$array))
            { 
               if($counter1 == 0){
                  $flag=$flag+1;
                 
                } 
              array_push($array,$right_direct_user);
              $counter1++;
            }
          }
        }
        if($flag>=3)
        {
            // check if the user already exist in qualified user list table
            $userexist = QualifiedUserList::where('user_id',$user->id)->first();
            if(empty($userexist)){
              $date= \Carbon\Carbon::now();
              $today= $date->toDateTimeString();
              $QualifiedUsers = new QualifiedUserList;
              $QualifiedUsers->user_id = $user->id;
              $QualifiedUsers->entry_time=$today;
              $QualifiedUsers->save();
              echo "User id ---> ".$user->id."\n";
            }
          
        }
        $flag=0;
        $array =[];
      }

      
        $this->info("Binary Qualified Cron Run Successfully");

    }
}
