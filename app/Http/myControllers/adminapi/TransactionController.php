<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use App\Models\Depositaddress;
use App\Models\AddressTransaction;
use App\Models\AddressTransactionPending;
use App\Models\AddFunds;
use App\Models\Topup;
use App\Models\AddCoins;
use App\Models\AllTransaction;
use App\Models\Dashboard;
use App\Models\WithdrawMode;
use App\Models\Invoice;
use App\Models\FundRequest;
use App\Models\TransactionInvoice;
use App\Models\FundTransactionInvoice;
use App\User;
use DB;
use Config;
use Validator;

class TransactionController extends Controller
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
    public function __construct(CommonController $commonController) {
        $this->statuscode =	Config::get('constants.statuscode');
        $this->commonController = $commonController;
    }

    /**
     * get list of all desposit addresses
     *
     * @return void
     */
    public function getDepositAddresses(Request $request) {
        $arrInput  = $request->all();

        $query =    Depositaddress::join('tbl_users as tu','tu.id','=','tbl_deposit_address.id')
                    ->selectRaw('tbl_deposit_address.srno,tbl_deposit_address.currency_code,tbl_deposit_address.address,tbl_deposit_address.entry_time,tbl_deposit_address.label,tbl_deposit_address.total_received,(CASE tbl_deposit_address.status WHEN 0 THEN "Inactive" WHEN 1 THEN "Active" ELSE "" END) as status,tu.user_id,tu.fullname');
        if(isset($arrInput['id'])){
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_deposit_address.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['status'])) {
            //0:Inactve and 1:Active
            $query = $query->where('tbl_deposit_address.status',$arrInput['status']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = ['tbl_deposit_address.currency_code','tbl_deposit_address.address','tbl_deposit_address.label','tbl_deposit_address.total_received','tu.user_id','tu.fullname'];
            $search = $arrInput['search']['value'];
            $query = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $totalRecord  = $query->count('tbl_deposit_address.srno');
        $query        = $query->orderBy('tbl_deposit_address.srno','desc');
        // $totalRecord  = $query->count();
        $arrDespositAddr  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrDespositAddr;

    	if($arrData['recordsTotal'] > 0) {
    		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
    	} else {
    		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
    	}
    }

    /**
     * get list of all desposit address transactions
     *
     * @return void
     */
    public function getDepositAddrTrans(Request $request) {
        $arrInput  = $request->all();

        $query =    AddressTransaction::join('tbl_users as tu','tu.id','=','tbl_deposit_address_transaction.id')


                    ->selectRaw('tbl_deposit_address_transaction.transaction_hash,tbl_deposit_address_transaction.address,tbl_deposit_address_transaction.confirmation,tbl_deposit_address_transaction.network_type,tbl_deposit_address_transaction.usd,tbl_deposit_address_transaction.remark,(CASE tbl_deposit_address_transaction.status WHEN 0 THEN "Inactive" WHEN 1 THEN "Active" ELSE "" END) as status,tu.user_id,tu.fullname');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_deposit_address_transaction.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['status'])) {
            $query = $query->where('tbl_deposit_address_transaction.status',$arrInput['status']);
        }
    	if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_deposit_address_transaction');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_deposit_address_transaction.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $query = $query->orderBy('tbl_deposit_address_transaction.srno','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }
        
    	if($arrData['recordsTotal'] > 0) {
    		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
    	} else {
    		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
    	}
    }



    /**
     * get list of all Perfectmoney report
     *
     * @return void
     */
    public function getPerfectmoneyReport(Request $request) {
        $arrInput  = $request->all();

        $query =    Invoice::join('tbl_users as tu','tu.id','=','tbl_invoices.id')
                  ->where('tbl_invoices.payment_mode','=', 'PM')
                    ->selectRaw('tbl_invoices.payee_account,tbl_invoices.payee_account_name,tbl_invoices.payer_account,tbl_invoices.payment_id,tbl_invoices.price_in_usd,(CASE tbl_invoices.in_status WHEN 0 THEN "Pending" WHEN 1 THEN "Confirm" ELSE "" END) as in_status,tu.user_id,tu.fullname');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_invoices.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['in_status'])) {
            $query = $query->where('tbl_invoices.in_status',$arrInput['in_status']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_invoices');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_invoices.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $query            = $query->orderBy('tbl_invoices.srno','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }
        
        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }



      /**
     * get list of all desposit address transactions
     *
     * @return void
     */
    public function getConfirmAddrTrans(Request $request) {
        $arrInput  = $request->all();
        
        $query = TransactionInvoice::join('tbl_users as tu','tu.id','=','tbl_transaction_invoices.id');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_transaction_invoices.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['status'])) {
            $query = $query->where('tbl_transaction_invoices.in_status',$arrInput['status']);
        }
        /* if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_transaction_invoices');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_transaction_invoices.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        } */

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry = $query;
            $qry = $qry->selectRaw('tu.user_id,tu.fullname,tbl_transaction_invoices.address,tbl_transaction_invoices.status_url as checkout_url,tbl_transaction_invoices.hash_unit as amount,tbl_transaction_invoices.currency_price,tbl_transaction_invoices.payment_mode,(CASE tbl_transaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirmed" ELSE "" END) as status,tbl_transaction_invoices.entry_time');
            $records = $qry->get();
            $res = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res,"CoinpaymentAddressTransactions");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
        }
     

        $query = $query->selectRaw('tbl_transaction_invoices.address,tbl_transaction_invoices.entry_time,tbl_transaction_invoices.price_in_usd,tbl_transaction_invoices.payment_mode,tbl_transaction_invoices.currency_price,tbl_transaction_invoices.hash_unit,tbl_transaction_invoices.status_url,(CASE tbl_transaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirmed" ELSE "" END) as status,tu.user_id,tu.fullname');
        $query = $query->orderBy('tbl_transaction_invoices.srno','desc');

        $totalworkingwall = $query->sum('hash_unit');    
        $totalwcurrencyprice = $query->sum('currency_price');    
        $usdtotal = $query->sum('price_in_usd');    
          
        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
            // $totalworkingwall = array_sum(array_column($arrData['records'],'hash_unit'));    
        } else {
            $arrData = $query->get(); 
        }

        $arrData['balance'] = number_format($totalworkingwall, 2, '.', ''); 
        $arrData['topup_bal'] = number_format($totalwcurrencyprice, 2, '.', '');
        $arrData['total'] = number_format($usdtotal, 2, '.', '');
        // $totalData = $arrData['records'];
        // dd($totalData);
        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } 
        
        else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
    public function getConfirmAddrTransSA(Request $request) {
        $arrInput  = $request->all();
        
        $query = TransactionInvoice::join('tbl_users as tu','tu.id','=','tbl_transaction_invoices.id')->where('tu.user_id',$arrInput['id']);
        /*if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }*/
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_transaction_invoices.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['status']) && isset($arrInput['id'])) {
            $query = $query->where('tbl_transaction_invoices.in_status',$arrInput['status']);
        }
        /* if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_transaction_invoices');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_transaction_invoices.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        } */

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry = $query;
            $qry = $qry->selectRaw('tu.user_id,tu.fullname,tbl_transaction_invoices.address,tbl_transaction_invoices.status_url as checkout_url,tbl_transaction_invoices.hash_unit as amount,tbl_transaction_invoices.currency_price,tbl_transaction_invoices.payment_mode,(CASE tbl_transaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirmed" ELSE "" END) as status,tbl_transaction_invoices.entry_time');
            $records = $qry->get();
            $res = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res,"CoinpaymentAddressTransactions");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
        }
     

        $query = $query->selectRaw('tbl_transaction_invoices.address,tbl_transaction_invoices.entry_time,tbl_transaction_invoices.price_in_usd,tbl_transaction_invoices.payment_mode,tbl_transaction_invoices.currency_price,tbl_transaction_invoices.hash_unit,tbl_transaction_invoices.status_url,(CASE tbl_transaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirmed" ELSE "" END) as status,tu.user_id,tu.fullname');
        $query = $query->orderBy('tbl_transaction_invoices.srno','desc');

        $totalworkingwall = $query->sum('hash_unit');    
        $totalwcurrencyprice = $query->sum('currency_price');    
        $usdtotal = $query->sum('price_in_usd');    
          
        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
            // $totalworkingwall = array_sum(array_column($arrData['records'],'hash_unit'));    
        } else {
            $arrData = $query->get(); 
        }

        $arrData['balance'] = number_format($totalworkingwall, 2, '.', ''); 
        $arrData['topup_bal'] = number_format($totalwcurrencyprice, 2, '.', '');
        $arrData['total'] = number_format($usdtotal, 2, '.', '');
        // $totalData = $arrData['records'];
        // dd($totalData);
        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } 
        
        else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    public function getConfirmExpired(Request $request) {
        $arrInput  = $request->all();
        
        $query = TransactionInvoice::join('tbl_users as tu','tu.id','=','tbl_transaction_invoices.id');
       
       

       
     

        $query = $query->selectRaw('tbl_transaction_invoices.address,tbl_transaction_invoices.entry_time,tbl_transaction_invoices.price_in_usd,tbl_transaction_invoices.payment_mode,tbl_transaction_invoices.currency_price,tbl_transaction_invoices.hash_unit,tbl_transaction_invoices.status_url,(CASE tbl_transaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirm" ELSE "" END) as status,tu.user_id,tu.fullname');
        $query = $query->orderBy('tbl_transaction_invoices.srno','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }
        $totalworkingwall = $query->sum('hash_unit');    
        $arrData['balance'] = number_format($totalworkingwall, 2, '.', '');  
        
        $totalwcurrencyprice = $query->sum('currency_price');    
        $arrData['topup_bal'] = number_format($totalwcurrencyprice, 2, '.', '');    

        $usdtotal = $query->sum('price_in_usd');    
        $arrData['total'] = number_format($usdtotal, 2, '.', '');    
        
        
        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    /**
     * get list of all desposit address transactions pending report
     *
     * @return void
     */
    public function getAddressTransactionPending(Request $request) {
        $arrInput  = $request->all();

        $query = AddressTransactionPending::join('tbl_users as tu','tu.id','=','tbl_deposit_address_transaction_pending.id')
                    ->selectRaw('tbl_deposit_address_transaction_pending.*,tu.user_id,tu.fullname');

        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_deposit_address_transaction_pending.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['status'])) {
            $query = $query->where('tbl_deposit_address_transaction_pending.status',$arrInput['status']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_deposit_address_transaction_pending');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_deposit_address_transaction_pending.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $query = $query->orderBy('tbl_deposit_address_transaction_pending.srno','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }

        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    /**
     * get all transactions
     *
     * @return void
     */
    public function getAllTransaction(Request $request){
        $arrInput = $request->all();

        $query =    AllTransaction::join('tbl_users as tu','tu.id','=','tbl_all_transaction.id')
                    ->selectRaw('tbl_all_transaction.*,tu.user_id,tu.fullname,(CASE tbl_all_transaction.status WHEN 0 THEN "Pending" WHEN 1 THEN "Confirmed" ELSE "" END) as status');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_all_transaction.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_all_transaction');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_all_transaction.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $query              = $query->orderBy('tbl_all_transaction.srno','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }

        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
        } else {        
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Record not found','');
        }
    }

    public function getAllTransactionTotals($request){
        $query =  AllTransaction::join('tbl_users as tu','tu.id','=','tbl_all_transaction.id')
                ->selectRaw('COALESCE(ROUND(SUM(tbl_all_transaction.credit),5),0) as credit,COALESCE(ROUND(SUM(tbl_all_transaction.debit),5),0) as debit,COALESCE(ROUND(SUM(tbl_all_transaction.balance),5),0) as balance');
        if(isset($request->id)) {
            $query = $query->where('tbl_all_transaction.id',$request->id);
        }
        if(isset($request->frm_date) && isset($request->to_date)) {
            $request->frm_date = date('Y-m-d',strtotime($request->frm_date));
            $request->to_date  = date('Y-m-d',strtotime($request->to_date));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_all_transaction.entry_time,'%Y-%m-%d')"),[$request->frm_date, $request->to_date]);
        }
        if(isset($request->search->value) && !empty($request->search->value)){
            //searching loops on fields
            $fields = getTableColumns('tbl_all_transaction');
            $search = $request->search->value;
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_all_transaction.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%');
            });
        }
        $totalSummary = $query->orderBy('tbl_all_transaction.entry_time','desc')->first();

        return $totalSummary;
    }

    /**
     * get all funds
     *
     * @return void
     */
    public function getFunds(Request $request){
        $arrInput = $request->all();

        $query = DB::table('tbl_add_funds as taf')
                 ->join('tbl_users as tu','tu.id','=','taf.id')
                 ->leftjoin('tbl_users as tu1','tu1.id','=','taf.created_by')
                 ->select('taf.*','tu.user_id','tu.fullname','tu1.user_id as created_by');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(taf.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_add_funds');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('taf.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu1.user_id','LIKE','%'.$search.'%');
            });
        }
        $totalRecord  = $query->count('taf.srno');
        $query        = $query->orderBy('taf.srno','desc');
        // $totalRecord  = $query->count();
        $arrFunds     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrFunds;

        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
        } else {        
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Record not found','');
        }
    }

    /**
     * store funds
     *
     * @return void
     */
    public function addFunds(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'                    => 'required',
            'network_type'          => 'required',
            'network_type_value'    => 'required',
            'remark'                => 'required',
            'created_by'            => 'required'
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
        } else {
            $arrInput['created_by'] = $this->commonController->getLoggedUserData(['remember_token'=>$arrInput['created_by']])->id;

            //$coinName = $this->commonController->getProjectSettings()->coin_name;
            $arrInsertFunds = [
                'id'                  => $arrInput['id'],
                'network_type'        => $arrInput['network_type'],
                'network_type_value'  => $arrInput['network_type_value'],
                'remark'              => $arrInput['remark'],
                'created_by'          => $arrInput['created_by'],
                'fund_type'           => 'Credit',
                'entry_time'          => now(),
            ];
            $storeId1 = AddFunds::insertGetId($arrInsertFunds);
            if(!empty($storeId1)){
                //only usd column will update with respective to income type column
                if($arrInput['network_type']=='Direct'){
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Direct Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->direct_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'direct_income';
                    }
                }else if($arrInput['network_type']=='Level') {
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Level Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->level_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'level_income';
                    }
                }else if($arrInput['network_type']=='Binary') {
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Binary Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->binary_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'binary_income';
                    }
                }else if($arrInput['network_type']=='Roi') {
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Roi Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->roi_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'roi_income';
                    }
                }
                //$balance = $usdValue + $arrInput['network_type_value'];
                $balance = $networkValue + $arrInput['network_type_value'];
                $arrUpdate = [
                    $networkCol => $networkValue + $arrInput['network_type_value'],
                    //'usd'       => $balance
                ];
                $dashboardUpdate = Dashboard::where('id',$arrInput['id'])->update($arrUpdate);
                $arrInsertTrans = [
                    'id' => $arrInput['id'],
                    'network_type'      => $arrInput['network_type'],
                    'credit'            => $arrInput['network_type_value'],
                    'debit'             => 0,
                    'balance'           => $balance,
                    'refference'        => $storeId1,
                    'remarks'           => $arrInput['remark'],
                    'type'              => 'ADMIN',
                    'transaction_date'  => now(),
                    'entry_time'        => now(),
                ];
                $storeId2 = AllTransaction::insertGetId($arrInsertTrans);
                if(!empty($dashboardUpdate)){
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record added successfully','');    
                }else{
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating dashboard data','');
                }
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding fund record','');
            }
        }
    }

    /**
     * deduct funds
     *
     * @return void
     */
    public function deductFunds(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'                    => 'required',
            'network_type'          => 'required',
            'network_type_value'    => 'required',
            'remark'                => 'required',
            'created_by'            => 'required'
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
        } else {
            $arrInput['created_by'] = $this->commonController->getLoggedUserData(['remember_token'=>$arrInput['created_by']])->id;

            //$coinName = $this->commonController->getProjectSettings()->coin_name;
            $arrInsertFunds = [
                'id'                  => $arrInput['id'],
                'network_type'        => $arrInput['network_type'],
                'network_type_value'  => $arrInput['network_type_value'],
                'remark'              => $arrInput['remark'],
                'created_by'          => $arrInput['created_by'],
                'fund_type'           => 'Debit',
                'entry_time' => now(),
            ];
            $storeId1 = AddFunds::insertGetId($arrInsertFunds);
            if(!empty($storeId1)){
                //only usd column will update with respective to income type column
                if($arrInput['network_type']=='Direct'){
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Direct Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->direct_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'direct_income';
                    }
                }else if($arrInput['network_type']=='Level') {
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Level Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->level_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'level_income';
                    }
                }else if($arrInput['network_type']=='Binary') {
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Binary Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->binary_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'binary_income';
                    }
                }else if($arrInput['network_type']=='Roi') {
                    $objNetwork = Dashboard::where('id',$arrInput['id'])->first();
                    if(empty($objNetwork)){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Roi Income record not avalibale for entered user in dashboard','');
                    } else {
                        $networkValue = $objNetwork->roi_income;
                        $usdValue     = $objNetwork->usd;
                        $networkCol   = 'roi_income';
                    }
                }
               //$balance = $usdValue + $arrInput['network_type_value'];
                $balance = $networkValue - $arrInput['network_type_value'];
                $arrUpdate = [
                    $networkCol => $networkValue - $arrInput['network_type_value'],
                    //'usd'       => $balance
                ];
                $dashboardUpdate = Dashboard::where('id',$arrInput['id'])->update($arrUpdate);
                $arrInsertTrans = [
                    'id'                => $arrInput['id'],
                    'network_type'      => $arrInput['network_type'],
                    'credit'            => 0,
                    'debit'             => $arrInput['network_type_value'],
                    'balance'           => $balance,
                    'refference'        => $storeId1,
                    'remarks'           => $arrInput['remark'],
                    'type'              => 'ADMIN',
                    'transaction_date'  => now(),
                    'entry_time'        => now(),
                ];
                $storeId2 = AllTransaction::insertGetId($arrInsertTrans);
                if(!empty($dashboardUpdate)){
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Fund deducted successfully','');    
                }else{
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating dashboard data','');
                }
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deducting fund record','');
            }
        }
    }
      /**
     * get all transactions
     *
     * @return void
     */
    public function getWithdrawals(Request $request){
        $arrInput = $request->all();

        $query =    Topup::join('tbl_users as tu','tu.id','=','tbl_topup.id')
                    ->selectRaw('tbl_topup.*,tu.user_id,tu.fullname')
                    ->groupby('tbl_topup.id');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_topup.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
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
        $query              = $query->orderBy('tbl_topup.srno','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }
        foreach($arrData['record'] as $key => $value)
        {
            $mode = $this->getWithdrawROIMode($value->id);
            $value->mode = $mode;
        }
        
        if($arrData['totalRecord'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
        } else {        
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Record not found','');
        }
         
    }

     /**
     * get user profile details
     *
     * @return \Illuminate\Http\Response
     */
    public function getWithdrawalMode(Request $request){
        $arrInput = $request->all();
        $getWithDraw = $this->getWithdrawROIMode($arrInput['id']);
        //get user about data (personal data)
         $userID = User::where('id',$arrInput['id'])->pluck('user_id');$userName = User::where('id',$arrInput['id'])->pluck('fullname');
         
        $arrFinalData['mode'] = $getWithDraw;
        $arrFinalData['userId'] = $userID[0];
        $arrFinalData['fullname'] = $userName[0];
       

        if(count($arrFinalData) > 0){
            return sendresponse($this->statuscode[200]['code'],$this->statuscode[200]['status'],'Record found', $arrFinalData);
        } else {
            return sendresponse($this->statuscode[404]['code'],$this->statuscode[404]['status'],'Record not found','');
        }
    }
    //=============withdraw mode=================    
    public function getWithdrawROIMode($id) {

        $userData = User::where('id', $id)->first();

       
        if (!empty($userData)) {
            $mode1 = WithdrawMode::where('id', $userData->id)->select('network_type')->orderBy('id', 'desc')->first();

            if (empty($mode1)) {
               
                 $checkTopup = Topup::where('id', $userData->id)->select('id','payment_type')->first();
      

                    
                if (!empty($checkTopup)) {

                    $type = $checkTopup->payment_type;

                } 
            } else {
                $mode = $mode1;
                $type = $mode->network_type;
            }

            if (!isset($type) || $type == '') {
                $type = 'BTC';
                $mode = '1';
            }

            $mode = $type;
            return $mode;


        } else {
            return 0;
        }
    } 


     /**
     * get user profile details
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePaymentMode(Request $request){
        $arrInput = $request->all();


        $userID = WithdrawMode::where('id',$arrInput['id'])->pluck('id');
        
        if(isset($userID[0]) || !empty($userID[0]))
        {
                $arrInsertModes = [
                'network_type'        => $arrInput['mode'],
               ];
                $modeUpdate = WithdrawMode::where('id',$arrInput['id'])->update($arrInsertModes);
        }
        else
        { 
             $arrInsertModes = [
                'id'                  => $arrInput['id'],
                'network_type'        => $arrInput['mode'],
                'entry_time'          => now(),
            ];
            $modeUpdate = WithdrawMode::insertGetId($arrInsertModes);
        }

        if(!empty($modeUpdate) > 0){
            return sendresponse($this->statuscode[200]['code'],$this->statuscode[200]['status'],'successfully Saved', '');
        } else {
            return sendresponse($this->statuscode[404]['code'],$this->statuscode[404]['status'],'Please Change Payment Mode','');
        }
    }
    /* public function getFundCoinPaymentReport(Request $request) {
        $arrInput  = $request->all();

        $query = FundTransactionInvoice::join('tbl_users as tu','tu.id','=','tbl_fundtransaction_invoices.id');
        if(isset($arrInput['id'])) {
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_fundtransaction_invoices.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['status'])) {
            $query = $query->where('tbl_fundtransaction_invoices.in_status',$arrInput['status']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_fundtransaction_invoices');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_fundtransaction_invoices.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry = $query;
            $qry = $qry->selectRaw('tu.user_id,tu.fullname,tbl_fundtransaction_invoices.address,tbl_fundtransaction_invoices.status_url as checkout_url,tbl_fundtransaction_invoices.hash_unit as amount,tbl_fundtransaction_invoices.currency_price,tbl_fundtransaction_invoices.payment_mode,(CASE tbl_fundtransaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirm" ELSE "" END) as status,tbl_fundtransaction_invoices.entry_time');
            $records = $qry->get();
            $res = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res,"FundCoinpaymentTransactions");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
        }        

        $query = $query->selectRaw('tbl_fundtransaction_invoices.address,tbl_fundtransaction_invoices.entry_time,tbl_fundtransaction_invoices.price_in_usd,tbl_fundtransaction_invoices.payment_mode,tbl_fundtransaction_invoices.currency_price,tbl_fundtransaction_invoices.hash_unit,tbl_fundtransaction_invoices.status_url,(CASE tbl_fundtransaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirm" ELSE "" END) as status,tu.user_id,tu.fullname,tbl_fundtransaction_invoices.product_url');
        $query = $query->orderBy('tbl_fundtransaction_invoices.srno','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }
        
        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    } */
    public function getFundCoinPaymentReport(Request $request)
    {
        $arrInput  = $request->all();

        $query = FundTransactionInvoice::join('tbl_users as tu', 'tu.id', '=', 'tbl_fundtransaction_invoices.id');
        if (isset($arrInput['id'])) {
            $query = $query->where('tu.user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_fundtransaction_invoices.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (isset($arrInput['status'])) {
            $query = $query->where('tbl_fundtransaction_invoices.in_status', $arrInput['status']);
        }
        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = getTableColumns('tbl_fundtransaction_invoices');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere('tbl_fundtransaction_invoices.' . $field, 'LIKE', '%' . $search . '%');
                }
                $query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
                    ->orWhere('tu.fullname', 'LIKE', '%' . $search . '%');
            });
        }

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry = $query;
            $qry = $qry->selectRaw('tu.user_id,tu.fullname,tbl_fundtransaction_invoices.address,tbl_fundtransaction_invoices.status_url as checkout_url,tbl_fundtransaction_invoices.hash_unit as amount,tbl_fundtransaction_invoices.currency_price,tbl_fundtransaction_invoices.payment_mode,(CASE tbl_fundtransaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirm" ELSE "" END) as status,tbl_fundtransaction_invoices.entry_time');
            $records = $qry->get();
            $res = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res, "FundCoinpaymentTransactions");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
        }

        $query = $query->selectRaw('tbl_fundtransaction_invoices.address,tbl_fundtransaction_invoices.entry_time,tbl_fundtransaction_invoices.price_in_usd,tbl_fundtransaction_invoices.payment_mode,tbl_fundtransaction_invoices.currency_price,tbl_fundtransaction_invoices.hash_unit,tbl_fundtransaction_invoices.status_url,(CASE tbl_fundtransaction_invoices.in_status WHEN 0 THEN "Pending" WHEN 2 THEN "Expired" WHEN 1 THEN "Confirm" ELSE "" END) as status,tu.user_id,tu.fullname,tbl_fundtransaction_invoices.product_url,tbl_fundtransaction_invoices.invoice_id');
        $query = $query->orderBy('tbl_fundtransaction_invoices.srno', 'desc');

        $totalworkingwall = $query->sum('hash_unit');    
        $totalwcurrencyprice = $query->sum('currency_price');    
        $usdtotal = $query->sum('price_in_usd'); 

        if (isset($arrInput['start']) && isset($arrInput['length'])) {
            $arrData = setPaginate1($query, $arrInput['start'], $arrInput['length']);
        } else {
            $arrData = $query->get();
        }

        $arrData['balance'] = number_format($totalworkingwall, 2, '.', ''); 
        $arrData['topup_bal'] = number_format($totalwcurrencyprice, 2, '.', '');
        $arrData['total'] = number_format($usdtotal, 2, '.', '');

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
    public function getFundTransactionStatusCount(Request $request)
    {
        try {

            // $query = TransactionInvoice::select('srno','in_status');
            // $query = TransactionInvoice::join('tbl_users as tu','tu.id','=','tbl_transaction_invoices.id');
            // dd($query);

               
                $confirmed =FundTransactionInvoice::select('srno','in_status')->where('in_status', 1)->count();
                $expired =FundTransactionInvoice::select('srno','in_status')->where('in_status', 2)->count();
                $pending =FundTransactionInvoice::select('srno','in_status')->where('in_status', 0)->count();
                $Invoicedata = array();
               
                $Invoicedata['pending'] = $pending;
                $Invoicedata['confirmed'] = $confirmed;
                $Invoicedata['expired'] = $expired;

                $confirmedsum =FundTransactionInvoice::where('in_status', 1)->sum('hash_unit');
                $expiredsum =FundTransactionInvoice::where('in_status', 2)->sum('hash_unit');
               
               
                $Invoicedata['confirmedsum'] = number_format($confirmedsum, 2, '.', '');  
                $Invoicedata['expiredsum'] = number_format($expiredsum, 2, '.', '');

                      
            if (!empty($Invoicedata)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "Data found",$Invoicedata);
                //  $arrStatus = Response::HTTP_OK;
                // $arrCode = Response::$statusTexts[$arrStatus];
                // $arrMessage = 'data found successfully';
                // return sendResponse($arrStatus, $arrCode, $arrMessage, $Invoicedata); 
                // return sendresponse($this->statuscode[200]['code'],$this->statuscode[200]['status'],'successfully Saved', $Invoicedata);
            } 
            else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], "Transaction not found", '');

                // $arrStatus = Response::HTTP_NOT_FOUND;
                // $arrCode = Response::$statusTexts[$arrStatus];
                // $arrMessage = 'Currency data found successfully';
                // return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
              
                

            // if (count($Invoicedata) > 0) {
            //     /* $arrStatus = Response::HTTP_OK;
            //     $arrCode = Response::$statusTexts[$arrStatus];
            //     $arrMessage = 'Currency data found successfully';
            //     return sendResponse($arrStatus, $arrCode, $arrMessage, $Invoicedata); */
            //     return sendresponse($this->statuscode[200]['code'],$this->statuscode[200]['status'],'successfully Saved', $Invoicedata);
            // } else {

            //     $arrStatus = Response::HTTP_NOT_FOUND;
            //     $arrCode = Response::$statusTexts[$arrStatus];
            //     $arrMessage = 'Currency data found successfully';
            //     return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            // }
        } catch (Exception $e) {
            dd($e);
            $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }
    public function getTransactionStatusCount(Request $request)
    {
        try {

            // $query = TransactionInvoice::select('srno','in_status');
            // $query = TransactionInvoice::join('tbl_users as tu','tu.id','=','tbl_transaction_invoices.id');
            // dd($query);

               
                $confirmed =TransactionInvoice::select('srno','in_status')->where('in_status', 1)->count();
                $expired =TransactionInvoice::select('srno','in_status')->where('in_status', 2)->count();
                $pending =TransactionInvoice::select('srno','in_status')->where('in_status', 0)->count();
                $Invoicedata = array();

                $Invoicedata['pending'] = $pending;
                $Invoicedata['confirmed'] = $confirmed;
                $Invoicedata['expired'] = $expired;


                 
                $confirmedsum =TransactionInvoice::where('in_status', 1)->sum('hash_unit');
                $expiredsum =TransactionInvoice::where('in_status', 2)->sum('hash_unit');
                // $cancelsum =TransactionInvoice::where('in_status', 0)->sum('hash_unit');
                // $Invoicedata = array();
                
               
                $Invoicedata['confirmedsum'] = number_format($confirmedsum, 2, '.', '');  
                $Invoicedata['expiredsum'] = number_format($expiredsum, 2, '.', '');
                // $Invoicedata['cancelsum'] = number_format($cancelsum, 2, '.', '');  
                
  


               
        
              
                
                
              

               
            if (!empty($Invoicedata)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "Data found",$Invoicedata);
                //  $arrStatus = Response::HTTP_OK;
                // $arrCode = Response::$statusTexts[$arrStatus];
                // $arrMessage = 'data found successfully';
                // return sendResponse($arrStatus, $arrCode, $arrMessage, $Invoicedata); 
                // return sendresponse($this->statuscode[200]['code'],$this->statuscode[200]['status'],'successfully Saved', $Invoicedata);
            } 
            else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], "Transaction not found", '');

                // $arrStatus = Response::HTTP_NOT_FOUND;
                // $arrCode = Response::$statusTexts[$arrStatus];
                // $arrMessage = 'Currency data found successfully';
                // return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function getPaymentInvoiceTransaction(Request $request){        

        $rules = array(
            'invoice_id' => 'required',
        );
        $validator = Validator::make($request->all(), $rules, []);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err     = '';
            foreach ($message->all() as $error) {
                if (count($message->all()) > 1) {
                    $err = $err.' '.$error;
                } else {
                    $err = $error;
                }
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, []);
        }
        $checkExist=TransactionInvoice::select('trans_id','invoice_id','id','refund_status')->where('invoice_id',$request->invoice_id)->first();
        $checkExistFund=FundTransactionInvoice::select('trans_id','invoice_id','id','refund_status')->where('invoice_id',$request->invoice_id)->first();
        $invoice = (!empty($checkExist))?$checkExist:((!empty($checkExistFund))?$checkExistFund:null);
        $invoice_type = (!empty($checkExist))?"Topup":((!empty($checkExistFund))?"Fund":'');
        if (!empty($invoice) || $invoice != null) {
            $txn=array();
            $txn['id'] = $invoice->trans_id;

            $transData = get_node_trans_status('publicInvoiceStatus', $txn);

            if(empty($transData) || $transData == null || @$transData['msg'] == "failed"){

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], "Not found", []);
            }
            if ($transData['data']['status'] == 'OK' && $transData['data']['code'] == 200) {
                $arrData = $transData['data']['data'];
                $arrData['invoice_type'] = $invoice_type;
                
                if ($arrData['paymentStatus'] == "EXPIRED") {
                    $expired = array();
                    $expired['paymentStatus'] = "EXPIRED";
                    $expired['invoice_type'] = $invoice_type;
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "Data found",$expired);
                }elseif ($arrData['paymentStatus'] == "PAID") {
                    $paid = array();
                    $paid['paymentStatus'] = "PAID";
                    $paid['invoice_type'] = $invoice_type;
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "Data found",$paid);
                }elseif($arrData['paymentStatus'] == "PENDING"){
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], "Data found",$arrData);
                }
            }else{

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], "Transaction not found", ['invoice_type' => $invoice_type]);
            }
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], "Transaction not found", []);
        }

    } 
}