<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BlockUserOnTimeOver extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cron:blockon_user';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Block user if not in 48 Housrs ';

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

		$olddate = \Carbon\Carbon::now()->subHours(168)->toDateTimeString();
		//dd($olddate);
		$date = new \DateTime();
		$date->modify('-3 hours');
		$formatted_date = $date->format('Y-m-d H:i:s');
		$users = DB::table('tbl_users')->select('*')
			->where('tbl_users.type', '')
			->where('tbl_users.status', 'Active')
			->where('tbl_users.topup_status_check', '=', 0)
			->where('tbl_users.entry_time', '<=', $olddate)
			->limit(100)
			->get();
       // dd($users);
		foreach ($users as $user) {

			$usersTopup = DB::table('tbl_topup')->where('id', '=', $user->id)->get();
			//dd(count($usersTopup) == 0);
			if (count($usersTopup) == 0) {

				DB::table('tbl_users')
					->where('id', '=', $user->id)
					->update(array('status' => 'Inactive'));
				echo $user->user_id . "\n";
			}else{
               

				DB::table('tbl_users')
					->where('id', '=', $user->id)
					->update(array('topup_status_check' => 1));

			}

			# code...
		}

		echo "successfully  run \n";
	}
}