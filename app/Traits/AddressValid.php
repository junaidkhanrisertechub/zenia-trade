<?php

namespace App\Traits;
use Illuminate\Http\Response as Response;
use Exception;
use Illuminate\Http\Request;




trait AddressValid{
  

  /**
     * Check BTC/ETH address valid or not
     *
     * @return \Illuminate\Http\Response
     */ 
    public function checkAddressValidation(Request $request) {
     try{
            $rules = array('address' => 'required');
            $validator = checkvalidation($request->all(), $rules,'');
            if (!empty($validator)) {
                $arrStatus   = Response::HTTP_NOT_FOUND;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = $validator; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }

            //--------------Check adress exist with received-------------//
            if ($request->input('mode') == 'BTC') {
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
                                    $arrMessage  = 'Address is not valid'; 
                                   return sendResponse($arrStatus,$arrCode,$arrMessage,''); 

                                
                            } else {
                                return 'BTC';
                            }
                        } else {
                            return 'BTC';
                        }
                    } else {
                        return 'BTC';
                    }
                } else {
                    return 'BTC';
                }
            } else if ($request->input('mode') == 'ETH') {
                $Transaction = ETHConfirmation(trim($request->input('address')));
                if (!empty($Transaction) && $Transaction['msg'] == 'failed') {

                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Address is not valid'; 
                   return sendResponse($arrStatus,$arrCode,$arrMessage,''); 
                    
                } else {
                    return 'ETH';
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