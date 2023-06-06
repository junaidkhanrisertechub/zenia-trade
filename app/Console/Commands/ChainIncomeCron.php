<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Http\Request;
use App\Traits\Income;

use App\Http\Controllers\Controller;
use Config;

use App\User;
use DB;

class ChainIncomeCron extends Command
{
    use Income;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:chain_income';    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chain Income Cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
 
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    { 

                    $getUsers =  User::select('id','user_id','ref_user_id')
                    ->where('tbl_users.topup_status','1')
                    ->where('tbl_users.type','=','')
                    ->where('tbl_users.status','=','Active') 
                    ->where('id',10199)  
                    ->get();

                    if (!empty($getUsers)) {                       
                        foreach ($getUsers as $val) 
                        {
                            $GetCount = DB::table('tbl_users')
                            ->where('tbl_users.ref_user_id','=',$val->id)
                            ->where('tbl_users.topup_status', '1')
                            ->where('tbl_users.status', 'Active')
                            ->where('tbl_users.type', '')
                            ->select('user_id','id')
                            ->count();
                            
                            $this->give_chain_income($GetCount, $val->id);
                        }
                       
}

}
}