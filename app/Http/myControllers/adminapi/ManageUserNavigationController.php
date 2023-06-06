<?php

namespace App\Http\Controllers\adminapi;


use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\UserInfo;
use App\Models\UserNavigation;
use App\Models\FcmUserNotification;
use App\Models\UserFcmDetails;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManageUserNavigationController extends Controller {
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

    public function getUserNavigationDetails(Request $request)
    {
        try {
            $arrInput = $request->all();
            $query = UserNavigation::select('id','menu','path','icon_class','main_menu_position','status','entry_time')->where('parent_id',0);
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_user_navigation.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
            }
            if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_user_navigation');
                $search = $arrInput['search']['value'];
                $query = $query->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhere('tbl_user_navigation.'.$field, 'LIKE', '%' . $search . '%');
                    }
                });
            }
            $totalRecord = $query->orderBy('id','desc')->count();
            $arrFundReq = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['totalRecord'] = $totalRecord;
            $arrData['filterRecord'] = $totalRecord;
            $arrData['record'] = $arrFundReq;
            if($arrData['totalRecord'] > 0){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $arrData);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function addParentMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'parent_menu'           => 'required',
                'position'              => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 

            if($request->position <= 0){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Position should have Positive Numbers', '');
            }

            $check_menu = UserNavigation::select('id')->where([['menu',$request->parent_menu],['sub_menu_position',0]])->orWhere([['main_menu_position',$request->position],['sub_menu_position',0]])->first();
            if(!empty($check_menu)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Parent menu or position exist!!', '');
            }else{
                $insert = new UserNavigation;
                $insert->menu                   = $request->parent_menu;
                $insert->path                   = $request->path;
                $insert->icon_class             = $request->icon_class;
                $insert->main_menu_position     = $request->position;
                $insert->entry_time     = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $insert->save();
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Parent Menu Added Successfully', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }
    
    public function editParentMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'id'              => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 

            if(($request->id <= 0)){
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Please Try Again', '');
            }
            
            $get_data = UserNavigation::select('id','menu','path','icon_class','main_menu_position','status','entry_time')->where('id',$request->id)->first();
            if(!empty($get_data)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $get_data);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }
    
    public function updateParentMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'parent_menu'           => 'required',
                'position'              => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 
    
            if($request->position <= 0){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Position should have Positive Numbers', '');
            }
    
            $check_position = UserNavigation::select('id')->where([['main_menu_position',$request->position],['sub_menu_position',0]])->first();
            $check_menu = UserNavigation::select('id')->where([['menu',$request->parent_menu],['sub_menu_position',0]])->first();
            if(!empty($check_menu)){
                if($check_menu->id != $request->id){
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Parent menu exist!!', '');
                }
            }
            if(!empty($check_position)){
                if($check_position->id != $request->id){
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Parent position exist!!', '');
                }
            }
            $update = array();
            $update['menu']                     = $request->parent_menu;
            $update['path']                     = $request->path;
            $update['icon_class']               = $request->icon_class;
            $update['main_menu_position']       = $request->position;
            $result = UserNavigation::where('id',$request->id)->update($update);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Parent Menu Updated Successfully', '');    
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function deleteParentMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'id'           => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 
            
            $get_data = UserNavigation::select('id','status')->where('id',$request->id)->first();
            
            if(!empty($get_data)){
                $update = array();
                if($get_data->status == 'Inactive'){
                    $update['status']                   = 'Active';
                }else{
                    $update['status']                   = 'Inactive';
                }
                $result = UserNavigation::where('id',$get_data->id)->update($update);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Parent Menu  Status Changed Successfully', '');    
            }else{
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Invalid Request !!','');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function getParentMenuList(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                // 'id'           => 'required',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 
            
            $query = UserNavigation::select('id','menu')
            ->where([['parent_id',0],['status','Active'],['path',NULL],['menu','!=','Logout']])->orderBy('id','desc')->get();
            if(!$query->isEmpty()){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $query);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function addChildMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'child_menu'            => 'required',
                'parent_menu'           => 'required|numeric',
                'path'                  => 'required',
                'position'              => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 
            
            if($request->position <= 0){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Position should have Positive Numbers', '');
            }

            $check_parent_menu = UserNavigation::select('id')->where([['id',$request->parent_menu],['parent_id',0],['sub_menu_position',0],['path',NULL],['status','Active']])->first();
            if(empty($check_parent_menu)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Invalid Parent Menu', '');
            }
            $check_child_menu = UserNavigation::select('id')->where([['parent_id',$request->parent_menu],['main_menu_position',0],['menu',$request->child_menu]])->first();
            if(empty($check_child_menu)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Child Menu exist!!', '');
            }
            
            $check_child_position = UserNavigation::select('id')->where([['parent_id',$request->parent_menu],['main_menu_position',0],['sub_menu_position',$request->position]])->first();
            
            if(empty($check_child_position)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Child Position exist!!', '');
            }else{
                $insert = new UserNavigation;
                $insert->menu                   = trim($request->child_menu);
                $insert->parent_id              = trim($request->parent_menu);
                $insert->path                   = trim($request->path);
                $insert->icon_class             = trim($request->icon_class);
                $insert->sub_menu_position      = trim($request->position);
                $insert->entry_time     = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $insert->save();
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Child Menu Added Successfully', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function getUserSubNavigationDetails(Request $request)
    {
        try {
            $arrInput = $request->all();
            $query = DB::table('tbl_user_navigation as t1')->select('t1.id','t1.menu','t1.parent_id','t1.path','t1.icon_class','pm.menu as parent_menu','pm.main_menu_position','t1.sub_menu_position','t1.status','t1.entry_time')
            ->leftJoin('tbl_user_navigation as pm','pm.id','=','t1.parent_id')
            ->where('t1.parent_id','!=',0);
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(t1.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
            }
            if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_user_navigation');
                $search = $arrInput['search']['value'];
                $query = $query->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhere('tbl_user_navigation.'.$field, 'LIKE', '%' . $search . '%');
                    }
                });
            }
            $totalRecord = $query->orderBy('t1.id','desc')->count();
            $arrFundReq = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
            $arrData['totalRecord'] = $totalRecord;
            $arrData['filterRecord'] = $totalRecord;
            $arrData['record'] = $arrFundReq;
            if($arrData['totalRecord'] > 0){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $arrData);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function editChildMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'id'              => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 

            if(($request->id <= 0)){
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Please Try Again', '');
            }
            
            $get_data = UserNavigation::select('id','menu','parent_id','path','icon_class','sub_menu_position','status','entry_time')->where('id',$request->id)->first();
            if(!empty($get_data)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $get_data);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }
    
    public function updateChildMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'child_menu'            => 'required',
                'parent_id'             => 'required',
                'path'                  => 'required',
                'position'              => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 
    
            if($request->position <= 0){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Position should have Positive Numbers', '');
            }
    
            // $check_position = UserNavigation::select('id')->where([['sub_menu_position',$request->position],['main_menu_position',0]])->first();
            $check_position = UserNavigation::select('id')->where([['parent_id',$request->parent_id],['sub_menu_position',$request->position]])->first();
            if(!empty($check_position)){
                if($check_position->id != $request->id){
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Child position exist!!', '');
                }
            }

            $check_menu = UserNavigation::select('id')->where([['menu',$request->child_menu],['parent_id','!=',0],['main_menu_position',0]])->first();
            if(!empty($check_menu)){
                if($check_menu->id != $request->id){
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Child menu exist!!', '');
                }
            }

            $update = array();
            $update['menu']                     = $request->child_menu;
            $update['parent_id']                = $request->parent_id;
            $update['path']                     = $request->path;
            $update['icon_class']               = $request->icon_class;
            $update['sub_menu_position']       = $request->position;
            $result = UserNavigation::where('id',$request->id)->update($update);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Child Menu Updated Successfully', '');    
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function deleteChildMenu(Request $request)
    {
        try {
            $arrInput = $request->all();
            $rules = array(
                'id'           => 'required|numeric',
            );
            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } 
            
            $get_data = UserNavigation::select('id','status')->where('id',$request->id)->first();
            
            if(!empty($get_data)){
                $update = array();
                if($get_data->status == 'Inactive'){
                    $update['status']                   = 'Active';
                }else{
                    $update['status']                   = 'Inactive';
                }
                $result = UserNavigation::where('id',$get_data->id)->update($update);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Child Menu  Status Changed Successfully', '');    
            }else{
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Invalid Request !!','');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

}
