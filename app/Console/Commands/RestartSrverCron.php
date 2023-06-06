<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use DB;
use Config;
use Response;
use App\User;
use App\Models\TodayDetails;
use App\Models\PowerBV;

//use App\Http\Controllers\userapi\AwardRewardController;


class RestartSrverCron extends Command{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:restart_server';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'restart_server';

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
        echo shell_exec('sh ./../hsccshell/serversrestart.sh');
    }
}


