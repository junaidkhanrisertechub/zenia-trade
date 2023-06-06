<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\ProductController;
use Config;
use App\Models\Topup;
use App\Models\CapitalReturnsIncome;
use App\Models\Product;
use App\Dashboard;
use DB;

class CapitalReturnsIncomeCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:capital_returns_income';    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maturity Profit Cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ProductController $autosiptopup) {
        parent::__construct();

        $this->autosiptopup = $autosiptopup;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    
    public function handle()
    {   
        try {
            
          $getUsers =  Topup::join('tbl_product as tp','tp.id','=','tbl_topup.type')
                        ->select('tbl_topup.amount','tbl_topup.id as userId','tbl_topup.srno','tp.capital_returns_percentage','tbl_topup.type','tbl_topup.entry_time')
                        ->where('tbl_topup.duration','=',DB::raw('tbl_topup.total_roi_count'))
                        ->where('tbl_topup.roi_status','Inactive')
                        ->where('tbl_topup.capital_returns_status',0)
                        ->get();
       
           if(!empty($getUsers)){

                $insert_capital_returns_income = array();
                $update_dash_arr = array();
                $update_capital_returns_status = array();

                foreach($getUsers as $value)
                {
                    $userId = $value->userId;
                    $Id = $value->srno;
                    $topupAmt = $value->amount;
                    $capitalReturnsPer = $value->capital_returns_percentage;
                    $product_id = $value->type;
                    $MainTpEntryTime = $value->entry_time;
                
                    $CapitalReturnsTotalAmt = ($topupAmt * $capitalReturnsPer)/100;

                    $capitalReturnsArray = array();
                    $capitalReturnsArray['user_id'] = $userId;
                    $capitalReturnsArray['amount'] = $CapitalReturnsTotalAmt;
                    $capitalReturnsArray['topup_amount'] = $topupAmt;
                    $capitalReturnsArray['capital_returns_per'] = $capitalReturnsPer;
                    $capitalReturnsArray['product_id'] = $product_id;
                    $capitalReturnsArray['topup_entry_time'] = $MainTpEntryTime;

                    array_push($insert_capital_returns_income, $capitalReturnsArray);

                    $CapitalReturnsUpdate = array();
                    $CapitalReturnsUpdate['id'] = $userId;
                    $CapitalReturnsUpdate['capital_returns_income'] = $CapitalReturnsTotalAmt;
                    // $CapitalReturnsUpdate['roi_income'] = $CapitalReturnsTotalAmt;
                    $CapitalReturnsUpdate['roi_wallet'] = $CapitalReturnsTotalAmt;

                    array_push($update_dash_arr, $CapitalReturnsUpdate);

                    array_push($update_capital_returns_status, $userId);
                   
                }

                $count = 1;
                $array = array_chunk($insert_capital_returns_income,1000);
               // dd($array);
                while($count <= count($array))
                {
                  $key = $count-1;
                  CapitalReturnsIncome::insert($array[$key]);
                  echo $count." insert count array ".count($array[$key])."\n";
                  $count ++;
                }

                /*Update Dashboard array*/
                $count1 = 1;
                $array1 = array_chunk($update_dash_arr,1000);
                while($count1 <= count($array1))
                {
                    $key1 = $count1-1;
                    $arrProcess = $array1[$key1];
                    $mainArr = array();
                    foreach ($arrProcess as $k => $v) {
                        $mainArr[$v['id']]['id'] = $v['id'];
                
                        if (!isset($mainArr[$v['id']]['capital_returns_income']) && !isset($mainArr[$v['id']]['roi_wallet'])) 
                        {

                            // $mainArr[$v['id']]['capital_returns_income']=$mainArr[$v['id']]['roi_income']=$mainArr[$v['id']]['roi_wallet']=0;
                            $mainArr[$v['id']]['capital_returns_income']=$mainArr[$v['id']]['roi_wallet']=0;
                            
                        }
                        $mainArr[$v['id']]['capital_returns_income'] += $v['capital_returns_income']; 
                        // $mainArr[$v['id']]['roi_income'] += $v['roi_income']; 
                        $mainArr[$v['id']]['roi_wallet'] += $v['roi_wallet']; 
                        
                    }

                    $ids = implode(',', array_column($mainArr, 'id'));
                    $capital_returns_income_qry = 'capital_returns_income = (CASE id';
                    // $roi_income_qry = 'roi_income = (CASE id';
                    $roi_wallet_qry = 'roi_wallet = (CASE id';
                    
                    foreach ($mainArr as $key => $val) {
                        $capital_returns_income_qry = $capital_returns_income_qry . " WHEN ".$val['id']." THEN capital_returns_income + ".$val['capital_returns_income'];             
                        // $roi_income_qry = $roi_income_qry . " WHEN ".$val['id']." THEN roi_income + ".$val['roi_income'];
                        $roi_wallet_qry = $roi_wallet_qry . " WHEN ".$val['id']." THEN roi_wallet + ".$val['roi_wallet'];
                        
                    }

                    $capital_returns_income_qry = $capital_returns_income_qry . " END)";         
                    // $roi_income_qry = $roi_income_qry . " END)";
                    $roi_wallet_qry = $roi_wallet_qry . " END)";
                    
                    // $updt_qry = "UPDATE tbl_dashboard SET ".$capital_returns_income_qry." , ".$roi_income_qry." , ".$roi_wallet_qry." WHERE id IN (".$ids.")";
                    $updt_qry = "UPDATE tbl_dashboard SET ".$capital_returns_income_qry." , ".$roi_wallet_qry." WHERE id IN (".$ids.")";
                    $updt_user = DB::statement(DB::raw($updt_qry));

                    echo $count1." update from user dash array ".count($mainArr)."\n";
                    $count1 ++;
                }

                $count3 = 1;     
                $array3 = array_chunk($update_capital_returns_status,1000);
                while($count3 <= count($array3))
                {
                  $key3 = $count3-1;
                  Topup::whereIn('id',$array3[$key3])->update(['capital_returns_status'=>1]);
                  echo $count3." update capital returns status array ".count($array3[$key3])."\n";
                  $count3 ++;
                }

                
            }             
        } catch (Exception $e) {
            dd($e);
        }
    }
}
