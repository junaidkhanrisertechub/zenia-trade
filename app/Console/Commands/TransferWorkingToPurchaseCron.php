<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Controller;
use App\Models\WorkingToPurchaseTransfer;
use App\Models\Dashboard;
use App\Models\ProjectSettings as ProjectSettingModel;
use DB;

class TransferWorkingToPurchaseCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:transfer_working_to_purchase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer Balance from working to purchase';

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
    public function handle() {dd('Stop');

        echo 'CRON started at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";

        $projectSettings = ProjectSettingModel::select('working_percentage','purchase_percentage')->where('status', 1)->first();
        $purchase_percentage = $projectSettings->purchase_percentage;
        $working_percentage = $projectSettings->working_percentage;

        $dashData = Dashboard::select('id','direct_income','roi_income','binary_income','supper_maching_income','freedom_club_income','total_income_without_roi')->where('working_wallet','>',0)->get();
        $all_data=array();
        $all_dash_data=array();
        foreach ($dashData as $key => $req) {

            list($usec, $sec) = explode(" ", microtime());

            $time_start = ((float)$usec + (float)$sec);

            $old_total_income_without_roi= $req->total_income_without_roi;
                
            $total_income_without_roi = round(($req->binary_income+$req->freedom_club_income+$req->supper_maching_income+$req->direct_income),2); 

            $first_time_balance = round(($total_income_without_roi-$old_total_income_without_roi),2);

            echo "For User Id : ".$req->id."\n"; 

            $purchase_wallet_amount = round((($first_time_balance * $purchase_percentage)/100),2);

            $working_wallet_amount = round(($first_time_balance - $purchase_wallet_amount),2);            

            $insertData = array();
            $insertData['id'] = $req->id;
            $insertData['binary_income'] = $req->binary_income;
            $insertData['roi_income'] = $req->roi_income;;
            $insertData['direct_income'] = $req->direct_income;;
            $insertData['supper_maching_income'] = $req->supper_maching_income;
            $insertData['freedom_club_income'] = $req->freedom_club_income;
            $insertData['purchase_wallet_amount'] = $purchase_wallet_amount;
            $insertData['working_wallet_amount'] = $working_wallet_amount;
            $insertData['purchase_percentage'] = $purchase_percentage;
            $insertData['working_percentage'] = $working_percentage;
            $insertData['old_total_income_without_roi'] = $old_total_income_without_roi;
            $insertData['total_income_without_roi'] = $total_income_without_roi;
            $insertData['balance'] = $first_time_balance;
            //$insertData->save();
            array_push($all_data, $insertData);

            $updateData = array();
            $updateData['id'] = $req->id;;
            $updateData['working_wallet'] = DB::raw('working_wallet - '.$purchase_wallet_amount);
            $updateData['top_up_wallet'] = DB::raw('top_up_wallet + '.$purchase_wallet_amount);
            $updateData['first_time_balance'] = $first_time_balance;                    
            $updateData['working_to_topup'] = DB::raw('working_to_topup + '.$purchase_wallet_amount);
            $updateData['total_income_without_roi'] = $total_income_without_roi;
            $updateData['old_total_income_without_roi'] = $old_total_income_without_roi;

            $updateDash = Dashboard::where('id',$req->id)->update($updateData);

            list($usec, $sec) = explode(" ", microtime());
            $time_end = ((float)$usec + (float)$sec);
            $time = $time_end - $time_start;
            echo "time ".$time."\n";
        }

        $count = 1;
        $array = array_chunk($all_data, 1000);
        //$dashArray = array_chunk($all_dash_data, 1000);
      
        while ($count <= count($array)) {
            $key = $count - 1;
            WorkingToPurchaseTransfer::insert($array[$key]);
            $count++;
        }

        $count3 = 1;     
        
        $array3 = array_chunk($all_dash_data,1000);

        while($count3 <= count($array3))
        {
            $key3 = $count3-1;
            $arrProcess = $array3[$key3];
            $ids = implode(',', array_column($arrProcess, 'id'));

            $working_wallet_qry = 'working_wallet = (CASE id';
            $top_up_wallet_qry = 'top_up_wallet = (CASE id';
            $first_time_balance_qry = 'first_time_balance = (CASE id';;                    
            $working_to_topup_qry = 'working_to_topup = (CASE id';
            $total_income_without_roi_qry = 'total_income_without_roi = (CASE id';
            $old_total_income_without_roi_qry = 'old_total_income_without_roi = (CASE id';

            foreach ($arrProcess as $key => $val) {
                $working_wallet_qry = $working_wallet_qry . " WHEN ".$val['id']." THEN ".$val['working_wallet'];
                $top_up_wallet_qry = $top_up_wallet_qry . " WHEN ".$val['id']." THEN ".$val['top_up_wallet'];
                $first_time_balance_qry = $first_time_balance_qry . " WHEN ".$val['id']." THEN ".$val['first_time_balance'];
                $working_to_topup_qry = $working_to_topup_qry . " WHEN ".$val['id']." THEN ".$val['working_to_topup'];
                $total_income_without_roi_qry = $total_income_without_roi_qry . " WHEN ".$val['id']." THEN ".$val['total_income_without_roi'];
                $old_total_income_without_roi_qry = $old_total_income_without_roi_qry . " WHEN ".$val['id']." THEN ".$val['old_total_income_without_roi'];
            }

            $working_wallet_qry = $working_wallet_qry . " END)";
            $top_up_wallet_qry = $top_up_wallet_qry . " END)";
            $first_time_balance_qry = $first_time_balance_qry . " END)";
            $working_to_topup_qry = $working_to_topup_qry . " END)";
            $total_income_without_roi_qry = $total_income_without_roi_qry . " END)";
            $old_total_income_without_roi_qry = $old_total_income_without_roi_qry . " END)";

            $updt_qry = "UPDATE tbl_dashboard SET ".$working_wallet_qry." , ".$top_up_wallet_qry." , ".$first_time_balance_qry." , ".$working_to_topup_qry." , ".$total_income_without_roi_qry." , ".$old_total_income_without_roi_qry." WHERE id IN (".$ids.")";
            $updt_user = DB::statement(DB::raw($updt_qry));

            echo $count3." update dash array ".count($arrProcess)."\n";
            $count3 ++;
        }



        echo 'CRON end at '. \Carbon\Carbon::now()->toDateTimeString() ."\n";
    }

}
