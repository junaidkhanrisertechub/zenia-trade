<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use App\Models\PushNotification;
use App\Models\Activitynotification;
use App\Models\BulkEmails;
use App\Models\News;
use App\Models\Popup;
use App\User;
use DB;
use Config;
use Validator;
use Mail;
use File;

class NotificationController extends Controller
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
        $this->settings = Config::get('constants.settings');
        $this->commonController = $commonController;
    }
    /**
     * store push notifications
     *
     * @return \Illuminate\Http\Response
     */
    public function storePushnotification(Request $request) {
        $arrInput = $request->all();
        
        $rules = array(
            'message'    => 'required',
            'status'     => 'required',
            'end_time'   => 'required'
        );        
        $validator = Validator::make($arrInput, $rules);        
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
        } else {
            $arrInsert = [
                'message'    => $arrInput['message'],
                'status'     => $arrInput['status'],
                'end_time'   => $arrInput['end_time'],
                'entry_time' => now(),
            ];
            $storeId    = PushNotification::insertGetId($arrInsert);
            if(!empty($storeId)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Push notification added successfully','');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error while adding push notification','');
            }
        }
    }
    /**
     * store activity notification
     *
     * @return \Illuminate\Http\Response
     */
    public function storeActivityNotification(Request $request) {
        $arrInput  = $request->all();

        $rules = array(
            'id'        => 'required',
            'status'    => 'required',
            'message'   => 'required'
        );        
        $validator = Validator::make($arrInput, $rules);        
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
        } else {
            $arrInsert = [
                'id'        => $arrInput['id'],
                'status'    => $arrInput['status'],
                'message'   => $arrInput['message'],
                'entry_time' => now(),
            ];
            $storeId = Activitynotification::insertGetId($arrInsert);
            if(!empty($storeId)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Activity notification added successfully','');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error while adding activity notification','');
            }
        }
    }
    /**
     * get all records of push notifications
     *
     * @return \Illuminate\Http\Response
     */
    public function getPushNotification(Request $request) {
        $arrInput = $request->all();
        
        $query = PushNotification::where('status','1');
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query  = $query->whereBetween(DB::raw("(DATE_FORMAT(entry_time,'%Y-%m-%d'))"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_push_notification');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $totalRecord    = $query->count('sr_no');
        $query          = $query->orderBy('sr_no','desc');
        // $totalRecord    = $query->count();
        $arrPushNotify  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

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
     * get all records of activity notifications
     *
     * @return \Illuminate\Http\Response
     */
    public function getActivityNotification(Request $request) {
        $arrInput = $request->all();
        
        $query = Activitynotification::join('tbl_users as tu','tu.id','=','tbl_activity_notification.id')
                ->select('tbl_activity_notification.*','tu.user_id','tu.fullname');
        if(isset($arrInput['id'])){
            $query = $query->where('tbl_activity_notification.id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_activity_notification.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_activity_notification');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_activity_notification.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_activity_notification.srno');
        $query          = $query->orderBy('tbl_activity_notification.srno','desc');
        // $totalRecord    = $query->count();
        $arrPushNotify  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrPushNotify;

        if($arrData['recordsTotal'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Activity notification record found',$arrData);
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Activity notification record not found','');
        }
    }
    /**
     * get bulk emails records
     * @param
     * @return \Illuminate\Http\Response
     */
    public function getBulkEmails(Request $request) {
        $arrInput = $request->all();

        $query = BulkEmails::join('tbl_users as tu','tu.id','=','tbl_bulk_emails.created_by')->select('tbl_bulk_emails.*','tu.user_id as created_by');
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_bulk_emails.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_bulk_emails');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_bulk_emails.'.$field,'LIKE','%'.$search.'%');
                }
                $query->orWhere('tu.user_id','LIKE','%'.$search.'%');
            });
        }
        $totalRecord    = $query->count('tbl_bulk_emails.srno');
        $query          = $query->orderBy('tbl_bulk_emails.srno','desc');
        // $totalRecord    = $query->count();
        $arrBulkEmails  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrBulkEmails;

        if($arrData['recordsTotal'] > 0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
        }
    }
    /**
     * update push notifications record by srno with parameter
     * @param srno (int)
     * @return \Illuminate\Http\Response
     */
    public function updatePushNotification(Request $request) {
        $arrInput = $request->all();
        
        //validate input fields
        $rules = array(
        	'srno'     => 'required',
            'message'  => 'required',
            'status'   => 'required',
            'end_time' => 'required'
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
        } else {
	        $arrUpdateData = [
	        	'message' 	=> $arrInput['message'],
	        	'status' 	=> $arrInput['status'],
	        	'end_time' 	=> $arrInput['end_time']
	        ];
	        $update = PushNotification::where('sr_no',$arrInput['srno'])->update($arrUpdateData);

	        if(!empty($update)){
	            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record updated successfully','');
	        }else{
	            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Data already existed with given inputs','');
	        }
	    }
    }

    /**
     * send bulk emails
     * @param
     * @return \Illuminate\Http\Response
     */
    public function sendBulkEmails(Request $request) {
        $arrInput = $request->all();

        $rules = array(
            'subject'    => 'required',
            'message'    => 'required',
            'created_by' => 'required'
        );        
        $validator = Validator::make($arrInput, $rules);        
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
        } else {
            $arrUsers = $this->commonController->getAllUserList();
            $arrInput['created_by'] = $this->commonController->getLoggedUserData(['remember_token'=>$arrInput['created_by']])->id;

            $arrEmails = [];
            foreach ($arrUsers as $value) {
                array_push($arrEmails, $value->email);
            }

            $arrSendMail = [
                'pagename'  => 'emails.admin-emails.updateuserpassreply',
                'msg'       => $arrInput['message'],
            ];

            $to_mail    = $this->settings['enquiry_email'];
            $subject    = $arrInput['subject'];
            $bcc_mails  = $arrEmails;
            
            $mail = Mail::send($arrSendMail['pagename'], $arrSendMail, function ($message) use ($to_mail, $subject, $bcc_mails) {
                $from_mail = Config::get('constants.settings.from_email');
                $to_email = $to_mail;
                $project_name = Config::get('constants.settings.projectname');
                $message->from($from_mail, $project_name);
                $message->to($to_mail)->bcc($bcc_mails)->subject($project_name ." | ".$subject);
            });

            $arrInsert = [
                'from_mail'  => $this->settings['from_email'],
                'to_mail'    => 'all users',
                'subject'    => $arrInput['subject'],
                'message'    => $arrInput['message'],
                'created_by' => $arrInput['created_by'],
                'entry_time' => now(),
            ];
            $storeID = BulkEmails::insertGetId($arrInsert);
            if(!empty($storeID)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Bulk emails send successfully','');
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while sending emails','');
            }
        }
    }

    /**
     * get all records of news
     *
     * @return \Illuminate\Http\Response
     */
    public function getNews(Request $request) {
        $arrInput = $request->all();
        
        $query = News::select('*');
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_news');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query         = $query->orderBy('id','desc');
        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }

        if((isset($arrData['totalRecord']) > 0) || (count($arrData) > 0)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
        }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
        }
    }
    /**
     * store news
     * @param
     * @return \Illuminate\Http\Response
     */
    public function storeNews(Request $request){
        $arrInput = $request->all();

        $rules = array('subject' => 'required','description' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $arrInsert = [
                'sub'   => $arrInput['subject'],
                'text'  => $arrInput['description'],
                'entry_time' => now(),
            ];
            $storeId = News::insertGetId($arrInsert);
            if(!empty($storeId)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'News added successfully','');
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding news','');
            }
        }
    }
    /**
     * update news
     * @param
     * @return \Illuminate\Http\Response
     */
    public function updateNews(Request $request){
        $arrInput = $request->all();

        $rules = array('id' => 'required','subject' => 'required','description' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $arrUpdate = [
                'sub'   => $arrInput['subject'],
                'text'  => $arrInput['description'],
            ];
            $update = News::where('id',$arrInput['id'])->update($arrUpdate);
            if(!empty($update)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'News updated successfully','');
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'News already updated with same input','');
            }
        }
    }
    /**
     * delete news
     * @param
     * @return \Illuminate\Http\Response
     */
    public function deleteNews(Request $request){
        $arrInput = $request->all();

        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $delete = News::where('id',$arrInput['id'])->delete();
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'News deleted successfully','');
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleteing news','');
            }
        }
    }
    /**
     * edit news
     * @param
     * @return \Illuminate\Http\Response
     */
    public function editNews(Request $request){
        $arrInput = $request->all();

        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $editNews = News::where('id',trim($arrInput['id']))->first();
            if(!empty($editNews)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$editNews);
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Record not found','');
            }
        }
    }

    /**
     * get all records of popups
     *
     * @return \Illuminate\Http\Response
     */
    public function getPopup(Request $request) {
        $arrInput = $request->all();
            
        $rules = array(
            'remember_token' => 'required',
            'start' => 'required',
            'length' => 'required',
            /*'frm_date' => 'date',
            'to_date' => 'date'*/
        );  

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message,'');
        }else {
            $query = Popup::query();
            if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
                $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
            }

            if(isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])){
                //searching loops on fields
                $fields = getTableColumns('tbl_popup');
                $search = $arrInput['search']['value'];
                $query  = $query->where(function ($query) use ($fields, $search){
                    foreach($fields as $field){
                        $query->orWhere($field,'LIKE','%'.$search.'%');
                    }
                });
            }
            $totalRecord  = $query->count('srno');
            $query        = $query->orderBy('srno','desc');
            // $totalRecord  = $query->count();
            $arrPopups    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $arrPopups;

            if($totalRecord > 0){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$arrData);
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
            }
        }
    }
    /**
     * store Popup
     * @param
     * @return \Illuminate\Http\Response
     */
    public function storePopup(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'subject'       => 'required',
            'expdate'       => 'required',
            'description'   => 'required',
            'attachment'          => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        );
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {

            $userId = User::where('remember_token','=',$request->remember_token)->pluck('id')->first();
            $fileName = null;
            //file uploading
            // $path       = public_path().'/popups/';
            // if(!File::exists($path)){
            //     File::makeDirectory($path, $mode = 0777, true, true);
            // }
            $file       = Input::file('attachment');
            // $fileName   = time().'-'.$file->getClientOriginalName();
            // $file->move($path, $fileName);

            $arrInsert = [
                'sub'           => $arrInput['subject'],
                'text'          => $arrInput['description'],
                'expdate'       => date('Y-m-d',strtotime($arrInput['expdate'])),
                'attachment'    => $fileName,
                'created_by'    => $userId,
                'entry_time'    => now()
            ];
            
            /** @var [ins into Change History table] */
            //$this->commonController->storeChangeHistory($table = "tbl_popup", $request, 'insert');

            $storeId = Popup::insertGetId($arrInsert);
            if(!empty($storeId)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Popup added successfully.','');
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
    /**
     * update Popup
     * @param
     * @return \Illuminate\Http\Response
     */
    public function updatePopup(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'srno'       => 'required',
            'subject'        => 'required',
            'expdate'    => 'required',
            'description'       => 'required',
            'attachment' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        );
        
        /*$messages = array(
                        'sub.required' => 'Please enter subject.',
                        'expdate.required' => 'Please enter popup expiry date.',
                        'text.required' => 'Please enter description.',
                        'attachment.required' => 'Please attache popup image file.',
                    );*/  

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message,'');
        } else {
            $userId = User::where('remember_token','=',$request->remember_token)->pluck('id')->first();
            $fileName = null;
            //file uploading
            // $path       = public_path().'/popups/';
            // if(!File::exists($path)){
            //     File::makeDirectory($path, 0775, true);
            // }
            $file       = Input::file('attachment');
            // $fileName   = time().'-'.$file->getClientOriginalName();
            // $file->move($path, $fileName);

            $arrUpdate = [
                'sub'           => $arrInput['subject'],
                'text'          => $arrInput['description'],
                'expdate'       => date('Y-m-d',strtotime($arrInput['expdate'])),
                'attachment'    => $fileName,
                'edited_by'     => $userId,
                'updated_at'    => now()
            ];
            $update = Popup::where('srno',$arrInput['srno'])->update($arrUpdate);
            if(!empty($update)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Popup updated successfully.','');
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Popup already updated with same input.', '');
            }
            /** @var [ins into Change History table] */
            //$storeId = $this->commonController->storeChangeHistory($table = "tbl_popup", $request, 'update');
            /*if($storeId > 0 ) {
                
                
            } else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Unauthorized user','');
            }*/
        }
    }
    /**
     * delete Popup
     * @param
     * @return \Illuminate\Http\Response
     */
    public function deletePopup(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'srno' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message,'');

        } else {
            /** @var [ins into Change History table] */
            //$this->commonController->storeChangeHistory($table = "tbl_popup", $request, 'delete');
            $delete = Popup::where('srno',$arrInput['srno'])->delete();
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Popup deleted successfully.','');
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Something went wrong. Please try later.','');
            }
        }
    }
    /**
     * edit Popup
     * @param
     * @return \Illuminate\Http\Response
     */
    public function editPopup(Request $request){
        $arrInput = $request->all();

        $rules = array('srno' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $editPopup = Popup::where('srno',$arrInput['srno'])->first();
            if(!empty($editPopup)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found.',$editPopup);
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Record not found.','');
            }
        }
    }
}