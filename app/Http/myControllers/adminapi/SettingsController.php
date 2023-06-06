<?php

namespace App\Http\Controllers\adminapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\adminapi\CommonController;
use App\Models\ProjectSettings;
use App\Models\ReservedAddress;
use App\Models\Withdrawbydate;
use App\User;
use DB;
use Config;
use Validator;
use Auth;
use Illuminate\Http\Response; 

class SettingsController extends Controller
{
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode,$constants_settings,$commonController;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CommonController $CommonController) {
        $this->statuscode           = Config::get('constants.statuscode');
        $this->constants_settings   = Config::get('constants.settings');
        $this->commonController     = $CommonController;
    }

    /**
     * get settings of project
     *
     * @return void
     */
    public function getProjectSettings() 
    {

		$ProjectSettings = ProjectSettings::where('status',1)->first();

        if(!empty($ProjectSettings)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Project settings found', $ProjectSettings);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Project settings not found', '');
        }
	}
    /**
     * get common settings of emails
     *
     * @return void
     */
    public function getConstantSettings() 
    {

        $ProjectSettings = $this->constants_settings;

        if(!empty($ProjectSettings)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Settings found', $ProjectSettings);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Settings not found', '');
        }
    }

    /**
     * get all country without pagination
     *
     * @return void
     */
    public function getCountry() 
    {

        $arrCountry = $this->commonController->getAllCountry();

        if(count($arrCountry) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrCountry);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    /**
     * get all products list
     *
     * @return void
     */
    public function getProductList() {
        $arrProducts = $this->commonController->getAllProducts();

        if(count($arrProducts)>0) 
        {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Records found', $arrProducts);
        } else {        
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Records not found', '');
        }
    }
    public function UpdateWithdrawSetting(Request $request) {
        try{
            $arrUpdt = array();
            $arrUpdt['withdraw_status'] = $request->withdraw_status;
            $arrUpdt['withdraw_start_time'] =(int) $request->withdraw_start_time;
            $arrUpdt['withdraw_day'] = $request->withdraw_day;
            $arrUpdt['withdraw_off_msg'] = $request->withdraw_off_msg;
            $updt = ProjectSettings::where('id',1)->update($arrUpdt);
            if($updt) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Settings updated', $updt);
            } 
        }catch(Exception $e){
            dd($e);
        }        
    }
    public function getUpdateWithdrawDte() 
    {
        $Withdrawbydate = Withdrawbydate::where('id',1)->first();
        $dataInput = array();
        $dataInput['id'] = $Withdrawbydate->id;
        $dataInput['first_day'] = $Withdrawbydate->first_day;
        $dataInput['second_day'] = $Withdrawbydate->second_day;
        $dataInput['third_day'] = $Withdrawbydate->third_day;
        $dataInput['sts'] = $Withdrawbydate->status;

        if(!empty($dataInput))
        {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $dataInput);
        }else{
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data not found', '');
        }
    }
     public function withdraw_by_date(Request $request) 
    {
        try{
                $arrUpdt = array();
                $arrUpdt['status'] = $request->status;
                $arrUpdt['first_day'] =(int) $request->first_day;
                $arrUpdt['second_day'] = $request->second_day;
                $arrUpdt['third_day'] = $request->third_day;
                $updt = Withdrawbydate::where('id',1)->update($arrUpdt);
                if($updt) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'WithdrawByDate updated', $updt);
            } 
        }catch(Exception $e)
        {
            
        }        
    }


    public function getAdminLoginDetails() {

        $arrData['user_id']         = Auth::user()->user_id;
        //$arrData['current_time']    = \Carbon\Carbon::now();
        $arrData['current_time']    = (Object)['date'=>\Carbon\Carbon::now()->setTimezone('Europe/London')->format('Y/m/d H:i:s')];
        $arrData['ip_address']      = $_SERVER['REMOTE_ADDR'];
        $arrData['server_time']     = getTimeZoneByIP($arrData['ip_address']);
        $arrWhere = [['used_status','Unused']];
        $arrData['address_balance'] = ReservedAddress::where($arrWhere)->count();
        // dd($arrData['server_time']);
        
        if(!empty($arrData)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
  
    public function  getAdminServerTime() {
        $arrData['user_id']         = Auth::user()->user_id;
        $arrData['ip_address']      = $_SERVER['REMOTE_ADDR'];
        $arrData['server_time']     = getTimeZoneByIP($arrData['ip_address']);
        // dd($arrData['server_time']);

        if(!empty($arrData)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
     /**
     * Check address is valid or not
     * @return \Illuminate\Http\Response
     */
    public function checkAddresses(Request $request) {
        try{

                $rules = array('address' => 'required', 'network_type' => 'required');
                $validator = checkvalidation($request->all(), $rules,'');
                if (!empty($validator)) {
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = $validator; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                }
                //--------------Check adress exist with received-------------//
                if (trim($request->input('network_type')) == 'BTC') {
                    $AddTrecived = blockio_address(trim($request->input('address')));
                    if (!empty($AddTrecived) && $AddTrecived['msg'] == 'fail') {
                        $chainTrecived = blockchain_address(trim($request->input('address')));
                        if (!empty($chainTrecived) && $chainTrecived['msg'] == 'failed') {
                            $cyperTrecived = blockcyper_address(trim($request->input('address')));
                            if (!empty($cyperTrecived) && $cyperTrecived['msg'] == 'failed') {
                                $bitapsrecived = blockbitaps_address(trim($request->input('address')));
                                if (!empty($bitapsrecived) && $bitapsrecived['msg'] == 'failed') {

                                    $arrStatus   = Response::HTTP_NOT_FOUND;
                                    $arrCode     = Response::$statusTexts[$arrStatus];
                                    $arrMessage  = 'Bitcoin address is not valid'; 
                                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');

                                } else {
                                    $arrStatus   = Response::HTTP_OK;
                                    $arrCode     = Response::$statusTexts[$arrStatus];
                                    $arrMessage  = 'Bitcoin address is valid'; 
                                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                                }
                            } else {
                                    $arrStatus   = Response::HTTP_OK;
                                    $arrCode     = Response::$statusTexts[$arrStatus];
                                    $arrMessage  = 'Bitcoin address is valid'; 
                                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                            }
                        } else {
                            $arrStatus   = Response::HTTP_OK;
                            $arrCode     = Response::$statusTexts[$arrStatus];
                            $arrMessage  = 'Bitcoin address is valid'; 
                            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                        }
                    } else {
                        $arrStatus   = Response::HTTP_OK;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Bitcoin address is valid'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                    }
                } else if (trim($request->input('network_type')) == 'ETH') {
                    $Transaction = ETHConfirmation(trim($request->input('address')));
                    if (!empty($Transaction) && $Transaction['msg'] == 'failed') {

                        $arrStatus   = Response::HTTP_NOT_FOUND;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Ethereum address is not valid'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                    } else {
                        $arrStatus   = Response::HTTP_OK;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Ethereum address is valid'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                    }
                } /*else if(trim($request->input('network_type')) == 'XRP') {
                    $Transaction = XRPConfirmation(trim($request->input('address')));
                    if (!empty($Transaction) && $Transaction['msg'] == 'failed') {
                        $arrStatus   = Response::HTTP_NOT_FOUND;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Ripple address is not valid'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                       
                    } else {
                        $arrStatus   = Response::HTTP_OK
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Ripple address is valid'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                   }
                }*/
           }catch(Exception $e){
               $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
               $arrCode     = Response::$statusTexts[$arrStatus];
               $arrMessage  = 'Something went wrong,Please try again'; 
               return sendResponse($arrStatus,$arrCode,$arrMessage,'');
           }       
    }
}