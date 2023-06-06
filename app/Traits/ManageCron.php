<?php

namespace App\Traits;

use Illuminate\Http\Response as Response;
use Exception;
use App\User;

use App\Http\Controllers\userapi\GenerateRoiController;
use App\Models\DailyBonus;
use App\Models\Topup;
use App\Models\CronStatus;
use App\Models\CronRunStatus;
use Illuminate\Http\Request;
use App\Models\Level;
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
use App\Models\Upline;
use App\Models\UplineIncome;
use App\Models\QualifiedUserList;
use App\Models\FranchiseIncome;
use App\Models\Rank;
use DB;



trait ManageCron
{
  public function __construct(GenerateRoiController $generateRoi) {
    parent::__construct();
    $this->generateRoi = $generateRoi;
  }
     function RunRoi(){
        $signature = "cron:optimized_roi_dynamic";
        $croncheck= CronStatus::select('*')->where('name',$signature)->first();

        if($croncheck->status ==1)
        {

      //  echo "this cron is active\n";
  
  
        
  
        $Runcheck= CronStatus::select('running_status')->where('name',$signature)->first();
        if($Runcheck->running_status == 1)
        {
                // echo "Now the cron is running\n";
        
        }
        CronStatus::where('name',$signature)->update(array('running_status' => '1'));
  
        DB::raw('LOCK TABLES `tbl_dashboard` WRITE');
  
        DB::raw('LOCK TABLES `tbl_dailybonus` WRITE');
  
        $day = \Carbon\Carbon::now()->format('D');
          //dd($day);
        if($day == 'Sun' || $day == 'Sat'){
              // dd('In');
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'ROI is not allowed on this day', ''); 
        }
  
        /*$user = User::join('tbl_topup', 'tbl_topup.id', '=', 'tbl_users.id')
        ->select('tbl_topup.amount','tbl_users.rank','tbl_users.id','tbl_users.mobile','tbl_users.country','tbl_users.user_id','tbl_users.email','tbl_topup.pin','tbl_topup.type','tbl_topup.entry_time','tbl_topup.withdraw','tbl_topup.old_status')->where([['tbl_users.status', '=', 'Active'],['tbl_users.type', '=', ''],['tbl_topup.roi_status', '=', 'Active']])
        ->get();*/
  
        $user = Topup::select('tbl_topup.id','tbl_topup.pin','tbl_topup.type','tbl_topup.amount','tbl_topup.entry_time')
          ->where('tbl_topup.roi_status','Active')
          ->join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')
          ->where('tu.status', 'Active')->where('tu.type', '')
          ->get();
  
        $today = \Carbon\Carbon::now();
        $today_datetime = $today->toDateTimeString();
        $today_datetime2 = $today->toDateString();
  
  
        //echo $msg = 'ROI CRON started at' . $today_datetime . "\n" ;
  
  
        $dashArr=array();
        /*$traArr=array();
        $actArr=array();*/
        $roiArr= array();
        /*$finalArr= array();*/
        $arr_id = array();
        if (!empty($user)) {
          foreach ($user as $key => $val) {
            $invest_amount = $val->amount;
            $id = $val->id;
            $pin = $val->pin;
            $type = $val->type;
            $entry_time = $val->entry_time;
           /* $email = $user[$key]->email;
            $user_id = $user[$key]->user_id;
            $country = $user[$key]->country;
            $mobile = $user[$key]->mobile;
            $withdraw = $user[$key]->withdraw;
            $old_status = $user[$key]->old_status;
            $rank = $user[$key]->rank;*/
  
            $checkUpdate = $this->generateRoi->generateroidynamic($invest_amount, $id, $pin, $type, $entry_time);
  
            if($checkUpdate !=404 ){
             $roiArr[]=$checkUpdate['dailydata'];
             /*$dashArr[]=$checkUpdate['updateCoinData'];*/
             $arr_id[]=$id;
             /*$traArr[]=$checkUpdate['trandata'];*/
             /*$actArr[]=$checkUpdate['actdata'];*/ 
            }
        }
  
        $count = 1;
        $array = array_chunk($roiArr,1000);
        /* dd($array);*/
        while($count <= count($array))
        {
  
          $key = $count-1;
          DailyBonus::insert($array[$key]);
        //  echo $count." count array ".count($array[$key])."\n";
          $count ++;
        }
        //----------------------------------
        /* $count1 = 1;
        $array_traArr = array_chunk($traArr,1000);
        while($count1 <= count($array_traArr))
        {
          $key = $count1-1;
         AllTransaction::insert($array_traArr[$key]);
          echo $count1." count array ".count($array_traArr[$key])."\n";
          $count1 ++;
        }
        //----------------------------------
         $count2 = 1;
        $array_actArr = array_chunk($actArr,1000);
        while($count2 <= count($array_actArr))
        {
          $key = $count2-1;
          Activitynotification::insert($array_actArr[$key]);
          echo $count2." count array ".count($array_actArr[$key])."\n";
          $count2 ++;
        }*/
  
          //-----------------
          //dd($dashArr);
          /*foreach ($dashArr as $value) {
              # code...
            Dashboard::where('id', $value['id'])->limit(1)->update($value);
          }*/
          $Total_ROI = 0.05;
          
          $updateCoinData = array();
          $updateCoinData['usd'] = DB::raw('usd +'.$Total_ROI);
          $updateCoinData['total_profit'] = DB::raw('total_profit +'. $Total_ROI); 
          $updateCoinData['roi_income'] = DB::raw('roi_income + '. $Total_ROI);
          $updateCoinData['roi_income_withdraw'] = DB::raw('roi_income_withdraw + '. $Total_ROI);
          $updateCoinData['working_wallet'] = DB::raw('working_wallet + '.$Total_ROI);   
          
          $count2 = 1;     
          $array2 = array_chunk($arr_id,1000);
          while($count2 <= count($array2))
          {
            $key2 = $count2-1;
            Dashboard::whereIn('id',$array2[$key2])->update($updateCoinData);
          //  echo $count2." update count array ".count($array2[$key2])."\n";
            $count2 ++;
          }
  
         // $this->info('ROI generated successfully');
          $today = \Carbon\Carbon::now();
          $today_datetime = $today->toDateTimeString();
          $today_datetime2 = $today->toDateString();
        
         // dd($today);
          /*$payoutHistory =DailyBonus::select('entry_time')->whereDate('entry_time', '=', $today_datetime2)->count();*/
  
        //  echo $msg = 'ROI CRON end at' . $today_datetime . "\n" ;
  
          DB::raw('UNLOCK TABLES');
  
       } else {
  
        //$this->info('User is not exist');
        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not exist', ''); 
      }
  
      CronStatus::where('name',$signature)->update(array('running_status' => '0'));
      
      $Runcheck= CronStatus::select('running_status')->where('name',$signature)->first();
      if($Runcheck->running_status == 0)
      {
            //  echo "Now the cron is Idle \n";
      }
      $cronRunEntry=new CronRunStatus();
      $cronRunEntry['cron_id'] =$croncheck->id;
      $cronRunEntry['run_status'] =1;
      $cronRunEntry['run_time'] =$today_datetime;
      $cronRunEntry->save();

      $arrStatus   = Response::HTTP_OK;
      $arrCode     = Response::$statusTexts[$arrStatus];
      $arrMessage  = 'Cron Run Successfully';
      return sendResponse($arrStatus, $arrCode, $arrMessage,'');
   }
        else{
         // echo "this cron is inactive \n";
         return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'This Cron is Inactive', ''); 
        }
  
        // if($croncheck->running_status ==0)
        // {
        // echo "this cron is Running";
        // }
        // else{
        //   echo "this cron is Idle";
      //  }
  
  
  
  
    }

    function getCurrentDateTime()
    {

        $date = \Carbon\Carbon::now();
        return $date->toDateTimeString();
    }
}