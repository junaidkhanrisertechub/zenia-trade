<?php

namespace App\Http\Controllers\adminapi;


use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\UserInfo;
use App\Models\AdminRights;
use App\Models\Navigation;
use App\Models\FcmUserNotification;
use App\Models\UserFcmDetails;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManageAdminNavigationController extends Controller {
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

    public function getAdminNavigationDetails(Request $request)
    {
        try {
            $arrInput = $request->all();
            $query = Navigation::select('id','menu','path','icon_class','main_menu_position','status','entry_time')->where('parent_id',0);
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_ps_admin_navigation.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
            }
            if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_ps_admin_navigation');
                $search = $arrInput['search']['value'];
                $query = $query->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhere('tbl_ps_admin_navigation.'.$field, 'LIKE', '%' . $search . '%');
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

    public function addAdminParentMenu(Request $request)
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

            $check_menu = Navigation::select('id')->where([['menu',$request->parent_menu],['sub_menu_position',0]])->orWhere([['main_menu_position',$request->position],['sub_menu_position',0]])->first();
             
            if(empty($check_menu)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Parent menu or position exist!!', '');
            }else{
                $insert = new Navigation;
                $insert->menu                   = trim($request->parent_menu);
                if(!empty(trim($request->path))){
                    $insert->path               = trim($request->path);
                }else{
                    $insert->path               = NULL;
                }
                $insert->icon_class             = trim($request->icon_class);
                $insert->main_menu_position     = trim($request->position);
                $insert->entry_time             = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $insert->save();
                if(!empty(trim($request->path))){
                    $insert_rights = new AdminRights;
                    $insert_rights['user_id'] = 1;
                    $insert_rights['parent_id'] = $insert->id;
                    $insert_rights['navigation_id'] = $insert->id;
                    $insert_rights->save();
                }
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Parent Menu Added Successfully', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }
    
    public function editAdminParentMenu(Request $request)
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
            
            $get_data = Navigation::select('id','menu','path','icon_class','main_menu_position','status','entry_time')->where('id',$request->id)->first();
            if(!empty($get_data)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $get_data);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }
    
    public function updateAdminParentMenu(Request $request)
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
    
            $check_position = Navigation::select('id')->where([['main_menu_position',$request->position],['sub_menu_position',0]])->first();
            $check_menu = Navigation::select('id')->where([['menu',$request->parent_menu],['sub_menu_position',0]])->first();
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

            if(!empty(trim($request->path))){
                $check_child = Navigation::where('parent_id',$request->id)->get();
                if(!$check_child->isEmpty()){
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], "Parent has child, So Path field shloud be empty!!", '');
                }
            }
            
            $update = array();
            $update['menu']                     = trim($request->parent_menu);
            if(!empty(trim($request->path))){
                $update['path']                 = trim($request->path);
            }else{
                $update['path']                 = NULL;
            }
            $update['icon_class']               = trim($request->icon_class);
            $update['main_menu_position']       = $request->position;
            $result = Navigation::where('id',$request->id)->update($update);
            $check_rights = AdminRights::where('navigation_id',$request->id)->first();
            if(!empty(trim($request->path))){
                if(empty($check_rights)){
                    $insert_rights = new AdminRights;
                    $insert_rights['user_id'] = 1;
                    $insert_rights['parent_id'] = $request->id;
                    $insert_rights['navigation_id'] = $request->id;
                    $insert_rights->save();
                }
            }else{
                if(!empty($check_rights)){
                    $result = AdminRights::where('id',$check_rights->id)->delete();
                }
            }
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Parent Menu Updated Successfully', '');    
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function deleteAdminParentMenu(Request $request)
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
            
            $get_data = Navigation::select('id','status')->where('id',$request->id)->first();
            
            if(!empty($get_data)){
                $update = array();
                if($get_data->status == 'Inactive'){
                    $update['status']                   = 'Active';
                }else{
                    $update['status']                   = 'Inactive';
                }
                $result = Navigation::where('id',$get_data->id)->update($update);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Parent Menu Status Changed Successfully', '');    
            }else{
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Invalid Request !!','');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function getAdminParentMenuList(Request $request)
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
            
            $query = Navigation::select('id','menu')
            ->where([['parent_id',0],['status','Active'],['path',NULL],['menu','!=','Logout']])
            ->orWhere([['path',''],['parent_id',0],['status','Active'],['menu','!=','Logout']])
            ->orderBy('id','desc')->get();
            if(!$query->isEmpty()){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $query);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function addAdminChildMenu(Request $request)
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

            $check_parent_menu = Navigation::select('id')->where([['id',$request->parent_menu],['parent_id',0],['sub_menu_position',0],['path',NULL],['status','Active']])->first();
            if(empty($check_parent_menu)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Invalid Parent Menu', '');
            }
            $check_child_menu = Navigation::select('id')->where([['parent_id',$request->parent_menu],['main_menu_position',0],['menu',$request->child_menu]])->first();
            if(!empty($check_child_menu)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Child Menu exist!!', '');
            }
            
            $check_child_position = Navigation::select('id')->where([['parent_id',$request->parent_menu],['main_menu_position',0],['sub_menu_position',$request->position]])->first();
            
            if(!empty($check_child_position)){
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Child Position exist!!', '');
            }else{
                $insert = new Navigation;
                $insert->menu                   = trim($request->child_menu);
                $insert->parent_id              = trim($request->parent_menu);
                $insert->path                   = trim($request->path);
                $insert->icon_class             = trim($request->icon_class);
                $insert->sub_menu_position      = trim($request->position);
                $insert->entry_time     = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $insert->save();

                if(isset($insert->id)){
                    $insert_rights = new AdminRights;
                    $insert_rights->user_id = 1;
                    $insert_rights->parent_id = $request->parent_menu;
                    $insert_rights->navigation_id = $insert->id;
                    $insert_rights->save();
                }
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Child Menu Added Successfully', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function getAdminSubNavigationDetails(Request $request)
    {
        try {
            $arrInput = $request->all();
            $query = DB::table('tbl_ps_admin_navigation as t1')->select('t1.id','t1.menu','t1.parent_id','t1.path','t1.icon_class','pm.menu as parent_menu','pm.main_menu_position','t1.sub_menu_position','t1.status','t1.entry_time')
            ->leftJoin('tbl_ps_admin_navigation as pm','pm.id','=','t1.parent_id')
            ->where('t1.parent_id','!=',0);
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(t1.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
            }
            if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_ps_admin_navigation');
                $search = $arrInput['search']['value'];
                $query = $query->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhere('tbl_ps_admin_navigation.'.$field, 'LIKE', '%' . $search . '%');
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

    public function editAdminChildMenu(Request $request)
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
            
            $get_data = Navigation::select('id','menu','parent_id','path','icon_class','sub_menu_position','status','entry_time')->where('id',$request->id)->first();
            if(!empty($get_data)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Found', $get_data);
            }else{
                return sendresponse($this->statuscode[402]['code'], $this->statuscode[402]['status'], 'Data Not Found', '');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }
    
    public function updateAdminChildMenu(Request $request)
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
    
            // $check_position = Navigation::select('id')->where([['sub_menu_position',$request->position],['main_menu_position',0]])->first();
            $check_position = Navigation::select('id')->where([['parent_id',$request->parent_id],['sub_menu_position',$request->position]])->first();
            if(!empty($check_position)){
                if($check_position->id != $request->id){
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Child position exist!!', '');
                }
            }

            $check_menu = Navigation::select('id')->where([['menu',$request->child_menu],['parent_id','!=',0],['main_menu_position',0]])->first();
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
            $update['sub_menu_position']        = $request->position;
            $result = Navigation::where('id',$request->id)->update($update);

            if($result){
                $update_rights = array();
                $update_rights['parent_id']                = $request->parent_id;
                $result = AdminRights::where('navigation_id',$request->id)->update($update_rights);
            }

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Child Menu Updated Successfully', '');    
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function deleteAdminChildMenu(Request $request)
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
            
            $get_data = Navigation::select('id','status')->where('id',$request->id)->first();
            
            if(!empty($get_data)){
                $update = array();
                if($get_data->status == 'Inactive'){
                    $update['status']                   = 'Active';
                }else{
                    $update['status']                   = 'Inactive';
                }
                $result = Navigation::where('id',$get_data->id)->update($update);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Admin Child Menu Status Changed Successfully', '');    
            }else{
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Invalid Request !!','');
            }
        } catch (\Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong, Please Try Again!', '');
        }
        
    }

    public function getAdminNavigationDetail(Request $request)
    {
        try {
            
            $arrInput = $request->all();
            // validate the info, create rules for the inputs
            $rules = array();
            $validator = Validator::make($arrInput, $rules);
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
            } else {

                $loggedUserId = Auth::user()->id;
                // echo $loggedUserId;
                if($loggedUserId){
                    $user_id = $loggedUserId;
                    $arrNavigations = DB::table('tbl_ps_admin_navigation as admin_nav')
                        // ->leftJoin('tbl_ps_admin_rights as admin_rights','admin_rights.navigation_id','=','admin_nav.id')
                        ->select('admin_nav.id','admin_nav.parent_id','admin_nav.menu','admin_nav.path','admin_nav.icon_class')
                        ->where([['admin_nav.status','Active'],['admin_nav.parent_id','==',0]])
                        // ->where([['admin_nav.status','Active'],['admin_nav.parent_id','==',0],['user_id',$user_id]])
                        ->orderBy('admin_nav.main_menu_position','asc')
                        ->get();
                    foreach($arrNavigations as $parent => $v){
                        $child = get_admin_sub_menu($v,$v->id,$user_id);
                        $final_nav[] =  $child;
                    }
                    //check data is empty or not
                    if(!empty($final_nav)) {
                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Records found', $final_nav);
                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found', '');
                    }
                }else{
                    return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Invalid User','');
                }
            }
        }catch(\Exception $e){
            dd($e);
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        } 

    }


}
