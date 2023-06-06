<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use App\Models\ProjectSettings;
use App\Models\Promotionals;
use App\User;
use App\Models\PromotionalType;
use App\Models\PromotionalIncome;
use App\Models\PromotionalSocialIncome;
use Config;
use Validator;
use Exception;
use Auth;
use DB;

class PromotionalController extends Controller {

    public function __construct(CurrencyConvertorController $currencyConvertor) {

        $this->statuscode = Config::get('constants.statuscode');
        $this->emptyArray = (object) array();
        $this->currencyConvertor = $currencyConvertor;
        $date = \Carbon\Carbon::now();
        $this->today = $date->toDateTimeString();
    }
    //=======get Promotional Income=====//
    public function showPromotional(Request $request){
        
        $query = Promotionals::select('subject','link','remark',DB::raw("DATE_FORMAT(entry_time,'%Y/%m/%d') as entry_time"),'status')                
            ->where('tbl_promotionals.id',Auth::user()->id);

        
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(!empty($request->search['value']) && isset($request->search['value'])){
            //searching loops on fields
            $fields = ['link','subject','remark','entry_time'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
                $query->orWhere(DB::raw("DATE_FORMAT(entry_time,'%Y/%m/%d')"),'LIKE','%'.$search.'%');
            });
        }
        $query     = $query->orderBy('srno','desc');
        $arrData   = setPaginate1($query,$request->start,$request->length);

        if($arrData['recordsTotal'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$arrData);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
        }
    }


     public function checkPromotional(Request $request){
        
           $now = \Carbon\carbon::now();
            $start = new \carbon\Carbon('first day of this month');
            $start1 = new \carbon\Carbon('first day of this month');
            $start2 = new \carbon\Carbon('first day of this month');
            $end = new \carbon\Carbon('last day of this month');
            
           // $totaldays = $end->diffInDays($start);

            $A15thday =  $start1->addDays(14);
            $A15thday1 =  $start2->addDays(14);
            $A16thday =  $A15thday1->addDays(1);
          // dd(date($now) >= date($A16thday));
        if(date($now) >= date($start) && date($now) <= date($A15thday)){
                 $isExisted = Promotionals::whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$start->toDateString(), $A15thday->toDateString()])->where('id',Auth::user()->id)->where('status','!=','rejected')->count();
        }elseif (date($now) >= date($A16thday) && date($now) <= date($end)) {
                $isExisted = Promotionals::whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$A16thday->toDateString(),$end->toDateString()])->where('id',Auth::user()->id)->where('status','!=','rejected')->count();
        }
           
         
        if($isExisted > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$isExisted);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
        }
    }

     public function check90Days(Request $request){
        $id = Auth::user()->id;
        $now = \Carbon\carbon::now();
        $isExisted = User::where('id',$id)->where(DB::raw("DATEDIFF(curdate(), DATE_FORMAT(entry_time,'%Y-%m-%d'))"), ">", 90)->count('id');
         
        if($isExisted > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$isExisted);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
        }
    }

    /**
     * add Promotional
     *
     * @return \Illuminate\Http\Response
     */
    public function storePromotional(Request $request) {
        $rules = array(
          //  'subject'   => 'required',
            'link'      => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {
            $now = \Carbon\carbon::now();
            $start = new \carbon\Carbon('first day of this month');
            $start1 = new \carbon\Carbon('first day of this month');
            $start2 = new \carbon\Carbon('first day of this month');
            $end = new \carbon\Carbon('last day of this month');
            
           // $totaldays = $end->diffInDays($start);

            $A15thday =  $start1->addDays(14);
            $A15thday1 =  $start2->addDays(14);
            $A16thday =  $A15thday1->addDays(1);

           /* if($now >= $start && $now <=$A15thday){
                 $isExisted = Promotionals::whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$start, $A15thday])->where('id',Auth::user()->id)->where('status','!=','rejected')->count();
            }elseif ($now >= $A16thday && $now <=$end) {
                $isExisted = Promotionals::whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$A16thday,$end])->where('id',Auth::user()->id)->where('status','!=','rejected')->count();
            }
           */

            if(date($now) >= date($start) && date($now) <= date($A15thday)){
                 $isExisted = Promotionals::whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$start->toDateString(), $A15thday->toDateString()])->where('id',Auth::user()->id)->where('status','!=','rejected')->count();
            }elseif (date($now) >= date($A16thday) && date($now) <= date($end)) {
                    $isExisted = Promotionals::whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$A16thday->toDateString(),$end->toDateString()])->where('id',Auth::user()->id)->where('status','!=','rejected')->count();
            }

            //dd($isExisted);

            if($isExisted < 1) {
                $arrInsert = [
                    'subject'   => $request->subject,
                     'id' => Auth::user()->id,
                    'link'                  => $request->link,
                    'entry_time'            => \carbon\Carbon::now()
                ];
                $storeId = Promotionals::insertGetId($arrInsert);

                if(!empty($storeId)){
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Promotional added successfully.','');
                } else {
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
                }    
            } else {
               if($now >= $start && $now <=$A15thday){
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You next promotion will be between '.$A16thday->toDateString() .' and ' . $end->toDateString(),'');
                }else{
                     $nextstart = new \carbon\Carbon('first day of next month');
                     $nextstart1 = new \carbon\Carbon('first day of next month');
                     $next15thday =  $nextstart1->addDays(14);
                     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'You next promotion will be between '.$nextstart->toDateString() .' and ' . $next15thday->toDateString(),'');
                    
                }
            }
        }
    }

    //=======get Promotional Income=====//
    public function showPromotionalTypes(Request $request){
        
        $arrPromotionalsTypes = PromotionalType::get();

        if(!empty($arrPromotionalsTypes)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$arrPromotionalsTypes);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
        }
    }
    //=======get Promotional Income=====//
    public function showPromotionalIncome(Request $request){
        
        $query = PromotionalIncome::where('toUserId',Auth::user()->id);

        
        
        if(!empty($request->search['value']) && isset($request->search['value'])){
            //searching loops on fields
            $fields = ['amount','status','entry_time',];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query     = $query->orderBy('entry_time','desc');
        $arrData   = setPaginate($query,$request->start,$request->length);

        if($arrData['totalRecord'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$arrData);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
        }
    }
}
