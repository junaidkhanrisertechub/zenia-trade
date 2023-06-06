<?php

namespace App\Http\Controllers\userapi;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\SecureLoginData;
use App\Http\Controllers\userapi\Google2FAController;
use App\Http\Controllers\userapi\SendotpController;
use App\Http\Controllers\userapi\AuthController;
use App\Http\Controllers\userapi\TransactionConfiController;
use App\Models\Otp;
use App\Models\ChangeHistory;
use App\Models\Activitynotification;
use Hash;
use Config;
use Validator;

class ChangeAddressController extends Controller {

    public function __construct(SendotpController $mailauthotp, Google2FAController $Google2FAController, AuthController $AuthController, TransactionConfiController $TransactionConfiController) {
        // dd('hiii');
        $this->linkexpire = Config::get('constants.settings.linkexpire');
        $this->statuscode = Config::get('constants.statuscode');
        $this->emptyArray = (object) array();
        $this->mailauthotp = $mailauthotp;
        $this->Google2FAController = $Google2FAController;
        $this->Google2faValidate = $AuthController;
        $this->confirmTxn = $TransactionConfiController;
    }

    public function secureLogindata($user_id, $password, $query) {
        $securedata = array();
        $securedata['user_id'] = $user_id;
        $securedata['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $securedata['query'] = $query;
        $securedata['pass'] = $password;
        $SecureLogin = SecureLoginData::create($securedata);
    }

    /**
     * check user excited or not by passing parameter
     *
     * @return \Illuminate\Http\Response
     */
    public function sendotpBtcAddress(Request $request) {
        $arrInput = $request->all();
        //validate the info, create rules for the inputs
        $rules = array(
            'remember_token' => 'required',
            'old_btc_address' => '',
            'btc_address' => 'required',
        );
        $validator = checkvalidation($request->all(), $rules,'');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }

        

        $userexist = User::where('remember_token', '=', trim($request->input('remember_token')))->first();
        if (empty($userexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
        }
        $btcexist = User::where([['remember_token', '=', trim($request->input('remember_token'))], ['btc_address', '=', trim($request->input('old_btc_address'))]])->first();
        if (empty($btcexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old BTC address is not matched', '');
        }
        if ($request->input('old_btc_address') == $request->input('btc_address')) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old and new address should not be same', '');
        }

        $request->request->add(['address' => $request->input('btc_address')]);

        $address = $this->confirmTxn->checkAddress($request);
        if ($address != 'BTC') {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BTC address is not valid', '');
        }



        if (!empty($userexist)) {


            if (!empty($userexist) && $userexist->google2fa_status == 'enable') {
                // verify google authentication
                $arrData = array();
                $arrData['remember_token'] = $userexist->remember_token;
                $arrData['otpmode'] = 'FALSE';
                $arrData['google2faauth'] = 'TRUE';


                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Please enter your 2FA verification code', $arrData);
            } else if (!empty($userexist) && $userexist->google2fa_status == 'disable') {

                $this->secureLogindata($userexist->user_id, $userexist->password, 'Sent Otp on mail');

                $user_name = $userexist->email;
                $sendotp = $this->mailauthotp->sendotponmail($userexist, $user_name);
                return response()->json($sendotp->original);
            } else {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
            }
        }
    }

    /* eth address send otp */

    public function sendotpEthAddress(Request $request) {
        $arrInput = $request->all();
        //validate the info, create rules for the inputs
        $rules = array(
            'remember_token' => 'required',
            'old_eth_address' => '',
            'eth_address' => 'required',
        );
        $validator = checkvalidation($request->all(), $rules,'');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }

        $userexist = User::where('remember_token', '=', trim($request->input('remember_token')))->first();
        if (empty($userexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
        }
        $ethexist = User::where([['remember_token', '=', trim($request->input('remember_token'))], ['ethereum', '=', trim($request->input('old_eth_address'))]])->first();
        if (empty($ethexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ETH address is not matched', '');
        }

        if ($request->input('old_eth_address') == $request->input('eth_address')) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old and new address should not be same', '');
        }
        $request->request->add(['address' => $request->input('eth_address')]);

        $address = $this->confirmTxn->checkAddress($request);
        if ($address != 'ETH') {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ETH address is not valid', '');
        }

        if (!empty($userexist)) {


            if (!empty($userexist) && $userexist->google2fa_status == 'enable') {
                // verify google authentication
                $arrData = array();
                $arrData['remember_token'] = $userexist->remember_token;
                $arrData['otpmode'] = 'FALSE';
                $arrData['google2faauth'] = 'TRUE';


                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Please enter your 2FA verification code', $arrData);
            } else if (!empty($userexist) && $userexist->google2fa_status == 'disable') {

                $this->secureLogindata($userexist->user_id, $userexist->password, 'Sent Otp on mail');

                $user_name = $userexist->email;
                $sendotp = $this->mailauthotp->sendotponmail($userexist, $user_name);
                return response()->json($sendotp->original);
            } else {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
            }
        }
    }

    /* chnage password send otp */

    public function sendotpChangePwd(Request $request) {

        $messsages = array(
            'new_pwd.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *',
        );
        $rules = array(
            'remember_token' => 'required|',
            'current_pwd' => 'required',
            'new_pwd' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{7,}/|min:7|max:30',
            'conf_pwd' => 'required|same:new_pwd'
        );

        $validator = checkvalidation($request->all(), $rules,$messsages);
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }
        $check_useractive = User::where([['remember_token', '=', trim($request->Input('remember_token'))], ['status', '=', 'Active']])->first();
        if (!empty($check_useractive)) {


            // if(md5(trim($request->Input('current_pwd')))==$check_useractive->password) 
            if (md5($request->Input('current_pwd')) == $check_useractive->password) {


                if (!empty($check_useractive) && $check_useractive->google2fa_status == 'enable') {

                    $arrData = array();
                    $arrData['remember_token'] = $check_useractive->remember_token;
                    $arrData['otpmode'] = 'FALSE';
                    $arrData['google2faauth'] = 'TRUE';


                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Please enter your 2FA verification code', $arrData);
                } else if (!empty($check_useractive) && $check_useractive->google2fa_status == 'disable') {

                    $this->secureLogindata($check_useractive->user_id, $check_useractive->password, 'Sent Otp on mail');

                    $user_name = $check_useractive->email;
                    $sendotp = $this->mailauthotp->sendotponmail($check_useractive, $user_name);
                    return response()->json($sendotp->original);
                } else {

                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Token is not valid', $this->emptyArray);
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Current password not matched ', $this->emptyArray);
            }
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not valid', $this->emptyArray);
        }
    }

    /*
      Change password
     */

    public function changePassword(Request $request) {

        $messsages = array(
            'new_pwd.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *',
        );
        $rules = array(
            'remember_token' => 'required|',
            'current_pwd' => 'required',
            'new_pwd' => 'required|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[$@$!%*?&])[A-Za-z\d$@$!%*?&]{7,}/|min:7|max:30',
            'conf_pwd' => 'required|same:new_pwd',
            'otp' => 'required'
        );

        $validator = checkvalidation($request->all(), $rules,$messsages);
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }
        $check_useractive = User::where([['remember_token', '=', trim($request->Input('remember_token'))], ['status', '=', 'Active']])->first();
        if (!empty($check_useractive)) {


            // if(md5(trim($request->Input('current_pwd')))==$check_useractive->password) 
            if (md5($request->Input('current_pwd')) == $check_useractive->password) {


                if (!empty($check_useractive) && $check_useractive->google2fa_status == 'enable') {
                    $request->request->add(['googleotp' => $request->input('otp'), 'factor_status' => 'enable']);
                    $validateOtp = $this->Google2faValidate->postValidateToken($request);
                } else if (!empty($check_useractive) && $check_useractive->google2fa_status == 'disable') {

                    $validateOtp = $this->validateMailOtp($request);
                } else {

                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Token is not valid', $this->emptyArray);
                }
                if ($validateOtp->original['code'] == '200') {

                    $updateData = array();
                    $updateData['password'] = Hash::make($request->Input('conf_pwd'));
                    $updateOtpSta = User::where('id', $check_useractive->id)->update($updateData);
                    if (!empty($updateOtpSta)) {

                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Password changed successfully', $this->emptyArray);
                    } else {

                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'current and new password is same', $this->emptyArray);
                    }
                } else {
                    return $validateOtp;
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Current password not matched ', $this->emptyArray);
            }
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not valid', $this->emptyArray);
        }
    }

    /**
     * cheange btc address
     *
     * @return \Illuminate\Http\Response
     */
    public function changeBTCAddress(Request $request) {
        $arrInput = $request->all();
        //validate the info, create rules for the inputs
        $rules = array(
            'remember_token' => 'required',
            'old_btc_address' => '',
            'btc_address' => 'required',
            'otp' => 'required',
        );
        $validator = checkvalidation($request->all(), $rules,'');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }

        $userexist = User::where('remember_token', '=', trim($request->input('remember_token')))->first();
        // dd($check_useractive->google2fa_status);

        if (empty($userexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
        }
        $btcexist = User::where([['remember_token', '=', trim($request->input('remember_token'))], ['btc_address', '=', trim($request->input('old_btc_address'))]])->first();
        if (empty($btcexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old BTC address is not matched', '');
        }
        if ($request->input('old_btc_address') == $request->input('btc_address')) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old and new address should not be same', '');
        }
        $request->request->add(['address' => $request->input('btc_address')]);

        $address = $this->confirmTxn->checkAddress($request);
        if ($address != 'BTC') {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BTC address is not valid', '');
        }
        if (!empty($userexist)) {


            if (!empty($userexist) && $userexist->google2fa_status == 'enable') {

                $request->request->add(['googleotp' => $request->input('otp'), 'factor_status' => 'enable']);
                $validateOtp = $this->Google2faValidate->postValidateToken($request);
            } else if (!empty($userexist) && $userexist->google2fa_status == 'disable') {

                $validateOtp = $this->validateMailOtp($request);
            } else {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Token is not valid', $this->emptyArray);
            }
            if ($validateOtp->original['code'] == '200') {

                $updateData = array();
                $updateData['btc_address'] = trim($request->input('btc_address'));
                $updateBtc = User::where('remember_token', trim($request->input('remember_token')))->update($updateData);

                $changedata = array();
                $changedata['table_name'] = 'tbl_confirmation_request';
                $changedata['tbl_column'] = 'btc_address';
                $changedata['old_value'] = $request->input('old_btc_address');
                $changedata['new_value'] = $request->input('btc_address');
                $changedata['entry_time'] = \Carbon\Carbon::now();
                $changedata['changed_by'] = $userexist->id;
                $changedata['remark'] = 'Changed btc addreess';
                $changedata['action'] = 'Update';
                $HistoryData = ChangeHistory::create($changedata);

                $actdata = array();
                $actdata['id'] = $userexist->id;
                $actdata['message'] = 'Your bitcoin address changed ' . $request->input('old_btc_address') . ' to ' . $request->input('btc_address') . ' successfully';
                $actdata['status'] = 1;
                $actDta = Activitynotification::create($actdata);

                if (!empty($updateBtc)) {

                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'BTC address changed successfully', '');
                } else {

                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old and New BTC address is same', '');
                }
            } else {
                return $validateOtp;
            }
        }
    }

    /**
     * change eth address
     *
     * @return \Illuminate\Http\Response
     */
    public function changeETHAddress(Request $request) {
        $arrInput = $request->all();
        //validate the info, create rules for the inputs
        $rules = array(
            'remember_token' => 'required',
            'old_eth_address' => '',
            'eth_address' => 'required',
            'otp' => 'required',
        );
        $validator = checkvalidation($request->all(), $rules,'');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }

        $userexist = User::where('remember_token', '=', trim($request->input('remember_token')))->first();
        // dd($check_useractive->google2fa_status);

        if (empty($userexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
        }
        $ethexist = User::where([['remember_token', '=', trim($request->input('remember_token'))], ['ethereum', '=', trim($request->input('old_eth_address'))]])->first();
        if (empty($ethexist)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old ETH address is not matched', '');
        }
        if ($request->input('old_eth_address') == $request->input('eth_address')) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old and new address should not be same', '');
        }
        $request->request->add(['address' => $request->input('btc_address')]);

        $address = $this->confirmTxn->checkAddress($request);
        if ($address != 'ETH') {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'ETH address is not valid', '');
        }
        if (!empty($userexist)) {


            if (!empty($userexist) && $userexist->google2fa_status == 'enable') {

                $request->request->add(['googleotp' => $request->input('otp'), 'factor_status' => 'enable']);
                $validateOtp = $this->Google2faValidate->postValidateToken($request);
            } else if (!empty($userexist) && $userexist->google2fa_status == 'disable') {

                $validateOtp = $this->validateMailOtp($request);
            } else {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Token is not valid', $this->emptyArray);
            }
            if ($validateOtp->original['code'] == '200') {

                $updateData = array();
                $updateData['ethereum'] = trim($request->input('eth_address'));
                $updateBtc = User::where('remember_token', trim($request->input('remember_token')))->update($updateData);

                $changedata = array();
                $changedata['table_name'] = 'tbl_confirmation_request';
                $changedata['tbl_column'] = 'eth_address';
                $changedata['old_value'] = $request->input('old_eth_address');
                $changedata['new_value'] = $request->input('eth_address');
                $changedata['entry_time'] = \Carbon\Carbon::now();
                $changedata['changed_by'] = $userexist->id;
                $changedata['remark'] = 'Changed eth addreess';
                $changedata['action'] = 'Update';
                $HistoryData = ChangeHistory::create($changedata);

                $actdata = array();
                $actdata['id'] = $userexist->id;
                $actdata['message'] = 'Your Etherium address changed ' . $request->input('old_eth_address') . ' to ' . $request->input('eth_address') . ' successfully';
                $actdata['status'] = 1;
                $actDta = Activitynotification::create($actdata);

                if (!empty($updateBtc)) {

                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'ETH address changed successfully', '');
                } else {

                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Old and New ETH address is same', '');
                }
            } else {
                return $validateOtp;
            }
        }
    }

    /* validate mail otp */

    public function validateMailOtp(Request $request) {

        $rules = array(
            'remember_token' => 'required|',
            'otp' => 'required|numeric|digits:6'
        );

        $validator = checkvalidation($request->all(), $rules,'');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }


        $remember_token = trim($request->input('remember_token'));
        $otp = trim($request->input('otp'));

        $users = User::join('tbl_user_otp_magic', 'tbl_users.id', '=', 'tbl_user_otp_magic.id')->where('tbl_users.remember_token', $remember_token)->orderBy('tbl_user_otp_magic.otp_id', 'desc')->first();

        if (empty($users)) { // check user exist with token
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Token or otp is not valid', $this->emptyArray);
        }

        $id = User::where([['remember_token', '=', $remember_token],])->pluck('id')->first();
        $checotpstatus = Otp::where([
                    ['id', '=', $id],
                    ['otp', '=', md5($otp)],
                    ['otp_status', '=', '1']])->orderBy('otp_id', 'desc')->first();
        // check otp status 1 - already used otp
        if (!empty($checotpstatus)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'OTP expired', $this->emptyArray);
        } else if (empty($checotpstatus) && !empty($users)) {

            // check otp matched or not
            $otpmatched = Otp::where([
                        ['id', '=', $id],
                        ['otp', '=', md5($otp)],
                        ['otp_status', '=', '0']])->orderBy('otp_id', 'desc')->first();

            if (!empty($otpmatched)) {

                Auth::login($users, true);
                if (Auth::check()) {
                    $updateData = array();
                    $updateData['otp_status'] = 1; //1 -verify otp
                    $updateData['out_time'] = date('Y-m-d H:i:s');
                    $updateOtpSta = Otp::where('id', $id)->update($updateData);
                    if (!empty($updateOtpSta)) {

                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Otp verified successfully', $this->emptyArray);
                    } else {

                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again ', $this->emptyArray);
                    }
                } else {

                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not registered', $this->emptyArray);
                }
            } else {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid otp for token', $this->emptyArray);
            }
            // end of else
        }
    }

}
