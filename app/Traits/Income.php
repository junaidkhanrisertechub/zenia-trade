<?php

namespace App\Traits;

use Illuminate\Http\Response as Response;
use Exception;
use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\User;
use App\Models\LevelIncome;
use App\Models\LevelView;
use App\Models\Activitynotification;
use App\Models\Dashboard;
use App\Models\LevelIco;
use App\Models\LevelincomeIco;
use App\Models\ProjectSettings;
use App\Models\AllTransaction;
use App\Models\CurrentAmountDetails;
use App\Models\DirectIncome;
use App\Models\Leadership;
use App\Models\LeadershipIncome;
use App\Models\LevelRoi;
use App\Models\Packages;
use App\Models\LevelIncomeRoi;
use App\Models\Topup;
use App\Models\Upline;
use App\Models\UplineIncome;
use App\Models\QualifiedUserList;
use App\Models\FranchiseIncome;
use App\Models\Rank;
use App\Models\ChainIncomeSettings;
use App\Models\ChainIncome;
use DB;

trait Income
{

    /**
     * Get level by id
     * 
     * @return \Illuminate\Http\Response
     */
function give_chain_income($GetCount, $userid)
{
    $getchain_data=ChainIncomeSettings::select('directs','income','id')->get();

    foreach($getchain_data as $chain)
    {

        // if($chain->directs==$GetCount)
        if($GetCount>=$chain->directs)
        {
          $chain_income_count=ChainIncome::where('chain_setting_id',$chain->id)
          ->where('user_id',$userid)
          ->count();
              
                  if($chain_income_count == 0)
                  {
                      $data=array('chain_income'=>$chain->income,'user_id'=>$userid,'chain_setting_id'=>$chain->id,'entry_time'=>\Carbon\Carbon::now()); 
            
                      ChainIncome::create($data); 

                      $working_wallet = Dashboard::where([['id', '=', $userid],])->pluck('working_wallet')->first();
                      $chain_wallet = Dashboard::where([['id', '=', $userid],])->pluck('chain_income_wallet')->first();
                      $chain_wallet_withdrwal = Dashboard::where([['id', '=', $userid],])->pluck('chain_income_wallet_wihdrwal')->first();

                      
                      $updateData = array();
                      $updateData['working_wallet'] = round($working_wallet + $chain->income, 7);
                      $updateData['chain_income_wallet'] = round($chain->income + $chain_wallet, 7);
                      $updateData['chain_income_wallet_wihdrwal'] = round($chain->income + $chain_wallet_withdrwal, 7);

                      $updateOtpSta = Dashboard::where('id', $userid)->limit(1)->update($updateData);

                      echo "Chain Income Given to ".$userid;
                  }
        }
    }
}
    function get_upline($re1)
    {

        $getlevel = Upline::where([['level_id', '=', $re1],])->pluck('percentage')->first();

        return $getlevel;
    }


    /*******unilevel *************************/
    function uplineIncome($user_id, $amount, $pacakgeId)
    {
        try {


            $virtual_pid = ($user_id);
            $arrLevelUsers = array();
            $arrLevelUsers[0][] = $virtual_pid;
            $lvCount = 1;
            if ($pacakgeId > '1') {
                do {

                    foreach ($arrLevelUsers[$lvCount - 1] as $user_id) {
                        if ($user_id == '0')
                            break;
                        $q = User::where([['ref_user_id', '=', $user_id],])->get();

                        foreach ($q as $k => $value) {
                            if ($q[$k]["id"] != "0") {

                                $check_if_ref_exist = Topup::where([['id', $q[$k]["id"]], ['type', 6]])->get()->toArray();;

                                $direct = 0;
                                $flag = 0;
                                //check if user of ref id exist in top up 
                                if (count($check_if_ref_exist) > 0) {
                                    $flag = 1;
                                }


                                $dateTime = $this->getCurrentDateTime();
                                $arrLevelUsers[$lvCount][] = $q[$k]["id"];

                                if ($flag == 1) {

                                    $percentage = $this->get_upline($lvCount);
                                    $payamt = ($amount) * ($percentage / 100);
                                    $payamt1 = $payamt;

                                    $leveldata = array();
                                    $leveldata['amount'] = $payamt1;

                                    $leveldata['tax_amount'] = 0;
                                    $leveldata['amt_pin'] = 0;
                                    $leveldata['level'] = $lvCount;

                                    $leveldata['toUserId'] = $q[$k]["id"];
                                    $leveldata['fromUserId'] = $virtual_pid;
                                    $leveldata['status'] = 1;

                                    $leveldata['entry_time'] = $dateTime;
                                    $leveldata['type'] = $pacakgeId;


                                    $insertLevelDta = UplineIncome::create($leveldata);

                                    //-------update refer user usd value
                                    $toUsdValue = Dashboard::where([['id', '=', $q[$k]["id"]],])->pluck('usd')->first();
                                    $upline_income = Dashboard::where([['id', '=', $q[$k]["id"]],])->pluck('upline_income')->first();
                                    $total_profit = Dashboard::where([['id', '=', $q[$k]["id"]],])->pluck('total_profit')->first();
                                    $upline_income_withdraw = Dashboard::where([['id', '=', $q[$k]["id"]],])->pluck('upline_income_withdraw')->first();
                                    $working_wallet = Dashboard::where([['id', '=', $q[$k]["id"]],])->pluck('working_wallet')->first();
                                    $level_income = Dashboard::where([['id', '=', $q[$k]["id"]],])->pluck('level_income')->first();

                                    $updateData = array();
                                    $updateData['usd'] = round($toUsdValue + $payamt1, 7);
                                    $updateData['total_profit'] = round($total_profit + $payamt1, 7);
                                    $updateData['upline_income'] = round($upline_income + $payamt1, 7);
                                    $updateData['upline_income_withdraw'] = round($upline_income_withdraw + $payamt1, 7);
                                    $updateData['working_wallet'] = round($working_wallet + $payamt1, 7);
                                    $updateOtpSta = Dashboard::where('id', $q[$k]["id"])->limit(1)->update($updateData);

                                    $coin_name = ProjectSettings::where(['status' => 1])->pluck('coin_name')->first();
                                    $up_user = User::where([['id', '=', $virtual_pid]])->pluck('user_id')->first();
                                    $balance = AllTransaction::where('id', '=', $q[$k]["id"])->orderBy('srno', 'desc')->pluck('balance')->first();
                                    //----All transaction 
                                    $Trandata = array();      // insert in transaction 
                                    $Trandata['id'] = $q[$k]["id"];
                                    $Trandata['network_type'] = $coin_name;
                                    $Trandata['refference'] = $insertLevelDta->id;
                                    $Trandata['credit'] = round($payamt1, 7);
                                    $Trandata['type'] = 'UPLINE INCOME';
                                    $Trandata['balance'] = $balance + $payamt1;
                                    $Trandata['status'] = 1;
                                    $Trandata['entry_time'] = $dateTime;
                                    $Trandata['remarks'] = 'Upline income amount ' . $payamt1 . '  ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';

                                    $TransactionDta = AllTransaction::create($Trandata);
                                    // activbity notification
                                    $actdata = array();      // insert in transaction 
                                    $actdata['id'] = $q[$k]["id"];
                                    $actdata['message'] = 'Upline income amount ' . $payamt1 . ' ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';
                                    $actdata['status'] = 1;
                                    $actdata['entry_time'] = $dateTime;
                                    $actDta = Activitynotification::create($actdata);
                                }
                            }
                        }

                        $next = @count($arrLevelUsers[$lvCount]);
                    }
                    if ($lvCount == 5 || $next == 0) {
                        break;
                    }
                    $lvCount++;
                } while ($next != 0);
            }
        } catch (Exception $e) {
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    /**
     * Get level by id
     * 
     * @return \Illuminate\Http\Response
     */

    function get_level($re1)
    {

        $getlevel = Level::where([['level_id', '=', $re1],])->pluck('percentage')->first();

        return $getlevel;
    }

    /**
     * Get level income
     * 
     * @return \Illuminate\Http\Response
     */

    function pay_level($user_id, $amount, $pacakgeId)
    {
        try {
            $virtual_pid = ($user_id);
            $arrLevelUsers = array();
            $arrLevelUsers[0][] = $virtual_pid;
            $lvCount = 1;
            do {
                foreach ($arrLevelUsers[$lvCount - 1] as $user_id) {
                    if ($user_id == '0')
                        break;
                    $q = User::where([['id', '=', $user_id],])->get();
                    foreach ($q as $k => $value) {
                        if ($q[$k]["ref_user_id"] != "0") {

                            $check_if_ref_exist = Topup::where('id', $q[$k]["ref_user_id"])->get()->toArray();


                            // $getusers_of_this_ref = User::leftjoin('tbl_topup as tdb', 'tdb.id', '=', 'tbl_users.id')->select([DB::RAW('distinct(tdb.id) as id1')])->where('tbl_users.ref_user_id', $q[$k]["ref_user_id"])->groupBy('tdb.id')->get();



                            $direct = 0;
                            $flag = 0;

                            //check if user of ref id exist in top up 
                            /*if(count($check_if_ref_exist)>0){
                                    $flag=1;
                                }*/


                            $flag = 1;
                            // foreach ($getusers_of_this_ref as $key => $users) {
                            //     if ($users->id1 != '') {
                            //         //$flag=1;
                            //         $direct = $direct + 1;
                            //     }
                            // }
                            $dateTime = $this->getCurrentDateTime();
                            $arrLevelUsers[$lvCount][] = $q[$k]["ref_user_id"];
                            //	$payamt=( $amount )* ($this->get_level($lvCount)/100);


                            // if ($direct >= $lvCount) {



                            if ($flag == 1) {

                                $percentage = $this->get_level($lvCount);
                                $payamt = ($amount) * ($percentage / 100);
                                $payamt1 = $payamt;
                                $tax = 0;
                                $payamt1 -= $tax;
                                $tax2 = 0;
                                $payamt1 -= $tax2;

                                if ($payamt1 > 0) {


                                    $leveldata = array();
                                    $leveldata['amount'] = $payamt1;
                                    $leveldata['tax_amount'] = $tax;
                                    $leveldata['amt_pin'] = $tax2;
                                    $leveldata['level'] = $lvCount;
                                    $leveldata['toUserId'] = $q[$k]["ref_user_id"];
                                    $leveldata['fromUserId'] = $virtual_pid;
                                    $leveldata['status'] = 1;
                                    $leveldata['entry_time'] = $dateTime;
                                    $leveldata['type'] = $pacakgeId;
                                    $insertLevelDta = LevelIncome::create($leveldata);

                                    //-------update refer user usd value
                                    $toUsdValue = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('usd')->first();
                                    $tolevel_income = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('level_income')->first();
                                    $total_profit = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('total_profit')->first();
                                    $level_income_withdraw = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('level_income_withdraw')->first();
                                    $working_wallet = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('working_wallet')->first();
                                    $level_income = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('level_income')->first();

                                    $updateData = array();
                                    $updateData['usd'] = round($toUsdValue + $payamt1, 7);
                                    $updateData['total_profit'] = round($total_profit + $payamt1, 7);
                                    $updateData['level_income'] = round($tolevel_income + $payamt1, 7);
                                    $updateData['level_income_withdraw'] = round($level_income_withdraw + $payamt1, 7);
                                    $updateData['working_wallet'] = round($working_wallet + $payamt1, 7);

                                    $updateOtpSta = Dashboard::where('id', $q[$k]["ref_user_id"])->limit(1)->update($updateData);

                                    $coin_name = ProjectSettings::where(['status' => 1])->pluck('coin_name')->first();
                                    $up_user = User::where([['id', '=', $virtual_pid]])->pluck('user_id')->first();
                                    $balance = AllTransaction::where('id', '=', $q[$k]["ref_user_id"])->orderBy('srno', 'desc')->pluck('balance')->first();
                                    //----All transaction 
                                    $Trandata = array();      // insert in transaction 
                                    $Trandata['id'] = $q[$k]["ref_user_id"];
                                    $Trandata['network_type'] = $coin_name;
                                    $Trandata['refference'] = $insertLevelDta->id;
                                    $Trandata['credit'] = round($payamt1, 7);
                                    $Trandata['type'] = 'LEVEL INCOME';
                                    $Trandata['balance'] = $balance + $payamt1;
                                    $Trandata['status'] = 1;
                                    $Trandata['entry_time'] = $dateTime;
                                    $Trandata['remarks'] = 'Level income amount ' . $payamt1 . '  ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';

                                    $TransactionDta = AllTransaction::create($Trandata);
                                    // activbity notification
                                    $actdata = array();      // insert in transaction 
                                    $actdata['id'] = $q[$k]["ref_user_id"];
                                    $actdata['message'] = 'Level income amount ' . $payamt1 . ' ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';
                                    $actdata['status'] = 1;
                                    $actdata['entry_time'] = $dateTime;
                                    $actDta = Activitynotification::create($actdata);
                                }
                            } else {
                                $percentage = $this->get_level($lvCount);
                                $payamt = ($amount) * ($percentage / 100);
                                $payamt1 = $payamt;
                                $tax = 0;
                                $payamt1 -= $tax;
                                $tax2 = 0;
                                $payamt1 -= $tax2;

                                if ($payamt1 > 0) {
                                    $leveldata = array();
                                    $leveldata['amount'] = 0;
                                    $leveldata['tax_amount'] = $tax;
                                    $leveldata['amt_pin'] = $tax2;
                                    $leveldata['level'] = $lvCount;
                                    $leveldata['toUserId'] = $q[$k]["ref_user_id"];
                                    $leveldata['fromUserId'] = $virtual_pid;
                                    $leveldata['status'] = 1;
                                    $leveldata['entry_time'] = $dateTime;
                                    $leveldata['type'] = $pacakgeId;
                                    $leveldata['remark'] = 'Laps Amount:' . $payamt1;
                                    $insertLevelDta = LevelIncome::create($leveldata);
                                }
                            }
                        }
                    }

                    $next = count($arrLevelUsers[$lvCount]);
                }
                if ($lvCount == 0 || $next == 0) {
                    break;
                }
                $lvCount++;
            } while ($next != 0);
        } catch (Exception $e) {
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }




    /**
     * Get level by id
     * 
     * @return \Illuminate\Http\Response
     */

    function get_level_roi($re1)
    {

        $getlevel = LevelRoi::where([['level_id', '=', $re1],])->pluck('percentage')->first();

        return $getlevel;
    }

    /**
     * Get level income
     * 
     * @return \Illuminate\Http\Response
     */

    function pay_level_roi($user_id, $amount, $pacakgeId, $pin, $nextEntrydate)
    {
        try {

            $virtual_pid = ($user_id);
            $arrLevelUsers = array();
            $arrLevelUsers[0][] = $virtual_pid;
            $lvCount = 1;

            //if($pacakgeId != '1'){
            do {
                foreach ($arrLevelUsers[$lvCount - 1] as $user_id) {
                    if ($user_id == '0')
                        break;

                    $q = User::where([['id', '=', $user_id],])->get();
                    foreach ($q as $k => $value) {

                        /********

                                if($q[$k]["ref_user_id"] != "0"){
                                $check_if_ref_exist = Topup::where('id',$q[$k]["ref_user_id"])->max('type');

                                //dd(max($check_if_ref_exist['type'])); 
                                $direct = 0;
                                $flag = 0;
                                //check if user of ref id exist in top up 
                                if($check_if_ref_exist>0){
                                    $flag=1;
                                }

                         ***********/

                        $arrLevelUsers[$lvCount][] = $q[$k]["ref_user_id"];
                        // if($check_if_ref_exist!=1){

                        /*$packageExist=Packages::select('level_roi')->where([['id','=',$q[$k]["ref_user_id"]]])->limit(1)->first();*/



                        /******** check qualification ********/


                        $flag = 0;


                        $TopupdUserCount = Topup::where([['id', '=', $q[$k]["ref_user_id"]]])->count();


                        if ($TopupdUserCount >= 1) {


                            if ($lvCount == 1) {

                                $qualifiedUserCount = QualifiedUserList::where([['user_id', '=', $q[$k]["ref_user_id"]]])->count();

                                if ($qualifiedUserCount >= 1) {
                                    $flag = 1;
                                }
                            } else if ($lvCount == 2) {

                                $ref_count = User::join('tbl_topup as tt', 'tt.id', '=', 'tbl_users.id')->where('tbl_users.ref_user_id', '=', $q[$k]["ref_user_id"])->distinct('tt.id')->count('tt.id');



                                if ($ref_count >= 4) {

                                    $flag = 1;
                                }
                            } else if ($lvCount == 3) {


                                $ref_count = User::join('tbl_topup as tt', 'tt.id', '=', 'tbl_users.id')->where('tbl_users.ref_user_id', $q[$k]["ref_user_id"])->distinct('tt.id')->count('tt.id');


                                if ($ref_count >= 8) {

                                    $flag = 1;
                                }
                            }
                        }






                        $dateTime = $this->getCurrentDateTime();

                        if ($flag == 1 /*&& $lvCount<=$packageExist['level_roi']*/) {
                            $percentage = $this->get_level_roi($lvCount);
                            $payamt = ($amount * $percentage) / 100;
                            $payamt1 = $payamt;
                            $tax = 0;
                            $payamt1 -= $tax;
                            $tax2 = 0;
                            $payamt1 -= $tax2;


                            $leveldata = array();
                            $leveldata['amount'] = $payamt1;
                            $leveldata['tax_amount'] = $tax;
                            $leveldata['amt_pin'] = $tax2;
                            $leveldata['level'] = $lvCount;
                            $leveldata['toUserId'] = $q[$k]["ref_user_id"];
                            $leveldata['fromUserId'] = $virtual_pid;
                            $leveldata['status'] = 1;
                            $leveldata['entry_time'] = $nextEntrydate;
                            $leveldata['pin'] = $pin;
                            $leveldata['type'] = $pacakgeId;
                            $insertLevelDta = LevelIncomeRoi::create($leveldata);

                            //-------update refer user usd value
                            $toUsdValue = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('usd')->first();
                            $level_income_roi = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('level_income_roi')->first();
                            $total_profit = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('total_profit')->first();
                            $level_income_roi_withdraw = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('level_income_roi_withdraw')->first();
                            $working_wallet = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('working_wallet')->first();

                            $updateData = array();
                            $updateData['usd'] = round($toUsdValue + $payamt1, 7);
                            $updateData['total_profit'] = round($total_profit + $payamt1, 7);
                            $updateData['level_income_roi'] = round($level_income_roi + $payamt1, 7);
                            $updateData['level_income_roi_withdraw'] = round($level_income_roi_withdraw + $payamt1, 7);
                            $updateData['working_wallet'] = round($working_wallet + $payamt1, 7);

                            $updateOtpSta = Dashboard::where('id', $q[$k]["ref_user_id"])->limit(1)->update($updateData);

                            $coin_name = ProjectSettings::where(['status' => 1])->pluck('coin_name')->first();
                            $up_user = User::where([['id', '=', $virtual_pid]])->pluck('user_id')->first();
                            $balance = AllTransaction::where('id', '=', $q[$k]["ref_user_id"])->orderBy('srno', 'desc')->pluck('balance')->first();
                            //----All transaction 
                            $Trandata = array();      // insert in transaction 
                            $Trandata['id'] = $q[$k]["ref_user_id"];
                            $Trandata['network_type'] = $coin_name;
                            $Trandata['refference'] = $insertLevelDta->id;
                            $Trandata['credit'] = round($payamt1, 7);
                            $Trandata['type'] = 'LEVEL INCOME ROI';
                            $Trandata['balance'] = $balance + $payamt1;
                            $Trandata['status'] = 1;
                            $Trandata['entry_time'] = $dateTime;
                            $Trandata['remarks'] = 'Level income roi amount ' . $payamt1 . '  ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';

                            $TransactionDta = AllTransaction::create($Trandata);
                            // activbity notification
                            $actdata = array();      // insert in transaction 
                            $actdata['id'] = $q[$k]["ref_user_id"];
                            $actdata['message'] = 'Level income roi amount ' . $payamt1 . ' ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';
                            $actdata['status'] = 1;
                            $actdata['entry_time'] = $dateTime;
                            $actDta = Activitynotification::create($actdata);
                        } else {

                            $percentage = $this->get_level_roi($lvCount);

                            $payamt = ($amount * $percentage) / 100;
                            $payamt1 = $payamt;
                            $tax = 0;
                            $payamt1 -= $tax;
                            $tax2 = 0;
                            $payamt1 -= $tax2;

                            $leveldata = array();
                            $leveldata['amount'] = 0;
                            $leveldata['tax_amount'] = $tax;
                            $leveldata['amt_pin'] = $tax2;
                            $leveldata['level'] = $lvCount;
                            $leveldata['toUserId'] = $q[$k]["ref_user_id"];
                            $leveldata['fromUserId'] = $virtual_pid;
                            $leveldata['status'] = 1;
                            $leveldata['entry_time'] = $dateTime;
                            $leveldata['type'] = $pacakgeId;
                            $leveldata['remark'] = 'Level income roi Laps Amount:' . $payamt1;


                            $insertLevelDta = LevelIncomeRoi::create($leveldata);
                        }
                        //  }
                        //  }
                    }

                    $next = @count($arrLevelUsers[$lvCount]);
                }
                if ($lvCount == 3 || $next == 0) {
                    break;
                }
                $lvCount++;
            } while ($next != 0);
            // }
        } catch (Exception $e) {
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }



    /**
     * Get leaderahip income
     * 
     * @return \Illuminate\Http\Response
     */


    function pay_leadership($user_id, $amount, $pacakgeId)
    {

        try {
            $virtual_pid = ($user_id);
            $arrLevelUsers = array();
            $arrLevelUsers[0][] = $virtual_pid;
            $lvCount = 1;
            $ativeArray = array();
            $Idarray = array();
            do {
                foreach ($arrLevelUsers[$lvCount - 1] as $user_id) {
                    if ($user_id == '0')
                        break;
                    $q = User::where([['id', '=', $user_id],])->get();
                    foreach ($q as $k => $value) {
                        if ($q[$k]["ref_user_id"] != "0") {
                            $arrLevelUsers[$lvCount][] = $q[$k]["ref_user_id"];
                            // check ref id has done investemnt
                            $activeInve = Dashboard::where('id', '=', $q[$k]["ref_user_id"])->pluck('total_investment')->first();
                            $percentage = 0;

                            if ($activeInve != 0) {
                                if (!in_array($activeInve, $ativeArray)) {


                                    $Leadership1 = Leadership::where([['status', '=', 'Active'], ['amount', '<=', $activeInve]])->orderBy('amount', 'dec')->limit(1)->first();

                                    if (!empty($Leadership1)) {
                                        array_push($Idarray, $q[$k]["ref_user_id"]);
                                        array_push($ativeArray, $activeInve);
                                        // check ref user have amount grtaer than plan amount 2000/5000/10000
                                        $percentage = $Leadership1['percentage'];
                                    }
                                    // percentage not equal to zero
                                } else {
                                    $percentage = 0;
                                }

                                if ($percentage != 0 && !empty($ativeArray)) {
                                    $dateTime = $this->getCurrentDateTime();
                                    $payamt = ($amount) * ($percentage / 100);
                                    $payamt1 = $payamt;
                                    $tax = 0;
                                    $payamt1 -= $tax;
                                    $tax2 = 0;
                                    $payamt1 -= $tax2;

                                    $leveldata = array();
                                    $leveldata['amount'] = $payamt1;
                                    $leveldata['tax_amount'] = $tax;
                                    $leveldata['amt_pin'] = $tax2;
                                    $leveldata['level'] = $lvCount;
                                    $leveldata['toUserId'] = $q[$k]["ref_user_id"];
                                    $leveldata['fromUserId'] = $virtual_pid;
                                    $leveldata['status'] = 1;
                                    $leveldata['entry_time'] = $dateTime;
                                    $leveldata['type'] = $pacakgeId;
                                    $insertLevelDta = LeadershipIncome::create($leveldata);

                                    //-------update refer user usd value
                                    $toUsdValue = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('usd')->first();
                                    $tolevel_income = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('leadership_income')->first();
                                    $total_profit = Dashboard::where([['id', '=', $q[$k]["ref_user_id"]],])->pluck('total_profit')->first();

                                    $updateData = array();
                                    $updateData['usd'] = round($toUsdValue + $payamt1, 7);
                                    $updateData['total_profit'] = round($total_profit + $payamt1, 7);
                                    $updateData['leadership_income'] = round($tolevel_income + $payamt1, 7);
                                    $updateOtpSta = Dashboard::where('id', $q[$k]["ref_user_id"])->limit(1)->update($updateData);

                                    $coin_name = ProjectSettings::where(['status' => 1])->pluck('coin_name')->first();
                                    $up_user = User::where([['id', '=', $virtual_pid]])->pluck('user_id')->first();
                                    $balance = AllTransaction::where('id', '=', $q[$k]["ref_user_id"])->orderBy('srno', 'desc')->pluck('balance')->first();
                                    //----All transaction 
                                    $Trandata = array();      // insert in transaction 
                                    $Trandata['id'] = $q[$k]["ref_user_id"];
                                    $Trandata['network_type'] = $coin_name;
                                    $Trandata['refference'] = $insertLevelDta->id;
                                    $Trandata['credit'] = round($payamt1, 7);
                                    $Trandata['type'] = 'LEADERSHIP INCOME';
                                    $Trandata['balance'] = $balance + $payamt1;
                                    $Trandata['status'] = 1;
                                    $Trandata['entry_time'] = $dateTime;
                                    $Trandata['remarks'] = 'Leadership income amount ' . $payamt1 . '  ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';

                                    $TransactionDta = AllTransaction::create($Trandata);
                                    // activbity notification
                                    $actdata = array();      // insert in transaction 
                                    $actdata['id'] = $q[$k]["ref_user_id"];
                                    $actdata['message'] = 'Leadership income amount ' . $payamt1 . ' ' . $coin_name . ' percentage ' . $percentage . '% from ' . $up_user . ' at level ' . $lvCount . ' amount ' . $amount . '';
                                    $actdata['status'] = 1;
                                    $actdata['entry_time'] = $dateTime;
                                    $actDta = Activitynotification::create($actdata);
                                }
                            }
                        }
                    }

                    $next = @count($arrLevelUsers[$lvCount]);
                }
                if ($lvCount == 500 || $next == 0) {

                    break;
                }
                $lvCount++;
            } while ($next != 0);
            //exit;
        } catch (Exception $e) {

            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }


    /**
     * Get Binary income income
     * 
     * @return \Illuminate\Http\Response
     */
    function pay_binary($user_id, $amount, $topup_status)
    {
        try {


            /*if($topup_status == "1")
            {
                $amountnew = $amount/2;
            }else{
                $amountnew = $amount;
            }*/


            //echo "$amount";die;
            $virtual_parent_id1 = $user_id;
            $from_user_id_for_today_count = $user_id;
            $left_users = array();
            $right_users = array();
            $loopOn1 = true;





            if ($virtual_parent_id1 > 0) {
                do {

                    $posDetails = User::select('id', 'virtual_parent_id', 'position')->where('id', $virtual_parent_id1)->get();

                    //echo "hii";die;
                    if (count($posDetails) <= 0) {

                        $loopOn1 = false;
                    } else {
                        // $binary_income_status = User::where([['id', $virtual_parent_id1]])->pluck('binary_income_status')->first();
                        // if ($binary_income_status = 1) {
                        // } else {
                            foreach ($posDetails as $k => $v) {
                                $virtual_parent_id1 = $posDetails[$k]->virtual_parent_id;
                                if ($virtual_parent_id1 > 0) {
                                    $position = $posDetails[$k]->position;
                                    if ($user_id != $virtual_parent_id1) {
                                        // $userExist = CurrentAmountDetails::where([['user_id', '=', $virtual_parent_id1]])->first();
                                        if ($position == 1) {
                                            /*$updateOtpSta1 = User::where('id', $virtual_parent_id1)->update(
	                                            array('l_bv' => DB::raw('l_bv + ' . $amount . '')));
	                                    if (!empty($userExist)) {
	                                        $updateLeftBv = CurrentAmountDetails::where('user_id', $virtual_parent_id1)->update(
	                                                array('left_bv' => DB::raw('left_bv + ' . $amount . '')));
	                                    }*/

                                            array_push($left_users, $virtual_parent_id1);
                                        }
                                        if ($position == 2) {
                                            /* $updateOtpSta1 = User::where('id', $virtual_parent_id1)->update(
	                                            array('r_bv' => DB::raw('r_bv + ' . $amount . '')));

	                                    if (!empty($userExist)) {
	                                        $updateLeftBv = CurrentAmountDetails::where('user_id', $virtual_parent_id1)->update(
	                                                array('right_bv' => DB::raw('right_bv + ' . $amount . '')));
	                                    }*/
                                            array_push($right_users, $virtual_parent_id1);
                                        }
                                    }
                                } else {
                                    $loopOn1 = false;
                                }
                            }
                        // }
                    }
                } while ($loopOn1 == true);
                //exit;
            }
            $updateLCountArr = array();
            $updateLCountArr['l_bv'] = DB::raw('l_bv + ' . $amount . '');
            $updateLCountArr['curr_l_bv'] = DB::raw('curr_l_bv + ' . $amount . '');



            $updateRCountArr = array();
            $updateRCountArr['r_bv'] = DB::raw('r_bv + ' . $amount . '');
            $updateRCountArr['curr_r_bv'] = DB::raw('curr_r_bv + ' . $amount . '');


            // for curr amount details

            /* $updateLAmountArr = array();
            $updateLAmountArr['left_bv'] = DB::raw('left_bv + ' . $amount . '');
            
         

            $updateRAmountArr = array();
            $updateRAmountArr['right_bv'] = DB::raw('right_bv + ' . $amount . '');
*/

            // Update count
            $count1 = 1;
            $array1 = array_chunk($left_users, 1000);
            // dd('left_users',$left_users,"\nright_users",$right_users,array_merge($left_users,$right_users));

            while ($count1 <= count($array1)) {
                //dd($array1);
                $key1 = $count1 - 1;
                User::whereIn('id', $array1[$key1])->update($updateLCountArr);
                // CurrentAmountDetails::whereIn('user_id', $array1[$key1])->update($updateLAmountArr);
                $count1++;
            }

            $count2 = 1;
            $array2 = array_chunk($right_users, 1000);
            while ($count2 <= count($array2)) {
                $key2 = $count2 - 1;
                User::whereIn('id', $array2[$key2])->update($updateRCountArr);
                // CurrentAmountDetails::whereIn('user_id', $array2[$key2])->update($updateRAmountArr);
                $count2++;
            }

            // checking Influencer target business and update roi status
            // $count3 = 1;
            // $array3 = array_merge($left_users,$right_users);
            // while ($count3 <= count($array3)) {
            //     $key3 = $count3 - 1;
            //     $target_business = Topup::where([['id', $array3[$key3]],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
                
            //     $user_info = User::where([['id', $array3[$key3]]])->select('l_bv','r_bv')->first();
            //     $total_business= $user_info->l_bv + $user_info->r_bv;
            //     if ($total_business >= $target_business) {
            //         Topup::where('id', $array3[$key3])->where('type',7)->update(['roi_stop_status'=>'1']);
            //         User::where('id', $array3[$key3])->update(['roi_withdraw_status'=>'1']);
            //     }
            //     $count3++;
            // }
        } catch (Exception $e) {
            dd($e);
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, $e);
        }
    }



    function pay_binary_level($user_id, $amount, $topup_status)
    {
        try {
            $ref_user_id1 = $user_id;
            $from_user_id_for_today_count = $user_id;
            $left_users = array();
            $right_users = array();
            $loopOn1 = true;
            if ($ref_user_id1 > 0) {
                do {
                    $posDetails = User::select('id', 'ref_user_id', 'position')->where('id', $ref_user_id1)->get();
                    if (count($posDetails) <= 0) {
                        $loopOn1 = false;
                    } else {
                          foreach ($posDetails as $k => $v) {
                                $ref_user_id1 = $posDetails[$k]->ref_user_id;
                                if ($ref_user_id1 > 0) {
                                    $position = $posDetails[$k]->position;
                                    if ($user_id != $ref_user_id1) {
                                        if ($position == 1) {
                                         array_push($left_users, $ref_user_id1);
                                        }
                                        if ($position == 2) {
                                           array_push($right_users, $ref_user_id1);
                                        }
                                    }
                                } else {
                                    $loopOn1 = false;
                                }
                            }
                    }
                } while ($loopOn1 == true);
            }
            $updateLCountArr = array();
            $updateLCountArr['l_guardian'] = DB::raw('l_guardian + ' . $amount . '');
            $updateRCountArr = array();
            $updateRCountArr['r_guardian'] = DB::raw('r_guardian + ' . $amount . '');
            // Update count
            $count1 = 1;
            $array1 = array_chunk($left_users, 1000);
             while ($count1 <= count($array1)) {
                //dd($array1);
                $key1 = $count1 - 1;
                User::whereIn('id', $array1[$key1])->update($updateLCountArr);
                $count1++;
            }

            $count2 = 1;
            $array2 = array_chunk($right_users, 1000);
            while ($count2 <= count($array2)) {
                $key2 = $count2 - 1;
                User::whereIn('id', $array2[$key2])->update($updateRCountArr);
                $count2++;
            }
             // checking Influencer target business and update roi status
            $count3 = 1;
            $array3 = array_merge($left_users,$right_users);
             while ($count3 <= count($array3)) {
                $key3 = $count3 - 1;
                $target_business = Topup::where([['id', $array3[$key3]],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
                $user_info = User::where([['id', $array3[$key3]]])->select('l_guardian','r_guardian')->first();
                $total_business= $user_info->l_guardian + $user_info->r_guardian;
                if ($total_business >= $target_business) {
                    Topup::where('id', $array3[$key3])->where('type',7)->update(['roi_stop_status'=>'1']);
                    User::where('id', $array3[$key3])->update(['roi_withdraw_status'=>'1']);
                }
                 $count3++;
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, $e);
        }
    }


    /**
     * Get Direct income income
     * 
     * @return \Illuminate\Http\Response
     */


    public function pay_direct($user_id, $amount, $direct_income, $invoice_id)
    {

        try {
            $ref_user_id = User::where([['id', '=', $user_id]])->pluck('ref_user_id')->first();
            $user_name = User::where([['id', '=', $user_id]])->pluck('user_id')->first();
            $dateTime = $this->getCurrentDateTime();


            $rank = User::where([['id', '=', $ref_user_id]])->pluck('rank')->first();
            $percentagedata = Rank::where([['rank', '=', $rank]])->pluck('income_percentage')->first();
            $percentage = $percentagedata;

            $payable_direct_income = (($amount * $percentage) / 100);
            //----------dirct income------------


            $check_if_ref_exist = Topup::where('id', $ref_user_id)->count();

                if ($check_if_ref_exist > 0 && $rank != null) {

                    $Directata = array();      // insert in transaction 
                    $Directata['amount'] = $payable_direct_income;
                    $Directata['toUserId'] = $ref_user_id;
                    $Directata['fromUserId'] = $user_id;
                    $Directata['entry_time'] = $dateTime;
                    $Directata['status'] = 'Paid';
                    $Directata['invoice_id'] = $invoice_id;
                    $Directata['remark'] = 'Direct income';
                    $TransactionDta = DirectIncome::create($Directata);

                    //---update dashboard value
                    $toUsdValue = Dashboard::where([['id', '=', $ref_user_id],])->pluck('usd')->first();
                    $direct_income = Dashboard::where([['id', '=', $ref_user_id],])->pluck('direct_income')->first();
                    $total_profit = Dashboard::where([['id', '=', $ref_user_id],])->pluck('total_profit')->first();
                    $direct_income_withdraw = Dashboard::where([['id', '=', $ref_user_id],])->pluck('direct_income_withdraw')->first();
                    $working_wallet = Dashboard::where([['id', '=', $ref_user_id],])->pluck('working_wallet')->first();

                    $updateData = array();
                    $updateData['usd'] = round($toUsdValue + $payable_direct_income, 7);
                    $updateData['total_profit'] = round($total_profit + $payable_direct_income, 7);
                    $updateData['direct_income'] = round($direct_income + $payable_direct_income, 7);
                    $updateData['direct_income_withdraw'] = round($direct_income_withdraw + $payable_direct_income, 7);
                    $updateData['working_wallet'] = round($working_wallet + $payable_direct_income, 7);
                    $updateOtpSta = Dashboard::where('id', $ref_user_id)->limit(1)->update($updateData);
                }
            /* else
            {


                 $Directata = array();      // insert in transaction 
            $Directata['amount'] = 0;
            $Directata['laps_amount'] = $payable_direct_income;
            $Directata['toUserId'] = $ref_user_id;
            $Directata['fromUserId'] = $user_id;
            $Directata['entry_time'] = $dateTime;
            $Directata['status'] = 'Paid';
            $Directata['invoice_id'] = $invoice_id;
            $Directata['remark'] = 'Direct income';
            $TransactionDta = DirectIncome::create($Directata);

            


            }*/







            /* $balance = AllTransaction::where('id', '=',$ref_user_id)->orderBy('srno','desc')->pluck('balance')->first();

*/

            //-------all transaction entry
            /*$Trandata = array();      // insert in transaction 
	        $Trandata['id'] = $ref_user_id;
	        $Trandata['network_type'] = 'USD';
	        $Trandata['refference'] = $TransactionDta->id;
	        $Trandata['credit'] = $percentage;
	        $Trandata['type'] = 'DIRECT INCOME';
	        $Trandata['balance'] = $balance+$percentage;
	        $Trandata['status'] = 1;
            $Trandata['entry_time'] = $dateTime;
	        $Trandata['remarks'] = 'Direct income amount ' . $payable_direct_income . ' USD percentage ' . $percentage . '% from ' . $user_name . '  amount ' . $amount . '';

	        $TransactionDta = AllTransaction::create($Trandata);*/
            //-----Activity notificatin
            /*  $actdata = array();      // insert in transaction 
	        $actdata['id'] = $ref_user_id;
	        $actdata['message'] = 'Direct income amount ' . $payable_direct_income . ' USD percentage ' . $percentage . '% from ' . $user_name . '  amount ' . $amount . '';
	        $actdata['status'] = 1;
            $actdata['entry_time'] = $dateTime;
	        $actDta = Activitynotification::create($actdata);
*/
        } catch (Exception $e) {

            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function pay_franchise($from_user_id, $to_user_id, $percentage, $amount, $topup_id, $invoice_id)
    {

        try {
            $from_user = User::where([['id', '=', $from_user_id]])->first();
            $to_user = User::where([['id', '=', $to_user_id]])->first();
            $dateTime = $this->getCurrentDateTime();
            $payable_franchise_income = (($amount * $percentage) / 100);

            //----------franchise income------------
            $franchiseData = array();      // insert in transaction 
            $franchiseData['amount'] = $payable_franchise_income;
            $franchiseData['to_user_id'] = $to_user_id;
            $franchiseData['from_user_id'] = $from_user_id;
            $franchiseData['percentage'] = $percentage;
            $franchiseData['on_amount'] = $amount;
            $franchiseData['topup_id'] = $topup_id;
            $franchiseData['pin'] = $invoice_id;
            $franchiseData['entry_time'] = $dateTime;
            $TransactionDta = FranchiseIncome::create($franchiseData);

            //---update dashboard value
            $toUsdValue = Dashboard::where([['id', '=', $to_user_id],])->pluck('usd')->first();
            $franchise_income = Dashboard::where([['id', '=', $to_user_id],])->pluck('franchise_income')->first();
            $total_profit = Dashboard::where([['id', '=', $to_user_id],])->pluck('total_profit')->first();
            $franchise_income_withdraw = Dashboard::where([['id', '=', $to_user_id],])->pluck('franchise_income_withdraw')->first();
            $working_wallet = Dashboard::where([['id', '=', $to_user_id],])->pluck('working_wallet')->first();

            $updateData = array();
            $updateData['usd'] = round($toUsdValue + $payable_franchise_income, 7);
            $updateData['total_profit'] = round($total_profit + $payable_franchise_income, 7);
            $updateData['franchise_income'] = round($franchise_income + $payable_franchise_income, 7);
            $updateData['franchise_income_withdraw'] = round($franchise_income_withdraw + $payable_franchise_income, 7);
            $updateData['working_wallet'] = round($working_wallet + $payable_franchise_income, 7);
            $updateOtpSta = Dashboard::where('id', $to_user_id)->limit(1)->update($updateData);
        } catch (Exception $e) {
            //dd($e);
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    function getCurrentDateTime()
    {

        $date = \Carbon\Carbon::now();
        return $date->toDateTimeString();
    }
}
