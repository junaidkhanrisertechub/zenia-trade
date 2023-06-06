<?php

namespace App\Console\Commands;

use App\Http\Controllers\userapi\TransactionConfiController;
use App\Models\ProjectSetting as ProjectSettingModel;
use App\Models\TransactionInvoice;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class ConfirmTransactionCron extends Command {

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'cron:confirm_transaction';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Deposit usd to address';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(TransactionConfiController $confirmTxn) {
		parent::__construct();
		$this->confirmTxn = $confirmTxn;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {
		//echo "hellooo";

	 $croncheck= CronStatus::select('*')->where('name',$this->signature)->first();
      /*if($croncheck->status == 1)
      {*/
      // echo "this cron is active\n";


		$projectSettings = ProjectSettingModel::where('status', 1)->first();
		if ($projectSettings->confirm_transaction_status == 0) {

			ProjectSettingModel::where('status', 1)->update(array('confirm_transaction_status' => 0));
			$UserInvoice = TransactionInvoice::where([['in_status', '=', 0], ['top_up_status', '=', 0]])
							->orderBy('entry_time', 'ASC')->get();
			// 'address','id','invoice_id','price_in_usd','payment_mode','trans_id','srno'
			if (!empty($UserInvoice)) {
				foreach ($UserInvoice as $k => $v) {
					$address = $UserInvoice[$k]->address;
					$id = $UserInvoice[$k]->id;
					$invoice_id = $UserInvoice[$k]->invoice_id;
					$price_in_usd = $UserInvoice[$k]->price_in_usd;
					$payment_mode = $UserInvoice[$k]->payment_mode;
					$trans_id = $UserInvoice[$k]->trans_id;
					$srno = $UserInvoice[$k]->srno;
					$req1 = new request();
					if ($trans_id) {
						$req1['txid'] = $trans_id;
						$req1['id'] = $id;
						$req1['srno'] = $srno;
						$req1['invoice_id'] = $invoice_id;
						$req1['price_in_usd'] = $price_in_usd;
						$req1['payment_mode'] = $payment_mode;
						$this->confirmTxn->confirmTransaction($req1);
					}
				}
				ProjectSettingModel::where('status', 1)->update(array('confirm_transaction_status' => 0));

				echo "run successfully ";
				$this->info('Usd Deposit to address is done');
			} else {

				$this->info('Something went wrong');
			}
		} else {

			echo " already running ";

		}

		$today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();
        //$today_datetime2 = $today->toDateString();
      
    /*$cronRunEntry=new CronRunStatus();
    $cronRunEntry['cron_id'] =$croncheck->id;
    $cronRunEntry['run_status'] =1;
    $cronRunEntry['run_time'] =$today_datetime;
    $cronRunEntry->save();

    }else{
        echo "this cron is inactive \n";
    }*/
  }

}
