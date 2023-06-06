<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use App\Models\ProjectSettings;
use App\Models\Topup;
use App\Models\WithdrawPending;
use App\User;
use App\Models\StatisticSettings;
use App\Models\TransactionInvoice;
use App\Models\CryptoAddressValidation;
use Config;
use Validator;
use Exception;
use DB;

class SettingsController extends Controller {
/**
     * Get project coin name     *
     * @return \Illuminate\Http\Response
     */
    function getProjectDetails() {
      try{
            $coin_name = ProjectSettings::where([['status', '=', 1],])->pluck('coin_name')->first();
            if (!empty($coin_name)) {
                $DataArr = array();
                $DataArr['coin_name'] = $coin_name;
                    $arrStatus   = Response::HTTP_OK;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Project coin name get successfully'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,$DataArr);
               
            } else {
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Data not found'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }
        }catch(Exception $e){
           $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
           $arrCode     = Response::$statusTexts[$arrStatus];
           $arrMessage  = 'Something went wrong,Please try again'; 
           return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }    
    }

    /**
     * Get project details     *
     * @return \Illuminate\Http\Response
     */
    public function getProjectSettingsDetails() {
        
     try{
            $dataname = ProjectSettings::where([['status', '=', 1],])->first();

            if (!empty($dataname)) {

                    $arrStatus   = Response::HTTP_OK;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Project data found successfully'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,$dataname);
            } else {
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Data not found'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }
        }catch(Exception $e){
           $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
           $arrCode     = Response::$statusTexts[$arrStatus];
           $arrMessage  = 'Something went wrong,Please try again'; 
           return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }    
    }

    public static function addressValidation($networkTpe,$crypto_address)
    {
        $query=CryptoAddressValidation::where('btc_symbol',$networkTpe)->first();
        $address = str_split($crypto_address);

        if($networkTpe=='BTC')
        {
           
            $rules=explode(",",$query->rules);
     
            if(strlen($crypto_address) >= $query->min_length && strlen($crypto_address) <= $query->max_length)
            {
                    if($rules[0]==$address[0]) 
                     {
                        return "true";
                     }
                     elseif($rules[1]==$address[0])
                     {
                        return "true";
                     }
                     elseif($rules[2]==$address[0])
                     {
                        return "true";
                     }  
                     else{
                        return "You have enter wrong address BTC address can start with only ".$rules[0].", ".$rules[1].", ".$rules[2]; 
                     }
            }
            else
            {
             return "BTc address length is not vaid address length should be less than ".$query->max_length."and grater than".$query->min_length;
            }
        }

        if($networkTpe=='ETH')
        {
            if(strlen($crypto_address) >= $query->min_length && strlen($crypto_address) <= $query->max_length)
            {
                 if($query->rules==$address[0])
                 {
                    return "You have enter wrong address Ethereum address can start with only ".$query->rules; 
                 }  
                 else
                 {
                    return "true";
                 }
            }
            else
            {
                return "Ethereum address length is not vaid address length should be less than ".$query->max_length."and grater than".$query->min_length;  
            }
        }
    }

    public function checkValidation(Request $request)
    {
        // dd(12); 
        $this->statuscode  = Config::get('constants.statuscode');

        try{

            $query=CryptoAddressValidation::where('btc_symbol',$request->crypto_address)->first();
            $address = str_split($request->address);
            $rules=explode(",",$query->rules);
           
            if($request->crypto_address=='BTC')
            { 
                if(strlen($request->address) >= $query->min_length && strlen($request->address) <= $query->max_length)
                {
                        if($rules[0]==$address[0])
                         {
                            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],"Btc Address Valid", '');
                         }
                         elseif($rules[1]==$address[0])
                         {
                            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],"Btc Address Valid", '');
                         }
                         elseif($rules[2]==$address[0])
                         {
                            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],"Btc Address Valid", '');
                         }
                         else{
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"You have enter wrong address BTC address can start with only ".$rules[0].", ".$rules[1].", ".$rules[2], ''); 
                         }
                }
                else
                {
                 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"BTc address length is not vaid address length should be less than ".$query->max_length." and grater than ".$query->min_length, '');
                }
            }

            if($request->crypto_address=='ETH')
            {
                if(strlen($request->address) >= $query->min_length && strlen($request->address) <= $query->max_length)
                {
                        if($rules[0]==$address[0])
                         {
                            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],"Btc Address Valid", '');
                         }
                         else
                         {
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"You have enter wrong address BTC address can start with only ".$rules[0].", ".$rules[1].", ".$rules[2], ''); 
                         }
                }
                else
                {
                 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"BTc address length is not vaid address length should be less than ".$query->max_length."and grater than".$query->min_length, '');
                }
            }

            if($request->crypto_address=='TRX')
            {

            }
        }
        catch(Exception $e){
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
         }  
    }
    
     /**
     * Check address is valid or not
     * @return \Illuminate\Http\Response
     */
    public function checkAddresses(Request $request) {
        try{

                $rules = array('address' => 'required|regex:/^\S*$/', 'network_type' => 'required');
                $validator = checkvalidation($request->all(), $rules,'');
                if (!empty($validator)) {
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = $validator; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                }
                //--------------Check adress exist with received-------------//
                if (trim($request->input('network_type')) == 'BTC') {
                    //dd(strlen($request->address));
                      if(strlen($request->address) >= 26 && strlen($request->address) <= 42){
                         $arrStatus   = Response::HTTP_OK;
                         $arrCode     = Response::$statusTexts[$arrStatus];
                         $arrMessage  = 'Bitcoin address is valid'; 
                          return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                    }else{
                         $arrStatus   = Response::HTTP_NOT_FOUND;
                                    $arrCode     = Response::$statusTexts[$arrStatus];
                                    $arrMessage  = 'Bitcoin address is not valid'; 
                                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                    }
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


    
    public function getProjectStatistics() {
        try{

            $total_users = User::select('id')->where('id','!=',1)->count('id');
            /*$total_deposite = Topup::selectRaw('COALESCE(SUM(amount),0) as total')->where('top_up_by','!=',1)->pluck('total')->first();*/
            $total_deposite = TransactionInvoice::selectRaw('COALESCE(ROUND(SUM(hash_unit),2),0) as total')->where('in_status',1)->pluck('total')->first();
            $total_withdraw = WithdrawPending::selectRaw('COALESCE(SUM(amount),0) as total')->where('status',1)->pluck('total')->first();

            $settings = StatisticSettings::selectRaw("start_from_date,(accounts_multiplier * ".$total_users.") as total_users ,(deposit_multiplier * ".$total_deposite.") as total_deposit,(withdraw_multiplier * ".$total_withdraw.") as total_withdraw")->where('status',1)->first();

            if (!empty($settings)) {
                $DataArr = $settings->toArray();
                $date = \Carbon\Carbon::parse($settings->start_from_date);
                $now = \Carbon\Carbon::now();
                $diff = $date->diffInDays($now);
                $DataArr['running_day'] = $diff;
                $arrStatus   = Response::HTTP_OK;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Data found'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,$DataArr);
               
            } else {
                $arrStatus   = Response::HTTP_NOT_FOUND;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Data not found'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }
        }catch(Exception $e){
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }    
    }

}
