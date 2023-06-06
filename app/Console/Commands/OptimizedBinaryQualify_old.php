<?php

namespace App\Console\Commands;

use App\Http\Controllers\adminapi\ManageCronController;
use Illuminate\Console\Command;
use App\Models\Topup;
use App\Models\QualifiedUserList;
use App\Models\TodayDetails;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\User;
use DB;
use Response;
use Config;


class OptimizedBinaryQualify_old extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:optimized_binary_qualify_old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimized binary qualify users cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ManageCronController $manageCronController)
    {
        parent::__construct();
        $this->manageCronController = $manageCronController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // dd('end');
        //$mobile = 1234567890;
      $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();

      if($croncheck != null)
      {
      if($croncheck->status ==1)
      {
      echo "this cron is active\n";

      $this->manageCronController->RunBinaryQualifyCron($this->signature,'cron'); 
        echo "\n Cron run successfully \n" ;

        // $current_time = \Carbon\Carbon::now()->toDateTimeString();
        // $msg = "Qualify Cron end at ".$current_time."\nTotal qualified ids : ".$qualified_count."\n";
        // //sendSMS($mobile,$msg);
        // echo $msg;

    
    }
    else{
        echo "this cron is inactive \n";
      }
    } else{
      echo "this cron is inactive \n";
    }
    
}
}
