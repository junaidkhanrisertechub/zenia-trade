<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\GenerateRoiController;
use App\Http\Controllers\adminapi\ManageCronController;
use App\Models\DailyBonus;
use App\Models\Topup;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\Dashboard;
use DB;



class OptimizedRoiDynamic extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:optimized_roi_dynamic';
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
    public function __construct(GenerateRoiController $generateRoi,ManageCronController $manageCronController) {
      parent::__construct();
      $this->generateRoi = $generateRoi;
      $this->manageCronController = $manageCronController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle() {

      //echo $this->signature;

      $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();

      if($croncheck->status ==1)
      {
        echo "this cron is active\n";


        

        $Runcheck= CronStatus::select('running_status')->where('name',$this->signature)->first();
        if($Runcheck->running_status == 1)
        {
        dd("Now the cron is running\n");
        
        }
        $this->manageCronController->RunCronRoiDynamic();
        echo "Run Successfully..";
      }
      else{
        //echo "this cron is inactive \n";
        
      }

      // if($croncheck->running_status ==0)
      // {
      // echo "this cron is Running";
      // }
      // else{
      //   echo "this cron is Idle";
    //  }






  }

}