<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\ExchangeRateReport;
use DB;
use Config;
use Validator;

class ExchangeController extends Controller
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
     * get buy report with only 0-open status from exchange order
     *
     * @return void
     */
    public function getBuyOpenReport(Request $request){
    	$arrInput = $request->all();

    	$query = Exchange::join('tbl_users as tu','tu.id','=','tbl_exchange_order.id')->selectRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.coin_rate) as coin_volume ,(tbl_exchange_order.total_quantity * tbl_exchange_order.usd_rate) as usd_volume ,(tbl_exchange_order.total_quantity * tbl_exchange_order.btc_rate) as btc_volume, tbl_exchange_order.coin_rate, tbl_exchange_order.usd_rate, tbl_exchange_order.btc_rate, tu.user_id, tu.fullname,tbl_exchange_order.id,tbl_exchange_order.status')->where([['tbl_exchange_order.status','=',0],['tbl_exchange_order.order_type','=','Buy']]);
        if(isset($arrInput['id'])){
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_exchange_order');
            $search = $arrInput['search']['value'];
            $query = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_exchange_order.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.coin_rate)','LIKE','%'.$search.'%')
                ->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.usd_rate)','LIKE','%'.$search.'%')
                ->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.btc_rate)','LIKE','%'.$search.'%')
                ->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_exchange_order.srno');
        $query          = $query->orderBy('tbl_exchange_order.srno','desc');
        // $totalRecord    = $query->count();
        $arrExgOrderBuy = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrExgOrderBuy;

    	if($arrData['recordsTotal'] > 0) {;
           	return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
           	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
    /**
     * get sell report with only 0-open status from exchange order
     *
     * @return void
     */
    public function getSellOpenReport(Request $request){
        $arrInput = $request->all();

        $query = Exchange::join('tbl_users as tu','tu.id','=','tbl_exchange_order.id')->selectRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.coin_rate) as coin_volume ,(tbl_exchange_order.total_quantity * tbl_exchange_order.usd_rate) as usd_volume ,(tbl_exchange_order.total_quantity * tbl_exchange_order.btc_rate) as btc_volume, tbl_exchange_order.coin_rate, tbl_exchange_order.usd_rate, tbl_exchange_order.btc_rate, tu.user_id, tu.fullname,tbl_exchange_order.id,tbl_exchange_order.status')->where([['tbl_exchange_order.status','=',0],['tbl_exchange_order.order_type','=','Sell']]);
        if(isset($arrInput['id'])){
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_exchange_order');
            $search = $arrInput['search']['value'];
            $query = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_exchange_order.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.coin_rate)','LIKE','%'.$search.'%')
                ->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.usd_rate)','LIKE','%'.$search.'%')
                ->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.btc_rate)','LIKE','%'.$search.'%')
                ->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_exchange_order.srno');
        $query         = $query->orderBy('tbl_exchange_order.srno','desc');
        // $totalRecord    = $query->count();
        $arrExgOrderSell = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrExgOrderSell;

        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
    /**
     * get buy and sell history with filters from exchange order
     *
     * @return void
     */
    public function getBuySellHistory(Request $request){
        $arrInput = $request->all();

        $query = Exchange::join('tbl_users as tu','tu.id','=','tbl_exchange_order.id')->selectRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.coin_rate) as coin_volume ,(tbl_exchange_order.total_quantity * tbl_exchange_order.usd_rate) as usd_volume ,(tbl_exchange_order.total_quantity * tbl_exchange_order.btc_rate) as btc_volume, tbl_exchange_order.coin_rate, tbl_exchange_order.usd_rate, tbl_exchange_order.btc_rate, tu.user_id, tu.fullname,tbl_exchange_order.id,tbl_exchange_order.status');
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])){
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_exchange_order.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['id'])){
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(isset($arrInput['order_type'])){
            $query = $query->where('tbl_exchange_order.order_type',$arrInput['order_type']);
        }
        if(isset($arrInput['status'])){
            $query = $query->where('tbl_exchange_order.status',$arrInput['status']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_exchange_order');
            $search = $arrInput['search']['value'];
            $query = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_exchange_order.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.coin_rate)','LIKE','%'.$search.'%')
                ->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.usd_rate)','LIKE','%'.$search.'%')
                ->orWhereRaw('(tbl_exchange_order.total_quantity * tbl_exchange_order.btc_rate)','LIKE','%'.$search.'%')
                ->orWhere('tu.user_id','LIKE','%'.$search.'%')
                ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_exchange_order.srno');
        $query           = $query->orderBy('tbl_exchange_order.srno','desc');
        // $totalRecord     = $query->count();
        $arrExgOrder     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrExgOrder;

        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
    /**
     * get exchange rate report
     *
     * @return void
     */
    public function getExchangeRateReport(Request $request){
        $arrInput = $request->all();

        $query = ExchangeRateReport::query();
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])){
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_exchange_rate_report');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $totalRecord    = $query->count('srno');
        $query            = $query->orderBy('srno','desc');;
        // $totalRecord      = $query->count();
        $arrExgRateReport = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrExgRateReport;
        
        if($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
    /**
     * get exchange order summary
     *
     * @return void
     */
    public function getExchangeOrderSummary(Request $request){
        $arrInput = $request->all();

        $last24Hours = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $currTime    = date('Y-m-d H:i:s');

        $avgSum = Exchange::selectRaw('SUM(coin_rate) as coin_avg, SUM(btc_rate) as btc_avg, SUM(usd_rate) as usd_avg')->whereNotIn('status',[3])->first();
        $totalAvgCount = Exchange::whereNotIn('status',[3])->count();

        $avgCount['avg_btc']    = $avgSum->btc_avg / $totalAvgCount;
        $avgCount['avg_usd']    = $avgSum->usd_avg / $totalAvgCount;
        $avgCount['avg_coin']   = $avgSum->coin_avg / $totalAvgCount;

        $lastRates = Exchange::selectRaw('ANY_VALUE(COALESCE(coin_rate,0)) as coin_rate,ANY_VALUE(COALESCE(btc_rate,0)) as btc_rate,ANY_VALUE(COALESCE(usd_rate,0)) as usd_rate,COALESCE(max(entry_time),"") as entry_time')->whereNotIn('status',[3])->where(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),'<',date('Y-m-d'))->first();
              
        $volume24Hours = Exchange::selectRaw('(total_quantity * coin_rate) as coin_volume, (total_quantity * btc_rate) as btc_volume, (total_quantity * usd_rate) as usd_volume')->whereNotIn('status',[3])->whereBetween('entry_time',[$last24Hours,$currTime])->orderBy('entry_time','asc')->first();

        $highRate24Hours = Exchange::selectRaw('MAX(coin_rate) as coin_rate, MAX(btc_rate) as btc_rate, MAX(usd_rate) as usd_rate')->whereNotIn('status',[3])->whereBetween('entry_time',[$last24Hours,$currTime])->first();

        $lowRate24Hours = Exchange::selectRaw('MIN(coin_rate) as coin_rate, MIN(btc_rate) as btc_rate, MIN(usd_rate) as usd_rate')->whereNotIn('status',[3])->whereBetween('entry_time',[$last24Hours,$currTime])->first();

        $arrFinalSummary['average']     = $avgCount;
        $arrFinalSummary['last']        = $lastRates;
        $arrFinalSummary['volume24H']   = $volume24Hours;
        $arrFinalSummary['high24H']     = $highRate24Hours;
        $arrFinalSummary['low24H']      = $lowRate24Hours;

        if(count($arrFinalSummary) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrFinalSummary);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
}