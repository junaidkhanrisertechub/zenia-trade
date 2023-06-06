<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use App\Http\Controllers\adminapi\ManageCronController;
use Illuminate\Support\Facades\Auth;
use App\Models\Currencyrate;
use App\Models\Currency;
use App\Models\Dashboard;
use App\Models\Enquiry;
use App\Models\UsersChangeData;
use App\Models\AddressTransaction;
use App\Models\AddressTransactionPending;
use App\Models\TodaySummary;
use App\Models\AddFunds;
use App\Models\Topup;
use App\Models\Invoice;
use App\Models\DirectIncome;
use App\Models\BinaryIncome;
use App\Models\LevelIncome;
use App\Models\DailyBouns;
use App\Models\AllTransaction;
use App\Models\ReplyEnquiryReport;
use App\Models\WithdrawalConfirmed;
use App\Models\WithdrawPending;
use App\Models\LeadershipIncome;
use App\Models\PayoutHistory;
use App\Models\UplineIncome;
use App\Models\LevelIncomeRoi;
use App\Models\AwardWinner;
use App\Models\PromotionalSocialIncome;
use App\Models\SupperMatchingIncome;
use App\Models\freedomclubincome;
use App\Models\CronStatus;
use App\User;
use App\Traits\ManageCron;
use DB;
use Config;
use Validator;
use Illuminate\Http\Response as Response;

class TestingController extends Controller
{
    use ManageCron;
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode, $commonController;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CommonController $commonController,ManageCronController $manageCronController)
    {
        $this->statuscode =    Config::get('constants.statuscode');
        $this->commonController = $commonController;
        $this->manageCronController = $manageCronController;
    }

  
    public function getCrons(Request $request) {
		$arrInput = $request->all();

		$query = CronStatus::select('*');
		
		$totalRecord = $query->count('tbl_cron_status.id');
		$query = $query->orderBy('tbl_cron_status.id', 'desc');
		// $totalRecord = $query->count();
		$arrUserData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrUserData;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
		}
	}
    public function getCronRun(Request $request) {
		$arrInput = $request->all();

		$query = CronStatus::join('tbl_cron_run as cr', 'cr.cron_id', '=', 'tbl_cron_status.id')
                            ->select('tbl_cron_status.name','cr.run_status','cr.run_time','tbl_cron_status.cron_name');
		
		$totalRecord = $query->count('cr.id');
		$query = $query->orderBy('cr.id', 'desc');
		// $totalRecord = $query->count();
		$arrUserData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrUserData;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
		}
	}
    public function RunCronFromAdminSide(Request $request) {
		$arrInput = $request->all();
		//dd($arrInput);
        $cron_name = $arrInput['cron_id'];
        $cron_run_count = $arrInput['cron_run_count'];
		$cron_fun = $arrInput['cron_fun'];
		//define('a',$cron_fun);
        //dd($cron_fun);
		$runCron = '';
        for($i=0;$i<$cron_run_count;$i++)
        {
			if($cron_fun == 'RunCronRoiDynamic'){
            	$runCron = $this->manageCronController->RunCronRoiDynamic();
			}else if($cron_fun == 'RunBinaryQualifyCron'){
				$runCron = $this->manageCronController->RunBinaryQualifyCron($cron_name);
			}else if($cron_fun == 'RunBinaryIncomeCron'){
				$runCron = $this->manageCronController->RunBinaryIncomeCron($cron_name);
			}else if($cron_fun == 'AddPowerUptoAdmin'){
				$runCron = $this->manageCronController->AddPowerUptoAdmin($cron_name);
			}else if($cron_fun == 'RemovePowerUptoAdmin'){
				$runCron = $this->manageCronController->RemovePowerUptoAdmin($cron_name);
			}else{
				echo 'Cron name is not valid';
			}
        }
       
		//dd($runCron);
        return $runCron;
		
        
	}
    public function getActiveCrons(Request $request) {
		$arrInput = $request->all();

		$query = CronStatus::select('*')->where('status',1);
		
		$totalRecord = $query->count('tbl_cron_status.id');
		$query = $query->orderBy('tbl_cron_status.id', 'desc');
		// $totalRecord = $query->count();
		$arrUserData = $query->get();
		//dd($arrUserData);
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrUserData;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
		}
	}

    public function changeCronStatus(Request $request) {
		//$id = Auth::user()->id;
		$arrInput = $request->all();
		$rules = array(
			'id' => 'required',
			'status' => 'required',
		);
		$validator = Validator::make($arrInput, $rules);
		//if the validator fails, redirect back to the form
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {

			/** @var [ins into Change History table] */
			if ($arrInput['status'] == '0') {
				$do = 'Active';
				$status = '1';
				$msg = 'Cron  activated successfully';
			} else {
				$do = 'InActive';
				$status = '0';
				$msg = 'Cron Inactivated successfully';
				
			}

			$change = CronStatus::where('id', $arrInput['id'])->update(['status' => $status]);
			if (!empty($change)) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
			} else {
				return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error occured while blocking user', '');
			}
		}
	}
  


}
