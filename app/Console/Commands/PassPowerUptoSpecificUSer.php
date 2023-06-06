<?php

namespace App\Console\Commands;
use App\Http\Controllers\adminapi\ManageCronController;

use Illuminate\Console\Command;

class PassPowerUptoSpecificUSer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:passpower_uptospecificuser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron add power upto specific user';

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
        //
        $this->manageCronController->PassBvuptospecificUser($this->signature,'cron'); 
    }
}
