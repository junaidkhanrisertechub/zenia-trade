<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use Illuminate\Http\Response as Response;
use App\Http\Requests;
use App\Models\Enquiry;
use App\Models\ReplyEnquiryReport;
use App\User;
use DB;
use Config;
use Mail;

class EnquiryController extends Controller
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
     * get all enquiries
     * @return \Illuminate\Http\Response
     */
    public function getEnquiry(Request $request) {
        $arrInput = $request->all();
        $url    = url('attachment/');
        $query  = Enquiry::selectRaw('*,CONCAT("'.$url.'","/",attachment) as attachment');
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_enquiry');
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
        $arrEnquiry     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrEnquiry;

        if($arrData['recordsTotal'] > 0) {
    	   return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
    	} else {
    	   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record Not Found', '');
    	}
    }

        public function sendEnquiryMailCountZero(){
        try {
        $data= Enquiry::where('status','1')->update(['status' => '0']);
        
        $intCode            = Response::HTTP_OK;
        $strStatus          = Response::$statusTexts[$intCode];
        $strMessage         = trans('ok');
        return sendResponse($intCode, $strStatus, $strMessage,$data);
            
        }catch (Exception $e) {
            $intCode            = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strStatus          = Response::$statusTexts[$intCode];
            $strMessage         = trans('user.error');
            return sendResponse($intCode, $strStatus, $strMessage,[]);
        }
    }

    /**
     * get all enquiry reply reports
     * @return \Illuminate\Http\Response
     */
    public function getReplyEnquiryReport(Request $request) {
        $arrInput = $request->all();

        $query = ReplyEnquiryReport::select('tbl_reply_enquiry_reports.*');
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_reply_enquiry_reports.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['id'])){
            $query = $query->where('tu.user_id',$arrInput['id']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_reply_enquiry_reports');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_reply_enquiry_reports.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%');
            });
        }
        $totalRecord     = $query->count('tbl_reply_enquiry_reports.srno');
        $query           = $query->orderBy('tbl_reply_enquiry_reports.srno','desc');
        // $totalRecord     = $query->count();
        $arrReplyEnquiry = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrReplyEnquiry;

        if($arrData['recordsTotal'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    /**
     * get all User Link reports
     * @return \Illuminate\Http\Response
     */
    public function getUserLinkReport(Request $request) {
        $arrInput = $request->all();

        $query = User::select('tbl_users.*');
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(isset($arrInput['id'])){
            $query = $query->where('tbl_users.user_id',$arrInput['id']);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_users');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_users.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tbl_users.user_id','LIKE','%'.$search.'%');
            });
        }
        $totalRecord     = $query->count('tbl_users.id');
        $query           = $query->orderBy('tbl_users.id','desc');
        // $totalRecord     = $query->count();
        $arrUserLink = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrUserLink;

        if($arrData['recordsTotal'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    /**
     * send enquiry mail reply from admin
     * @return \Illuminate\Http\Response
     */
    public function sendEnquiryMail(Request $request) {
        $arrInput   = $request->all();
        $arrInput['from_mail'] = $this->settings['from_email'];

        $sendInputData = ['pagename' => 'emails.admin-emails.enquiry','msg' => $request->input('message')];
        $to_mail    = $request->input('to_mail');
        $subject    = $request->input('subject');
        $mail       = sendMail($sendInputData,$to_mail,$subject);
        
        // $arrInput['created_by'] = $this->commonController->getLoggedUserData(['remember_token'=>$arrInput['created_by']])->id;

        $arrInsert = [
            'to_mail'     => $arrInput['to_mail'],
            'from_mail'   => $arrInput['from_mail'],
            'subject'     => $arrInput['subject'],
            'message'     => $arrInput['message'],
            //'created_by'  => $arrInput['created_by'],
            'entry_time' => now()
        ];
        $saveReply = ReplyEnquiryReport::insertGetId($arrInsert);

        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Message sent successfully', '');
    }
}