<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use App\Models\Activitynotification;
use App\User;
use Config;
use Exception;
use Auth;

class ActivityNotificationController extends Controller {

    

    /**
     * Activity notification report
     *
     * @return \Illuminate\Http\Response
     */ 

    public function ActivtiyNotification($id, $message) {
        try{ 
                if (!empty($id)) {

                    if (!empty($message)) {

                        $pushdata = array();
                        $pushdata['id'] = $id;
                        $pushdata['message'] = $message;
                        $pushdata['status'] = '1';
                        $insertresetDta = Activitynotification::create($pushdata);

                        if (empty($insertresetDta)) {
                          $arrStatus   = Response::HTTP_NOT_FOUND;
                          $arrCode     = Response::$statusTexts[$arrStatus];
                          $arrMessage  = 'Data not found'; 
                          return sendResponse($arrStatus,$arrCode,$arrMessage,'');   
                           
                        } else {

                          $arrData = array();
                          $arrData['message'] = $message;
                          $arrStatus   = Response::HTTP_OK;
                          $arrCode     = Response::$statusTexts[$arrStatus];
                          $arrMessage  ='Notification Found'; 
                          return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData); 

                        }
                    } else {
                        
                          $arrStatus   = Response::HTTP_NOT_FOUND;
                          $arrCode     = Response::$statusTexts[$arrStatus];
                          $arrMessage  = 'Notification Message should not be empty'; 
                          return sendResponse($arrStatus,$arrCode,$arrMessage,''); 
              
                    }
                }else {
                    
                          $arrStatus   = Response::HTTP_NOT_FOUND;
                          $arrCode     = Response::$statusTexts[$arrStatus];
                          $arrMessage  = 'Notification Id should not be null'; 
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
     * Activity notification report By user
     *
     * @return \Illuminate\Http\Response
     */ 

    public function ActivityNotReport(Request $request) {
    
          try{
                // check token is valid
                $Userid = Auth::user()->id;
                if (!empty($Userid)) {
                    $ExistNotification = Activitynotification::where([['id', '=', $Userid], ['status', '=', 1]])->orderBy('srno', 'desc')->get();

                    if (count($ExistNotification) != '0' && !empty($ExistNotification)) {

                        if ($request->input('limit') != '0') {
                            $take = $request->input('limit');
                        } else {

                            $take = '1';
                        }
                        $Notification = Activitynotification::where([['id', '=', $Userid], ['status', '=', 1]])->take($take)->get();
                        if (!empty($Notification) && count($Notification) != '0') {
                            foreach ($Notification as $notkey => $notval) {

                                $NtId = $Notification[$notkey]['srno'];
                                $updateData = array();
                                $updateData['status'] = 0;
                                $updateOtpSta = Activitynotification::where('srno', $NtId)->update($updateData);
                            }
                          $arrStatus   = Response::HTTP_OK;
                          $arrCode     = Response::$statusTexts[$arrStatus];
                          $arrMessage  = 'Activity notification record found successfully'; 
                          return sendResponse($arrStatus,$arrCode,$arrMessage,$Notification);
                           
                        }
                    } else {
                          $arrStatus   = Response::HTTP_NOT_FOUND;
                          $arrCode     = Response::$statusTexts[$arrStatus];
                          $arrMessage  = 'Activity notification record  not exist'; 
                          return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                        
                    }
                } else {
                  
                          $arrStatus   = Response::HTTP_NOT_FOUND;
                          $arrCode     = Response::$statusTexts[$arrStatus];
                          $arrMessage  = 'Invalid user'; 
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
     * All users Activity notification report
     *
     * @return \Illuminate\Http\Response
     */ 
    public function AllUserActivityNotReport(Request $request) {

         try{
                // check token is valid
                $Userid = Auth::user()->id;
                if (!empty($Userid)) {

                    $ExistNotification = Activitynotification::join('tbl_users as tu', 'tu.id', '=', 'tbl_activity_notification.id')->select('tbl_activity_notification.*', 'tu.user_id', 'tu.fullname')->where([['tbl_activity_notification.id', '=', $Userid]])->orderBy('tbl_activity_notification.entry_time', ' desc')
                            ->limit($request->input('limit'))
                            ->get();

                    if (count($ExistNotification) != '0' && !empty($ExistNotification)) {
                        
                        $arrStatus   = Response::HTTP_OK;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Activity notification record found successfully'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,$ExistNotification); 

                    } else {
                        $arrStatus   = Response::HTTP_NOT_FOUND;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  = 'Activity notification record  not exist'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,''); 
                      
                    }
                } else {
                      
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Invalid user'; 
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
