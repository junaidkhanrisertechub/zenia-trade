<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\GenerateRoiController;
use App\User;
use App\Models\DailyBonus;
use App\Models\SupperMatchingIncome;
use App\Models\Activitynotification;
use App\Models\AllTransaction;
use App\Models\Dashboard;


class SupperMachingBonusIncomeCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:Supper_maching_income';
    //protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Every three hours roi generation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GenerateRoiController $generatesuppermatchingincome) {
        parent::__construct();
        $this->generatesuppermatchingincome = $generatesuppermatchingincome;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //echo "hellooo";
  dd('This is Old cron pls use cron:supper_matching_income_new');
        //echo "hellooo";
        $day = \Carbon\Carbon::now()->format('D');
        //dd($day);
     
        $user = User::join('tbl_super_matching', 'tbl_super_matching.user_id', '=', 'tbl_users.id')
                ->select('tbl_users.id','tbl_users.mobile','tbl_users.country','tbl_users.user_id','tbl_users.email','tbl_super_matching.pin','tbl_super_matching.rank','tbl_super_matching.entry_time')
                ->where([['tbl_users.status', '=', 'Active'],['tbl_users.type', '=', '']])
              /*  ->where('tbl_super_matching.rank','Ace')*/
              //  ->skip(500)->take(500)
               // ->limit(1)
                ->get();
      //  dd($user);
        $today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();
        $today_datetime2 = $today->toDateString();

        $payoutHistory =SupperMatchingIncome::select('entry_time')->whereDate('entry_time', '=', $today_datetime2)->count();

        $msg = 'Supper Matching Bonus CRON started at' . $today_datetime . "\n" .
            "Total records:: " . $payoutHistory . "\n";

        if (!empty($user)) {
            foreach ($user as $key => $value) {
               $id = $user[$key]->id;
                $pin = $user[$key]->pin;
                $type = $user[$key]->type;
                $entry_time = $user[$key]->entry_time;
                $email = $user[$key]->email;
                $user_id = $user[$key]->user_id;
                $country = $user[$key]->country;
                $mobile = $user[$key]->mobile;
                $withdraw = $user[$key]->withdraw;
                $old_status = $user[$key]->old_status;
                $rank = $user[$key]->rank;
              
                //except package 1 (trial $100)
             //   if($user[$key]->srno == '18'){
                echo "For User Id ".$id." pin ".$pin." \n";
                    $checkUpdate = $this->generatesuppermatchingincome->generatesuppermatchingincome($id, $pin, $type, $entry_time,$email,$user_id,$country,$mobile,$withdraw,$old_status,$rank);
               // }
            }
            $this->info('Supper Matching Bonus generated successfully');
        } else {

            $this->info('User is not exist');
        }
        
    }

}
