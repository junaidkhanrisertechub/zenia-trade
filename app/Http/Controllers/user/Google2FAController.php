<?php

namespace App\Http\Controllers\user;

use Illuminate\Support\Facades\Crypt;
use Google2FA;
use Cache;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use \ParagonIE\ConstantTime\Base32;
/*use Illuminate\Support\Facades\Auth;*/
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Encryption\DecryptException;
use DB;
use Illuminate\Support\Facades\URL;
use Config;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as Response;
// use model here
use App\User as UserModel;
use App\Models\Otp;
use App\Models\Activitynotification as ActivitynotificationModel;
use PragmaRX\Google2FAPhp\Exceptions\InvalidCharactersException;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;


class Google2FAController extends Controller
{
    use ValidatesRequests;
    public $arrOutputData = [];
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function enableTwoFactor(Request $request)
    {     
        $strMessage      = trans('user.error');
        //$domainpath=Config::get('constants.settings.domainpath');
        $domainpath = URL::to('/');
        try {
            $user  = Auth::user();
           //$user =  UserModel::find(829);
            $email = $user->email;

            if(empty($user->google2fa_secret)){
                //generate new secret
                $secret = $this->generateSecret(); 
                $google2fa_secret = Crypt::encrypt($secret);
                $user->google2fa_secret = $google2fa_secret;
                
                $user->save();
            } else{
                $secret=Crypt::decrypt(Auth::user()->google2fa_secret);
            }
            $qrcodestring= "otpauth://totp/".$domainpath.":".$email."?secret=".$secret."&issuer=".$domainpath."";
            $arrOutputData = array();
            $arrOutputData['secret']=$secret;
            $arrOutputData['qrcodestring']=$qrcodestring;
            $arrOutputData['google2fa_status']= Auth::user()->google2fa_status;
            $intCode    = Response::HTTP_OK;
            $strMessage = Response::$statusTexts[$intCode];
            $strStatus  = "Ok"; 
        } catch (Exception $e) {
            $intCode        = Response::HTTP_BAD_REQUEST;
            $strStatus      = Response::$statusTexts[$intCode];
        }
        return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData); 
        
    }



    function index()
    {
        $data['title'] = 'Google 2 FA | HSCC';
        $data['token'] = '';

        
        $user_id = Auth::User()->user_id; 

        $userData =  UserModel::SELECT('google2fa_secret','google2fa_status')->where('user_id', $user_id)->first();
        $google2fa_status = $userData->google2fa_status;
         $data['google2fa_status'] = $google2fa_status;
       // echo "ggg---".($google2fa_status);
         return view('user.twofa.twofa', compact('data'));
    }



    /**
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function disableTwoFactor(Request $request)
    {
        $user = $request->user();
        //make secret column blank
        $user->google2fa_secret = null;
        $user->save();

        return view('2fa/disableTwoFactor');
    }

    /**
     * Generate a secret key in Base32 format
     *
     * @return string
     */
    private function generateSecret()
    {  
        try{

        $randomBytes = random_bytes(10);        
        return Base32::encode($randomBytes);


       }catch(Exception $e){
        //dd($e);
       }

    }

       /**
     *
     * @param  App\Http\Requests\ValidateSecretRequest $request
     * @return \Illuminate\Http\Response
     */
    public function postValidateToken(Request $request)
    {   
        $strMessage     = trans('user.error');
        $intCode        = Response::HTTP_BAD_REQUEST;
        $strStatus      = Response::$statusTexts[$intCode];
        $arrOutputData  = [];        
        //$domainpath=Config::get('constants.settings.domainpath');
        $domainpath = URL::to('/');
        try {
            $arrInput = $request->all();
            $arrRules = array(
                'googleotp'         => 'bail|required|digits:6',
                'factor_status'     =>'required'
            );
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) 
                return setValidationErrorMessage($validator);

            $user = Auth::user();
            $userId = $user->id;
            $google2fa_secret = $user->google2fa_secret;
            $key = $userId . ':' . $request->input('googleotp'); 
            //$encryptsecret=Crypt::encrypt($google2fa_secret); 
            //dd('hii');
            $secret = Crypt::decrypt($google2fa_secret);
            $verified= Google2FA::verifyKey($secret, $arrInput['googleotp']);

            if(!empty($verified)){
                $reusetoken=!Cache::has($key);
                if(empty($reusetoken)) {   
                    $strMessage     = 'Cannot reuse token';
                } else{ 
                    Cache::add($key, true, 4);
                    //$checklogdin=Auth::loginUsingId($userId);
                    $user->factor_status = $arrInput['factor_status'];
                    $user->google2fa_status = $arrInput['factor_status'];
                    $user->save(); 
                    $intCode      = Response::HTTP_OK;
                    $strStatus    = Response::$statusTexts[$intCode];
                    $strMessage   = ($arrInput['factor_status']=='enable') ?  'Your 2FA Is Enabled Successfully Done' : 'Your 2FA Is Disabled Successfully Done';
                }  
            }else {
                if(empty($verified)){
                    $strMessage = 'Invalid otp';
                }
            }   
        } catch(InvalidCharactersException $e){
            
        } catch (Exception $e) {
            $intCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strStatus      = Response::$statusTexts[$intCode];
        }
        return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);           
    }

     /**
     *
     * @param  App\Http\Requests\ValidateSecretRequest $request
     * @return \Illuminate\Http\Response
     */
    public function loginpostValidateToken(Request $request)
    {
        $strMessage     = trans('user.error');
        $intCode        = Response::HTTP_BAD_REQUEST;
        $strStatus      = Response::$statusTexts[$intCode];
        $arrOutputData  = [];        
        //$domainpath=Config::get('constants.settings.domainpath');
        $domainpath = URL::to('/');
        try {
            $arrInput = $request->all();
            $arrRules = array(
                'googleotp'         => 'bail|required|digits:6',
            );
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) 
                return setValidationErrorMessage($validator);
            $user = Auth::user();
            $userId = $user->id;
            /*$google2fa_secret = $user->google2fa_secret;
            $key = $userId . ':' . $request->input('googleotp'); */
            //$encryptsecret=Crypt::encrypt($google2fa_secret); 
            // $secret = Crypt::decrypt($google2fa_secret);
            // $verified= Google2FA::verifyKey($secret, $arrInput['googleotp']);
            $google2fa_secret = Auth::user()->google2fa_secret;
            //dd($google2fa_secret);
            $key = $userId . ':' . $request->input('googleotp'); 
           // dd($google2fa_secret);
            /*$encryptsecret=Crypt::encrypt($google2fa_secret); 
            $secret = Crypt::decrypt($encryptsecret);*/
            $encryptsecret=Crypt::encrypt($google2fa_secret); 
            //$secret = Crypt::decrypt($encryptsecret);
           // $secret = Crypt::decrypt($google2fa_secret);
            //dD(1, $secret);
            $verified= Google2FA::verifyKey($google2fa_secret, $request->input('googleotp'));
            if(!empty($verified)){
                $reusetoken=!Cache::has($key);
                if(empty($reusetoken)) {   
                    $strMessage     = 'Cannot reuse token';
                } else{ 
                    //Cache::add($key, true, 4);
                    /*$user->factor_status = $arrInput['factor_status'];
                    $user->google2fa_status = $arrInput['factor_status'];
                    $user->save(); */
                    // $actdata=array();     
                    // $actdata['id']=$userId;
                    // $actdata['message']='Your 2FA Is successfully verified';
                    // $actdata['status']=1;
                    // $actDta=ActivitynotificationModel::create($actdata);    

                    $intCode      = Response::HTTP_OK;
                    $strStatus    = Response::$statusTexts[$intCode];
                    $arrOutputData['mobileverification']= 'FALSE';
                    $arrOutputData['mailverification']  = 'FALSE';
                    $arrOutputData['google2faauth']     = 'TRUE';
                    $arrOutputData['mailotp']           = 'FALSE';
                    $arrOutputData['otpmode']           = 'FALSE';
                    $arrOutputData['master_pwd']        = 'FALSE';
                    $strMessage   = 'Your 2FA Is successfully verified';
                }  
            }else {
                if(empty($verified)){
                    $strMessage = 'Invalid otp';
                }
            }   
        } catch(InvalidCharactersException $e){

        } catch (Exception $e) {
            $intCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strStatus      = Response::$statusTexts[$intCode];
        }
        return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);           
    }


  /**
     *
     * @param  App\Http\Requests\ValidateSecretRequest $request
     * @return \Illuminate\Http\Response
     */
    public function postLoginValidateToken(Request $request)
    {
        //s dd($request);
           $strMessage     = trans('user.error');
           $intCode        = Response::HTTP_BAD_REQUEST;
           $strStatus      = Response::$statusTexts[$intCode];
           $arrOutputData  = [];        
           //$domainpath=Config::get('constants.settings.domainpath');
           $domainpath = URL::to('/');
           try {
               $arrInput = $request->all();
               $arrRules = array(
                   'googleotp'         => 'bail|required|digits:6',
               );
               $validator = Validator::make($arrInput, $arrRules);
               if ($validator->fails()) 
                   return setValidationErrorMessage($validator);
            //    $id = Auth::user()->id;
               $user = Auth::user();
               
               $userId =  $user->id;
                   
   
               //$google2fa_secret = $user->getOriginal('google2fa_secret');
               //$google2fa_secret = $user->google2fa_secret;
               if($request->input('status') == "enable")
               {
                 $google2fa_secret = $request->input('secret');
               }
               else
               {
                  $google2fa_secret = $user->google2fa_secret;
                 // $google2fa_secret = Crypt::decrypt($google2fa_secret);
               }
               
               //dd($google2fa_secret);
             //  $google2fa_secret=Config::get('constants.settings.google2fa_secret');
               
               //$key = $userId . ':' . $request->input('googleotp'); 
               $encryptsecret=Crypt::encrypt($google2fa_secret); 
               // $secret = Crypt::decrypt($google2fa_secret);
               // $verified= Google2FA::verifyKey($secret, $arrInput['googleotp']);
               //$google2fa_secret = Auth::user()->google2fa_secret;
              // $google2fa_secret = "eyJpdiI6IjB0RmlqMWtpRFNpeFRQZGF6TEhmanc9PSIsInZhbHVlIjoicENsUTRrbVVXcmY2TkxmVm56dk9hWjBzRlBEb2ZIdDZzUkVleFMxZnhnYz0iLCJtYWMiOiI2N2ExMWU0YzNhNjVjNDAxODcyMDA3NjZjZGU3MDMwNmNhMDMzNjgxNWM5YWFiYjk4OGNiYzY1NGMxMmNmODE3In0=";
               
               $key = $userId . ':' . $request->input('googleotp'); 
               //dd($key);
               /*$encryptsecret=Crypt::encrypt($google2fa_secret); 
               $secret = Crypt::decrypt($encryptsecret);*/
   
               //$encryptsecret=Crypt::encrypt($google2fa_secret); 
               //dd($encryptsecret);
               //$secret = Crypt::decrypt($encryptsecret);
               //$secret = Crypt::decrypt($google2fa_secret);
               $secret = $google2fa_secret;
              // dd($secret);
               //dD(1, $secret);
               $verified= Google2FA::verifyKey($secret, $request->input('googleotp'));
   
               if(!empty($verified)){
                   $reusetoken=!Cache::has($key);
                   if(empty($reusetoken)) {   
                       $strMessage     = 'Cannot reuse token';
                   } else{ 
                       //Cache::add($key, true, 4);
   
                       $updateData = array();
                       $updateData['google2fa_status'] = trim($request->input('status'));
                        if($request->input('status') == "enable"){
                         $updateData['google2fa_secret'] = $encryptsecret;
                       }
                       else{
                         $updateData['google2fa_secret'] = NULL; 
                       }
   
                       $updateVeriSta = UserModel::where('id', $userId)->update($updateData);
        
                       $intCode      = Response::HTTP_OK;
                       $strStatus    = Response::$statusTexts[$intCode];
                     
                       if ($updateData['google2fa_status'] == 'enable') {
                            $strMessage   = 'G2FA Activated successfully';
                           $pagename = "emails.linking_google_twofa";
                            $username = $user->user_id;
                            $subject='Your HSCC Account is Now Secured with Google 2FA';
                            $data = array('pagename' => $pagename, 'username' => $username,'name'=>$user->fullname);
                            $mail = sendMail($data, $user->email, $subject);
                            if ($mail) {
                                $intCode = Response::HTTP_OK;
                                $strStatus = Response::$statusTexts[$intCode];
                            
                            } else {
                                $intCode = Response::HTTP_NOT_FOUND;
                                $strStatus = Response::$statusTexts[$arrStatus];
                                $arrMessage = 'Failed to send email for reset-password';
                                return sendResponse($intCode, $strStatus, $arrMessage, '');
                            }
                       } else {
                            $strMessage   = "Your HSCC Account's G2FA has Been Removed";
                           $pagename = "emails.removing_unlinking_twofa";
                            $username = $user->user_id;
                            $subject="Your HSCC Account's Google 2FA has Been Removed";
                            $data = array('pagename' => $pagename, 'username' => $username,'name'=>$user->fullname);
                            $mail = sendMail($data, $user->email, $subject);
                            if ($mail) {
                                $intCode = Response::HTTP_OK;
                                $strStatus = Response::$statusTexts[$intCode];

                            } else {
                                $intCode = Response::HTTP_NOT_FOUND;
                                $strStatus = Response::$statusTexts[$arrStatus];
                                $arrMessage = 'Failed to send email for reset-password';
                                return sendResponse($intCode, $strStatus, $arrMessage, '');
                            }
                       }
                       
                   }  
               }else {
                   if(empty($verified)){
                       $strMessage = 'Invalid otp';
                   }
               }   
           } catch(InvalidCharactersException $e){
              dd($e);
           } catch (Exception $e) {
             dd($e);
               $intCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
               $strStatus      = Response::$statusTexts[$intCode];
           }
           return sendResponse($intCode, $strStatus, $strMessage,$arrOutputData);           
    }

    public function send2faResetLink(Request $request){
        try {
            $arrInput = $request->all();
            /*$arrRules = array(
                'secret_2fa'         => 'required|alpha_num',
            );
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                return setValidationErrorMessage($validator);
            }*/

            $user = Auth::User();
            $secret_2fa = Auth::User()->google2fa_secret;
           // $generate_token=str_random(64);
            $generate_token=str::random(64);
            
           // $path=Config::get('constants.settings.domainpath-vue')."two-fa?reset_token=".$generate_token;
            
           $path=Config::get('constants.settings.domainpath')."reset-g2fa-mail-link?token=".$generate_token;
            
            $mytime_new = \Carbon\Carbon::now();
            $expire_time = \Carbon\Carbon::now()->addMinutes(5)->toDateTimeString();
            $current_time_new = $mytime_new->toDateTimeString();
            // $path=Config::get('constants.settings.domainpath-vue')."reset-twofa-user?user_id=".Auth::User()->user_id;
            // $path = route('g2fa.reset.link', str_random(64));
            $strMessage   = "Reset Your Google 2FA for your HSCC Account";  
            $pagename = "emails.reset_twofa";
            $username = $user->user_id;
            $subject="Reset Your Google 2FA for your HSCC Account";
            $data = array('pagename' => $pagename, 'username' => $username,'name'=>$user->fullname,'path'=>$path);
            $mail = sendMail($data, $user->email, $subject);
            if ($mail) {
                $updateUser=UserModel::where('id',$user->id)->update(['g2fa_token'=>$generate_token,'g2fa_token_expire'=>$expire_time,'g2fa_token_status'=>0]);
                $intCode = Response::HTTP_OK;
                $strStatus = Response::$statusTexts[$intCode];
                $arrMessage = 'Reset G2FA link shared on your email';
                return sendResponse($intCode, $strStatus, $arrMessage, '');
            } else {
                $intCode = Response::HTTP_NOT_FOUND;
                $strStatus = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Failed to send email for reset G2FA';
                return sendResponse($intCode, $strStatus, $arrMessage, '');
            }
            
        } catch (Exception $e) {
            dd($e);
            $intCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strStatus      = Response::$statusTexts[$intCode];
            return sendResponse($intCode, $strStatus, 'Something went wrong, Please try again.',$arrOutputData);
        }
    }


    public function resetG2faUserDisable(Request $request){
        try {
            $user = Auth::User();
            $today = \Carbon\Carbon::now();

                $twofatoken = $request->token;
                $users_id = Auth::user()->id;
                $arrIn['id']=$users_id;
                $arrIn['otp']=$twofatoken;
                $arrIn['google2fa_secret'] = Auth::user()->google2fa_secret;
                $res=$this->validateGoogle2FA($arrIn);
                if ($res == false) {
                        $arrOutputData = [];
                        $strMessage = "Invalid Google 2FA Token";
                        $intCode = Response::HTTP_NOT_FOUND;
                        $strStatus = Response::$statusTexts[$intCode];
                        return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }

                $strMessage   = "Your HSCC Account's G2FA has Been Removed";
                $pagename = "emails.removing_unlinking_twofa";
                     $updateUser=UserModel::where('id',$user->id)->update(['google2fa_secret'=>NULL,'google2fa_status'=>'disable',
                         'g2fa_token_status'=>0]);
                     $username = $user->user_id;
                     $subject="Your HSCC Account's Google 2FA has Been Removed";
                     $data = array('pagename' => $pagename, 'username' => $username,'name'=>$user->fullname);
                     // $mail = sendMail($data, $user->email, $subject);
                     $mail =1;
                     if ($mail) {
                         $arrStatus = Response::HTTP_OK;
                         $arrCode = Response::$statusTexts[$arrStatus];
                         $arrMessage = 'Your G2FA disabled successfully';
                         return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                     } else {
                        
                         $intCode = Response::HTTP_NOT_FOUND;
                         $strStatus = Response::$statusTexts[$intCode];
                         $arrMessage = 'Failed to send email for reset-password';
                         return sendResponse($intCode, $strStatus, $arrMessage, '');
                     }
            

            
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function resetG2faUser(Request $request){
        try {
            $user = Auth::User();
            $today = \Carbon\Carbon::now();
             if ($user->g2fa_token_status == 1) {

                $twofatoken = $request->token;
                $users_id = Auth::user()->id;
                $arrIn['id']=$users_id;
                $arrIn['otp']=$twofatoken;
                $arrIn['google2fa_secret'] = Auth::user()->google2fa_secret;
                $res=$this->validateGoogle2FA($arrIn);
                if ($res == false) {
                        $arrOutputData = [];
                        $strMessage = "Invalid Google 2FA Token";
                        $intCode = Response::HTTP_NOT_FOUND;
                        $strStatus = Response::$statusTexts[$intCode];
                        return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }

                $strMessage   = "Your HSCC Account's G2FA has Been Removed";
                $pagename = "emails.removing_unlinking_twofa";
                     $updateUser=UserModel::where('id',$user->id)->update(['google2fa_secret'=>NULL,'google2fa_status'=>'disable',
                         'g2fa_token_status'=>0]);
                     $username = $user->user_id;
                     $subject="Your HSCC Account's Google 2FA has Been Removed";
                     $data = array('pagename' => $pagename, 'username' => $username,'name'=>$user->fullname);
                     // $mail = sendMail($data, $user->email, $subject);
                     $mail =1;
                     if ($mail) {
                         $arrStatus = Response::HTTP_OK;
                         $arrCode = Response::$statusTexts[$arrStatus];
                         $arrMessage = 'Your G2FA reset successfully';
                         return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                     } else {
                        
                         $intCode = Response::HTTP_NOT_FOUND;
                         $strStatus = Response::$statusTexts[$intCode];
                         $arrMessage = 'Failed to send email for reset-password';
                         return sendResponse($intCode, $strStatus, $arrMessage, '');
                     }
            }

            $otpdata = Otp::where('id', $user->id)->where('otp', hash('sha256', $request->otp))->orderBy('entry_time', 'desc','otpexpire')->first();
            if (empty($otpdata)) {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Incorrect Otp';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                
              } else if ($otpdata->otp_status != 0) {
                    $arrStatus = Response::HTTP_NOT_FOUND;
                    $arrCode = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Otp Already Used';
                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                } else if($today > $otpdata->otpexpire){
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode = Response::$statusTexts[$arrStatus];
                        $arrMessage = 'Otp Expire';
                        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    }else{
                       
                            if ($user->g2fa_token_status != 1) {

                                $strMessage   = "Your HSCC Account's G2FA has Been Activated";
                                
                                $updateUser=UserModel::where('id',$user->id)->update(['google2fa_secret'=>$request->google2fa_secret,'google2fa_status'=>'enable',
                                         'g2fa_token_status'=>1]);
                                $username = $user->user_id;
                                //dd($request->resettoken);       
                                if($request->resettoken == "" or $request->resettoken == null)
                                {
                                    $subject="Your HSCC Account's G2FA has Been Activated";
                                    $pagename = "emails.linking_google_twofa";
                                    $data = array('pagename' => $pagename, 'username' => $username,'name'=>$user->fullname);
                                    $mail = sendMail($data, $user->email, $subject);   
                                    $arrStatus = Response::HTTP_OK;
                                            $arrCode = Response::$statusTexts[$arrStatus];
                                            $arrMessage = 'Your G2FA activated successfully';
                                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                                }
                                else{
                                    $strMessage   = "Your HSCC Account's G2FA has Been Removed";
                                    $pagename = "emails.removing_unlinking_twofa";
                                        $updateUser=UserModel::where('id',$user->id)->update(['google2fa_secret'=>NULL,'google2fa_status'=>'disable',
                                            'g2fa_token_status'=>0]);
                                        $username = $user->user_id;
                                        $subject="Your HSCC Account's Google 2FA has Been Removed";
                                        $data = array('pagename' => $pagename, 'username' => $username,'name'=>$user->fullname);
                                        $mail = sendMail($data, $user->email, $subject);
                                        $arrStatus = Response::HTTP_OK;
                                            $arrCode = Response::$statusTexts[$arrStatus];
                                            $arrMessage = 'Your G2FA reset successfully';
                                        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                                }                            
                            }                               
                        
                    }          
            
            
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function validateGoogle2FA($arrInput)
    {
       $arrOutputData  = [];
       try {             
           $userId = $arrInput['id'];
           $google2fa = $arrInput['otp'];
           $google2fa_secret = $arrInput['google2fa_secret'];

           $encryptsecret=Crypt::encrypt($google2fa_secret);                
           $key = $userId . ':' . $google2fa; 
           $secret = $google2fa_secret;
           $verified= Google2FA::verifyKey($secret, $google2fa);

           if(!empty($verified)){
               $reusetoken=!Cache::has($key);
               if(empty($reusetoken)) {   
                   $strMessage     = 'Cannot reuse token';
                   return false;
               } else{ 
                    Cache::add($key, true, 4);                 
                    $strMessage   = 'Your 2FA is verified successfully';
                    return true;
               } 
           }else {
               if(empty($verified)){
                   $strMessage = 'Invalid otp';
                   return false;
               }
           }   
       } catch (Exception $e) {
         dd($e);
           $intCode        = Response::HTTP_INTERNAL_SERVER_ERROR;
           $strStatus      = Response::$statusTexts[$intCode];
       }
    }

    public function onMailLinkClick(Request $request){

      $token  =  $request->token;
      $data['title'] = 'Google 2 FA | HSCC';
      $data['token'] = $token;
      $data['google2fa_status'] = '';
      return view('user.twofa.twofa', compact('data' ));

        //dd(auth()->guard('api')->check());
    }

}