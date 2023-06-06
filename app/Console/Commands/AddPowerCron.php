<?php

namespace App\Console\Commands;

use App\Http\Controllers\adminapi\ManageCronController;
use Illuminate\Console\Command;
use App\Models\PowerBV;
use App\User;
use App\Models\CurrentAmountDetails;
use Config;
use DB;

class AddPowerCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:addpower_uptoadmin';
    protected $hidden = true;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Cron Add Power Upto Admin';

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
        $this->manageCronController->AddPowerUptoAdmin($this->signature,'cron'); 
    }
}
