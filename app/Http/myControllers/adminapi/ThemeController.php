<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\SocialType;
use App\Models\Promotionals;
use DB;
use Config;
use Validator;

class ThemeController extends Controller
{
    
    public function __construct() {
        $this->statuscode =Config::get('constants.statuscode');        
    }  

    public function showVidtypes(Request $request){
    	$vidtypes=SocialType::all();
    	if(count($vidtypes)>0){
    		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$vidtypes);
    	}
    	else{
    		return sendresponse($this->statuscode[404]['code'], $this->statuscode[200]['status'],'Record not found','');
    	}

    }
    public function showVideos(Request $request){
    	$query = Video::leftjoin('tbl_social_types as tst','tst.id','=','tbl_video.social_type_id')->select('tst.name as type','tbl_video.*')->where('status','Active');

        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_video');
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $totalRecord    = $query->count('id');
        $query          = $query->orderBy('id','desc');
        // $totalRecord    = $query->count();
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
    public function storeVideo(Request $request) {
        $rules = array(
            'name'          => 'required',
            'text'          => 'required',
            'link'          => 'required|url',
           // 'type'          => 'required',
            //'perClickIncome'=> 'required'
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
            $storeId = Video::insertGetId([
                'name'          => $request->name,
              //  'social_type_id'          => $request->type,
                'text'          => $request->text,
                'link'          => $request->link,
               // 'per_click_income'=> $request->perClickIncome,
                'created_at'    => now(),
            ]);
            if($storeId){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Video added successfully.', '');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
    
    public function editVideo(Request $request) {
        
        $objVideo = Video::leftjoin('tbl_social_types as tst','tst.id','=','tbl_video.social_type_id')->select('tst.name as type','tbl_video.*')->where('tbl_video.id',$request->id)->first();
        if(!empty($objVideo)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$objVideo);
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
        }
    }
    /**
     * update Video
     *
     * @return \Illuminate\Http\Response
     */
    public function updateVideo(Request $request) {
        $rules = array(
            'id'            => 'required',
            'name'          => 'required',
            'text'          => 'required',
            'link'          => 'required|url',
          //  'type'          => 'required',
           // 'perClickIncome'=> 'required'
            // 'status'        => 'required',
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
            $update = Video::where('id',$request->id)->limit(1)->update([
                'name'          => $request->name,
              //  'social_type_id'          => $request->type,
                'text'          => $request->text,
                'link'          => $request->link,
              //  'per_click_income'=> $request->perClickIncome,
                'updated_at'    => now(),
            ]);
            if($update){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Video updated successfully.', '');
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
    /**
     * delete specific record of video
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteVideo(Request $request) {
        
        $delete = Video::where('id',$request->id)->delete();
        if(!empty($delete)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Video deleted successfully','');
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong. Please try later.','');
        }
    }
    public function getUserTask(Request $request){
        $query = Promotionals::join('tbl_users as tu','tu.id','=','tbl_promotionals.id')
                ->join('tbl_social_types as tp','tp.id','=','tbl_promotionals.promotional_type_id')
                ->select('tbl_promotionals.*','tu.user_id as user_id','tu.fullname as fullname','tp.name as type',DB::raw('(CASE tbl_promotionals.income_status WHEN "0" THEN "Paid" WHEN "1" THEN "Unpaid" ELSE "" END) as status'));

        if(isset($request->frm_date) && isset($request->to_date)) {
            $request->frm_date = date('Y-m-d',strtotime($request->frm_date));
            $request->to_date  = date('Y-m-d',strtotime($request->to_date));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.date,'%Y-%m-%d')"),[$request->frm_date, $request->to_date]);
        }
        if(!empty($request->id) && isset($request->id)){
            //searching loops on fields
            $fields = getTableColumns('tbl_promotionals');
            $search = $request->id;
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_promotionals.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%')
                      ->orWhere('tu.fullname','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_promotionals.srno');
        $query          = $query->orderBy('tbl_promotionals.srno','desc');
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
}
