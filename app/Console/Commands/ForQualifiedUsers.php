<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Models\QualifiedUserList;
use DB;
use Response;
use Config;


class ForQualifiedUsers extends Command
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

            $today = \Carbon\Carbon::now();
            $today_datetime = $today->toDateTimeString();
            $today_datetime2 = $today->toDateString();

            $qualifyUser = QualifiedUserList::whereDate('entry_time', '=', $today_datetime2)->count();


           $msg = 'Qualify CRON started at'.$today_datetime ."\n" .
                   "Total records:: " . $qualifyUser . "\n";

            //$ss = sendSms('9860163716',$msg);
            /*$ss = sendSms('8669605501',$msg);*/
            
        foreach($users as $user){

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
        $today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();


        $today_datetime2 = $today->toDateString();

        $qualifyUser = QualifiedUserList::whereDate('entry_time', '=', $today_datetime2)->count();


       $msg2 = 'Qualify CRON end at'.$today_datetime ."\n" .
               "Total records:: " . $qualifyUser . "\n";

       // $ss = sendSms('9860163716',$msg2);
        /*$ss = sendSms('8669605501',$msg2);*/
}
}
