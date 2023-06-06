<?php

namespace App\Http\Controllers\adminapi;


use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\UserInfo;
use App\Models\FireBaseSettings;
use App\Models\FcmUserNotification;
use App\Models\UserFcmDetails;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FireBaseController extends Controller {
	/**
	 * define property variable
	 *
	 * @return
	 */
	public $statuscode;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->statuscode = Config::get('constants.statuscode');
	}

    public function getFirebaseDetails(Request $request)
    {
        try {
            $query = FireBaseSettings::select('url','fcm_key')->where('status','Active')->first();
            if(!empty($query)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $query);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function firebaseRecords(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'firebaseurl'          => 'required|url',
                'fcm_key'              => 'required',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 
            $query = FireBaseSettings::select('id')->where('status','Active')->first();
            if(!empty($query)){
                $update['url'] = $request->firebaseurl;
                $update['fcm_key'] = $request->fcm_key;
                $result = FireBaseSettings::where('id',$query->id)->update($update);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'FireBase Records Updated Successfully', '');
            }else{
                $insert = new FireBaseSettings;
                $insert->url = $request->firebaseurl;
                $insert->fcm_key = $request->fcm_key;
                $insert->entry_time = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $insert->save();
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'FireBase Records Added Successfully', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    // public function getAllUserId(Request $request)
    // {
    //     try {
    //         $get_allUser_list = UserInfo::select('user_id','id')->where([['type','=',''],['status','=','Active']])->get();
    //         if(!empty($get_allUser_list)){
    //             return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User data Found!', $get_allUser_list);
    //         }else{
    //             return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'User Not Found!', '');
    //         }
    //     } catch (\Exception $e) {
    //         return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
    //     }
    // }

    
    public function sendUserFirebaseNoti(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'type'              => 'required|numeric',
                'title'             => 'required',
                'message'           => 'required',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 

            if(($request->type != '1') && ($request->type != '2')){
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Please Select valid Type', '');
            }
            if($request->type == '1'){
                if(empty($request->username)){
                    return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Please Enter User Id', '');
                }
            }

            $get_fcm_keys = FireBaseSettings::select('url','fcm_key')->where('status','=','Active')->first();
            if(empty($get_fcm_keys)){
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Please Set Fire Base Keys', '');
            }
            if($request->type == '1'){
                $get_user_id = UserInfo::select('id','user_id','fullname')->where([['type','=',''],['status','=','Active'],['user_id',$request['username']]])->first();
                $user_fcm_details = UserFcmDetails::select('device_token')->where('user_id',$get_user_id['id'])->first();
                $noti_user_id = $get_user_id['id'];
                $device_token = array($user_fcm_details['device_token']);
            }elseif($request->type == '2'){
                $get_user_id = UserInfo::select('id','user_id','fullname')->where([['type','=',''],['status','=','Active']])->get();
                $user_fcm_details = UserFcmDetails::select('device_token')->where('device_token','!=','')->get()->toArray();
                $noti_user_id = 0;
                $device_token = array_column($user_fcm_details, 'device_token');
            }
            if(!empty($get_user_id)){
                if(!empty($user_fcm_details)){
                    $add_FCM_noti['id'] = $noti_user_id;
                    $add_FCM_noti['title'] = $request['title'];
                    $add_FCM_noti['message'] = $request['message'];
                    $result = FcmUserNotification::insert($add_FCM_noti);
                    
                    // $this->send_notification($notiData, $user_tokens,$postedData['body'], $notiData['noti_type'] ,'A');
                    $notiData['noti_type'] = 'adminalert';
                    $output = send_FCM_notification($request, $device_token, $get_fcm_keys['url'], $get_fcm_keys['fcm_key'],$notiData['noti_type'] ,'A');
                    
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Notification send to user', '');
                }else {
                    return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'No User FCM Details Found!', '');
                }
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Invalid User!', '');
            }

        } catch (\Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

}
