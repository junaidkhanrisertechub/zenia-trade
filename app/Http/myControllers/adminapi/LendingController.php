<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\RoiPercentage;
use App\Models\Dashboard;
use App\Models\Topup;
use App\Models\DailyBouns;
use App\Models\DailyBinaryIncome;
use App\Models\HsccBonus;
use DB;
use Config;
use Validator;

class LendingController extends Controller
{
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode,$settings,$commonController;

   	/**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CommonController $commonController) {
        $this->settings = Config::get('constants.settings');
        $this->statuscode = Config::get('constants.statuscode');
        $this->commonController = $commonController;
    }
    /**
     * get records of roi percentage
     *
     * @return void
     */
    public function getRoiPercentage(Request $request) {
        $arrInput = $request->all();
        
    	$query = RoiPercentage::query();
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])){
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_roi_percentage');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $totalRecord    = $query->count('srno');
        $query          = $query->orderBy('srno','desc');
        // $totalRecord    = $query->count();
        $arrPercentage  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrPercentage;

    	if($arrData['recordsTotal'] > 0) {
    	   return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
    	} else {
    	   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record Not Found', '');
    	}
    }

	/**
     * store percentage
     *
     * @return void
     */
    public function storeRoiPercentage(Request $request) {

    	$lastDate = RoiPercentage::orderBy('date','desc')->pluck('date')->first();
    	if(!empty($lastDate)){
    		$nextDay = date('Y-m-d', strtotime($lastDate.'+1 days'));
    	} else {
    		$nextDay = date('Y-m-d');
    	}
    	$arrInsert = [
    		'date' => $nextDay,
            'entry_time' => now(),
    		/*'percentage' => 0,*/
    	];
    	$storeId = RoiPercentage::insertGetId($arrInsert);

    	if($storeId){
    	   return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record added successfully', '');
    	} else {
    	   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Error occured while adding record', '');
    	}
    }

    /**
     * update percentage
     *
     * @return void
     */
    public function updateRoiPercentage(Request $request) {
    	$arrInput = $request->all();
    	// validate the info, create rules for the inputs
        $rules = array('srno' => 'required','percentage' => 'required');
        // run the validation rules on the inputs from the form
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
        } else {
        	$updatedBy = $this->commonController->getLoggedUserData(['remember_token'=>$arrInput['created_by']])->id;
	    	$arrUpdate = [
	    		'percentage' => $arrInput['percentage'],
	    		'created_by' => $updatedBy
	    	];
	    	$update = RoiPercentage::where('srno',$arrInput['srno'])->update($arrUpdate);
	    	if(!empty($update)){
	    	   return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record updated successfully', '');
	    	} else {
	    	   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record already updated with same input', '');
	    	}
	    }
    }

    /**
     * get investment dashboard record
     *
     * @return void
     */
    public function getInvestment(Request $request) {
        $arrInput = $request->all();

        $query = Dashboard::join('tbl_users as tu','tu.id','=','tbl_dashboard.id')
                ->select('tbl_dashboard.*','tu.user_id','tu.fullname');

        if(isset($arrInput['id'])){
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_dashboard.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_dashboard');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_dashboard.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                      ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_dashboard.srno');
        $query          = $query->orderBy('tbl_dashboard.srno','desc');
        // $totalRecord    = $query->count();
        $arrInvestment  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrInvestment;


        if($arrData['recordsTotal'] > 0) {
           return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
           return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record Not Found', '');
        }
    }

    /**
     * get investment dashboard record from dashboard
     *
     * @return void
     */
    public function getInvestmentDetails(Request $request) {
        $arrInput = $request->all();

        $query = Topup::join('tbl_users as tu','tu.id','=','tbl_topup.id')->select('tbl_topup.*','tu.user_id','tu.fullname');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }        
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_topup');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_topup.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $totalRecord     = $query->count('tbl_topup.srno');
        $query           = $query->orderBy('tbl_topup.srno','desc');
        // $totalRecord     = $query->count();
        $arrInvestmentD  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrInvestmentD;

        if($arrData['recordsTotal'] > 0) {
           return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
           return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record Not Found', '');
        }
    }

    /**
     * get daily bouns
     *
     * @return void
     */
    public function getDailyBonus(Request $request) {
        $arrInput = $request->all();

        $query = DailyBouns::join('tbl_users as tu','tu.id','=','tbl_dailybonus.id')
                /*->join('tbl_topup as tt', 'tt.pin', '=', 'tbl_dailybonus.pin')*/
                ->select('tbl_dailybonus.amount','tbl_dailybonus.daily_amount','tbl_dailybonus.daily_percentage','tbl_dailybonus.status','tbl_dailybonus.pin','tbl_dailybonus.entry_time','tu.user_id','tu.fullname','tbl_dailybonus.on_amount');
         //dd($query);       

        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_dailybonus.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }

        /*if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            
            $fields = getTableColumns('tbl_dailybonus');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_dailybonus.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }*/

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id','tbl_dailybonus.pin','tbl_dailybonus.amount','tbl_dailybonus.on_amount' ,'tbl_dailybonus.status' , 'tbl_dailybonus.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res,"AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
		}

        // $totalRecord    = $query->count('tbl_dailybonus.id');
       // $totalRecord    = $query->count('tbl_dailybonus.id');
        $query          = $query->orderBy('tbl_dailybonus.sr_no','desc');
        $totalRecord    = $query->count('tbl_dailybonus.id');

        $arrDailyBouns  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
        // dd($arrDailyBouns);
        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrDailyBouns;

        if($arrData['recordsTotal'] > 0) {
           return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
           return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record Not Found', '');
        }
    }
    public function getHsccBonus(Request $request) {
        $arrInput = $request->all();
        // dd($arrInput);
        $query = HsccBonus::join('tbl_users as tu','tu.id','=','tbl_hscc_bonus.user_id')
                /*->join('tbl_topup as tt', 'tt.pin', '=', 'tbl_dailybonus.pin')*/

               ->join('tbl_hscc_bonus_setting as ht', 'ht.id', '=', 'tbl_hscc_bonus.bonus_id')
                ->select('tbl_hscc_bonus.user_id', 'tbl_hscc_bonus.direct_amount', 'tbl_hscc_bonus.amount','tbl_hscc_bonus.laps_amount', 'ht.percentage', 'tbl_hscc_bonus.entry_time', 'tbl_hscc_bonus.binary_amount','tbl_hscc_bonus.remark', 'tbl_hscc_bonus.status','tu.user_id');
         //dd($query);       

        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_dailybonus.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }

        /*if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            
            $fields = getTableColumns('tbl_dailybonus');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_dailybonus.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }*/

        /* if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id','tbl_dailybonus.pin','tbl_dailybonus.amount','tbl_dailybonus.on_amount' ,'tbl_dailybonus.status' , 'tbl_dailybonus.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res,"AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
		} */

        // $totalRecord    = $query->count('tbl_dailybonus.id');
       // $totalRecord    = $query->count('tbl_dailybonus.id');
        $query          = $query->orderBy('tbl_hscc_bonus.id','desc');
        $totalRecord    = $query->count('tbl_hscc_bonus.id');

        $arrDailyBouns  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
        // dd($arrDailyBouns);
        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrDailyBouns;

        if($arrData['recordsTotal'] > 0) {
           return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
           return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record Not Found', '');
        }
    }

    public function getDailyBinary(Request $request) {
        $arrInput = $request->all();

        $query = DailyBinaryIncome::join('tbl_users as tu','tu.id','=','tbl_daily_binary_income.user_id')
                /*->join('tbl_topup as tt', 'tt.pin', '=', 'tbl_dailybonus.pin')*/
                ->select('tbl_daily_binary_income.amount','tbl_daily_binary_income.daily_binary_pin','tbl_daily_binary_income.daliy_percentage','tbl_daily_binary_income.daliy_binary_income','tbl_daily_binary_income.entry_time','tu.user_id','tu.fullname');
         //dd($query);       

        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['pin'])) {
            $query = $query->where('tbl_daily_binary_income.daily_binary_pin',$arrInput['pin']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_daily_binary_income.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }

        /*if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            
            $fields = getTableColumns('tbl_dailybonus');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_dailybonus.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }*/

        /* if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id','tbl_dailybonus.pin','tbl_dailybonus.amount','tbl_dailybonus.on_amount' ,'tbl_dailybonus.status' , 'tbl_dailybonus.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res,"AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
		} */

        // $totalRecord    = $query->count('tbl_dailybonus.id');
       // $totalRecord    = $query->count('tbl_dailybonus.id');
        $query          = $query->orderBy('tbl_daily_binary_income.id','desc');
        $totalRecord    = $query->count('tbl_daily_binary_income.user_id');

        $arrDailyBouns  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
        // dd($arrDailyBouns);
        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrDailyBouns;

        if($arrData['recordsTotal'] > 0) {
           return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
           return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record Not Found', '');
        }
    }
}