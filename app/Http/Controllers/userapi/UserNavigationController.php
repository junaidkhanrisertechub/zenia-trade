<?php

namespace App\Http\Controllers\userapi;


use App\Http\Controllers\Controller;
use Illuminate\Http\Response as Response;
use Illuminate\Http\Request as Request;
use App\User;
use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;

class UserNavigationController extends Controller {

    public function __construct(CommonController $commonController) {
        $this->statuscode = Config::get('constants.statuscode');
        $this->commonController = $commonController;
    }
    /**
     * Get country details
     *
     * @return \Illuminate\Http\Response
     */
    
    public function getUserNavigationDetail(Request $request)
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
                if($loggedUserId){

                    $arrNavigations = DB::table('tbl_user_navigation as user_nav')
                        ->select('user_nav.id','user_nav.parent_id','user_nav.menu','user_nav.path','user_nav.icon_class','user_nav.status')
                        ->where([['user_nav.status','Active'],['user_nav.parent_id','==',0]])
                        ->orderBy('user_nav.main_menu_position','asc')
                        ->get();

                    foreach($arrNavigations as $parent => $v){
                        $child = get_user_sub_menu($v,$v->id);
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
        }catch(Exception $e){
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        } 

    }

}
