<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Response;
use App\Http\Controllers\userapi\AwardRewardController;


class AssignAwardCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:assign_award';
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
    public function __construct(AwardRewardController $assignaward)
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
         $assign = $this->assignaward->cronAwardAssign();
         $this->info('Cron ran successfully');

    }
}
