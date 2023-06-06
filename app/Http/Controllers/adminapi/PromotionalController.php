<?php

namespace App\Http\Controllers\adminapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Level;
use App\Models\Video;
use App\Models\Dashboard;
use App\Models\Promotional;
use App\Models\ProjectSettings;
use App\Models\AllTransaction;
use App\Models\Activitynotification;
use App\Models\Promotionals;
use App\Models\PromotionalType;
use App\Models\PromotionalIncome;
use App\Models\PromotionalSocialIncome;
use App\User;
use App\Traits\Users;
use App\Models\Gallry;
use App\Models\Gallerya;
use App\Models\SettingGallery;

use DB;
use Config;
use Validator;
use Auth;

class PromotionalController extends Controller {

    use Users;

    public function __construct() {
        $this->statuscode =Config::get('constants.statuscode');
        $this->emptyArray=(object)array();
        $date = \Carbon\Carbon::now();
        $this->today= $date->toDateTimeString();
    }  
    //========================get video====================
    public function videolist(Request $request){

        $rules = array(
            'status'    => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {
            $query = Video::join('tbl_users as tu','tu.id','=','tbl_user_video.user_id')
                    ->select('tbl_user_video.*','tu.user_id','tu.fullname',DB::raw('(CASE tbl_user_video.status WHEN "0" THEN "Pending" WHEN "1" THEN "Approve" WHEN "2" THEN "Rejected" WHEN "3" THEN "Hide" ELSE "" END) as status'))
                    ->whereIn('tbl_user_video.status',$request->status);

            if(isset($request->frm_date) && isset($request->to_date)) {
                $request->frm_date = date('Y-m-d',strtotime($request->frm_date));
                $request->to_date  = date('Y-m-d',strtotime($request->to_date));
                $query  = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[$request->frm_date, $request->to_date]);
            }
            if(!empty($request->search->value) && isset($request->search->value)){
                //searching loops on fields
                $fields = getTableColumns('tbl_user_video');
                $search = $request->search->value;
                $query  = $query->where(function ($query) use ($fields, $search){
                    foreach($fields as $field){
                        $query->orWhere('tbl_user_video.'.$field,'LIKE','%'.$search.'%');
                    }
                    $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                          ->orWhere('tu.fullname','LIKE','%'.$search.'%');
                });
            }
            $totalRecord = $query->count('tbl_user_video.id');
            $query       = $query->orderBy('tbl_user_video.id','desc');
            // $totalRecord = $query->count();
            $arrVideos   = $query->skip($request->start)->take($request->length)->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $arrVideos;

            if($arrData['recordsTotal'] > 0){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$arrData);  
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
            }
        }
    } 
    //==========approved video link========================
    public function approvevideo(Request $request){
        $rules = array(
            'srno'              => 'required',
            'embedded_link'     => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        $approveVideo = User::join('tbl_user_video as tuv','tuv.user_id','=','tbl_users.id')
                        ->join('tbl_dashboard','tbl_dashboard.id','=','tbl_users.id')
                        ->select('tbl_users.id as uid','tuv.id','tbl_users.user_id','tbl_dashboard.promotional_income')
                        ->where([['tuv.status','=','0'],['tuv.id','=',trim($request->srno)]])
                        ->first(); 

        if(!empty($approveVideo) && count($approveVideo) > 0){
            /*get project settings*/
            $proSettings = ProjectSettings::where('status',1)->first();

            $coin_name              = $proSettings->coin_name;
            $promotionalIncomeAmt   = $proSettings->promotional_income_amount;

            $userarrData = $arrData  = [];

            $arrData['youtube_link']    = $request->embedded_link;
            $arrData['status']          = '1';
            $arrData['admin_date']      = $this->today;

            $VideoApproved = Video::where('id',trim($request->srno))->update($arrData);
            /*update dashboard promotional income*/
            $userarrData['promotional_income'] = $approveVideo->promotional_income + $promotionalIncomeAmt;

            $VideoApproved = Dashboard::where('id',$approveVideo->uid)->update($userarrData);

            /*insert into promotional tbl*/               
            $insertdata             = new Promotional;
            $insertdata->pro_id     = $approveVideo->id;
            $insertdata->amount     = $promotionalIncomeAmt;
            $insertdata->rec_date   = $this->today;
            $insertdata->entry_time = $this->today;
            $insertdata->toUserId   = $approveVideo->uid;
            $insertdata->fromUserId = 1;
            $insertdata->status     = 'Paid';
            $insertdata->invoice_id = '0';
            $insertdata->remark     = 'Promotional income';
            $insertdata->save();
            
            /*add into all transaction -credited value*/
            $trans                      = new AllTransaction;
            $trans->id                  = $approveVideo->uid;
            $trans->network_type        = $coin_name;
            $trans->credit              = $promotionalIncomeAmt;
            $trans->debit               = 0;
            $trans->refference          = 0;
            $trans->transaction_date    = now();
            $trans->status              = 1;
            $trans->type                = 'promotional_income';
            $trans->remarks             = 'Promotional income amount:'.$promotionalIncomeAmt;
            $trans->save();

            /*add into activity notification */
            $actdata = [
                'id'        =>  $approveVideo->uid,
                'message'   => 'Promotional income amount:'.$promotionalIncomeAmt,
                'status'    => 1
            ];           
            $actDta = Activitynotification::create($actdata);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Video approved successfully','');  
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong,please try again','');
        }
    } 
    //========reject/Hide video==========================//
    public function rejectHidevideo(Request $request){
        $rules = array(
            'srno'      => 'required',
            'status'    => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        $checkexist = Video::where('id',trim($request->srno))->first();
        if(!empty($checkexist) && ($request->status=='2' || $request->status=='3' || $request->status=='1')){
            /* approve video */
            $userarrData = $arrData  = [];

            $arrData['remark']  = $request->remark;
            $arrData['status']  = $request->status; 

            $VideoApproved = Video::where('id',$request->srno)->update($arrData);
           
            if($request->input('status')=='2'){
                $status = 'Video rejected successfully';
            }else if($request->input('status')=='3'){
                $status = 'Video hide successfully';
            }else if($request->input('status')=='1'){
                $status = 'Video show successfully';
            } 

            if(!empty($VideoApproved)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $status,'');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records already updated with given inputs','');
            }
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong,please try again','');
        }
    }
    //=======get Promotional Income=====//
    public function getPromotionalIncome(Request $request){
        
        $query = Promotional::join('tbl_users as tu','tu.id','=','tbl_promostion_income.fromUserId')
                ->join('tbl_users as tu1','tu1.id','=','tbl_promostion_income.toUserId')
                ->join('tbl_user_video as tuv','tuv.id','=','tbl_promostion_income.pro_id')
                ->select('tbl_promostion_income.*','tu.user_id as from_user_id','tu.fullname as from_fullname','tu1.user_id as to_user_id','tu1.fullname as to_fullname','tuv.youtube_link as for_link');

        if(isset($request->frm_date) && isset($request->to_date)) {
            $request->frm_date = date('Y-m-d',strtotime($request->frm_date));
            $request->to_date  = date('Y-m-d',strtotime($request->to_date));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_promostion_income.entry_time,'%Y-%m-%d')"),[$request->frm_date, $request->to_date]);
        }
        if(!empty($request->search->value) && isset($request->search->value)){
            //searching loops on fields
            $fields = getTableColumns('tbl_promostion_income');
            $search = $request->search->value;
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_promostion_income.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                      ->orWhere('tu.fullname','LIKE','%'.$search.'%')
                      ->orWhere('tu1.fullname','LIKE','%'.$search.'%')
                      ->orWhere('tu1.fullname','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_promostion_income.id');
        $query          = $query->orderBy('tbl_promostion_income.id','desc');
        // $totalRecord    = $query->count();
        $arrPro         = $query->skip($request->start)->take($request->length)->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrPro;

        if($arrData['recordsTotal'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$arrData);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
        }
    }

    //=======get Promotional Income=====//
    public function showPromotional(Request $request){
        
        $query = Promotionals::join('tbl_users as tu','tu.id','=','tbl_promotionals.id')
                ->select('tbl_promotionals.srno','tbl_promotionals.remark','tbl_promotionals.status','tbl_promotionals.show_status','tbl_promotionals.link','tbl_promotionals.subject','tu.user_id','tu.fullname',DB::raw("DATE_FORMAT(tbl_promotionals.date,'%Y/%m/%d') as date"),DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y/%m/%d') as entry_time"));

        if(isset($request->user_id)) {
            $query  = $query->where('tu.user_id',$request->user_id);
        }
        if(isset($request->status)) {
            $query  = $query->where('tbl_promotionals.status',$request->status);
        }
        if(isset($request->promotional_type_id)) {
            $query  = $query->where('tbl_promotionals.promotional_type_id',$request->promotional_type_id);
        }
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(!empty($request->search['value']) && isset($request->search['value'])){
            //searching loops on fields
            $fields = ['tbl_promotionals.link','tu.user_id','tu.fullname','tpt.promotional_name'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
                $query->orWhere(DB::raw("DATE_FORMAT(tbl_promotionals.date,'%Y/%m/%d')"),'LIKE','%'.$search.'%');
            });
        }
        $query     = $query->orderBy('tbl_promotionals.srno','desc');
        $arrData   = setPaginate($query,$request->start,$request->length);
        
        if($arrData['totalRecord'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$arrData);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
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
    //========reject/Hide Promotional==========================//
    public function approveRejectPromotional(Request $request){
        DB::beginTransaction();
        try{
            $rules = array(
                'id'      => 'required',
                'status'    => 'required',
            );
           //dd($request->status);
            $validator = Validator::make($request->all(), $rules);
            if($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
            }

           $promo = Promotionals::where('srno',$request->id)->first();
           if(empty($promo)){
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'],'some thing went wrong.', '');
           }

                $arrData            = [];
                $arrData['remark']  = $request->remark;
                $arrData['status']  = $request->status; 
                $update = Promotionals::where('srno',$request->id)->update($arrData);

            /* approve Promotional */
            if($request->status != 'rejected'){


                $insertPromoIncome = new PromotionalIncome;
                $insertPromoIncome->pro_id = $request->id;
                $insertPromoIncome->amount = 5;
                $insertPromoIncome->toUserId = $promo->id;
                $insertPromoIncome->fromUserId = Auth::user()->id;
                $insertPromoIncome->remark = $request->remark;
                $insertPromoIncome->entry_time = \Carbon\Carbon::now();
                $insertPromoIncome->save();

                $dash = Dashboard::where('id',$promo->id)->first();
                $arrUpdate = [
                    'promotional_income'=>$dash->promotional_income + 5,
                    'promotional_income_withdraw'=>$dash->promotional_income_withdraw + 5,
                     'working_wallet'=>$dash->working_wallet + 5
                ];
                Dashboard::where('id',$promo->id)->update($arrUpdate);
            }
            


           /* $start = new \carbon\Carbon('first day of this month');
            $end = new \carbon\Carbon('last day of this month');
            $countApprovedPromos = Promotionals::whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$start, $end])->where('id',$promo->id)->where('status','pending')->count();
      
            if($countApprovedPromos == 0){

                    $insertPromoIncome = new PromotionalIncome;
                    $insertPromoIncome->pro_id = $request->id;
                    $insertPromoIncome->amount = 5;
                    $insertPromoIncome->toUserId = $promo->id;
                    $insertPromoIncome->fromUserId = Auth::user()->id;
                    $insertPromoIncome->remark = $request->remark;
                    $insertPromoIncome->entry_time = \Carbon\Carbon::now();
                    $insertPromoIncome->save();

                    $dash = Dashboard::where('id',$promo->id)->first();
                    $arrUpdate = [
                        'promotional_income'=>$dash->promotional_income + 5,
                        'promotional_income_withdraw'=>$dash->promotional_income_withdraw + 5,
                         'working_wallet'=>$dash->working_wallet + 5
                    ];
                    Dashboard::where('id',$promo->id)->update($arrUpdate);

            }*/

        }catch(Exception $e){
              DB::rollBack();
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong','');
        }
           DB::commit();
           return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Promotional '.$request->status.' successfully', ''); 





        /*if(!empty($update)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Promotional '.$request->status.' successfully','');
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records already updated with given inputs','');
        }*/
    }

    //=======get Promotional Income=====//
    public function showPromotionalIncome(Request $request){
        
        $query = PromotionalIncome::join('tbl_users as tu','tu.id','=','tbl_promotional_income.toUserId')
                ->select('tbl_promotional_income.id','tu.user_id','tu.fullname','tbl_promotional_income.amount',DB::raw("DATE_FORMAT(tbl_promotional_income.entry_time,'%Y/%m/%d') as from_date"),DB::raw("DATE_FORMAT(tbl_promotional_income.entry_time,'%Y/%m/%d') as to_date"),DB::raw("DATE_FORMAT(tbl_promotional_income.entry_time,'%Y/%m/%d %H:%i:%s') as entry_time"));

        if(isset($request->user_id)) {
            $query  = $query->where('tu.user_id',$request->user_id);
        }
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_promotional_income.entry_time,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(!empty($request->search['value']) && isset($request->search['value'])){
            //searching loops on fields
            $fields = ['tpt.promotional_name'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query     = $query->orderBy('tbl_promotional_income.id','desc');
        $arrData   = setPaginate($query,$request->start,$request->length);

        if($arrData['totalRecord'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found',$arrData);  
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found','');
        }
    }
      /**
     * get all records of gallry
     *
     * @return \Illuminate\Http\Response
     */
    public function getGallryReport(Request $request) {

        $query = Gallry::select('*',DB::raw('DATE_FORMAT(created_at,"%Y/%m/%d %H:%i:%s") as created_at'))->where('status','Active');

        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_gallry');
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query          = $query->orderBy('id','desc');
        $totalRecord    = $query->count();
        if(isset($request->start)){
            $query  = $query->skip($request->start);
        }
        if(isset($request->length)){
            $query  = $query->take($request->length);
        }
        $arrPushNotify  = $query->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrPushNotify;

        if($arrData['recordsTotal'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
        }
    }
    /**
     * add Gallry
     *
     * @return \Illuminate\Http\Response
     */
    public function storeGallryReport(Request $request) {
        $rules = array(
            'name'          => 'required',
           // 'text'          => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err ='';
            foreach($message->all() as $error) {
                $err = $err." ".$error;
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,'');
        } else {
            $storeId = Gallry::insertGetId([
                'name'          => $request->name,
              //  'text'          => $request->text,
                'created_at'    => now(),
            ]);
            if($storeId){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Gallery added successfully.', '');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
    /**
     * get specific record of gallry
     *
     * @return \Illuminate\Http\Response
     */
    public function editGallry(Request $request) {
        
        $objGallry = Gallry::where('id',$request->id)->first();
        if(!empty($objGallry)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$objGallry);
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
        }
    }
    /**
     * update Gallry
     *
     * @return \Illuminate\Http\Response
     */
    public function updateGallryReport(Request $request) {
        $rules = array(
            'id'            => 'required',
            'name'          => 'required',
           // 'text'          => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err ='';
            foreach($message->all() as $error) {
                $err = $err ." ". $error;
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,'');
        } else {
            $update = Gallry::where('id',$request->id)->limit(1)->update([
                'name'          => $request->name,
              //  'text'          => $request->text,
                'updated_at'    => now()
            ]);
            if($update){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Gallery updated successfully.', '');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
    /**
     * delete specific record of gallry
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteGallry(Request $request) {
        
        $delete = Gallry::where('id',$request->id)->delete();
        $deleteImages = Gallerya::where('gid',$request->id)->delete();
        if(!empty($delete)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Gallery deleted successfully','');
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
        }
    }
    /**
     * get all records of gallry image
     *
     * @return \Illuminate\Http\Response
     */
    public function getGallryImageReport(Request $request) {

        $url    = url('uploads/gallery');
        $query  = Gallerya::selectRaw('*,(CASE WHEN attachment IS NOT NULL THEN CONCAT("'.$url.'","/",attachment) ELSE "" END) as attachment')->where('gid',$request->gid);
        
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_gallerya');
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $arrData = $query->orderBy('id','desc')->get();

        if(count($arrData) > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
        }
    }
    /**
     * add gallry image
     *
     * @return \Illuminate\Http\Response
     */
    public function storeGallryImage(Request $request) {

        $rules = array(
            'gid'           => 'required',
            'attachment'    => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err ='';
            foreach($message->all() as $error) {
                $err = $err." ".$error;
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,'');
        } else {
            $fileName = null;
            /* File Upload optional */ 
            if($request->hasFile('attachment')) {
                $file       = $request->file('attachment');
                // $fileName   = time().'.'.$file->getClientOriginalExtension();
                // $file->move(public_path('/uploads/gallery'), $fileName);
                $request->merge(['filename' => $fileName]);
            }
                
            $storeId = Gallerya::insertGetId([
                'gid'           => $request->gid,
                'attachment'    => $request->filename,
                'created_at'    => now(),
            ]);
            if($storeId){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Gallery image added successfully.', '');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
     public function SettingstoreGallryImage(Request $request) {

        $rules = array(
            // 'gid'           => 'required',
            'attachment'    => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err ='';
            foreach($message->all() as $error) {
                $err = $err." ".$error;
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,'');
        } else {
            $fileName = null;
            /* File Upload optional */ 
                if($request->hasFile('attachment')) 
            {
                // $file       = $request->file('attachment');
                // $fileName   = time().'.'.$file->getClientOriginalExtension();
                // $file->move(public_path('/admin_assets/uploads/gallery'), $fileName);
                $request->merge(['filename' => $fileName]);
            }
                
            $storeId = SettingGallery::insertGetId([
                'name'         => $request->name,
                'description'  => $request->description,

                'attachment'    => $request->filename,
                'created_at'    => now(),
            ]);
            if($storeId){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Setting Gallery image added successfully.', '');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
    /**
     * delete gallry image
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteGallryImage(Request $request) {

        $rules = array(
            'id'    => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err ='';
            foreach($message->all() as $error) {
                $err = $err." ".$error;
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,'');
        } else {
            $delete = Gallerya::where('id',$request->id)->delete();
            if($delete){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Gallery image deleted successfully.', '');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
}