<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Response;
use App\Models\UserContestAchievement;
use App\Models\ContestPrizeSetting;
use App\User;

class AssignContestPrizeCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:assign_contest_prize';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign Awards for users';

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
        $users = User::select('id','contest_lbv','contest_rbv')->where('contest_lbv','>=',20)->where('contest_rbv','>=',20)
                        ->get();
        $insertData = array();
        foreach ($users as $k => $v) {
            $settings = ContestPrizeSetting::select('id')->where('required_left_commanders',"<=",$v->contest_lbv)->where('required_left_commanders',"<=",$v->contest_lbv)->get();
            foreach ($settings as $ck => $cv) {
                $checkexist = UserContestAchievement::where('user_id',$v->id)->where('contest_id',$cv->id)->count('id');
                if ($checkexist == 0) {
                    $contestData = array();
                    $contestData['user_id'] = $v->id;
                    $contestData['contest_id'] = $cv->id;
                    $checkPrizeExist = UserContestAchievement::where('user_id',$v->id)->where('claim_status',1)->count('id');
                    if ($checkPrizeExist > 0) {
                        $contestData['claim_status'] = 3;                        
                    }

                    array_push($insertData,$contestData);
                }
            }
        }

        $count = 1;
        $array = array_chunk($insertData, 1000);
      
        while ($count <= count($array)) {
            $key = $count - 1;
            UserContestAchievement::insert($array[$key]);
            echo $count." insert user contest achievement count array ".count($array[$key])."\n";            
            $count++;
        }
    }
}
