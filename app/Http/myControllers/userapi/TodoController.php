<?php

namespace App\Http\Controllers\userapi;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activitynotification;
use Illuminate\Http\Response as Response;
use App\User;
use App\Models\Todo;
use DB;
use Config;
use Validator;
use Exception;

class TodoController extends Controller {

    

    /**
     * Add todo    *
     * @return \Illuminate\Http\Response
     */
    public function todoAdd(Request $request) {
       try{
            $rules = array(
                
                'itemname' => 'required|',
            );
            $validator = checkvalidation($request->all(), $rules,'');
            if (!empty($validator)) {
                $arrStatus   = Response::HTTP_NOT_FOUND;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = $validator; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }

            $userExistId = Auth::User()->id;
            if (!empty($userExistId)) {
                $tododata = ['id' => $userExistId,
                    'itemname' => trim($request->input('itemname')),
                    'status' => 'Active'
                ];
                $tododata = Todo::create($tododata);
                if (!empty($tododata)) {
                    $arrStatus   = Response::HTTP_OK;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Item added successfully'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                } else {
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Something went wrong,Please try again'; 
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
     * Get todo  list  *
     * @return \Illuminate\Http\Response
     */

    public function todoList(Request $request) {
       try{
            $userExistId = Auth::User()->id;
            if (!empty($userExistId)) {

                $tododata = Todo::where([['id', '=', $userExistId], ['status', '=', 'Active']])->get();

                if (!empty($tododata) && count($tododata) > 0) {

                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Data found successfully'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,$tododata);
                } else {
                    $arrStatus   = Response::HTTP_NOT_FOUND;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Something went wrong,Please try again'; 
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
     * Delete  todo item  *
     * @return \Illuminate\Http\Response
     */

    public function todoDelete(Request $request) {
       try{
            $rules = array(
               
                'id' => 'required|',
            );
            $validator = checkvalidation($request->all(), $rules,'');
            if (!empty($validator)) {
                $arrStatus   = Response::HTTP_NOT_FOUND;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = $validator; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }

            $userExistId = Auth::User()->id;
            if (!empty($userExistId)) {
                $checkIdexist = Todo::where([['sr_no', '=', $request->input('id')], ['status', '=', 'Active']])->first();

                if (!empty($checkIdexist)) {
                    $tododata = ['status' => 'Inactive'];
                    $tododata = Todo::where('sr_no', $request->input('id'))->update($tododata);

                    if ((!empty($tododata)) && ($tododata > 0)) {

                        $arrStatus   = Response::HTTP_OK;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  ='Item deleted successfully'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                    } else {
                        $arrStatus   = Response::HTTP_NOT_FOUND;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  ='Something went wrong,Please try again'; 
                        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                    }
                } else {
                    echo "hello";
                    exit();
                        $arrStatus   = Response::HTTP_NOT_FOUND;
                        $arrCode     = Response::$statusTexts[$arrStatus];
                        $arrMessage  ='Item not exist'; 
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
