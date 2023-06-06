<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\EWalletController;
use App\Http\Controllers\userapi\SettingsController;
use App\User;
use App\Models\WithdrawPending;
use App\Models\WithdrawalConfirmed;
use Mail;

class SaveTransactionHash extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:SaveCoinbaseTransactionHash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save Coinbase Transaction Hash by Transaction Id';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(EWalletController $ewallet,SettingsController $settings) {
        parent::__construct();
        $this->ewallet = $ewallet;
        $this->settings = $settings;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $getRec = WithdrawalConfirmed::where('transaction_hash','')->orwhere('transaction_hash',null)->limit(10)->get();
        //dd($getRec);
        if(!empty($getRec)){
            foreach ($getRec as $rec) {
                $resp = getCoinbaseTransactionHash('BTC', $rec->api_ref_id);
                if($resp['status']=='Success'){
                    $updateData = array();
                    $updateData['transaction_hash'] = $resp['transaction_hash'];
                    $updateData = WithdrawalConfirmed::where([['sr_no', '=', $rec->sr_no], ['api_ref_id', '=', $rec->api_ref_id]])->update($updateData);
                } else {
                    continue;
                }
            }
            
        }
    }

}
