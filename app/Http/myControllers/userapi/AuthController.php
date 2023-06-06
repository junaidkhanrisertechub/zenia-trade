<?php

namespace App\Http\Controllers\userapi;

use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\Activitynotification;
use App\User;
use Validator;
use Config;
use Crypt;
use Google2FA;

class AuthController extends Controller {

    /**
     * Send the post-authentication response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return \Illuminate\Http\Response
     */
    public function __construct() {

        $this->statuscode = Config::get('constants.statuscode');
        $this->emptyArray = (object) array();
    }

    private function authenticated(Request $request, Authenticatable $user) {
        /* if ($user->google2fa_secret) { */
        if (Auth::user()->google2fa_secret) {
            Auth::logout();

            //$request->session()->put('2fa:user:id', $user->id);
            $request->session()->put('2fa:user:id', Auth::user()->id);

            return redirect('2fa/validate');
        }

        // return redirect()->intended($this->redirectTo);
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function getValidateToken() {
        return view('2fa/validate');

        /* if (session('2fa:user:id')) {
          return view('2fa/validate');
          } */

        //return redirect('login');
    }

    /**
     *
     * @param  App\Http\Requests\ValidateSecretRequest $request
     * @return \Illuminate\Http\Response
     */
    public function postValidateToken(Request $request) {


        $rules = array(
            'remember_token' => 'required|',
            'googleotp' => 'bail|required|digits:6',
            'factor_status' => 'required'
        );
        
        $validator = checkvalidation($request->all(), $rules, '');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }

        $users = User::select('id','user_id')->where([['remember_token', '=', trim($request->input('remember_token'))],
                    ['status', '=', 'Active']])->first();
        if (!empty($users)) {

            $userId = User::where([['remember_token', '=', trim($request->input('remember_token'))],])->pluck('id')->first();

            $google2fa_secret = User::where([['remember_token', '=', trim($request->input('remember_token'))],])->pluck('google2fa_secret')->first();

            $key = $userId . ':' . $request->input('googleotp');

            $encryptsecret = Crypt::encrypt($google2fa_secret);
            $secret = Crypt::decrypt($encryptsecret);

            $verified = Google2FA::verifyKey($secret, $request->input('googleotp'));
            if (!empty($verified)) {


                $reusetoken = !Cache::has($key);
                if (empty($reusetoken)) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Cannot reuse token', $this->emptyArray);
                } else {
                    Cache::add($key, true, 4);
                    $checklogdin = Auth::loginUsingId($userId);

                    $updateData = array();
                    $updateData['google2fa_status'] = trim($request->input('factor_status'));
                    $updateVeriSta = User::where('id', $userId)->update($updateData);

                    if ($request->input('factor_status') == 'enable') {

                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Your 2FA Is Enabled Successfully Done', $this->emptyArray);
                    } else {

                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Your 2FA Is Disabled Successfully Done', $this->emptyArray);
                    }
                }
            } else {

                if (empty($verified)) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid otp', $this->emptyArray);
                }
            }
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Toekn is not valid', $this->emptyArray);
        }
    }

//==================================================================================

    public function loginpostValidateToken(Request $request) {


        $rules = array(
            'remember_token' => 'required|',
            'googleotp' => 'bail|required|digits:6',
        );
         
        $validator = checkvalidation($request->all(), $rules, '');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }
  

        $users = User::select('id','user_id')->where([['remember_token', '=', trim($request->input('remember_token'))],
                    ['status', '=', 'Active']])->first();

        if (!empty($users)) {


            $userId = User::where([['remember_token', '=', trim($request->input('remember_token'))],])->pluck('id')->first();
            $google2fa_secret = User::where([['remember_token', '=', trim($request->input('remember_token'))],])->pluck('google2fa_secret')->first();
            $key = $userId . ':' . $request->input('googleotp');

            $encryptsecret = Crypt::encrypt($google2fa_secret);
            $secret = Crypt::decrypt($encryptsecret);

            $verified = Google2FA::verifyKey($secret, $request->input('googleotp'));
            if (!empty($verified)) {


                $reusetoken = !Cache::has($key);
                if (empty($reusetoken)) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Cannot reuse token', $this->emptyArray);
                } else {

                    //==========activity notification==========
                    $actdata = array();
                    $actdata['id'] = $userId;
                    $actdata['message'] = 'Your 2FA Is successfully verified';
                    $actdata['status'] = 1;
                    $actDta = Activitynotification::create($actdata);
                    //==================================================
                    Cache::add($key, true, 4);
                    $checklogdin = Auth::loginUsingId($userId);
                    $arrData = array();
                    $arrData['mobileverification'] = 'TRUE';
                    $arrData['mailverification'] = 'TRUE';
                    $arrData['google2faauth'] = 'TRUE';
                    $arrData['mailotp'] = 'FALSE';

                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Your 2FA Is successfully verified', $arrData);
                }
            } else {

                if (empty($verified)) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid otp', $this->emptyArray);
                }
            }
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Token is not valid', $this->emptyArray);
        }
    }

}
