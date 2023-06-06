<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;
use App\Models\Topup;

use App\Models\AddChainBusinessBonus;
use App\Models\AddChainIncome;
use App\Models\Dashboard;
use App\User;


class ChainBusinessBonus extends Command {
	/**
	 * The name and signature of the console command.  Hacking
	 *
	 * @var string
	 */
	protected $signature = 'cron:chain_business_bonus_cron';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Give bonus for chain business';

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

        $users =  User::select("id","l_bv","r_bv")
        ->where([['l_bv','>=',2000],['r_bv','>=',2000],['status','=',"Active"],['type','=',""],['topup_status','=',"1"]])
        //->limit(1)
        ->get();

      if(!empty($users)){

        	$insert_chaindata = array();
	        $dashArr  = array();
	       
        
         
        foreach ($users as $k => $v) {
            //echo $v->id.',';
            $bonus = AddChainBusinessBonus::select('l_bv','r_bv','id','leo_wallet')->get();
            foreach ($bonus as  $key => $bvalue){

            if($v['l_bv'] >= $bvalue['l_bv']  && $v['r_bv'] >= $bvalue['r_bv']){

            	//dd($v->id.',');
                           
                $get_busniess=AddChainIncome::where('chain_business_id',$bvalue['id'])->where('user_id',$v->id)->count(); 

                    if($get_busniess==0)
                    {
                        //$arrdata=new AddChainIncome();
                        $arrdata = array();
                        $today = \Carbon\Carbon::now(); 
                        $datetime_to_current = $today;
                        $arrdata['user_id']=$v['id'];
                        $arrdata['chain_business_id']=$bvalue['id'];
                        $arrdata['l_bv']=$v['l_bv'];
                        $arrdata['r_bv']=$v['r_bv'];
                        $arrdata['income']=$bvalue['leo_wallet'];
                        $arrdata['entry_time']=$datetime_to_current;

                        array_push($insert_chaindata,$arrdata);

                        //$arrdata->save();

						// $working_wallet = Dashboard::where('id', '=', $v->id)->pluck('working_wallet')->first();
						// $chain_bonus_wallet = Dashboard::where('id', '=', $v->id)->pluck('chain_bonus_wallet')->first();
						// $chain_bonus_wallet_withdrwal = Dashboard::where([['id', '=', $v->id],])->pluck('chain_bonus_wallet_withdrwal')->first();

						
						// $updateData = array();
						// $updateData['working_wallet'] = round($working_wallet + $bvalue['leo_wallet'], 7);
						// $updateData['chain_bonus_wallet'] = round($bvalue['leo_wallet'] + $chain_bonus_wallet, 7);
						// $updateData['chain_bonus_wallet_withdrwal'] = round($bvalue['leo_wallet'] + $chain_bonus_wallet_withdrwal, 7);

						$updateCoinData = array();
		                $updateCoinData['chain_bonus_wallet'] = $bvalue['leo_wallet'];
		                $updateCoinData['working_wallet'] =$bvalue['leo_wallet'];
		                $updateCoinData['chain_bonus_wallet_withdrwal'] =$bvalue['leo_wallet'];
		                $updateCoinData['id'] =$v->id;

		                array_push($dashArr,$updateCoinData);

						// $updateOtpSta = Dashboard::where('id', $v->id)->update($updateData);
                    }
              }
            }
        }


            $count = 1;
            $array = array_chunk($insert_chaindata,1000);
           // dd($array);
            while($count <= count($array))
            {
              $key = $count-1;
              AddChainIncome::insert($array[$key]);
              echo $count." insert club business income array ".count($array[$key])."\n";
              $count ++;
            }


            $dashCount = 1;     

              $dasharray = array_chunk($dashArr,1000);

              while($dashCount <= count($dasharray))
              {     

                    $dashk = $dashCount-1;
                    $arrProcess = $dasharray[$dashk];
                    $mainArr = array();
                    foreach ($arrProcess as $k => $v) {

                      $mainArr[$v['id']]['id'] = $v['id'];
                     
                      if (!isset($mainArr[$v['id']]['working_wallet']) && !isset($mainArr[$v['id']]['chain_bonus_wallet']) 
                      && !isset($mainArr[$v['id']]['chain_bonus_wallet_withdrwal'])
                       ) {

                        $mainArr[$v['id']]['chain_bonus_wallet']=$mainArr[$v['id']]['working_wallet']=$mainArr[$v['id']]['chain_bonus_wallet_withdrwal']=0;
                        
                      }
                      $mainArr[$v['id']]['working_wallet'] += $v['working_wallet']; 
                      $mainArr[$v['id']]['chain_bonus_wallet'] += $v['chain_bonus_wallet']; 
                      
                      $mainArr[$v['id']]['chain_bonus_wallet_withdrwal'] += $v['chain_bonus_wallet_withdrwal']; 

                      
                  }
                 
                $ids = implode(',', array_column($mainArr, 'id'));

                $total_profit_qry = 'working_wallet = (CASE id';
                $chain_bonus_wallet_qry = 'chain_bonus_wallet = (CASE id';
                $chain_bonus_wallet_withdrwal_qry = 'chain_bonus_wallet_withdrwal = (CASE id';

                foreach ($mainArr as $key => $val) {
                  $total_profit_qry = $total_profit_qry . " WHEN ".$val['id']." THEN working_wallet + ".$val['working_wallet'];             
                 
                  $chain_bonus_wallet_qry = $chain_bonus_wallet_qry . " WHEN ".$val['id']." THEN chain_bonus_wallet + ".$val['chain_bonus_wallet'];

                  $chain_bonus_wallet_withdrwal_qry = $chain_bonus_wallet_withdrwal_qry . " WHEN ".$val['id']." THEN chain_bonus_wallet_withdrwal + ".$val['chain_bonus_wallet_withdrwal'];
                 
                }

                $total_profit_qry = $total_profit_qry . " END)";         
                
                $chain_bonus_wallet_qry = $chain_bonus_wallet_qry . " END)";
                
                $chain_bonus_wallet_withdrwal_qry = $chain_bonus_wallet_withdrwal_qry . " END)";

                $updt_qry = "UPDATE tbl_dashboard SET  ".$total_profit_qry." , ".$chain_bonus_wallet_qry." , ".$chain_bonus_wallet_withdrwal_qry."  WHERE id IN (".$ids.")";
                
                $updt_user = DB::statement(DB::raw($updt_qry));

                echo $dashCount." update from user dash array ".count($mainArr)."\n";
                $dashCount ++;
            }

      }
	}
}