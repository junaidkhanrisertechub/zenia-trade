<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Controller;
use App\Models\WorkingToPurchaseTransfer;
use App\Models\SupperMatchingIncome;
use App\Models\DailyBonus;
use App\Models\Dashboard;
use App\User;
use App\Models\ProjectSettings as ProjectSettingModel;
use DB;

class TransferDexToPassiveCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:transfer_dex_to_passive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer Balance from Dex to Passive';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $date = \Carbon\Carbon::now();
        $this->today = $date->toDateTimeString();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        echo 'CRON started at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";

        $dashData = Dashboard::select('tbl_dashboard.id',DB::raw("DATEDIFF(curdate(), DATE_FORMAT(tu.entry_time,'%Y-%m-%d')) as diffx"),DB::raw('tbl_dashboard.working_wallet-tbl_dashboard.working_wallet_withdraw as dex_balance'))
                    ->leftjoin('tbl_users as tu','tu.id','=','tbl_dashboard.id')
                    ->where(DB::raw("DATEDIFF(curdate(), DATE_FORMAT(tu.entry_time,'%Y-%m-%d'))"), ">", 90)
                    ->where(DB::raw('working_wallet-working_wallet_withdraw'),'>',0)
                    ->get();
        $all_data=array();
        $all_dash_data=array();
        foreach ($dashData as $key => $req) {

            list($usec, $sec) = explode(" ", microtime());

            $time_start = ((float)$usec + (float)$sec);
            
            $diffx = $req->diffx;

            $entry_time = "2021-08-31 00:00:00";

            if ($diffx > 90) {
                if($diffx == 91){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -1 day"));
                }elseif($diffx == 92){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -2 day"));
                }elseif($diffx == 93){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -3 day"));
                }elseif($diffx == 94){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -4 day"));
                }elseif($diffx == 95){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -5 day"));
                }elseif($diffx == 96){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -6 day"));
                }elseif($diffx == 97){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -7 day"));
                }elseif($diffx >= 98){
                    $entry_time = date('Y-m-d 00:00:00',strtotime($entry_time." -8 day"));
                }
                $total_super = SupperMatchingIncome::selectRaw('SUM(amount) as total')->where('id',$req->id)->where('entry_time','>=',$entry_time)->pluck('total')->first();
                $total_roi = DailyBonus::selectRaw('SUM(amount) as total')->where('id',$req->id)->where('entry_time','>=',$entry_time)->pluck('total')->first();

                $amount = $total_super + $total_roi;
                if ($amount > 0) {
                    if ($amount > $req->dex_balance) {
                       $amount = $req->dex_balance;
                    }
                    $insertData = array();
                    $insertData['to_user_id'] = $req->id;
                    $insertData['from_user_id'] = $req->id;
                    $insertData['amount'] = $amount;
                    $insertData['status'] = 1;
                    array_push($all_data, $insertData);

                    $updateData = array();
                    $updateData['id'] = $req->id;;
                    $updateData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw + '.$amount);
                    $updateData['passive_income'] = DB::raw('passive_income + '.$amount);

                    array_push($all_dash_data, $updateData);
                    echo "For User Id : ".$req->id."\n"; 

                }

                list($usec, $sec) = explode(" ", microtime());
                $time_end = ((float)$usec + (float)$sec);
                $time = $time_end - $time_start;
                echo "time ".$time."\n";
            }
        }

        $count = 1;
        $array = array_chunk($all_data, 1000);
        //$dashArray = array_chunk($all_dash_data, 1000);
      
        while ($count <= count($array)) {
            $key = $count - 1;
            DB::table('tbl_dex_to_passive_transfer')->insert($array[$key]);
            $count++;
        }

        $count3 = 1;     
        
        $array3 = array_chunk($all_dash_data,1000);

        while($count3 <= count($array3))
        {
            $key3 = $count3-1;
            $arrProcess = $array3[$key3];
            $ids = implode(',', array_column($arrProcess, 'id'));

            $working_wallet_withdraw_qry = 'working_wallet_withdraw = (CASE id';
            $passive_income_qry = 'passive_income = (CASE id';

            foreach ($arrProcess as $key => $val) {
                $working_wallet_withdraw_qry = $working_wallet_withdraw_qry . " WHEN ".$val['id']." THEN ".$val['working_wallet_withdraw'];
                $passive_income_qry = $passive_income_qry . " WHEN ".$val['id']." THEN ".$val['passive_income'];
            }

            $working_wallet_withdraw_qry = $working_wallet_withdraw_qry . " END)";
            $passive_income_qry = $passive_income_qry . " END)";

            $updt_qry = "UPDATE tbl_dashboard SET ".$working_wallet_withdraw_qry." , ".$passive_income_qry." WHERE id IN (".$ids.")";
            $updt_user = DB::statement(DB::raw($updt_qry));

            echo $count3." update dash array ".count($arrProcess)."\n";
            $count3 ++;
        }



        echo 'CRON end at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";
    }

}
