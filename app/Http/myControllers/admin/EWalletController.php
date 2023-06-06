<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\admin\CommonController;
use App\Http\Controllers\Controller;
use App\Models\AwardWinner;
use App\Models\BinaryIncome;
use App\Models\DirectIncome;
use App\Models\ExternalAddress;
use App\Models\FranchiseIncome;
use App\Models\Invoice;
use App\Models\LeadershipIncome;
use App\Models\LevelIncome;
use App\Models\LevelIncomeRoi;
use App\Models\ReservedAddress;
use App\Models\UplineIncome;
use App\Models\Names;
use App\Models\SupperMatchingIncome;
use App\Models\Dashboard;
use App\Models\WithdrawalConfirmed;
use App\Models\WithdrawPending;
use App\Models\dailybusiness;
use App\Models\QualifiedUserList;
use App\Models\verifyAdminOtpStatus;
use App\Models\WorkingToPurchaseTransfer;
use App\User;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Models\TheDailySummary;

use App\Models\ChainIncomeSettings;
use App\Models\ChainIncome;
use App\Models\AddChainIncome;


class EWalletController extends Controller
{
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
	public function __construct(CommonController $commonController, SettingsController $settings)
	{
		$this->statuscode = Config::get('constants.statuscode');
		$this->commonController = $commonController;
		$date = \Carbon\Carbon::now();
		$this->settings = $settings;
		$this->today = $date->toDateTimeString();
	}

	/**
	 * get records of withdrwal pending
	 *
	 * @return void
	 */
public function ChainIncome(Request $request)
{
	$arrInput = $request->all();
	$userId = Auth::User()->id;
	$query = ChainIncome::select('tbl_chain_income.chain_income','tu.user_id as user_id', 'tu.fullname as fullname','tbl_chain_income.entry_time')
		->join('tbl_users as tu', 'tu.id', '=', 'tbl_chain_income.user_id');
		// ->where([['tbl_chain_income.user_id', '=', $userId]]);

	if (isset($arrInput['id'])) {
		$query = $query->where('tu.user_id', $request->id);
	}
	if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
		$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
		$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
		$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_chain_income.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
	}

	if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
		$qry = $query;
		$qry = $qry->select('tbl_chain_income.chain_income','tu.user_id as user_id', 'tu.fullname as fullname','tbl_chain_income.entry_time');
		$records = $qry->get();
		$res = $records->toArray();
		if (count($res) <= 0) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
		}
		$var = $this->commonController->exportToExcel($res, "AllUsers");
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
	}

	$query         = $query->orderBy('tbl_chain_income.entry_time', 'desc');
	$totalRecord   = $query->count('tbl_chain_income.id');
	// $totalRecord   = $query->count();
	$arrFranchise      = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

	$arrData['recordsTotal']    = $totalRecord;
	$arrData['recordsFiltered'] = $totalRecord;
	$arrData['records']         = $arrFranchise;

	if (count($arrFranchise) > 0) {
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
	} else {
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
	}

}

public function ChainBonusIncome(Request $request)
{
	$arrInput = $request->all();
	$userId = Auth::User()->id;

	$query = AddChainIncome::select('setting.other_bonus', 'tbl_chain_business_bonus_income.income', 'tbl_chain_business_bonus_income.entry_time', 'tu.user_id as user_id', 'tu.fullname as fullname','tbl_chain_business_bonus_income.l_bv','tbl_chain_business_bonus_income.r_bv')
		->join('tbl_users as tu', 'tu.id', '=', 'tbl_chain_business_bonus_income.user_id')
		->join('tbl_chain_business_settings as setting', 'setting.id', '=', 'tbl_chain_business_bonus_income.chain_business_id');
		// ->where([['tbl_chain_business_bonus_income.user_id', '=', $userId]]);

	if (isset($arrInput['user_id']))
	{
		$query = $query->where('tu.user_id', $request->user_id);
	}
	if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
		$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
		$arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
		$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_Sales_UserReward.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
	}

	$query         = $query->orderBy('tbl_chain_business_bonus_income.entry_time', 'desc');
	$totalRecord   = $query->count('tbl_chain_business_bonus_income.id');
	// $totalRecord   = $query->count();
	$arrFranchise      = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

	$arrData['recordsTotal']    = $totalRecord;
	$arrData['recordsFiltered'] = $totalRecord;
	$arrData['records']         = $arrFranchise;

	if (count($arrFranchise) > 0) {
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
	} else {
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
	}
}

	public function getWithdrwalPending(Request $request)
	{
		$arrInput = $request->all();

		$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
			->leftjoin('tbl_country_new as cn', 'tu.country', '=', 'cn.iso_code')
			->select('tbl_withdrwal_pending.amount', 'tbl_withdrwal_pending.deduction', 'tbl_withdrwal_pending.entry_time', 'tbl_withdrwal_pending.network_type', 'tu.paypal_address', 'tu.user_id', 'tu.fullname', /*'tu.holder_name', 'tu.bank_name', 'tu.branch_name', 'tu.pan_no', 'tu.ifsc_code',*/ 'tu.btc_address', DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'), DB::raw('(CASE tbl_withdrwal_pending.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" ELSE "" END) as withdraw_type'), 'cn.country', 'tu.perfect_money_address')
			->where([['tbl_withdrwal_pending.status', 0]]);

		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['country'])) {
			$query = $query->where('tu.country', $arrInput['country']);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_pending');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tbl_withdrwal_pending.id');
		$query = $query->orderBy('tbl_withdrwal_pending.sr_no', 'desc');

		/*if(isset($arrInput['start']) && isset($arrInput['length'])){
			            $arrData = setPaginate($query,$arrInput['start'],$arrInput['length']);
			        } else {
			            $arrData = $query->get();
			        }
		*/
		// $totalRecord = $query->count();
		$arrPendings = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$totalSummary = $this->getWithdrwalPendingTotals($request);

		$arrData['total_amount'] = $totalSummary->amount + $totalSummary->deduction;
		$arrData['total_net_amount'] = $totalSummary->amount;
		$arrData['total_deduction'] = $totalSummary->deduction;
		$arrData['total_transaction_fee'] = $totalSummary->transaction_fee;
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function PaymentsSendApiNew($objPendings, $arrInput, $apiType)
	{
		/*$objUsers = User::where('id',$objPendings->id)->first();*/
		if ($objPendings->amount > 0) {


			$req = array(
				'amount'        => $objPendings->amount,
				'currency'      => $objPendings->network_type,
				'currency2'     => 'USD',
				'add_tx_fee'	=> 1,
				'address'       => $objPendings->to_address,
				'note'          => isset($arrInput['remark']) ? $arrInput['remark'] : 'Paid',
				'auto_confirm'  => 1
			);

			if ($apiType == 'coinpayment') {
				$req_data = coinpayments_api_call('create_withdrawal', $req, $arrInput['admin_otp']);
			} else if ($apiType == 'coinbase') {
				$req_data = getCoinbase_address('create_withdrawal', $req);
			} else {
				return array('code' => $this->statuscode[403]['code'], 'message' => 'Payment api parameter not given');
			}

			/*dd($req_data);*/

			if (@$req_data['msg'] == 'success') {
				//success
				$coinpayment_id = 0;
				$arrUpdate = [
					'status' => 1,
					'remark' => 'Paid',/*$req['remark'],*/
					'api_ref_id' => $req_data['data']['id'],
					'paid_date' => $this->today
				];
				$updatePending = WithdrawPending::where('sr_no', $objPendings->sr_no)->update($arrUpdate);
				if (!empty($updatePending)) {

					/*$deletKey = Names::where('sr_no',$objPendings->api_sr_no)->delete();*/

					$objUpdate = WithdrawPending::where('sr_no', $arrInput['srno'])->first();

					$withDrawdata = array();
					$withDrawdata['transaction_hash'] = $objUpdate->transaction_hash;
					$withDrawdata['id'] = $objUpdate->id;
					$withDrawdata['wp_ref_id'] = $objPendings->sr_no;
					$withDrawdata['amount'] = $objUpdate->amount;
					$withDrawdata['deduction'] = $objUpdate->deduction;
					$withDrawdata['to_address'] = $objUpdate->to_address;
					$withDrawdata['remark'] = $arrInput['remark'];
					$withDrawdata['status'] = 1;
					$withDrawdata['paid_date'] = $objUpdate->paid_date;
					$withDrawdata['notification'] = $objUpdate->notification;
					$withDrawdata['network_type'] = $objUpdate->network_type;
					$withDrawdata['entry_time'] = $this->today;
					$withDrawdata['api_ref_id'] = $objUpdate->api_ref_id;
					$WithDrawinsert = WithdrawalConfirmed::create($withDrawdata);


					if ($WithDrawinsert) {
						return 'Withdrawal request successfull';
					} else {
						return 'Error occured while adding record in withdrawal confirmed';
					}
				} else {
					return 'Error occured while updating record in withdrawal pending';
				}
			} else {
				return 'Coin payment api has failed';
			}
		} else {
			return 'Invalid Amount. Amount must be greater than 0';
		}
	}
	/**
	 * get records of withdrwal Verify
	 *
	 * @return void
	 */
    public function PendingWithdrawalBlade(){
        return view('admin.Withdrawal.PendingWithdrawalRequest');
    }
	public function getWithdrwalVerify(Request $request)
	{
		$arrInput = $request->all();

		$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
			->leftjoin('tbl_country_new as cn', 'tu.country', '=', 'cn.iso_code')
			->where('tbl_withdrwal_pending.verify', 0)
			->where('tbl_withdrwal_pending.status', 0)
			->where('tu.status', 'Active');
		//dd($query->toSql());

		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (isset($arrInput['user_id'])) {
			//dd('hello');
			$query = $query->where('tu.user_id', $arrInput['user_id']);
		}
		if (isset($arrInput['country'])) {
			$query = $query->where('tu.country', $arrInput['country']);
		}
		// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
		// 	//searching loops on fields
		// 	$fields = getTableColumns('tbl_withdrwal_pending');
		// 	$search = $arrInput['search']['value'];
		// 	$query = $query->where(function ($query) use ($fields, $search) {
		// 		foreach ($fields as $field) {
		// 			$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
		// 		}
		// 		$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
		// 	});
		// }
		$totalRecord = $query->count('tbl_withdrwal_pending.sr_no');
		$query = $query->orderBy('tbl_withdrwal_pending.sr_no', 'desc');
		//dd($query->get());
		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id', 'tu.fullname', 'tbl_withdrwal_pending.user_ip', 'cn.country', DB::raw('tbl_withdrwal_pending.amount+tbl_withdrwal_pending.deduction as amount'), 'tbl_withdrwal_pending.deduction', DB::raw('tbl_withdrwal_pending.amount as net_amount'), 'tbl_withdrwal_pending.network_type as currency_type', 'tbl_withdrwal_pending.to_address as address', DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'), DB::raw('(CASE tbl_withdrwal_pending.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN 7 THEN "HSCC-Bonus" END) as wallet_type'), 'tbl_withdrwal_pending.entry_time as Date');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}
		$query = $query->select('tbl_withdrwal_pending.ip_address', 'tbl_withdrwal_pending.to_address', 'tbl_withdrwal_pending.user_ip', DB::raw('tbl_withdrwal_pending.amount+tbl_withdrwal_pending.deduction as amount'), 'tbl_withdrwal_pending.sr_no', 'tbl_withdrwal_pending.deduction', 'tbl_withdrwal_pending.entry_time', 'tbl_withdrwal_pending.network_type', 'tu.user_id', 'tu.paypal_address', 'tu.fullname', 'tu.holder_name', 'tu.bank_name', 'tu.branch_name', 'tu.pan_no', 'tu.ifsc_code', 'tu.btc_address', DB::raw('(CASE tbl_withdrwal_pending.withdraw_type WHEN 2 THEN "Working" WHEN 7 THEN "Passive Income" END) as wallet_type'), DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'), DB::raw('(CASE tbl_withdrwal_pending.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN 7 THEN "HSCC-Bonus" ELSE "" END) as withdraw_type'), 'cn.country', 'tu.perfect_money_address');

		/*if(isset($arrInput['start']) && isset($arrInput['length'])){
			            $arrData = setPaginate($query,$arrInput['start'],$arrInput['length']);
			        } else {
			            $arrData = $query->get();
			        }
		*/
		// $totalRecord = $query->count();
		$arrPendings = $query->skip($arrInput['start'])->take($arrInput['length'])->get();


		$totalSummary = $this->getWithdrwalPendingTotals($request);

		$arrData['total_amount'] = $totalSummary->amount + $totalSummary->deduction;
		$arrData['total_net_amount'] = $totalSummary->amount;
		$arrData['total_deduction'] = $totalSummary->deduction;
		$arrData['total_transaction_fee'] = $totalSummary->transaction_fee;
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;
		//dd($arrData);

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getWithdrwalPendingTotals($request)
	{

		$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
			->selectRaw('COALESCE(ROUND(SUM(tbl_withdrwal_pending.amount),5),0) as amount,COALESCE(ROUND(SUM(tbl_withdrwal_pending.deduction),5),0) as deduction,COALESCE(ROUND(SUM(tbl_withdrwal_pending.transaction_fee),5),0) as transaction_fee')
			->where('tbl_withdrwal_pending.status', 0); //pending status

		if (isset($request->frm_date) && isset($request->to_date)) {
			$request->frm_date = date('Y-m-d', strtotime($request->frm_date));
			$request->to_date = date('Y-m-d', strtotime($request->to_date));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [$request->frm_date, $request->to_date]);
		}
		if (isset($request->id)) {
			$query = $query->where('tbl_withdrwal_pending.id', $request->id);
		}
		if (isset($request->search->value) && !empty($request->search->value)) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_pending');
			$search = $request->search->value;
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalSummary = $query->orderBy('tbl_withdrwal_pending.entry_time', 'desc')->first();

		return $totalSummary;
	}
    public function confirmedWithdrawalBlade(){
        return view('admin.Withdrawal.ConfirmWithdrawalReport');
    }

    public function rejectedWithdrawalReportBlade(){
        return view('admin.Withdrawal.RejectedWithdrawReport');
    }

    public function verifiedwithdrawalBlade(){
        return view('admin.Withdrawal.VerifyWithdrawalReport');
    }

	/**
	 * get records of withdrwal confirmed
	 *
	 * @return void
	 */
	public function getWithdrwalConfirmed(Request $request)
	{
		$arrInput = $request->all();

		$query = WithdrawalConfirmed::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_confirmed.id')
			->leftjoin('tbl_country_new as cn', 'tu.country', '=', 'cn.iso_code')
			->select(
				'tbl_withdrwal_confirmed.sr_no',
				'tbl_withdrwal_confirmed.transaction_hash',
				'tbl_withdrwal_confirmed.transaction_fee',
				'tbl_withdrwal_confirmed.deduction',
				'tbl_withdrwal_confirmed.ip_address',
				'tbl_withdrwal_confirmed.amount',
				'tbl_withdrwal_confirmed.remark',
				'tbl_withdrwal_confirmed.network_type',
				'tbl_withdrwal_confirmed.entry_time',
				'tbl_withdrwal_confirmed.to_address',
				'tu.user_id',
				'tu.paypal_address',
				'tu.fullname',
				DB::raw('(CASE tbl_withdrwal_confirmed.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'),
				DB::raw('(CASE tbl_withdrwal_confirmed.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN 7 THEN "HSCC-Bonus" WHEN  8 THEN "Passive Income" ELSE "" END) as withdraw_type'),
				'cn.country',
				'tu.perfect_money_address'
			);

		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_confirmed.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['country'])) {
			$query = $query->where('tu.country', $arrInput['country']);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_confirmed');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_confirmed.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}


		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id', 'tu.fullname', 'tbl_withdrwal_confirmed.amount', 'tbl_withdrwal_confirmed.deduction', DB::raw('(tbl_withdrwal_confirmed.amount + tbl_withdrwal_confirmed.deduction) as total_amount'), 'tbl_withdrwal_confirmed.network_type', 'tbl_withdrwal_confirmed.to_address', DB::raw('(CASE tbl_withdrwal_confirmed.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN  7 THEN "Passive Income" ELSE "" END) as withdraw_type'), DB::raw('(CASE tbl_withdrwal_confirmed.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'), 'cn.country', 'tbl_withdrwal_confirmed.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$totalRecord = $query->count('tbl_withdrwal_confirmed.sr_no');
		$query = $query->orderBy('tbl_withdrwal_confirmed.sr_no', 'desc');

		/*if(isset($arrInput['start']) && isset($arrInput['length'])){
			            $arrData = setPaginate($query,$arrInput['start'],$arrInput['length']);
			        } else {
			            $arrData = $query->get();
			        }
		*/
		// $totalRecord = $query->count();
		$arrConfirmed = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$totalSummary = $this->getWithdrwalConfirmedTotals($request);

		$arrData['total_amount'] = $totalSummary->amount + $totalSummary->deduction;
		$arrData['total_net_amount'] = $totalSummary->amount;
		$arrData['total_deduction'] = $totalSummary->deduction;
		$arrData['total_transaction_fee'] = $totalSummary->transaction_fee;
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrConfirmed;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getWithdrwalConfirmedSA(Request $request)
	{
		$arrInput = $request->all();

		$query = WithdrawalConfirmed::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_confirmed.id')
			->leftjoin('tbl_country_new as cn', 'tu.country', '=', 'cn.iso_code')
			->select(
				'tbl_withdrwal_confirmed.sr_no',
				'tbl_withdrwal_confirmed.transaction_hash',
				'tbl_withdrwal_confirmed.transaction_fee',
				'tbl_withdrwal_confirmed.deduction',
				'tbl_withdrwal_confirmed.ip_address',
				'tbl_withdrwal_confirmed.amount',
				'tbl_withdrwal_confirmed.remark',
				'tbl_withdrwal_confirmed.network_type',
				'tbl_withdrwal_confirmed.entry_time',
				'tbl_withdrwal_confirmed.to_address',
				'tu.user_id',
				'tu.paypal_address',
				'tu.fullname',
				DB::raw('(CASE tbl_withdrwal_confirmed.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'),
				DB::raw('(CASE tbl_withdrwal_confirmed.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN 7 THEN "HSCC-Bonus" WHEN  8 THEN "Passive Income" ELSE "" END) as withdraw_type'),
				'cn.country',
				'tu.perfect_money_address'
			)->where('tu.user_id', $arrInput['id']);

		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_confirmed.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		/*if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}*/
		if (isset($arrInput['country'])) {
			$query = $query->where('tu.country', $arrInput['country']);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_confirmed');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_confirmed.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}


		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id', 'tu.fullname', 'tbl_withdrwal_confirmed.amount', 'tbl_withdrwal_confirmed.deduction', DB::raw('(tbl_withdrwal_confirmed.amount + tbl_withdrwal_confirmed.deduction) as total_amount'), 'tbl_withdrwal_confirmed.network_type', 'tbl_withdrwal_confirmed.to_address', DB::raw('(CASE tbl_withdrwal_confirmed.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN  7 THEN "Passive Income" ELSE "" END) as withdraw_type'), DB::raw('(CASE tbl_withdrwal_confirmed.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'), 'cn.country', 'tbl_withdrwal_confirmed.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$totalRecord = $query->count('tbl_withdrwal_confirmed.sr_no');
		$query = $query->orderBy('tbl_withdrwal_confirmed.sr_no', 'desc');

		/*if(isset($arrInput['start']) && isset($arrInput['length'])){
			            $arrData = setPaginate($query,$arrInput['start'],$arrInput['length']);
			        } else {
			            $arrData = $query->get();
			        }
		*/
		// $totalRecord = $query->count();
		$arrConfirmed = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		// $totalSummary = $this->getWithdrwalConfirmedTotals($request);

		// $arrData['total_amount'] = $totalSummary->amount + $totalSummary->deduction;
		// $arrData['total_net_amount'] = $totalSummary->amount;
		// $arrData['total_deduction'] = $totalSummary->deduction;
		// $arrData['total_transaction_fee'] = $totalSummary->transaction_fee;
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrConfirmed;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}


	public function getWithdrwalVerified(Request $request)
	{
		$arrInput = $request->all();

		$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
			->leftjoin('tbl_country_new as cn', 'tu.country', '=', 'cn.iso_code')
			->select('tbl_withdrwal_pending.to_address', 'tbl_withdrwal_pending.amount', 'tbl_withdrwal_pending.sr_no', 'tbl_withdrwal_pending.deduction', 'tbl_withdrwal_pending.entry_time', 'tbl_withdrwal_pending.network_type', 'tu.user_id', 'tu.paypal_address', 'tu.fullname', 'tu.holder_name', 'tu.bank_name', 'tu.branch_name', 'tu.pan_no', 'tu.ifsc_code', 'tu.btc_address', DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'), DB::raw('(CASE tbl_withdrwal_pending.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN 7 THEN "HSCC-Bonus" WHEN  8 THEN "Passive Income" ELSE "" END) as withdraw_type'), 'cn.country', 'tu.perfect_money_address')
			->where('tbl_withdrwal_pending.verify', 1)
			->where('tbl_withdrwal_pending.status', 0)
			->where('tu.status', 'Active');

		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (isset($arrInput['user_id'])) {
			$query = $query->where('tu.user_id', $arrInput['user_id']);
		}
		if (isset($arrInput['country'])) {
			$query = $query->where('tu.country', $arrInput['country']);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_pending');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id', 'tu.fullname', 'tbl_withdrwal_pending.amount', 'tbl_withdrwal_pending.deduction', 'tbl_withdrwal_pending.network_type', 'tbl_withdrwal_pending.to_address', DB::raw('(CASE tbl_withdrwal_pending.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN 7 THEN "HSCC-Bonus" WHEN  8 THEN "Passive Income" ELSE "" END) as withdraw_type'), DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" END) as status'), 'tbl_withdrwal_pending.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}


		$totalRecord = $query->count('tbl_withdrwal_pending.sr_no');
		$query = $query->orderBy('tbl_withdrwal_pending.sr_no', 'desc');

		/*if(isset($arrInput['start']) && isset($arrInput['length'])){
			            $arrData = setPaginate($query,$arrInput['start'],$arrInput['length']);
			        } else {
			            $arrData = $query->get();
			        }
		*/
		// $totalRecord = $query->count();
		$arrPendings = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$totalSummary = $this->getWithdrwalPendingTotals($request);

		$arrData['total_amount'] = $totalSummary->amount + $totalSummary->deduction;
		$arrData['total_net_amount'] = $totalSummary->amount;
		$arrData['total_deduction'] = $totalSummary->deduction;
		$arrData['total_transaction_fee'] = $totalSummary->transaction_fee;
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function getWithdrwalConfirmedTotals($request)
	{

		$query = WithdrawalConfirmed::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_confirmed.id')
			->selectRaw('COALESCE(ROUND(SUM(tbl_withdrwal_confirmed.amount),5),0) as amount,COALESCE(ROUND(SUM(tbl_withdrwal_confirmed.deduction),5),0) as deduction,COALESCE(ROUND(SUM(tbl_withdrwal_confirmed.transaction_fee),5),0) as transaction_fee')
			->where('tbl_withdrwal_confirmed.status', 1); //confirmed status

		if (isset($request->frm_date) && isset($request->to_date)) {
			$request->frm_date = date('Y-m-d', strtotime($request->frm_date));
			$request->to_date = date('Y-m-d', strtotime($request->to_date));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_confirmed.entry_time,'%Y-%m-%d')"), [$request->frm_date, $request->to_date]);
		}
		if (isset($request->id)) {
			$query = $query->where('tbl_withdrwal_confirmed.id', $request->id);
		}
		if (isset($request->search->value) && !empty($request->search->value)) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_confirmed');
			$search = $request->search->value;
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_confirmed.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalSummary = $query->orderBy('tbl_withdrwal_confirmed.entry_time', 'desc')->first();

		return $totalSummary;
	}

	/**
	 * confirm pending withdrawal transaction
	 *
	 * @return void

	 */
	public function confirmWithdrawl(Request $request)
	{

		$arrInput = array_filter($request->all());
		// validate the info, create rules for the inputs
		$rules = array('sr_no' => 'required', 'remark' => 'required');
		// run the validation rules on the inputs from the form
		$validator = Validator::make($arrInput, $rules);
		// if the validator fails, redirect back to the form
		if ($validator->fails()) {
			$message = $validator->errors();
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
		} else {

			$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
				->select('tbl_withdrwal_pending.*')
				->where([['tbl_withdrwal_pending.status', '=', 0], ['tbl_withdrwal_pending.sr_no', '=', $arrInput['sr_no']]])->first();
			if (!empty($query)) {
				// update withdraw pending status
				$arrUpdate = [
					'status' => 1,
					'remark' => $arrInput['remark'],

				];
				$updatePending = WithdrawPending::where('sr_no', $arrInput['sr_no'])->update($arrUpdate);
				// add into confirm request
				$withDrawdata = array();
				$withDrawdata['transaction_hash'] = $query->transaction_hash;
				$withDrawdata['id'] = $query->id;
				$withDrawdata['wp_ref_id'] = $arrInput['sr_no'];
				$withDrawdata['amount'] = $query->amount;
				$withDrawdata['transaction_fee'] = $query->transaction_fee;
				$withDrawdata['from_address'] = $query->from_address;
				$withDrawdata['to_address'] = $query->to_address;
				$withDrawdata['remark'] = $arrInput['remark'];
				$withDrawdata['status'] = 1;
				$withDrawdata['paid_date'] = $query->paid_date;
				$withDrawdata['notification'] = $query->notification;
				$withDrawdata['network_type'] = $query->network_type;
				$withDrawdata['entry_time'] = $this->today;
				$withDrawdata['api_ref_id'] = $query->api_ref_id;
				$WithDrawinsert = WithdrawalConfirmed::create($withDrawdata);
				if (!empty($WithDrawinsert)) {
					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Confirmed withdrawl successfull', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Pending user not available', '');
			}
		}
	}


	/**
	 * Function to generate the unique random id for reserved address
	 *
	 */
	function verifyWithdrwal()
	{
		$randomId = substr(number_format(time() * rand(), 0, '', ''), 0, '10');
		$random = ReservedAddress::where('invoice_id', $randomId)->first();
		if (!empty($random)) {
			$this->uniqueRandomIdForBTCAddr();
		}
		return $randomId;
	}
	public function WithdrwalRequestVerify(Request $request) {
        $arrInput = $request->all();

        $rules = array(
            'request_id'    => 'required',
            'admin_otp' => 'required',
			'otp' => 'required|min:6'
        );
		$adminOtpStatusData = verifyAdminOtpStatus::select('withdraw_pending_update_status')->first();
		// $validator = Validator::make($arrInput, $rules);
		$validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err = '';
            foreach($message->all() as $error) {
                $err = $err ." ". $error;
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,'');
        }
		else if ($adminOtpStatusData->withdraw_pending_update_status == 1) {
			if (!isset($arrInput['otp']) && empty($arrInput['otp'])) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
			}
			$arrInput['user_id'] = Auth::User()->id;
			$arrInput['otp'] = $request->otp;
			$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
			if (!empty($verify_otp)) {
				if ($verify_otp['status'] == 200) {
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			}
		}
        // $validator = Validator::make($arrInput, $rules);
        // if ($validator->fails()) {
        //     $message = $validator->errors();

        //     $arrStatus = Response::HTTP_BAD_REQUEST;
        //     $arrCode = Response::$statusTexts[$arrStatus];
        //     $arrMessage = $message->request_id;
        //     return sendResponse($arrStatus, $arrCode, $arrMessage, []);
        // } else {

            $insertId = generateName($request->admin_otp);
            $arrUpdate['verify']=1;
            $arrUpdate['verify_time']=$this->today;
            $arrUpdate['api_sr_no']=$insertId;

				// dd("all requests");
			$updatePending = WithdrawPending::whereIn('sr_no',$request->request_id)->update($arrUpdate);

            if ($updatePending) {
            	for ($i=0; $i <count($arrInput['request_id']) ; $i++) {
				   //------ email for confrom withdrawal request
				   $request_amount_details = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
				   								->select('tbl_withdrwal_pending.amount',
				   									'tbl_withdrwal_pending.withdraw_type',
				   									'tbl_withdrwal_pending.deduction',
				   									'tbl_withdrwal_pending.entry_time',
				   									'tbl_withdrwal_pending.to_address',
				   									'tu.user_id','tu.email')
											   ->where('tbl_withdrwal_pending.sr_no',$arrInput['request_id'][$i])
											   ->first();
				 //	dd($request_amount_details);
				   $requested_amount = $request_amount_details->amount + $request_amount_details->deduction;
				//    $user_details = Auth::user();
				   $email = $request_amount_details->email;
				   $user_id = $request_amount_details->user_id;
				   $withdraw_type = $request_amount_details->withdraw_type;
				   $deduction = $request_amount_details->deduction;
				   $entry_date = date('d M Y',strtotime($request_amount_details->entry_time));
				   $wallet_address = $request_amount_details->to_address;
				   if ($withdraw_type == 2) {
				   	$withdraw_type_text="Working";
				   }elseif($withdraw_type == 3){
				   	$withdraw_type_text="ROI";
				   }elseif($withdraw_type == 7){
				   	$withdraw_type_text="HSCC Bonus";
				   }

				   $user_info = User::where('user_id', $user_id)->first();
				 //  dd($user_details);
				   // $subject = " Withdrawal request processed!";
				   $subject = "Withdrawal request processed successfully ";
				   $pagename = "emails.admin-emails.withdrawal_status";
				   $data = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id,'name'=>$user_info->fullname,'amount'=>$requested_amount,'deduction'=>$deduction,'withdraw_type'=>$withdraw_type_text,'wallet_address'=>$wallet_address,'date'=>$entry_date);
				   $email = $email;
				   $mail = sendMail($data, $email, $subject);
				}
					//dd($mail);
					//------ email for confrom withdrawal request

                $arrStatus = Response::HTTP_OK;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Request verified Successfully!';
                return sendResponse($arrStatus, $arrCode, $arrMessage, []);
            }
        // }
    }
	public function WithdrwalRequestVerifyold(Request $request)
	{
		$arrInput = $request->all();

		$rules = array(
			'request_id'    => 'required|numeric',
			// 'admin_otp' => 'required',
			// 'otp' => 'required|min:10|max:10'
		);
		$adminOtpStatusData = verifyAdminOtpStatus::select('withdraw_pending_update_status')->first();
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = $validator->errors();

			$arrStatus = Response::HTTP_BAD_REQUEST;
			$arrCode   = Response::$statusTexts[$arrStatus];
			$arrMessage = $message->request_id;
			return sendResponse($arrStatus, $arrCode, $arrMessage, []);
		} else if ($adminOtpStatusData->withdraw_pending_update_status == 1) {
			if (!isset($arrInput['otp']) && empty($arrInput['otp'])) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
			}
			$arrInput['user_id'] = Auth::User()->id;
			$arrInput['remark'] = 'admin_verify_withdraw';
			$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
			if (!empty($verify_otp)) {
				if ($verify_otp['status'] == 200) {
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			}
		}
		$ciphering = "AES-128-CTR";

		$iv_length = openssl_cipher_iv_length($ciphering);
		$options   = 0;

		$encryption_iv  = '1874654512313213';
		$encryption_key = "h9mnEzPXqkfkF9Eb";

		$encryption = openssl_encrypt($request->admin_otp, $ciphering, $encryption_key, $options, $encryption_iv);

		$insArr = array(
			"subject" => $encryption,
			"text"    => 1,
			"entry_time" => $this->today
		);
		$insertId = Names::insertGetId($insArr);

		$arrUpdate['verify'] = 1;
		$arrUpdate['verify_time'] = $this->today;
		$arrUpdate['api_sr_no'] = $insertId;
		$updatePending = WithdrawPending::whereIn('sr_no', $request->request_id)->update($arrUpdate);

		if ($updatePending) {

			//------ email for confrom withdrawal request
			$request_amount_details = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
				->select('tbl_withdrwal_pending.amount', 'tbl_withdrwal_pending.deduction', 'tu.user_id', 'tu.email')
				->where('tbl_withdrwal_pending.sr_no', $request->request_id)
				->first();
			//	dd($request_amount_details);
			$requested_amount = $request_amount_details->amount + $request_amount_details->deduction;
			//    $user_details = Auth::user();
			$email = $request_amount_details->email;
			$user_id = $request_amount_details->user_id;
			//  dd($user_details);
			$subject = " Withdrawal request processed!";
			$pagename = "emails.admin-emails.withdraw_request_conform";
			$data = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id, 'amount' => $requested_amount);
			$email = $email;
			/*$mail = sendMail($data, $email, $subject);*/
			//dd($mail);
			//------ email for confrom withdrawal request

			$arrStatus = Response::HTTP_OK;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Request verified Successfully!';
			return sendResponse($arrStatus, $arrCode, $arrMessage, []);
		}
	}
	/**
	 * withdrawal transaction request
	 *
	 * @return void
	 */
	public function WithdrwalRequest(Request $request)
	{
		$arrInput = $request->all();

		$rules = array(
			'srno' => 'required',
			'remark' => 'required',
		);
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
		} else {
			$auto_withdrawal_status = $this->commonController->getProjectSettings()->auto_withdrawal_status;
			if ($auto_withdrawal_status == 'on') {
				$objPendings = WithdrawPending::where('sr_no', $arrInput['srno'])->first();
				if (count($objPendings) > 0) {
					//coin payments api
					$coinPaymentRes = $this->PaymentsSendApi($objPendings, $arrInput, 'coinpayment');
					if ($coinPaymentRes['code'] === 200) {
						return sendresponse($coinPaymentRes['code'], $this->statuscode[$coinPaymentRes['code']]['status'], $coinPaymentRes['message'], '');
					} else {
						//coin base api
						$coinBaseRes = $this->PaymentsSendApi($objPendings, $arrInput, 'coinbase');
						if ($coinBaseRes['code'] === 200) {
							return sendresponse($coinBaseRes['code'], $this->statuscode[$coinBaseRes['code']]['status'], $coinBaseRes['message'], '');
						} else {
							return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Coinpayment and coinbase both request has failed', '');
						}
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Withdrwal pending record not available', '');
				}
			} else {

				return $this->saveWithdrawlData($arrInput);
			}
		}
	}
	/**
	 * withdrawal transaction request api and save withdrawl data
	 *
	 * @return void
	 */
	public function PaymentsSendApi($objPendings, $arrInput, $apiType, $callcron = 0)
	{
		$objUsers = User::where('id', $objPendings->id)->first();
		if ($objPendings->amount > 0) {

			$req = array(
				'amount' => $objPendings->amount,
				'currency' => $objPendings->network_type,
				'address' => $objPendings->to_address,
				'note' => ($arrInput['remark']) ? $arrInput['remark'] : 'Paid',
				'auto_confirm' => 1,
			);
			//dd($apiType);
			if ($apiType == 'coinbase') {

				$req_data = getCoinbase_address('create_withdrawal', $req);
				// $req_data = sendCoinbase_btc('create_withdrawal', $req);
				// dd($req_data);
				print_r($req_data);
			} elseif ($apiType == 'coinpayment') {

				$req_data = coinpayments_api_call('create_withdrawal', $req);
			} else {
				return array('code' => $this->statuscode[403]['code'], 'message' => 'Payment api parameter not given');
			}

			if ($req_data['msg'] == 'success') {
				//success
				$coinpayment_id = $req_data['transactionId'];
				$arrUpdate = [
					'status' => 1,
					'remark' => $arrInput['remark'],
					'api_ref_id' => $coinpayment_id,
				];
				$updatePending = WithdrawPending::where('sr_no', $objPendings->sr_no)->update($arrUpdate);
				if (!empty($updatePending)) {
					$objUpdate = WithdrawPending::where('sr_no', $arrInput['srno'])->first();

					if ($callcron == 1) {
						$th = $objUpdate->transaction_hash;
					} else {
						$th = $arrInput['remark'];
					}
					$arrInsert = [
						'transaction_hash' => $th,
						'id' => $objUpdate->id,
						'deduction' => $objUpdate->deduction,
						'withdraw_type' => $objUpdate->withdraw_type,
						'amount' => $objUpdate->amount,
						'transaction_fee' => $objUpdate->transaction_fee,
						'from_address' => $objUpdate->from_address,
						'to_address' => $objPendings->to_address,
						'remark' => $objUpdate->remark,
						'status' => $objUpdate->status,
						'paid_date' => $objUpdate->paid_date,
						'notification' => $objUpdate->notification,
						'network_type' => $objUpdate->network_type,
						'api_ref_id' => $objUpdate->api_ref_id,
						'wp_ref_id' => $objUpdate->sr_no,
						'entry_time' => now(),
					];
					$updateConfirmed = WithdrawalConfirmed::insertGetId($arrInsert);
					if ($updateConfirmed) {
						try {

							if ($objUpdate->withdraw_type == 2) {
								$type = 'Working';
							} else if ($objUpdate->withdraw_type == 3) {
								$type = 'Roi';
							} else {
								$type = 'Self';
							}
							$email = $objUsers->email;
							$user_id = $objUsers->user_id;
							$subject = " Withdrawal Paid! Check Your Wallet!";
							$pagename = "emails.paid_payment";
							// $link = '<a href="https://www.blockchain.com/btc/address/".$address>'.$address.'</a>';
							$address = "<a href='https://www.blockchain.com/btc/address/" . $objPendings->to_address . "'>
                               " . $objPendings->to_address . "
                             </a>";
							$data = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id, 'amount' => $objUpdate->amount, 'address' => $address, 'type' => $type);
							$email = $email;
							$mail = sendMail($data, $email, $subject);
						} catch (\Exception $e) {
							dd($e);
							return true;
							//return array('code'=>$this->statuscode[200]['code'],'message'=>'Withdrawal request successfull');
						}
						return array('code' => $this->statuscode[200]['code'], 'message' => 'Withdrawal request successfull');
					} else {
						return array('code' => $this->statuscode[404]['code'], 'message' => 'Error occured while adding record in withdrawal confirmed');
					}
				} else {
					return array('code' => $this->statuscode[404]['code'], 'message' => 'Error occured while updating record in withdrawal pending');
				}
			} else {
				return array('code' => $this->statuscode[404]['code'], 'message' => 'Coin payment api has failed');
			}
		} else {
			return array('code' => $this->statuscode[404]['code'], 'message' => 'Invalid Amount. Amount must be greater than 0');
		}
	}

	/**
	 * withdrawal transaction request and save withdrawl data without api
	 *  $arrInput (array)
	 * @return void
	 */
	private function saveWithdrawlData($arrInput)
	{

		$coinpayment_id = 0;
		$arrUpdate = [
			'status' => 1,
			'remark' => $arrInput['remark'],
			'api_ref_id' => $coinpayment_id,
		];
		$updatePending = WithdrawPending::where('sr_no', $arrInput['srno'])->update($arrUpdate);
		if (!empty($updatePending)) {
			$objUpdate = WithdrawPending::where('sr_no', $arrInput['srno'])->first();
			$objUser = User::where('id', $objUpdate->id)->first();
			//dd($objUser);
			$arrInsert = [
				'transaction_hash' => $objUpdate->transaction_hash,
				'id' => $objUpdate->id,
				'deduction' => $objUpdate->deduction,
				'withdraw_type' => $objUpdate->withdraw_type,
				'amount' => $objUpdate->amount,
				'transaction_fee' => $objUpdate->transaction_fee,
				'from_address' => $objUpdate->from_address,
				'to_address' => $objUser->btc_address,
				'remark' => $objUpdate->remark,
				'status' => $objUpdate->status,
				'paid_date' => $objUpdate->paid_date,
				'notification' => $objUpdate->notification,
				'network_type' => $objUpdate->network_type,
				'api_ref_id' => $objUpdate->api_ref_id,
				'wp_ref_id' => $objUpdate->sr_no,
				'entry_time' => now(),
				'account_no' => $objUser->account_no,
				'holder_name' => $objUser->holder_name,
				'bank_name' => $objUser->bank_name,
				'branch_name' => $objUser->branch_name,
				'pan_no' => $objUser->pan_no,
				'ifsc_code' => $objUser->ifsc_code,
			];
			$updateConfirmed = WithdrawalConfirmed::insertGetId($arrInsert);

			$email = $objUser->email;
			$user_id = $objUser->user_id;
			$subject = " Withdrawal request approved!";
			$pagename = "emails.withdraw_approved";
			// $link = '<a href="https://www.blockchain.com/btc/address/".$address>'.$address.'</a>';
			/*$address = "<a href='https://www.blockchain.com/btc/address/".$objPendings->to_address."'>
	               ".$objPendings->to_address."
*/
			$data = array('pagename' => $pagename, 'email' => $email, 'username' => $user_id);
			$email = $email;
			$mail = sendMail($data, $email, $subject);

			if ($updateConfirmed) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Withdrawal request successfull', '');
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Error occured while adding record in withdrawal confirmed', '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Error occured while updating record in withdrawal pending', '');
		}
	}

	/**
	 *  get direct income report
	 *  $arrInput (array)
	 * @return void
	 */
	public function getDirectIncome(Request $request)
	{
		$arrInput = $request->all();
		ini_set('memory_limit', '-1');
		$query = DirectIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_directincome.fromUserId')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_directincome.toUserId')
			->select('tbl_directincome.amount','tbl_directincome.percentage','tbl_directincome.on_amount','tbl_directincome.laps_amount', 'tbl_directincome.topup_wallet_amount as purchase_wallet_amount', 'tbl_directincome.working_wallet_amount', 'tbl_directincome.status', 'tbl_directincome.entry_time','tbl_directincome.remark', 'tu.user_id as from_user_id', 'tu1.user_id as to_user_id', 'tu.fullname as from_fullname', 'tu1.fullname as to_fullname');
		if (isset($arrInput['id'])) {
			$query = $query->where('tu1.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_directincome.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {

		// 	$fields = getTableColumns('tbl_directincome');
		// 	$search = $arrInput['search']['value'];
		// 	$query = $query->where(function ($query) use ($fields, $search) {
		// 		foreach ($fields as $field) {
		// 			$query->orWhere('tbl_directincome.' . $field, 'LIKE', '%' . $search . '%');
		// 		}
		// 		$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
		// 			->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%');

		// 	});
		// }

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu1.user_id as to_user_id', 'tu1.fullname as to_fullname', 'tu.user_id as from_user_id', 'tu.fullname as from_fullname', 'tbl_directincome.amount', 'tbl_directincome.status', 'tbl_directincome.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$totalRecord = $query->count('tbl_directincome.id');
		$query = $query->orderBy('tbl_directincome.id', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
		// dd($arrDirectInc);
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getfreedomclubdata(Request $request)
	{
		$arrInput = $request->all();

		$query = User::select('tbl_users.user_id', 'tbl_users.fullname', 'td.entry_time', 'td.amount', 'td.rank')
			->join('tbl_freedom_club_income as td', 'td.user_id', '=', 'tbl_users.id');


		if (isset($arrInput['id'])) {
			$query = $query->where('tbl_users.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(td.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
		// 	 $fields = getTableColumns('tbl_freedom_club_income');
		// 	$search = $arrInput['search']['value'];
		// 	$query = $query->where(function ($query) use ($fields, $search) {
		// 		foreach ($fields as $field) {
		// 			$query->orWhere('tbl_freedom_club_income.' . $field, 'LIKE', '%' . $search . '%');
		// 		}
		// 		$query->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%')
		// 			->orWhere('tbl_users.user_id', 'LIKE', '%' . $search . '%');

		// 	});
		// }

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tbl_users.user_id', 'tbl_users.fullname', 'td.amount', 'td.rank', 'td.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}

		$query = $query->orderBy('td.entry_time', 'desc');

		$totalRecord = $query->count('td.user_id');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getdailybussinessdata(Request $request)
	{
		$arrInput = $request->all();

		$query = dailybusiness::select('srno', 'total_business', 'entry_time');
		if (isset($arrInput['id'])) {
			$query = $query->where('srno', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('dailybusiness');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('dailybusiness.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('srno', 'LIKE', '%' . $search . '%')
					->orWhere('total_business', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('srno');
		$query = $query->orderBy('srno', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getsupermatchingbonus(Request $request)
	{
		$arrInput = $request->all();

		$query = SupperMatchingIncome::select('tu.user_id', 'tu.fullname', 'tbl_supper_matching_bonus_income.entry_time', 'tbl_supper_matching_bonus_income.amount', 'tbl_supper_matching_bonus_income.pin', 'tbl_supper_matching_bonus_income.rank')
			->join('tbl_users as tu', 'tu.id', '=', 'tbl_supper_matching_bonus_income.id');
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_supper_matching_bonus_income.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
		// 	 $fields = getTableColumns('tbl_supper_matching_bonus_income');
		// 	$search = $arrInput['search']['value'];
		// 	$query = $query->where(function ($query) use ($fields, $search) {
		// 		foreach ($fields as $field) {
		// 			$query->orWhere('tbl_supper_matching_bonus_income.' . $field, 'LIKE', '%' . $search . '%');
		// 		}
		// 		$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
		// 			->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');

		// 	});
		// }

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id', 'tu.fullname', 'tbl_supper_matching_bonus_income.entry_time', 'tbl_supper_matching_bonus_income.amount', 'tbl_supper_matching_bonus_income.pin', 'tbl_supper_matching_bonus_income.rank');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}


		$query = $query->orderBy('tbl_supper_matching_bonus_income.entry_time', 'DESC');
		$totalRecord = $query->count('tbl_supper_matching_bonus_income.id');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 *  get franchise income report
	 *  $arrInput (array)
	 * @return void
	 */
	public function getFranchiseIncome(Request $request)
	{
		$arrInput = $request->all();

		$query = FranchiseIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_franchise_income.from_user_id')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_franchise_income.to_user_id')
			->select('tbl_franchise_income.entry_time', 'tbl_franchise_income.amount', 'tbl_franchise_income.percentage', 'tbl_franchise_income.on_amount', 'tbl_franchise_income.pin', 'tu.user_id as from_user_id', 'tu1.user_id as to_user_id', 'tu.fullname as from_fullname', 'tu1.fullname as to_fullname');
		if (isset($arrInput['from_user_id'])) {
			$query = $query->where('tu.user_id', $arrInput['from_user_id']);
		}
		if (isset($arrInput['to_user_id'])) {
			$query = $query->where('tu1.user_id', $arrInput['to_user_id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_franchise_income.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_franchise_income');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_franchise_income.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tbl_franchise_income.id');
		$query = $query->orderBy('tbl_franchise_income.id', 'desc');
		// $totalRecord = $query->count();
		$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrDirectInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 *  get Binary income report
	 *  $arrInput (array)
	 * @return void
	 */
	public function getBinaryIncome(Request $request)
	{
		$arrInput = $request->all();

		$query = BinaryIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_payout_history.user_id')
			->select('tbl_payout_history.match_bv','tbl_payout_history.match_bv','tbl_payout_history.remark','tbl_payout_history.pin', 'tbl_payout_history.percentage', 'tbl_payout_history.entry_time', 'tbl_payout_history.laps_bv', 'tbl_payout_history.laps_amount', 'tbl_payout_history.left_bv', 'tbl_payout_history.right_bv', 'tbl_payout_history.amount', 'tu.user_id', 'tu.fullname');

		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['pin'])) {
			$query = $query->where('tbl_payout_history.pin', $arrInput['pin']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_payout_history.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
		// 	//searching loops on fields
		// 	$fields = getTableColumns('tbl_payout_history');
		// 	$search = $arrInput['search']['value'];
		// 	$query = $query->where(function ($query) use ($fields, $search) {
		// 		foreach ($fields as $field) {
		// 			$query->orWhere('tbl_payout_history.' . $field, 'LIKE', '%' . $search . '%');
		// 		}
		// 		$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
		// 	});
		// }

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id','tbl_payout_history.pin', 'tbl_payout_history.amount', 'tbl_payout_history.left_bv', 'tbl_payout_history.right_bv', 'tbl_payout_history.laps_amount', 'tbl_payout_history.rank', 'tbl_payout_history.percentage', 'tbl_payout_history.laps_bv', 'tbl_payout_history.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}


		$query = $query->orderBy('tbl_payout_history.id', 'desc');
		$totalRecord = $query->count('tbl_payout_history.user_id');
		// $totalRecord = $query->count();
		$arrBinaryInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
		// dd($arrBinaryInc);
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrBinaryInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 *  get Level income report
	 *  $arrInput (array)
	 * @return void
	 */
	public function getLevelIncome(Request $request)
	{
		$arrInput = $request->all();

		$query = LevelIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_level_income.fromUserId')->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_income.toUserId')->select('tbl_level_income.*', 'tu.user_id as from_user_id', 'tu1.user_id as to_user_id', 'tu.fullname as from_fullname', 'tu1.fullname as to_fullname')->where('tbl_level_income.status', 1);

		/*$query = LevelIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_level_income.fromUserId')
							  ->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_income.toUserId')
							  ->select('tbl_level_income.amount', 'tbl_level_income.level', 'tbl_level_income.entry_time', 'tu.user_id as from_user_id', 'tu1.user_id as to_user_id', 	'tu.fullname as from_fullname', 'tu1.fullname as to_fullname')
							  ->where('tbl_level_income.status', 1);  */


		if (isset($arrInput['id'])) {
			$query = $query->where('tu1.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_income.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_level_income');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_level_income.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tbl_level_income.id');
		$query = $query->orderBy('tbl_level_income.id', 'desc');
		// $totalRecord = $query->count();
		$arrLevelInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
		// dd($arrLevelInc);

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrLevelInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 * Level income report.
	 *
	 * @return void
	 */
	public function LevelIncomeRoiReport(Request $request)
	{

		$arrInput = $request->all();
		$Incomedata = LevelIncomeRoi::join('tbl_users as tu', 'tu.id', '=', 'tbl_level_income_roi.toUserId')->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_income_roi.fromUserId')
			->join('tbl_product as tp', 'tp.id', '=', 'tbl_level_income_roi.type')
			->select('tbl_level_income_roi.*', 'tu.user_id as to_user_id', 'tu1.user_id as from_user_id', 'tu.fullname as to_fullname', 'tu1.fullname as from_fullname', 'tp.name as plan_name', DB::raw('(CASE  WHEN tbl_level_income_roi.status = 1 THEN "Paid" END ) as status'))
			->where([['tbl_level_income_roi.status', '=', 1]])
			->orderBy('tbl_level_income_roi.entry_time', 'desc');
		if (isset($arrInput['id'])) {
			$Incomedata = $Incomedata->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$Incomedata = $Incomedata->whereBetween(DB::raw("DATE_FORMAT(tbl_level_income_roi.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_level_income_roi');
			$search = $request->input('search')['value'];
			$Incomedata = $Incomedata->where(function ($Incomedata) use ($fields, $search) {
				foreach ($fields as $field) {
					$Incomedata->orWhere('tbl_level_income_roi.' . $field, 'LIKE', '%' . $search . '%');
				}
				$Incomedata->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tp.name', 'LIKE', '%' . $search . '%')
					->orWhere(DB::raw('(CASE  WHEN tbl_level_income_roi.status = 1 THEN "Paid" END )'), 'LIKE', '%' . $search . '%');
			});
		}

		$totalRecord = $Incomedata->count();
		$arrPendings = $Incomedata->skip($request->input('start'))->take($request->input('length'))->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;

		if (!empty($arrPendings) && count($arrPendings) != '0') {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function UplineIncomeReport(Request $request)
	{

		$arrInput = $request->all();
		$Incomedata = UplineIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_upline_income.toUserId')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_upline_income.fromUserId')
			->join('tbl_product as tp', 'tp.id', '=', 'tbl_upline_income.type')
			->select('tbl_upline_income.*', 'tu.user_id as to_user_id', 'tu1.user_id as from_user_id', 'tu.fullname as to_fullname', 'tu1.fullname as from_fullname', 'tp.name as plan_name', DB::raw('(CASE  WHEN tbl_upline_income.status = 1 THEN "Paid" END ) as status'))
			->where([['tbl_upline_income.status', '=', 1]])
			->orderBy('tbl_upline_income.entry_time', 'desc');


		/*$Incomedata = UplineIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_upline_income.toUserId')
					  ->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_upline_income.fromUserId')
					  ->join('tbl_product as tp', 'tp.id', '=', 'tbl_upline_income.type')
					  ->select('tbl_upline_income.entry_time', 'tbl_upline_income.amount','tbl_upline_income.level','tu.user_id as to_user_id', 'tu1.user_id as from_user_id', 'tu.fullname as to_fullname', 'tu1.fullname as from_fullname', 'tp.name as plan_name', DB::raw('(CASE  WHEN tbl_upline_income.status = 1 THEN "Paid" END ) as status'))
					  ->where([['tbl_upline_income.status', '=', 1]])
					  ->orderBy('tbl_upline_income.entry_time', 'desc'); */



		if (isset($arrInput['id'])) {
			$Incomedata = $Incomedata->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$Incomedata = $Incomedata->whereBetween(DB::raw("DATE_FORMAT(tbl_upline_income.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_upline_income');
			$search = $request->input('search')['value'];
			$Incomedata = $Incomedata->where(function ($Incomedata) use ($fields, $search) {
				foreach ($fields as $field) {
					$Incomedata->orWhere('tbl_upline_income.' . $field, 'LIKE', '%' . $search . '%');
				}
				$Incomedata->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tp.name', 'LIKE', '%' . $search . '%')
					->orWhere(DB::raw('(CASE  WHEN tbl_upline_income.status = 1 THEN "Paid" END )'), 'LIKE', '%' . $search . '%');
			});
		}

		$totalRecord = $Incomedata->count();
		$arrPendings = $Incomedata->skip($request->input('start'))->take($request->input('length'))->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;

		if (!empty($arrPendings) && count($arrPendings) != '0') {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	/**
	 * [Award list]
	 * @param  Request $request [token alpha-num]
	 * @return [Array]
	 */
	public function useraward(Request $request)
	{

		$arrInput = $request->all();
		$query = AwardWinner::join('tbl_awards_list as tal', 'tal.award_id', 'tbl_award_winner.award_id')
			->join('tbl_users as tu', 'tu.id', 'tbl_award_winner.user_id')
			->select('tal.*', 'tbl_award_winner.*', 'tu.user_id', 'tu.fullname', 'tu.mobile')
			->orderBy('tbl_award_winner.entry_time', 'desc');
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_award_winner.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
			//searching loops on tbl_award_winner
			$fields = getTableColumns('tbl_award_winner');
			$search = $request->input('search')['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_award_winner.' . $field, 'LIKE', '%' . $search . '%');
				}
			});
		}
		$totalRecord = $query->count();
		$arrPendings = $query->skip($request->input('start'))->take($request->input('length'))->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;
		if (!empty($arrPendings) && count($arrPendings) != '0') {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found Successfully!', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
		}
	}
	/**
	 *  get leadership income report
	 *  $arrInput (array)
	 * @return void
	 */

	public function getLeadershipIncome(Request $request)
	{
		$arrInput = $request->all();

		$query = LeadershipIncome::join('tbl_users as tu', 'tu.id', '=', 'tbl_leadership_income.fromUserId')
			->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_leadership_income.toUserId')
			->join('tbl_product as tp', 'tp.id', '=', 'tbl_leadership_income.type')
			->select('tbl_leadership_income.*', 'tu.user_id as fromUserId', 'tp.name as type', 'tu1.user_id as toUserId', 'tu.fullname as from_fullname', 'tu1.fullname as to_fullname', DB::raw('(CASE  WHEN tbl_leadership_income.status = 1 THEN "Paid" END ) as status'));

		if (isset($arrInput['id'])) {
			$query = $query->where('tu1.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_leadership_income.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_leadership_income');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_leadership_income.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
					->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tbl_leadership_income.id');
		$query = $query->orderBy('tbl_leadership_income.id', 'desc');
		// $totalRecord = $query->count();
		$arrLevelInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrLevelInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 *  get Invoice report
	 *  $arrInput (array)
	 *  @return void
	 */
	public function getInvoice(Request $request)
	{
		$arrInput = $request->all();

		$query = Invoice::join('tbl_users as tu', 'tu.id', '=', 'tbl_invoices.id')
			->select('tbl_invoices.*', 'tu.user_id', 'tu.fullname');

		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['product_url'])) {
			if ($arrInput['product_url'] == 'blockchain') {
				$query = $query->whereIn('tbl_invoices.product_url', ['blockchain-local', 'blockchain-online']);
			} else {
				$query = $query->where('tbl_invoices.product_url', $arrInput['product_url']);
			}
		}
		if (isset($arrInput['status'])) {
			$query = $query->where('tbl_invoices.status', $arrInput['status']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_invoices.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_invoices');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_invoices.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tbl_invoices.srno');
		$query = $query->orderBy('tbl_invoices.srno', 'desc');
		// $totalRecord = $query->count();
		$arrInvoice = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrInvoice;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 * get reports of auto withdrwal pending
	 *
	 * @return void
	 */
	public function getAutoWithdrwalPending(Request $request)
	{
		$arrInput = $request->all();

		//$objProSettings = $this->commonController->getProjectSettings();
		$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
			->select('tbl_withdrwal_pending.*', 'tu.user_id', 'tu.fullname', DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" ELSE "" END) as status'))
			->where('tbl_withdrwal_pending.status', 0);
		//->where('tbl_withdrwal_pending.from_wallet','0');
		/*if(!empty($objProSettings->withdrawal_network_type)){
	            $query = $query->whereIn('network_type',explode(',',$objProSettings->withdrawal_network_type));
*/
		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_pending');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tbl_withdrwal_pending.sr_no');
		$query = $query->orderBy('tbl_withdrwal_pending.sr_no', 'desc');
		// $totalRecord = $query->count();
		$arrPendings = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 * get reports of auto withdrwal confirm
	 *
	 * @return void
	 */
	public function getAutoWithdrwalConfirmed(Request $request)
	{
		$arrInput = $request->all();

		//$objProSettings = $this->commonController->getProjectSettings();
		$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
			->select('tbl_withdrwal_pending.*', 'tu.user_id', 'tu.fullname', DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" ELSE "" END) as status'))
			->where('tbl_withdrwal_pending.status', 1)
			->where('tbl_withdrwal_pending.from_wallet', 1);
		/*if(!empty($objProSettings->withdrawal_network_type)){
			            $query = $query->whereIn('network_type',explode(',',$objProSettings->withdrawal_network_type));
		*/



		if (isset($arrInput['id'])) {
			$query = $query->where('tu.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_pending');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}
		$totalRecord = $query->count('tbl_withdrwal_pending.sr_no');
		$query = $query->orderBy('tbl_withdrwal_pending.sr_no', 'desc');
		// $totalRecord = $query->count();
		$arrPendings = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 * auto withdrawal transaction request
	 *
	 * @return void
	 */
	public function autoWithdrwalRequest(Request $request)
	{
		$arrInput = $request->all();

		$rules = array(
			'srno' => 'required',
			'remark' => 'required',
		);
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else {
			$objPendings = WithdrawPending::where('sr_no', trim($arrInput['srno']))->first();
			if (!empty($objPendings)) {
				$transaction_hash = make_blockchain_payment($objPendings->to_address, $objPendings->amount);
				if (!empty($transaction_hash) && $transaction_hash != 0) {
					$arrUpdate = [
						'transaction_hash' => $transaction_hash,
						'remark' => $arrInput['remark'],
						'status' => 1,
						'from_wallet' => '1',
						'paid_date' => now(),
					];
					$updatePending = WithdrawPending::where('sr_no', $objPendings->sr_no)->where('from_wallet', '1')->update($arrUpdate);

					if (!empty($updatePending)) {
						$objPendings1 = WithdrawPending::where('sr_no', $objPendings->sr_no)->first();
						$arrConfirmInsert = [
							'transaction_hash' => $objPendings1->transaction_hash,
							'id' => $objPendings1->id,
							'amount' => $objPendings1->amount,
							'transaction_fee' => $objPendings1->transaction_fee,
							'from_address' => $objPendings1->from_address,
							'to_address' => $objPendings1->to_address,
							'remark' => $objPendings1->remark,
							'status' => $objPendings1->status,
							'paid_date' => $objPendings1->paid_date,
							'notification' => $objPendings1->notification,
							'network_type' => $objPendings1->network_type,
							'api_ref_id' => $objPendings1->api_ref_id,
							'wp_ref_id' => $objPendings1->sr_no,
							'entry_time' => now(),
						];
						$updateConfirmed = WithdrawalConfirmed::insertGetId($arrConfirmInsert);
						return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Auto payment done successfully', '');
					} else {
						return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Unable to update payment status', '');
					}
				} else {
					return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Unable to make payment', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
			}
		}
	}

	/**
	 * Function to save external address
	 *
	 * @param $request : HTTP Request Object
	 *
	 */
	public function addBTCAddress(Request $request)
	{
		$arrInput = $request->all();

		$rules = array('address' => 'required');
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Address field is required', $message);
		}
		$req = new Request;
		$req->merge(['network_type' => 'BTC']);
		$req->merge(['address' => $arrInput['address']]);
		$op = $this->settings->checkAddresses($req);
		if ($op->original['code'] != '200') {
			$arrStatus = Response::HTTP_BAD_REQUEST;
			$arrCode = Response::$statusTexts[$arrStatus];
			$strMessage = 'Invalid BTC address';
			return sendResponse($arrStatus, $arrCode, $strMessage, '');
		}

		$externalAddr = ExternalAddress::where('address', $arrInput['address'])->first();
		if (empty($externalAddr)) {
			$reserverAddr = ReservedAddress::where('address', $arrInput['address'])->first();
			if (!empty($reserverAddr)) {
				$arrStatus = Response::HTTP_BAD_REQUEST;
				$arrCode = Response::$statusTexts[$arrStatus];
				$strMessage = 'BTC address is already exists';
				return sendResponse($arrStatus, $arrCode, $strMessage, '');
			}
			$newAddr = new ReservedAddress;
			$random_id = $this->uniqueRandomIdForBTCAddr();
			$newAddr->invoice_id = $random_id;
			$newAddr->address = $arrInput['address'];
			$newAddr->remark = $random_id;
			$newAddr->admin_remark = $arrInput['remark'];
			$newAddr->save();
			$arrStatus = Response::HTTP_OK;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'BTC address added Successfully!';
			return sendResponse($arrStatus, $arrCode, $arrMessage, []);
		} else {
			$arrStatus = Response::HTTP_BAD_REQUEST;
			$arrCode = Response::$statusTexts[$arrStatus];
			$strMessage = 'BTC address is already exists';
			return sendResponse($arrStatus, $arrCode, $strMessage, '');
		}
	}
	/**
	 * Function to generate the unique random id for reserved address
	 *
	 */
	function uniqueRandomIdForBTCAddr()
	{
		$randomId = substr(number_format(time() * rand(), 0, '', ''), 0, '10');
		$random = ReservedAddress::where('invoice_id', $randomId)->first();
		if (!empty($random)) {
			$this->uniqueRandomIdForBTCAddr();
		}
		return $randomId;
	}
	/**
	 * Function to generate the unique random id for reserved address
	 *
	 */
	/*function verifyWithdrwal() {
		$randomId = substr(number_format(time() * rand(), 0, '', ''), 0, '10');
		$random = ReservedAddress::where('invoice_id', $randomId)->first();
		if (!empty($random)) {
			$this->uniqueRandomIdForBTCAddr();
		}
		return $randomId;
	}*/



	public function WithdrwalRequestReject(Request $request)
	{
		$arrInput = $request->all();

		$rules = array(
			'sr_no'  => 'required',
			'remark' => 'required'
		);
		$validator = Validator::make($arrInput, $rules);
		if ($validator->fails()) {
			$message = $validator->errors();

			$arrStatus = Response::HTTP_BAD_REQUEST;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = $message->request_id;
			return sendResponse($arrStatus, $arrCode, $arrMessage, []);
		} else {
			$pending = WithdrawPending::select('id', 'amount', 'deduction')->where('sr_no', $request->sr_no)->first();
			$arrUpdate['status'] = 2;
			$arrUpdate['remark'] = $request->remark;
			$updatePending = WithdrawPending::where('sr_no', $request->sr_no)->update($arrUpdate);

			$dash['working_wallet'] = DB::RAW('working_wallet +' . ($pending->amount + $pending->deduction));

			$updtDash = Dashboard::where('id', $pending->id)->update($dash);
			if ($updatePending) {
				$arrStatus = Response::HTTP_OK;
				$arrCode = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Request rejected Successfully!';
				return sendResponse($arrStatus, $arrCode, $arrMessage, []);
			}
		}
	}

	/**
	 * approve Withdraw Request
	 *
	 * @return void
	 */
	public function approveWithdraw(Request $request)
	{
		$arrInput = $request->all();

		$rules = array(
			'id'                => 'required',
			'remark'            => 'required',
		);
		$validator = Validator::make($arrInput, $rules);
		//if the validator fails, redirect back to the form

		$adminOtpStatusData = verifyAdminOtpStatus::select('withdraw_verfied_otp_update_status')->first();
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		} else if ($adminOtpStatusData->withdraw_verfied_otp_update_status == 1) {
			if (!isset($arrInput['otp']) && empty($arrInput['otp'])) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
			}
			$arrInput['user_id'] = Auth::User()->id;
			$arrInput['remark'] = 'admin withdraw';
			$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
			if (!empty($verify_otp)) {
				if ($verify_otp['status'] == 200) {
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
			}
		}
		$updateRow = WithdrawPending::where('sr_no', $request->id)->update(['status' => 1, 'remark' => $request->remark]);

		if (!empty($updateRow)) {
			//fetch updated record with status 1
			$arrWithAmt = WithdrawPending::where('sr_no', $request->id)->where('status', 1)->first();
			$arrTemp = [];
			$isExistId = WithdrawalConfirmed::where('wp_ref_id', $arrWithAmt->sr_no)->count('sr_no');
			if ($isExistId == 0) {
				$withDrawdata = array();
				$withDrawdata['transaction_hash'] = $arrWithAmt->transaction_hash;
				$withDrawdata['id'] = $arrWithAmt->id;
				$withDrawdata['wp_ref_id'] = $arrWithAmt->sr_no;
				$withDrawdata['amount'] = $arrWithAmt->amount;
				$withDrawdata['withdraw_type'] = $arrWithAmt->withdraw_type;
				$withDrawdata['deduction'] = $arrWithAmt->deduction;
				$withDrawdata['to_address'] = $arrWithAmt->to_address;
				$withDrawdata['remark'] = $arrInput['remark'];
				$withDrawdata['status'] = 1;
				$withDrawdata['paid_date'] = $arrWithAmt->paid_date;
				$withDrawdata['notification'] = $arrWithAmt->notification;
				$withDrawdata['network_type'] = $arrWithAmt->network_type;
				$withDrawdata['entry_time'] = $this->today;
				$withDrawdata['api_ref_id'] = $arrWithAmt->api_ref_id;
				$withDrawdata['ip_address'] = getIPAddress();
				//insert paid record in tbl_withdrwal_history
				$store = WithdrawalConfirmed::insert($withDrawdata);
			}
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Withdrawal paid successfully.', '');
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Someting went wrong. Please try later.', '');
		}
	}
	public function rejectedWithdrawalReport(Request $request)
	{
		$arrInput = $request->all();

		$query = WithdrawPending::join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
			->leftjoin('tbl_country_new as cn', 'tu.country', '=', 'cn.iso_code')
			->select('tbl_withdrwal_pending.amount', 'tbl_withdrwal_pending.to_address', 'tbl_withdrwal_pending.deduction', 'tbl_withdrwal_pending.entry_time', 'tbl_withdrwal_pending.network_type', 'tu.paypal_address', 'tu.user_id', 'tu.fullname', 'tu.btc_address', DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" WHEN 2 THEN "Rejected" END) as status'), DB::raw('(CASE tbl_withdrwal_pending.withdraw_type WHEN 2 THEN "Working" WHEN 3 THEN "Roi" WHEN 4 THEN "Self Working" WHEN  5 THEN "Self Roi" WHEN 7 THEN "HSCC-Bonus" WHEN  8 THEN "Passive Income" ELSE "" END) as withdraw_type'), 'cn.country', 'tu.perfect_money_address')
			->where('tbl_withdrwal_pending.status', 2);

		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		if (isset($arrInput['user_id'])) {
			$query = $query->where('tu.user_id', $arrInput['user_id']);
		}
		if (isset($arrInput['country'])) {
			$query = $query->where('tu.country', $arrInput['country']);
		}
		if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
			//searching loops on fields
			$fields = getTableColumns('tbl_withdrwal_pending');
			$search = $arrInput['search']['value'];
			$query = $query->where(function ($query) use ($fields, $search) {
				foreach ($fields as $field) {
					$query->orWhere('tbl_withdrwal_pending.' . $field, 'LIKE', '%' . $search . '%');
				}
				$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
			});
		}


		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id', 'tu.fullname', 'tbl_withdrwal_pending.amount', 'tbl_withdrwal_pending.deduction', DB::raw('(tbl_withdrwal_pending.amount + tbl_withdrwal_pending.deduction) as total_amount'), 'tbl_withdrwal_pending.network_type', 'tbl_withdrwal_pending.to_address', DB::raw('(CASE tbl_withdrwal_pending.status WHEN 0 THEN "Unpaid" WHEN 1 THEN "Paid" WHEN 2 THEN "Rejected" END) as status'), 'tbl_withdrwal_pending.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}


		$query = $query->orderBy('tbl_withdrwal_pending.entry_time', 'desc');

		/*if(isset($arrInput['start']) && isset($arrInput['length'])){
			            $arrData = setPaginate($query,$arrInput['start'],$arrInput['length']);
			        } else {
			            $arrData = $query->get();
			        }
		*/
		$totalRecord = $query->count();
		$arrPendings = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		$totalSummary = $this->getWithdrwalPendingTotals($request, 2);

		$arrData['total_amount'] = $totalSummary->amount + $totalSummary->deduction;
		$arrData['total_net_amount'] = $totalSummary->amount;
		$arrData['total_deduction'] = $totalSummary->deduction;
		$arrData['total_transaction_fee'] = $totalSummary->transaction_fee;
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrPendings;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function withdrawalRequestApprove(Request $request) {
        $rules = array(
			'request_id'    => 'required',
			'remark'        => 'required',
			'otp' => 'required|min:10|max:10',
			//'status' => 'required|numeric|min:0|max:2',
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			$err = '';
			foreach ($message->all() as $error) {
				$err = $err . " " . $error;
			}
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		}

		$otpstatus = verifyAdminOtpStatus::select('withdraw_verfied_otp_update_status')->first();
		if($otpstatus->withdraw_verfied_otp_update_status == 1)
		{
			if(!empty($request->otp))
			{
				$arrInput['user_id'] = Auth::User()->id;
				$arrInput['remark'] = 'admin withdraw';
				$arrInput['otp'] = $request->otp;
				$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
				if (!empty($verify_otp)) {
					if ($verify_otp['status'] == 200) {
					} else {
						return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
					}
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
				}
			}
			else{
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Opt required', '');
			}

		}

        $request->request_id = array_unique($request->request_id);
        //update status and remark in tbl_withdrawal_amt
        $updateRow = WithdrawPending::whereIn('sr_no',$request->request_id)->update(['status'=>1, 'remark'=>$request->remark]);

        if(!empty($updateRow)) {
            //fetch updated record with status 1
            $arrWithAmt = WithdrawPending::whereIn('sr_no',$request->request_id)->where('status',1)->get();

            $arrTemp = [];
            foreach ($arrWithAmt as $value) {
                $isExistId = WithdrawalConfirmed::where('wp_ref_id', $value->sr_no)->count('sr_no');
	            if($isExistId == 0){
	                $withDrawdata = array();
					$withDrawdata['transaction_hash'] = $value->transaction_hash;
					$withDrawdata['id'] = $value->id;
					$withDrawdata['wp_ref_id'] = $value->sr_no;
					$withDrawdata['amount'] = $value->amount;
					$withDrawdata['deduction'] = $value->deduction;
					$withDrawdata['to_address'] = $value->to_address;
					$withDrawdata['remark'] = $request->remark;
					$withDrawdata['status'] = 1;
					$withDrawdata['paid_date'] = $value->paid_date;
					$withDrawdata['notification'] = $value->notification;
					$withDrawdata['network_type'] = $value->network_type;
					$withDrawdata['withdraw_type'] = $value->withdraw_type;
					$withDrawdata['entry_time'] = $this->today;
					$withDrawdata['api_ref_id'] = $value->api_ref_id;
	                //insert paid record in tbl_withdrwal_history
	                $store = WithdrawalConfirmed::insert($withDrawdata);
	            }
            }
            return sendresponse($this->statuscode[200]['code'],$this->statuscode[200]['status'],'Withdrawal paid successfully','');
        }

            //if($store) {
            /*} else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Someting went wrong. Please try later.','');
            }*/
        else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Someting went wrong. Please try later.','');
        }
    }

	public function withdrawalRequestApproveold(Request $request)
	{
		$rules = array(
			'request_id'    => 'required|numeric',
			'remark'        => 'required|alpha_num',
			'otp' => 'required|min:10|max:10|numeric',
			//'status' => 'required|numeric|min:0|max:2',
		);
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			$message = $validator->errors();
			$err = '';
			foreach ($message->all() as $error) {
				$err = $err . " " . $error;
			}
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		}
		$arrInput['user_id'] = Auth::User()->id;
		$arrInput['remark'] = 'admin withdraw';
		$arrInput['otp'] = $request->otp;
		$verify_otp = verify_Admin_Withdraw_Otp($arrInput);
		if (!empty($verify_otp)) {
			if ($verify_otp['status'] == 200) {
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
		}



		$request->request_id = array_unique($request->request_id);
		//update status and remark in tbl_withdrawal_amt
		$updateRow = WithdrawPending::whereIn('sr_no', $request->request_id)->update(['status' => 1, 'remark' => $request->remark]);

		if (!empty($updateRow)) {
			//fetch updated record with status 1
			$arrWithAmt = WithdrawPending::whereIn('sr_no', $request->request_id)->where('status', 1)->get();

			$arrTemp = [];
			foreach ($arrWithAmt as $value) {
				$isExistId = WithdrawalConfirmed::where('wp_ref_id', $value->sr_no)->count('sr_no');
				if ($isExistId == 0) {
					$withDrawdata = array();
					$withDrawdata['transaction_hash'] = $value->transaction_hash;
					$withDrawdata['id'] = $value->id;
					$withDrawdata['wp_ref_id'] = $value->sr_no;
					$withDrawdata['amount'] = $value->amount;
					$withDrawdata['deduction'] = $value->deduction;
					$withDrawdata['to_address'] = $value->to_address;
					$withDrawdata['remark'] = $request->remark;
					$withDrawdata['status'] = 1;
					$withDrawdata['paid_date'] = $value->paid_date;
					$withDrawdata['notification'] = $value->notification;
					$withDrawdata['network_type'] = $value->network_type;
					$withDrawdata['entry_time'] = $this->today;
					$withDrawdata['api_ref_id'] = $value->api_ref_id;
					//insert paid record in tbl_withdrwal_history
					$store = WithdrawalConfirmed::insert($withDrawdata);
				}
			}
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Withdrawal paid successfully', '');
		}

		//if($store) {
		/*} else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Someting went wrong. Please try later.','');
            }*/ else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Someting went wrong. Please try later.', '');
		}
	}
	public function getWithdrawalSummary(Request $request)
	{
		$query = WithdrawPending::selectRaw("network_type as currency,SUM(amount) as total_amount, SUM(deduction) as deduction");

		if (isset($request->status) && $request->status != "") {
			$query = $query->where('status', $request->status);
		} else {
			$query = $query->where('status', 0);
		}

		if (isset($request->verify_status) && $request->verify_status != "") {
			$query = $query->where('verify', $request->verify_status);
		} else {
			$query = $query->where('verify', 0);
		}

		$data = $query->groupBy('network_type')->get();
		if (!empty($data)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $data);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
		}
	}
    public function getQualifiedUsersBlade(){
        return view('admin.BinaryQualifiedUserReport');
    }

	public function getBinaryQualifiedUsers(Request $request)
	{
		$arrInput = $request->all();

		$query = QualifiedUserList::join('tbl_users as tu', 'tu.id', '=', 'tbl_qualified_user_list.user_id')
			->join('tbl_users as tu2', 'tu2.id', '=', 'tu.ref_user_id')
			->select('tu.user_id', 'tu.mobile', 'tu.fullname', 'tu2.user_id as sponser_user_id', 'tu2.fullname as sponser_fullname', 'tu.power_r_bv', 'tu.power_l_bv', 'tbl_qualified_user_list.entry_time');

		if (isset($arrInput['user_id'])) {
			$query = $query->where('tu.user_id', $arrInput['user_id']);
		}
		if (isset($arrInput['sponser_id'])) {
			$query = $query->where('tu2.user_id', $arrInput['sponser_id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_qualified_user_list.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
		// 	//searching loops on fields
		// 	$fields = getTableColumns('tbl_qualified_user_list');
		// 	$search = $arrInput['search']['value'];
		// 	$query = $query->where(function ($query) use ($fields, $search) {
		// 		foreach ($fields as $field) {
		// 			$query->orWhere('tbl_qualified_user_list.' . $field, 'LIKE', '%' . $search . '%');
		// 		}
		// 		$query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
		// 	});
		// }

		if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id', 'tu.mobile', 'tu.fullname', 'tu2.user_id as sponser_user_id', 'tu2.fullname as sponser_fullname', 'tu.power_r_bv', 'tu.power_l_bv', 'tbl_qualified_user_list.entry_time');
			//dd($qry);
			//$qry = $qry->select('tu.user_id','tu.mobile', 'tu.fullname','tu2.user_id as sponser_user_id', 'tu2.fullname as sponser_fullname',DB::raw('tu.power_r_bv + tu.power_l_bv as total_direct'), 'tbl_qualified_user_list.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res, "AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
		}


		$query = $query->orderBy('tbl_qualified_user_list.id', 'desc');
		$totalRecord = $query->count('tbl_qualified_user_list.id');
		// $totalRecord = $query->count();
		$arrBinaryInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
		// dd($arrBinaryInc);
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrBinaryInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	/**
	 *  get Binary income report
	 *  $arrInput (array)
	 * @return void
	 */
	public function getBalanceSheetReport(Request $request)
	{
		$arrInput = $request->all();

		$yesterday = \Carbon\Carbon::yesterday()->toDateString();

		$query = DB::table('tbl_daily_summary as ds')
			->join('tbl_users as b', 'ds.user_id', '=', 'b.id')
			->select('ds.id', 'b.user_id', 'b.fullname', 'ds.coinpayment_funds', 'ds.direct_income', 'ds.roi_income', 'ds.binary_income', 'ds.supermatching_income', 'ds.freedom_club_income', 'ds.credit_fund', 'ds.debit_fund', DB::raw("DATE_FORMAT(ds.entry_time,'%Y-%m-%d') as entry_time"));
			/*->where(function($q) {
						$q->where('ds.coinpayment_funds','>',0)
						  ->orWhere('ds.direct_income','>',0)
						  ->orWhere('ds.binary_income','>',0)
						  ->orWhere('ds.supermatching_income','>',0)
						  ->orWhere('ds.freedom_club_income','>',0)
						  ->orWhere('ds.credit_fund','>',0)
						  ->orWhere('ds.debit_fund','>',0);

						});
				//	->toSql();
						})*/;
		//->toSql();

		//dd($query);
		//->get();
		//DB::table('tbl_daily_summary as a')
		//->join('tbl_users as b','a.user_id', '=','b.id')
		//dd($query);
		if (isset($arrInput['id'])) {
			$query = $query->where('b.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(ds.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
		// 	//searching loops on fields
		// 	$fields = array(); //getTableColumns('tbl_payout_history');
		// 	$search = $arrInput['search']['value'];
		// 	$query = $query->where(function ($query) use ($fields, $search) {
		// 		/*foreach ($fields as $field) {
		// 			$query->orWhere('tbl_payout_history.' . $field, 'LIKE', '%' . $search . '%');
		// 		}*/
		// 		$query = $query->orWhere('b.user_id', 'LIKE', '%' . $search . '%')->orWhere('b.fullname', 'LIKE', '%' . $search . '%');
		// 	});
		// }

		/*if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
			$qry = $query;
			$qry = $qry->select('tu.user_id','tbl_payout_history.amount','tbl_payout_history.left_bv','tbl_payout_history.right_bv','tbl_payout_history.laps_amount','tbl_payout_history.rank','tbl_payout_history.percentage','tbl_payout_history.laps_bv','tbl_payout_history.entry_time');
			$records = $qry->get();
			$res = $records->toArray();
			if (count($res) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
			}
			$var = $this->commonController->exportToExcel($res,"AllUsers");
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
		}*/


		//$query = $query->groupBy('ds.id');//DB::enableQueryLog();
		$query = $query->orderBy('ds.entry_time', 'desc');
		$totalRecord = $query->count('ds.id');
		// $totalRecord = $query->count();
		$arrBinaryInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get(); //dd($query->toSql());
		// dd($arrBinaryInc);
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrBinaryInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}
	public function getWorkingToPurchaseReport(Request $request)
	{
		$arrInput = $request->all();

		$yesterday = \Carbon\Carbon::yesterday()->toDateString();

		$query = DB::table('tbl_working_to_purchase_transfer as ds')
			->join('tbl_users as b', 'ds.id', '=', 'b.id')
			->select('ds.id', 'b.user_id', 'b.fullname', 'ds.balance', 'ds.purchase_wallet_amount', 'ds.working_wallet_amount', 'ds.total_income_without_roi', 'ds.old_total_income_without_roi', DB::raw("DATE_FORMAT(ds.entry_time,'%Y-%m-%d') as entry_time"));
		if (isset($arrInput['id'])) {
			$query = $query->where('b.user_id', $arrInput['id']);
		}
		if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
			$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
			$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
			$query = $query->whereBetween(DB::raw("DATE_FORMAT(ds.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
		}
		$query = $query->orderBy('ds.entry_time', 'desc');
		$totalRecord = $query->count('ds.id');
		// $totalRecord = $query->count();
		$arrBinaryInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get(); //dd($query->toSql());
		// dd($arrBinaryInc);
		$arrData['recordsTotal'] = $totalRecord;
		$arrData['recordsFiltered'] = $totalRecord;
		$arrData['records'] = $arrBinaryInc;

		if ($arrData['recordsTotal'] > 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
		}
	}

	public function ChainBusinessBonusReport(Request $request)
{
	$arrInput = $request->all();
	//  dd($arrInput);

	$query = AddChainIncome::select('setting.other_bonus', 'tbl_chain_business_bonus_income.income', 'tbl_chain_business_bonus_income.entry_time', 'tu.user_id as user_id', 'tu.fullname as fullname','tbl_chain_business_bonus_income.l_bv','tbl_chain_business_bonus_income.r_bv')
		->join('tbl_users as tu', 'tu.id', '=', 'tbl_chain_business_bonus_income.user_id')
		->join('tbl_chain_business_settings as setting', 'setting.id', '=', 'tbl_chain_business_bonus_income.chain_business_id');
		// ->where([['tbl_chain_business_bonus_income.user_id', '=', $userId]]);

	if (isset($arrInput['user_id']))
	{
		$query = $query->where('tu.user_id', $request->user_id);
	}
	if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
		$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
		$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
		$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_chain_business_bonus_income.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
	}

	if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
		$qry = $query;
		$qry = $qry->select('setting.other_bonus', 'tbl_chain_business_bonus_income.income', 'tbl_chain_business_bonus_income.entry_time', 'tu.user_id as user_id', 'tu.fullname as fullname','tbl_chain_business_bonus_income.l_bv','tbl_chain_business_bonus_income.r_bv');
		$records = $qry->get();
		$res = $records->toArray();
		if (count($res) <= 0) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
		}
		$var = $this->commonController->exportToExcel($res, "AllUsers");
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
	}
	$query         = $query->orderBy('tbl_chain_business_bonus_income.entry_time', 'desc');
	$totalRecord   = $query->count('tbl_chain_business_bonus_income.id');
	// $totalRecord   = $query->count();
	// dd($totalRecord);
	$arrFranchise      = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

	$arrData['recordsTotal']    = $totalRecord;
	$arrData['recordsFiltered'] = $totalRecord;
	$arrData['records']         = $arrFranchise;

	if (count($arrFranchise) > 0) {
		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
	} else {
		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
	}
}
}
