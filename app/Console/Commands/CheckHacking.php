<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use App\Models\Topup;
use App\Models\AllTransaction;
use App\Models\Dashboard;
use App\User;

class CheckHacking extends Command {
	/**
	 * The name and signature of the console command.  Hacking
	 *
	 * @var string
	 */
	protected $signature = 'cron:check_hacking';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check user Top Up ';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {

		/*$olddate = \Carbon\Carbon::now()->subHours(168)->toDateTimeString();
		//dd($olddate);
		$date = new \DateTime();
		$date->modify('-3 hours');
		$formatted_date = $date->format('Y-m-d H:i:s');
	*/
		

		$Topup=Topup::select('id','amount','srno')->where('check_hack','=',0)
		  ->where('id','=',18382)
		    ->limit(100)
			->get();
             foreach ($Topup as $top) {

             	$userTopup=Topup::where('id','=',$top->id)->where('top_up_by','=',$top->id)->where('top_up_type','=',3)
             	->sum('amount');

             	//$userTransAmt=AllTransaction::where('id','=',$top->id)
                          	// ->sum('debit');

             $top_up_wallet=Dashboard::select('top_up_wallet')->where('id','=',$top->id)->first();
                

                          	 //$sum=$userTopu;

                          	 if($top_up_wallet >=$userTopup){
                               echo " No Hacking";
                               echo " user id:->".$top->id;
                               echo "\n";
                          	 }else{
                          	 	echo "Block User";
                          	 	  echo " user id:->".$top->id;
                               echo "\n";
                               
                                User::where('id','=',$top->id)->update(array('hacking' => 1))->limit(1);

                                // checked in cron
                                Topup::where('srno','=',$top->srno)->update(array('check_hack' => 1))->limit(1);


                          	 }

                  //dd($top_up_wallet,$sum);

             	# code...
             }
			//dd($Topup);
		echo "successfully  run \n";
	}
}