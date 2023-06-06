<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use DB;
use Response;
use App\User;
use App\Traits\Users;
use App\Http\Controllers\userapi\UserController;


class DownlineCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:downline_cron';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'downline_cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UserController $assignaward)
    {
        parent::__construct();
       $this->assignaward = $assignaward; 
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       $usersList=User::select('id','ref_user_id')->where('ref_user_id', '>', 0)->get();
         foreach ($usersList as $user) {
            $this->assignaward->downlineuserauto($user->ref_user_id, $user->id);
            echo   $user->id." is register successfully... \n"; 
    	 }
  }
}


                           