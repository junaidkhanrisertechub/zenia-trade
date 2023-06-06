<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserStructureModel;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use App\Traits\Users;
use App\User;
use App\Models\ProjectSettings;
use DB;


class BulkEntries extends Command
{
    use Users;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:BulkEntries';

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
       /* $check_cron_running = DB::table('tbl_project_settings')
                      ->select('bulk_entry_cron_status')
                      ->where('bulk_entry_cron_status','=', 0)
                      ->count();

                      if($check_cron_running >= 0)
                      {

                         $data['bulk_entry_cron_status'] = '1';
                        DB::table('tbl_project_settings')
                        ->update($data);*/

      $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();

      if($croncheck != null)
      {

      if($croncheck->status ==1)
      {
      echo "this cron is active\n";



        $userdata = UserStructureModel::select('id','status','user_id','mobile','email','no_structure','password','bcrypt_password','fullname','transaction_type')->where('status',0)->whereIn('transaction_type',array(1,2))->get();
      
        foreach ($userdata as $value) {
            $no_structure =  (int)$value->no_structure;
          //dd($no_structure); 

            $total_structure_completed = DB::table('tbl_users')
                      ->select('structure_id')
                      ->where('structure_id','=', $value->id)
                      ->count('structure_id');

            // dd(1,$total_structure_completed);
          //  $no_structure = $no_structure - $total_structure_completed;    
            for ($i = $total_structure_completed; $i<=$no_structure;$i++) {
                        $checkUpdate =  $this->binaryPlanRegistrationnew($value,$no_structure,$i);
                        echo $checkUpdate. "\n";   

                        $total_structure_completed = DB::table('tbl_users')
                      ->select('structure_id')
                      ->where('structure_id','=', $value->id)
                      ->count('structure_id');

                      if($total_structure_completed >= $value->no_structure)
                      {
                        $data1['status'] = '1';
                         DB::table('tbl_user_structure')
                        ->where('id','=', $value->id)
                        ->update($data1);
                      }
                      $i=$total_structure_completed;

            }


            
        }

      /*  $data2['bulk_entry_cron_status'] = '0';
                        DB::table('tbl_project_settings')
                       ->update($data2);*/

                       echo "Bulk Entries Cron Run Successfully...";

  /*  }
    else{
        echo "Bulk Entries Cron Already Running...";

    }*/

    $today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();
        $today_datetime2 = $today->toDateString();
    

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
