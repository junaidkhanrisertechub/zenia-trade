<?php

namespace App\Http\Controllers\adminapi;

use App\Http\Controllers\adminapi\CommonController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Models\Navigation;
use App\Models\AdminRights;
use App\Models\Metadata;
use App\Models\verifyOtpStatus;
use App\Models\verifyAdminOtpStatus;
use App\User;
use DB;
use Config;
use Validator;

class NavigationsController extends Controller
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
        $this->statuscode = Config::get('constants.statuscode');
        $this->commonController = $commonController;
    }

    /**
     * get navigations for side bar
     *
     * @return \Illuminate\Http\Response
     */    
    /*updated code*/
    public function getNavigations(Request $request) {
      
        $arrInput = $request->all();

        // validate the info, create rules for the inputs
        $rules = array();
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {

            $loggedUserId = Auth::user()->id;
            $arrNavigations = DB::table('tbl_ps_admin_navigation as pan')
                            ->join('tbl_ps_admin_rights as par','par.navigation_id','=','pan.id')
                            ->join('tbl_ps_admin_navigation as pan1','pan1.id','=','pan.parent_id')
                            ->select('pan.id','pan.parent_id','pan.menu','pan.path','pan.icon_class','pan.status','par.user_id','pan1.id as parent_id','pan1.menu as parent_menu','pan1.path as parent_path','pan1.icon_class as parent_icon_class','pan1.status as parent_status')
                            ->where([['par.user_id',$loggedUserId],['pan.status','Active'],['pan1.status','Active']])
                            ->orderBy('pan.id','asc')
                            ->get();
            
            $arrFinalData = $filterData = [];
            foreach ($arrNavigations as $value) {
                $filterData[$value->parent_menu]['parentmenu']  = (object)[
                    'parent_id'         => $value->parent_id,
                    'user_id'           => $value->user_id,
                    'parent_menu'       => $value->parent_menu,
                    'parent_path'       => $value->parent_path,
                    'parent_icon_class' => $value->parent_icon_class,
                    'parent_status'     => $value->parent_status
                ];

                if($value->id != $value->parent_id){
                    $filterData[$value->parent_menu]['childmenu'][] = $value;
                } else {
                    $filterData[$value->parent_menu]['childmenu'] = [];
                }
            }
            
            foreach ($filterData as $value) {
                $arrFinalData[] = (object)[
                    'parentmenu' => $value['parentmenu'],
                    'childmenu' => $value['childmenu']
                ];
            }
           
            //check data is empty or not
            if(!empty($arrFinalData)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Records found', $arrFinalData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found', '');
            }
        }
    }

    /**
     * get all navigation list for all subadmin
     *
     * @return \Illuminate\Http\Response
     */
    
    /*updated code*/
    public function getSubadminNavigations(Request $request) {
        $arrInput  = $request->all();
        // validate the info, create rules for the inputs
        $rules = array('id'  => 'required');
        // run the validation rules on the inputs from the form
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $arrUserData = $this->commonController->getLoggedUserData(['id' =>$arrInput['id'] ]);
            $arrNavigtData = $this->commonController->getNavigations();

            $arrNavigations = DB::table('tbl_ps_admin_navigation as pan')
                    ->join('tbl_ps_admin_rights as par','par.navigation_id','=','pan.id')
                    ->join('tbl_ps_admin_navigation as pan1','pan1.id','=','pan.parent_id')
                    ->select(DB::raw('"Yes" as is_assign'),'pan.id','pan.menu','pan.parent_id','pan.path','pan.icon_class','pan.status','pan1.menu as parent_menu')
                    ->where([['par.user_id',$arrInput['id']],['pan.status','Active'],['pan.assign_status','Active'],['pan1.status','Active']])
                    ->orderBy('pan.id','asc')
                    ->get();

            if(!isset($arrInput['status'])) {

                $arrNavigationsIds  = [];
                foreach ($arrNavigations as $value) {
                    array_push($arrNavigationsIds, $value->id);
                }
                foreach ($arrNavigtData as $value) {
                    if(!in_array($value->id, $arrNavigationsIds)){
                        $objNavigat = DB::table('tbl_ps_admin_navigation as pan')
                                    ->join('tbl_ps_admin_navigation as pan1','pan1.id','=','pan.parent_id')
                                    ->select(DB::raw('"No" as is_assign'),'pan.*','pan1.menu as parent_menu')
                                    ->where([['pan.id',$value->id],['pan.parent_id','<>',0],['pan.status','Active'],['pan.assign_status','Active'],['pan1.status','Active']])
                                    ->orderBy('pan.menu','asc')
                                    ->first();
                        if($objNavigat){
                            $arrNavigations[] = $objNavigat;
                        }
                    }
                }
            }
           
            $arrFinalData = $filterData = [];
            foreach ($arrNavigations as $value) {
                if(!isset($filterData[$value->parent_menu]['parentmenu']) && empty($filterData[$value->parent_menu]['parentmenu'])){

                    $filterData[$value->parent_menu]['parentmenu']  = (object)[
                        'is_assign'     => ($value->id == $value->parent_id)?'Yes':'No',
                        'parent_id'     => $value->parent_id,
                        'parent_menu'   => $value->parent_menu
                    ];
                }

                if($value->id != $value->parent_id){
                    $filterData[$value->parent_menu]['childmenu'][] = $value;
                } else {
                    $filterData[$value->parent_menu]['childmenu'] = [];
                }
            }
        
            foreach ($filterData as $value) {
                $arrFinalData[] = (object)[
                    'parentmenu'    => $value['parentmenu'],
                    'childmenu'     => $value['childmenu']
                ];
            }

            $arrData['userdata']    = $arrUserData;
            $arrData['navigations'] = $arrFinalData;
            //check data is empty or not
            if (!empty($arrData)) {
              
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Records found', $arrData);
            } else {
             
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found', '');
            }
        }
    }


    public function getAdminNavigations(Request $request) {
        $arrInput  = $request->all();
        // validate the info, create rules for the inputs
       // $rules = array('id'  => 'required');
        // run the validation rules on the inputs from the form
       // $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
       /* if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {*/

            $loggedUserId = Auth::user()->id;
            $arrUserData = $this->commonController->getLoggedUserData(['id' =>$loggedUserId ]);
            $arrNavigtData = $this->commonController->getNavigations();

            $arrNavigations = DB::table('tbl_ps_admin_navigation as pan')
                    ->join('tbl_ps_admin_rights as par','par.navigation_id','=','pan.id')
                    ->join('tbl_ps_admin_navigation as pan1','pan1.id','=','pan.parent_id')
                    ->select(DB::raw('"Yes" as is_assign'),'pan.id','pan.menu','pan.parent_id','pan.path','pan.icon_class','pan.status','pan1.menu as parent_menu')
                    ->where([['par.user_id',$loggedUserId],['pan.status','Active'],['pan.assign_status','Active'],['pan1.status','Active']])
                    ->orderBy('pan.id','asc')
                    ->get();

            if(!isset($arrInput['status'])) {

                $arrNavigationsIds  = [];
                foreach ($arrNavigations as $value) {
                    array_push($arrNavigationsIds, $value->id);
                }
                foreach ($arrNavigtData as $value) {
                    if(!in_array($value->id, $arrNavigationsIds)){
                        $objNavigat = DB::table('tbl_ps_admin_navigation as pan')
                                    ->join('tbl_ps_admin_navigation as pan1','pan1.id','=','pan.parent_id')
                                    ->select(DB::raw('"No" as is_assign'),'pan.*','pan1.menu as parent_menu')
                                    ->where([['pan.id',$value->id],['pan.parent_id','<>',0],['pan.status','Active'],['pan.assign_status','Active'],['pan1.status','Active']])
                                    ->orderBy('pan.menu','asc')
                                    ->first();
                        if($objNavigat){
                            $arrNavigations[] = $objNavigat;
                        }
                    }
                }
            }
           
            $arrFinalData = $filterData = [];
            foreach ($arrNavigations as $value) {
                if(!isset($filterData[$value->parent_menu]['parentmenu']) && empty($filterData[$value->parent_menu]['parentmenu'])){

                    $filterData[$value->parent_menu]['parentmenu']  = (object)[
                        'is_assign'     => ($value->id == $value->parent_id)?'Yes':'No',
                        'parent_id'     => $value->parent_id,
                        'parent_menu'   => $value->parent_menu
                    ];
                }

                if($value->id != $value->parent_id){
                    $filterData[$value->parent_menu]['childmenu'][] = $value;
                } else {
                    $filterData[$value->parent_menu]['childmenu'] = [];
                }
            }
        
            foreach ($filterData as $value) {
                $arrFinalData[] = (object)[
                    'parentmenu'    => $value['parentmenu'],
                    'childmenu'     => $value['childmenu']
                ];
            }

            $arrData['userdata']    = $arrUserData;
            $arrData['navigations'] = $arrFinalData;
            //check data is empty or not
            if (!empty($arrData)) {
              
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Records found', $arrData);
            } else {
             
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found', '');
            }
       // }
    }



    // Old Code
    public function assignSubadminRights1(Request $request) {
        $arrInput = $request->all();

        $arrInput1        = [];
        $arrInput1['id']  = $arrInput['id'];

        $arrInputNavigation = $arrInput['navigations']['arrData'];
        if(!empty($arrInputNavigation) && count($arrInputNavigation) > 0){
          $getParentIds = Navigation::where([['status','=','Active'],['parent_id','!=',0]])->whereIn('id',$arrInputNavigation)->get();
          foreach ($getParentIds as $value) {
            $arrInput1['navigations'][$value->parent_id][] = $value->id;
          }  
        }
        
        if(!empty($arrInput1['id'])) {

            //delete old navigations mapping of specific subadmin user
            AdminRights::where('user_id',trim($arrInput1['id']))->delete();

            if(!empty($arrInput1['navigations']) && count($arrInput1['navigations'])>0){
              //add new navigations mapping of specific subadmin user
              $arrInsertData = [];
              foreach ($arrInput1['navigations'] as $key => $value) {
                  foreach ($value as $value1) {
                    array_push($arrInsertData, [
                        'user_id'       => $arrInput1['id'], 
                        'parent_id'     => $key, 
                        'navigation_id' => $value1
                    ]);
                  }
              }
              $storedId = AdminRights::insert($arrInsertData);
              if($storedId) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Rights assigned successfully','');
              } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while assigning rights','');
              }
            } else {
              return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Rights assigned successfully','');
            }
        }
    }

    /**
     * update navigations rights for subadmin
     *
     * @return \Illuminate\Http\Response
     * { "navigations": { "arrData": 45,6,5 }, "id": "223" }
     * Updated code
     */

    public function assignSubadminRights(Request $request) {
        $arrInput = $request->all();

        $arrInput1        = [];
        $arrInput1['id']  = $arrInput['id'];
        $navigation_ids = explode(',',$arrInput['navigations']['arrData']);
        
        $count = count($navigation_ids);

        $arrInputNavigation = $arrInput['navigations']['arrData'];
        if(!empty($arrInputNavigation) && ($count > 0)){
          $getParentIds = Navigation::where([['status','=','Active'],['parent_id','!=',0]])->whereIn('id',$navigation_ids)->get();
          foreach ($getParentIds as $value) {
            $arrInput1['navigations'][$value->parent_id][] = $value->id;
          }  
        }      
        
        if(!empty($arrInput1['id'])) {

            //delete old navigations mapping of specific subadmin user
            AdminRights::where('user_id',trim($arrInput1['id']))->delete();

            if(!empty($arrInput1['navigations']) && count($arrInput1['navigations'])>0){
              //add new navigations mapping of specific subadmin user
              $arrInsertData = [];
              foreach ($arrInput1['navigations'] as $key => $value) {
                  foreach ($value as $value1) {
                    array_push($arrInsertData, [
                        'user_id'       => $arrInput1['id'], 
                        'parent_id'     => $key, 
                        'navigation_id' => $value1
                    ]);
                  }
              }
              $storedId = AdminRights::insert($arrInsertData);
              if($storedId) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Rights assigned successfully','');
              } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while assigning rights','');
              }
            } else {
              return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Rights assigned successfully','');
            }
        }
    }



    public function assignToAdmin(Request $request) {
        $arrInput = $request->all();
        //dd($arrInput);
        $arrInput1        = [];
        $loggedUserId = Auth::user()->id;
        $arrInput1['id']  = $loggedUserId;
        $navigation_ids = explode(',',$arrInput['navigations']['arrData']);
        
        $count = count($navigation_ids);

        $arrInputNavigation = $arrInput['navigations']['arrData'];
        if(!empty($arrInputNavigation) && ($count > 0)){
          $getParentIds = Navigation::where([['status','=','Active'],['parent_id','!=',0]])->whereIn('id',$navigation_ids)->get();
          foreach ($getParentIds as $value) {
            $arrInput1['navigations'][$value->parent_id][] = $value->id;
          }  
        }      
        
        if(!empty($arrInput1['id'])) {

            //delete old navigations mapping of specific subadmin user
            AdminRights::where('user_id',trim($arrInput1['id']))->delete();

            if(!empty($arrInput1['navigations']) && count($arrInput1['navigations'])>0){
              //add new navigations mapping of specific subadmin user
              $arrInsertData = [];
              foreach ($arrInput1['navigations'] as $key => $value) {
                  foreach ($value as $value1) {
                    array_push($arrInsertData, [
                        'user_id'       => $arrInput1['id'], 
                        'parent_id'     => $key, 
                        'navigation_id' => $value1
                    ]);
                  }
              }
              $storedId = AdminRights::insert($arrInsertData);
              if($storedId) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Rights assigned successfully','');
              } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while assigning rights','');
              }
            } else {
              return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Rights assigned successfully','');
            }
        }
    }

    /**
     * get metadata list
     *
     * @return \Illuminate\Http\Response
     */
    public function getMetadata(Request $request) {
        $pageUrl = $request->Input('page_url');
        $arrMetadata = Metadata::where('page_url','like','%'.$pageUrl.'%')->orderBy('entry_time','desc')->first();

        //check data is empty or not
        if (count($arrMetadata)>0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Records found', $arrMetadata);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found', '');
        }
    }
    
    /**
     * get only subadmin list
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllSubadminDetails(Request $request) {
        $arrInput = $request->all();
        
        $query = User::where('type','sub-admin')->where('id','!=',1);
        if(isset($arrInput['id'])){
            $query = $query->where('user_id',$arrInput['id']);
        }
        if(isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d',strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if(!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])){
            //searching loops on fields
            $fields = getTableColumns('tbl_users');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query        = $query->orderBy('id','desc');

        if(isset($arrInput['start']) && isset($arrInput['length'])){
            $arrData = setPaginate1($query,$arrInput['start'],$arrInput['length']);
        } else {
            $arrData = $query->get(); 
        }

        // $totalRecord  = $query->count();
        // $arrSubadmin  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        // $arrData['recordsTotal']    = $totalRecord;
        // $arrData['recordsFiltered'] = $totalRecord;
        // $arrData['records']         = $arrSubadmin;

        //if($arrData['recordsTotal'] > 0) {
        if((isset($arrData['totalRecord']) > 0) || (count($arrData) > 0)){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Subadmin found', $arrData);
        } else {        
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Subadmin not found', '');
        }
    }

    /**
     * get only subadmin list without pagination for dropdown
     *
     * @return \Illuminate\Http\Response
     */
    public function getSubadmins(Request $request) {
        $arrInput = $request->all();
        
        $arrSubadmins = $this->commonController->getSubadminList(['type'=>'sub-admin']);
        if(count($arrSubadmins) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Subadmins found', $arrSubadmins);
        } else {        
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Subadmins not found', '');
        }
    }
    public function getOtpSts(Request $request) {
        $arrInput = $request->all();
        
        $getsts = verifyOtpStatus::select('profile_update_status','withdraw_update_status','transfer_update_status','topup_update_status')->first();
        //dd($getsts);
        if(!empty($getsts)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Subadmins found', $getsts);
        } else {        
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Subadmins not found', '');
        }
    }
    public function updateOtpSts(Request $request){
        //dd($request);
        $otpsts = array();
        $otpsts['profile_update_status'] = ($request->profile ? 1:0);
        $otpsts['withdraw_update_status'] = ($request->withdraw ? 1:0);
        $otpsts['transfer_update_status'] = ($request->transfer ? 1:0);
        $otpsts['topup_update_status'] = ($request->topup ? 1:0);
                   
        $updateData=verifyOtpStatus::where('statusID', 1)->update($otpsts);
        //dd($otpsts);
        if(!empty($updateData)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Status Updated','');
        } else {        
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Status not updated', '');
        }
    }

    public function getAdminOtpSts(Request $request) {
        $arrInput = $request->all();
        
        $getsts = verifyAdminOtpStatus::select('profile_update_status','topup_update_status','withdraw_pending_update_status','withdraw_verfied_otp_update_status','topup_update_status','add_fund_otp_status','remove_fund_otp_status')->first();
        //dd($getsts);
        if(!empty($getsts)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $getsts);
        } else {        
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Data not found', '');
        }
    }
    public function updateAdminOtpSts(Request $request){
        //dd($request);
        $otpsts = array();
        $otpsts['profile_update_status'] = ($request->profile ? 1:0);
        $otpsts['withdraw_pending_update_status'] = ($request->withdraw_pending ? 1:0);
        $otpsts['withdraw_verfied_otp_update_status'] = ($request->withdraw_verified ? 1:0);
        $otpsts['topup_update_status'] = ($request->topup ? 1:0);
        $otpsts['add_fund_otp_status'] = ($request->add_fund ? 1:0);
        $otpsts['remove_fund_otp_status'] = ($request->remove_fund ? 1:0);
                   
        $updateData=verifyAdminOtpStatus::where('statusID', 1)->update($otpsts);
        //dd($otpsts);
        if(!empty($updateData)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Status Updated','');
        } else {        
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Status not updated', '');
        }
    }
}