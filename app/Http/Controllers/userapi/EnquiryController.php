<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response as Response;
use Illuminate\Http\Request;
use App\Models\Enquiry;
use Config;
use Validator;
use Exception;

class EnquiryController extends Controller {

    public function __construct(BlocktransactionController $blockio) {

        $this->statuscode = Config::get('constants.statuscode');
        
    }
   
   /**
     * Insert user enquiry
     *
     * @return \Illuminate\Http\Response
     */

    public function EnquiryInsert(Request $request) {
     try{  
            $messsages = array(
                 'fullname.max' => 'Fullname should be max 20 char',
                 'fullname.email' => 'Email should be in format abc@abc.com',
                 //'subject.max' => 'Subject should be max 200 char',
                 'message.max' => 'Subject should be max 1000 char',
                // 'file.mimes' => 'Image should be in jpeg,jpg,png format'
                );
            $rules = array(
                'fullname' => 'required|max:20',
                'email' => 'required|email',
                'subject' => 'required|max:200',
                'message' => 'required|max:1000',
               // 'file' => 'image|mimes:jpeg,jpg,png|',
            );


            $validator = checkvalidation($request->all(), $rules,$messsages);
            if (!empty($validator)) {

                $arrStatus   = Response::HTTP_NOT_FOUND;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = $validator; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                
            }

           /* if (!empty($request->file)) {
                $imageName = time() . '.' . $request->file->getClientOriginalExtension();
                $request->file->move(public_path('attachment'), $imageName);
            } else {
                $imageName = '';
            }*/
            
            $request->request->add(['status' => '1']);
            $EnquiryData = Enquiry::create($request->all());
            if (!empty($EnquiryData)) {

                $subject = "Enquiry submitted";
                $pagename = "emails.enquiry";
                $data = array('pagename' => $pagename, 'fullname' => $request->input('fullname'),
                    'email' => $request->input('email'),
                    'subject' => $request->input('subject'),
                    'msg' => $request->input('message'),
                );

                // $email = Config::get('constants.settings.enquiry_email');
                $email = "team.thehscc@gmail.com";
                $mail = sendMail($data, $email, $subject);

                //for user 
                $userPagename = "emails.thanks_for_enquiry";
                $userEmail = $request->input('email');
                $userData = array('pagename' => $userPagename, 'fullname' => $request->input('fullname'),
                    'email' => $userEmail,
                    'subject' => $request->input('subject'),
                    'msg' => $request->input('message'),
                );
                $mail = sendMail($userData, $userEmail, $subject);
                $arrStatus   = Response::HTTP_OK;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Your enquiry has been submitted successfully'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                
            } else {
                
                $arrStatus   = Response::HTTP_NOT_FOUND;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Problem with submitting enquiry data '; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }

        }catch(Exception $e){
            dd($e);  
           $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
           $arrCode     = Response::$statusTexts[$arrStatus];
           $arrMessage  = 'Something went wrong,Please try again'; 
           return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }    
    }

}
