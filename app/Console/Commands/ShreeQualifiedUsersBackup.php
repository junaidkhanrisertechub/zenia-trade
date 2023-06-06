<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Models\QualifiedUserList;
use DB;
use Response;
use Config;


class ShreeQualifiedUsersBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:qualified_user';

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
        // $users = User::with('topupico')->get();
        $users = User::all();
       
        $flag=0;
        $array=[];
        foreach($users as $k => $user){

          /** get left and right user of current  user **/
        //  $user->id = 16635;
        //$l_user = User::where('ref_user_id',$user->id)->where('position',1)->pluck('id');//left user
          $l_user = $user->where('ref_user_id',$user->id)->where('position',1)->pluck('id');
          

        //$r_user = User::where('ref_user_id',$user->id)->where('position',2)->pluck('id');//right user
          $r_user = $user->where('ref_user_id',$user->id)->where('position',2)->pluck('id');
          
         // dd($l_user);
          // $test = $user->where('id',399);
          // dd($test);
          // dd(count($test->topupico));

          /**   for testing */

            if($k==1){
               //dd(count($r_user));
             }

           /** end for testing */


          if(count($l_user)>0 && count($r_user)>0){
             if($k==1){
              
           }
           $topupico = DB::table('tbl_topup')->pluck('id');  
      
           // $dashboard_entry_user = json_decode($topupico, true);
           $settings   = Config::get('constants.settings');
         //  $dashboarddata = DB::table('tbl_dashboard')->where('total_bv','>=',$settings['min_tot_bv'])->pluck('id');
          // dd($dashboarddata);
            $dashboard_entry_user = json_decode($topupico, true);
            $counter = 0;
            $counter1 = 0;


            if(in_array($user->id,$dashboard_entry_user)){
               
                if(!in_array($user->id,$array))
                {
                  $flag=$flag+1;
                  array_push($array,$user->id);
                }
            }
           
         /*  
         foreach($l_user as $l_user){
            if(in_array($l_user,$dashboard_entry_user)){
                //dd('hi');
                if(!in_array($l_user,$array))
                { 
                   
                  if($counter == 0){
                  $flag=$flag+1;
                 
                } 
                 
                  array_push($array,$l_user);
                  $counter++;
                }
            }
          }
  
    
          foreach($r_user as $r_user){
            if(in_array($r_user,$dashboard_entry_user)){
                if(!in_array($r_user,$array))
                {
                  
                 if($counter1 == 0){
                  $flag=$flag+1;
                } 
 
                  
                  array_push($array,$r_user);
                  $counter1++;
                }
            }

           }*/
         

          }
         if($k==1){
            
         }
        
          if($flag>=1){

            // check if the user al;ready exist in qualified user list table
              $userexist = QualifiedUserList::where('user_id',$user->id)->first();
              if(empty($userexist)){
              $date= \Carbon\Carbon::now();
              $today= $date->toDateTimeString();
              $QualifiedUsers = new QualifiedUserList;
              $QualifiedUsers->user_id = $user->id;
              $QualifiedUsers->entry_time=$today;
              $QualifiedUsers->save();
             
              }
            }
             $flag=0;
             $array =[];
          

        } 
     


    }
}
