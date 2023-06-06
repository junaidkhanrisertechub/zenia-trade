<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Controllers\user\Google2FAController;
use App\Http\Controllers\user\SettingsController;
use App\Models\MarketTool;
use App\Models\UserWithdrwalSetting;
use Auth;
// use Config;
use App\Models\verifyOtpStatus;
use App\Models\User;
use App\Models\UserUpdateProfileCount;
use App\Models\Currency;
use App\Models\Rank;
use App\Models\Otp;
use App\Traits\CurrencyValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use DB;
use Hash;
use Exception;
use App\Traits\Users;
use App\Traits\AddressValid;

use App\Models\Activitynotification;
use App\Models\UserCurrAddrHistory;
use Aws\S3\S3Client;

class UserController extends Controller
{
    use Users, AddressValid, CurrencyValidation;
    public function __construct(Google2FAController $google2facontroller)
    {
        $this->linkexpire = Config::get('constants.linkexpire');
        $date             = \Carbon\Carbon::now();
        $this->today      = $date->toDateTimeString();
        $this->statuscode = Config::get('constants.statuscode');
        $this->google2facontroller = $google2facontroller;
    }

    public function index()
    {
        $profile = Auth::user();
        return view('user.profile', compact('profile'));
        // $uid = 5;
        // $profile = DB::select("select * from tbl_users Where id = '$uid'");
        // dd($profile);
        // return view('user.profile', ['profile' => $profile]);
    }

    function changeAddress(Request $request){
//         dd($request->all());
        try{
            $messsages = array(
                'trn_address' => 'Currency address must not contain special characters',
                'btc_address' => 'Currency address must not contain special characters'
            );
            $rules = array(
                'trn_address' => 'nullable|alpha_num',
                // 'btc' => 'required|alpha_num',
                'bnb_address' => 'nullable|alpha_num',
                'ethereum' => 'nullable|alpha_num',
                'usdt_trc20_address' => 'nullable|alpha_num',
                'usdt_erc20_address' => 'nullable|alpha_num',
                'sol_address' => 'nullable|alpha_num',
                'doge_address' => 'nullable|alpha_num',
            );

            $validator = checkvalidation($request->all(), $rules, $messsages);
            if (!empty($validator)) {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = $validator;
//                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                toastr()->error($arrMessage);
                return redirect()->back();
            }
            $id = Auth::User()->id;
            $arrInput            = $request->all();
            $arrInput['user_id'] = $id;

            if($request->trn_address == "" && $request->btc_address == "" && $request->doge_address == "" && $request->ethereum == "" && $request->usdt_trc20_address == "" && $request->usdt_erc20_address == "" && $request->sol_address == "" && $request->ltc_address == "")
            {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Address must be required';
//                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                toastr()->error($arrMessage);
                return redirect()->back();
            }

            $getuser_id = Auth::user()->id;
            if(!empty($getuser_id)){
                $addData = array();
                $addData['id'] = $getuser_id;
                $addData['status'] = 1;
                $addData['updated_by'] = $getuser_id;
                $currency_update_info="";
                if (!empty($request->Input('btc_address'))) {
                    $flag = 2;
                    $addData['currency'] = "BTC";
                    $addData['currency_address'] = trim($request->Input('btc_address'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    if (!empty($request->btc_address)) {
                        $checkAddress =  $this->checkcurrencyvalidaion('BTC',$request->input('btc_address'));

                        if ($checkAddress != '')
                        {
                            $arrStatus = Response::HTTP_NOT_FOUND;
                            $arrCode   = Response::$statusTexts[$arrStatus];
//                            return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                            toastr()->success('ALready updated');
                            return redirect()->back();
                        }

                    }
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('btc_address'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('btc_address'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }

                    if($flag == 2){
                        if($addressStatus){
                            $currency_update_info.="<br>Bitcoin Address: ".$request->Input('btc_address');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('trn_address'))) {
                    $flag = 1;
                    $addData['currency'] = "TRX";
                    $addData['currency_address'] = trim($request->Input('trn_address'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addTRXStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    $checkAddress =  $this->checkcurrencyvalidaion('trn_address',$request->input('trn_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
//                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                        toastr()->success('ALready updated');
                        return redirect()->back();
                    }


                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addTRXStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addTRXStatus->currency_address != trim($request->Input('trn_address'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('trn_address'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addTRXStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }
                    if($flag == 1){
                        if($addressStatus){
                            $currency_update_info.="<br>Tron Address: ".$request->Input('trn_address');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);

                        }
                    }
                }

                if (!empty($request->Input('ethereum'))) {
                    $flag = 3;
                    $addData['currency'] = "ETH";
                    $addData['currency_address'] = trim($request->Input('ethereum'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    // start with 0,1
                    if(!empty($request->ethereum))
                    {
                        $checkAddress =  $this->checkcurrencyvalidaion('ethereum',$request->input('ethereum'));

                        if ($checkAddress != '')
                        {
                            $arrStatus = Response::HTTP_NOT_FOUND;
                            $arrCode   = Response::$statusTexts[$arrStatus];
//                            return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                            toastr()->success('ALready updated');
                            return redirect()->back();
                        }

                    }

                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('ethereum'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('ethereum'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }

                    if($flag == 3){
                        if($addressStatus){
                            $currency_update_info.="<br>Ethereum Address: ".$request->Input('ethereum');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('bnb'))) {
                    $flag = 3;
                    $addData['currency'] = "BNB";
                    $addData['currency_address'] = trim($request->Input('bnb'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    // start with 0,1
                    if(strlen(trim($request->Input('bnb'))) >= 26 && strlen(trim($request->Input('bnb'))) <= 42){
                        $split_array = str_split(trim($request->Input('bnb')));
                        if ($split_array[0] == '0') {

                        } elseif ($split_array[0] == '1') {

                        } else {
                            toastr()->error('BNB-BSC Address should be start with "0 or 1"');
                            return redirect()->back();
//                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BNB-BSC Address should be start with "0 or 1"', '');
                        }
                    }else{
                        toastr()->error('BNB address is not valid!');
                        return redirect()->back();
//                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'BNB address is not valid!', '');
                    }
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('bnb'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('bnb'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }

                }

                if (!empty($request->Input('usdt_trc20_address')) && $request->usdt_trc20_address != null) {
                    $flag = 4;
                    $addData['currency'] = "USDT-TRC20";
                    $addData['currency_address'] = trim($request->Input('usdt_trc20_address'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    if(strlen(trim($request->Input('usdt_trc20_address'))) >= 26 && strlen(trim($request->Input('usdt_trc20_address'))) <= 50){
                        $split_array = str_split(trim($request->Input('usdt_trc20_address')));
                        if ($split_array[0] == 'T' || $split_array[0] == 't') {

                        } else {
                            toastr()->error('USDT-TRC20 Address should be start with "T or t"');
                            return redirect()->back();
//                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 Address should be start with "T or t"', '');
                        }
                    }else{
                        toastr()->error('USDT-TRC20 address is not valid!');
                        return redirect()->back();
//                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 address is not valid!', '');
                    }
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('usdt_trc20_address'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('usdt_trc20_address'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }

                    if($flag == 4){
                        if($addressStatus){
                            $currency_update_info.="<br>USDT-TRC20 Address: ".$request->Input('usdt_trc20_address');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('ltc_address'))) {
                    $flag = 5;
                    $addData['currency'] = "LTC";
                    $addData['currency_address'] = trim($request->Input('ltc_address'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    // start with L,M,3,ltc1
                    if(strlen(trim($request->Input('ltc_address'))) >= 26 && strlen(trim($request->Input('ltc_address'))) <= 50){
                        $split_array = str_split(trim($request->Input('ltc_address')));
                        $split_array1 = str_split(trim($request->Input('ltc_address')),4);
                        if ($split_array[0] == 3)
                        {

                        } elseif ($split_array[0] == 'L') {

                        } elseif ($split_array[0] == 'M') {

                        }
                        elseif ($split_array[0] == 'l' && $split_array[1] == 't' && $split_array[2] == 'c'  && $split_array[3] == '1'){

                        }
                        else {
                            toastr()->error('Litecoin address should be start with "L or M or ltc1"');
                            return redirect()->back();
//                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address should be start with "L or M or ltc1"', '');
                        }
                    }else{
                        toastr()->error('Litecoin address is not valid!');
                        return redirect()->back();
//                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address is not valid!', '');
                    }
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('ltc_address'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('ltc_address'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }

                    if($flag == 5){
                        if($addressStatus){
                            $currency_update_info.="<br>Litecoin Address: ".$request->Input('ltc_address');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('doge_address'))) {
                    $flag = 6;
                    $addData['currency'] = "DOGE";
                    $addData['currency_address'] = trim($request->Input('doge_address'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    if(strlen(trim($request->Input('doge_address'))) >= 26 && strlen(trim($request->Input('doge_address'))) <= 50){

                    }else{
                        toastr()->error('Doge address is not valid!');
                        return redirect()->back();
//                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Doge address is not valid!', '');
                    }
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('doge_address'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('doge_address'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }
                    if($flag == 6){
                        if($addressStatus){
                            $currency_update_info.="<br>Dogecoin Address: ".$request->Input('doge_address');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('bch'))) {
                    $flag = 7;
                    $addData['currency'] = "BCH";
                    $addData['currency_address'] = trim($request->Input('bch'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('bch'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('bch'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }
                    if($flag == 7){
                        if($addressStatus){
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('shib'))) {
                    $flag = 8;
                    $addData['currency'] = "SHIB";
                    $addData['currency_address'] = trim($request->Input('shib'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('shib'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('shib'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }
                    if($flag == 8){
                        if($addressStatus){
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('pm'))) {
                    $flag = 9;
                    $addData['currency'] = "PM";
                    $addData['currency_address'] = trim($request->Input('pm'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('pm'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('pm'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }
                    if($flag == 9){
                        if($addressStatus){
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('sol_address'))) {
                    $flag = 10;
                    $addData['currency'] = "SOL";
                    $addData['currency_address'] = trim($request->Input('sol_address'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    $split_array = str_split(trim($request->Input('sol_address')));
                    /*if ($split_array[0] == 's')
            { */
                    if (strlen(trim($request->Input('sol_address'))) >= 26 && strlen(trim($request->Input('sol_address'))) <= 50)
                    {
                    }
                    else
                    {
                        toastr()->error('sol address must be in between 26 to 42 characters!');
                        return redirect()->back();
//                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'sol address must be in between 26 to 42 characters!', '');
                    }
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('sol_address'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('sol_address'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }
                    if($flag == 10){
                        if($addressStatus){
                            $currency_update_info.="<br>Solana Address: ".$request->Input('sol_address');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }

                if (!empty($request->Input('usdt_erc20_address')) && $request->usdt_erc20_address != null) {
                    $flag = 11;
                    $addData['currency'] = "USDT-ERC20";
                    $addData['currency_address'] = trim($request->Input('usdt_erc20_address'));
                    $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                    if(!empty($request->ethereum))
                    {

                        $checkAddress =  $this->checkcurrencyvalidaion('usdt_erc20_address',$request->input('usdt_erc20_address'));

                        if ($checkAddress != '')
                        {
                            $arrStatus = Response::HTTP_NOT_FOUND;
                            $arrCode   = Response::$statusTexts[$arrStatus];
//                            return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                            toastr()->error('Already updated');
                            return redirect()->back();
                        }

                    }
                    $new_time = \Carbon\Carbon::now()->addDays(1);
                    $token = md5(Auth::user()->user_id.$new_time);
                    $addData['block_user_date_time'] = $new_time;
                    $addData['token'] = $token;
                    $addData['token_status'] = 0;
                    if(empty($addBTCStatus)){
                        $addressStatus = UserWithdrwalSetting::create($addData);
                    }else if($addBTCStatus->currency_address != trim($request->Input('usdt_erc20_address'))){
                        $updateAddress['block_user_date_time'] = $new_time;
                        $updateAddress['token'] = $token;
                        $updateAddress['token_status'] = 0;
                        $updateAddress['currency_address'] = trim($request->Input('usdt_erc20_address'));
                        $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                    }else{
                        $flag = 0;
                    }

                    if($flag == 11){
                        if($addressStatus){
                            $currency_update_info.="<br>USDT-ERC20 Address: ".$request->Input('usdt_erc20_address');
                            $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                        }
                    }
                }


                // $updateData = User::where('id', $getuser_id)->update($arrData);
                if (!empty($addressStatus)) {
                    $pagename = "emails.payment_information_updated";
                    $username = Auth::user()->user_id;
                    $subject='HSCC | Payment information updated.';
                    if ($currency_update_info != "") {

                        $data = array('pagename' => $pagename,'currency_update_info'=>$currency_update_info, 'username' => $username,'name'=>Auth::user()->fullname);
                        $mail = sendMail($data, Auth::user()->email, $subject);
                        if ($mail) {
                            /*$arrStatus  = Response::HTTP_OK;
                            $arrCode    = Response::$statusTexts[$arrStatus];*/
                        } else {
                            $arrStatus = Response::HTTP_NOT_FOUND;
                            $arrCode = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Failed to send email for profile update';
//                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                            toastr()->error($arrMessage);
                            return redirect()->back();
                        }
                    }
                    $arrStatus = Response::HTTP_OK;
                    $arrCode = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'User address updated successfully';
//                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    toastr()->success($arrMessage);
                    return redirect()->back();
                } else {
                    $arrStatus = Response::HTTP_OK;
                    $arrCode = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Already updated with same data';
//                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    toastr()->error($arrMessage);
                    return redirect()->back();
                }
            }
        }catch(Exception $e){
//             dd($e);
            $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
//            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            toastr()->error($arrMessage);
            return redirect()->back();
        }
    }
    /**
     * update user profile
     *
     * @return \Illuminate\Http\Response
     */
    public function updateUserData(Request $request)
    {
        // check user is from same browser or not
        //dd($request);
        // $req_temp_info = $request->header('User-Agent');
        // $result        = check_user_authentication_browser($req_temp_info, Auth::user()->temp_info);
        // if ($result == false) {
        //     return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
        // }

        // dd($request->profile_status);

        if (!empty($request->Input('fullname'))) {
            $rules = array('fullname' => 'required|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/');
        }

        if (!empty($request->Input('mobile'))) {
            $rules = array('mobile' => 'required|numeric|digits:10');
        }
        if (!empty($request->Input('email'))) {
            $rules = array('email' => 'required|email|max:50');
        }
        /*if (!empty($request->Input('paypal_address'))) {
		$rules = array('paypal_address' => 'required|email|m:100');
		}
		 */
        // $verify_otp = verify_Otp($request->input('otp'));
        //  dd($verify_otp);
        if (!empty($request->input('btc_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('btc_address', $request->input('btc_address'));

            if ($checkAddress != '') {
                // $arrStatus = Response::HTTP_NOT_FOUND;
                // $arrCode   = Response::$statusTexts[$arrStatus];
                // return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                $strMsg = "Bitcoin Address should be start with b or 1 or 3.";
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }
        if (!empty($request->input('ethereum'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('ethereum', $request->input('ethereum'));

            if ($checkAddress != '') {
                // $arrStatus = Response::HTTP_NOT_FOUND;
                // $arrCode   = Response::$statusTexts[$arrStatus];
                // return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                $strMsg = 'Ethereum Address should be start with \"0x\"';
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }
        if (!empty($request->input('trn_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('trn_address', $request->input('trn_address'));

            if ($checkAddress != '') {
                // $arrStatus = Response::HTTP_NOT_FOUND;
                // $arrCode   = Response::$statusTexts[$arrStatus];
                // return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                $strMsg = "TRX Address should be start with T or t";
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }

        if (!empty($request->input('bnb_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('bnb_address', $request->input('bnb_address'));

            if ($checkAddress != '') {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode   = Response::$statusTexts[$arrStatus];
                // return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                $strMsg = "BNB Address should be start with b or 1 or 3.";
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }

        if (!empty($request->input('doge_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('doge_address', $request->input('doge_address'));

            if ($checkAddress != '') {
                // $arrStatus = Response::HTTP_NOT_FOUND;
                // $arrCode   = Response::$statusTexts[$arrStatus];
                // return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                $strMsg = "Doge Coin Address should be start with D";
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }
        if (!empty($request->input('ltc_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('ltc_address', $request->input('ltc_address'));

            if ($checkAddress != '') {
                $strMsg = "LTC Address should be start with L or l or M or m.";
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }
        if (!empty($request->input('sol_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('sol_address', $request->input('sol_address'));

            if ($checkAddress != '') {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode   = Response::$statusTexts[$arrStatus];
                return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                // $strMsg = "Bitcoin Address should be start with b or 1 or 3.";
                // return back()->withErrors($strMsg);
            }
        }
        if (!empty($request->input('usdt_trc20_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('usdt_trc20_address', $request->input('usdt_trc20_address'));


            if ($checkAddress != '') {
                $strMsg = "Tether TRC-20 Address should be start with T or t.";
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }
        if (!empty($request->input('usdt_erc20_address'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('usdt_erc20_address', $request->input('usdt_erc20_address'));


            if ($checkAddress != '') {
                $strMsg = 'USDT-ERC20 Address should be start with \"0x\".';
                // return back()->withErrors($strMsg);
                toastr()->error($strMsg);
                return redirect()->back();
            }
        }
        if (!empty($request->input('TRC-20'))) {
            $checkAddress =  $this->checkcurrencyvalidaion('TRC-20', $request->input('TRC-20'));

            if ($checkAddress != '') {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode   = Response::$statusTexts[$arrStatus];
                return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                // $strMsg = "Bitcoin Address should be start with b or 1 or 3.";
                // return back()->withErrors($strMsg);
            }
        }


        if (!empty($rules)) {
            // $validator = checkvalidation($request->all(), $rules, '');
            // if (!empty($validator)) {

            //     $arrStatus  = Response::HTTP_NOT_FOUND;
            //     $arrCode    = Response::$statusTexts[$arrStatus];
            //     $arrMessage = $validator;
            //     return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            // }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                // return back()->withErrors($validator)->withInput();
                toastr()->error($message);
                return redirect()->back();
            }
        }

        $getuser_id = Auth::user()->id;
        // //dd($getuser_id);
        $otpdata = Otp::select('otp_id', 'otp_status', 'otp')
            ->where('id', Auth::user()->id)
            ->where('otp', md5($request->otp))
            ->where('otp_status', '=', 0)
            ->orderBy('entry_time', 'desc')->first();
        //dd($otpdata['otp_id']);
        $arrInput            = $request->all();
        $profile_status = verifyOtpStatus::select('profile_update_status')
            ->where('statusID', '=', 1)->get();
        $userData = User::where('id', $getuser_id)->first();
        if ($profile_status[0]->profile_update_status == 1) {
            if ($userData->google2fa_status == 'disable') {
                $arrInput['user_id'] = Auth::user()->id;
                if ($arrInput['type'] != "photo") {
                    $arrRules            = ['otp' => 'required|min:6|max:6'];
                    $validator           = Validator::make($arrInput, $arrRules);
                    if ($validator->fails()) {
                        return setValidationErrorMessage($validator);
                    }
                    $otpdata         = verify_Otp($arrInput);
                } else {
                    $otpdata['status'] = 200;
                }
            } else {
                $otpdata['status'] = 200;
            }
            $otpdata['status'] = 200;
        } else {
            $otpdata['status'] = 200;
        }


        // dd($arrInput);
        // if (!empty($otpdata)) {
        if ($arrInput['type'] != "photo") {

            if ($userData->google2fa_status == 'enable') {
                $arrIn  = array();

                $arrIn['id'] = $getuser_id;
                $arrIn['otp'] = $arrInput['otp_2fa'];
                $arrIn['google2fa_secret'] = $userData->google2fa_secret;
                if (empty($arrIn['otp'])) {
                    $arrOutputData = [];
                    $strMessage = "Google 2FA code Required";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    // return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                    toastr()->error($strMessage);
                    return redirect()->back();
                }
                $res = $this->google2facontroller->validateGoogle2FA($arrIn);
                if ($res == false) {
                    $arrOutputData = [];
                    $strMessage = "Invalid Google 2FA code";
                    $intCode = Response::HTTP_UNAUTHORIZED;
                    $strStatus = Response::$statusTexts[$intCode];
                    // return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                    toastr()->error($strMessage);
                    return redirect()->back();
                }
            }
        }

        if ($otpdata['status'] == 200) {
            //	if (true) {
            // Otp::where('otp_id', $otpdata->otp_id)->update(['otp_status' => 1]);
            //Otp::where('otp_id', $otpdata->otp_id)->delete();

            try {

                //$CheckActive = User::where([['remember_token', '=', trim($request->Input('remember_token'))], ['status', '=', 'Active']])->first();

                //---------update sponser id--------------
                $arrData = array();

                /*if (!empty($request->Input('paypal_address'))) {
				$arrData['paypal_address'] = trim($request->Input('paypal_address'));
				}*/

                if (!empty($request->Input('fullname'))) {
                    $arrData['fullname'] = trim($request->Input('fullname'));
                }

                if (!empty($request->Input('trn_address'))) {
                    $arrData['trn_address'] = trim($request->Input('trn_address'));
                }
                if (!empty($request->Input('ethereum'))) {
                    $arrData['ethereum'] = trim($request->Input('ethereum'));
                }


                if (!empty($request->Input('bnb_address'))) {
                    $arrData['bnb_address'] = trim($request->Input('bnb_address'));
                }

                if (!empty($request->Input('mobile'))) {
                    $arrData['mobile'] = trim($request->Input('mobile'));
                }
                if (!empty($request->Input('email'))) {
                    $arrData['email'] = trim($request->Input('email'));
                }

                if (!empty($request->Input('btc_address'))) {
                    $arrData['btc_address'] = trim($request->Input('btc_address'));
                }
                if (!empty($request->Input('ltc_address'))) {
                    $arrData['ltc_address'] = trim($request->Input('ltc_address'));
                }

                if (!empty($request->Input('doge_address'))) {
                    $arrData['doge_address'] = trim($request->Input('doge_address'));
                }

                if (!empty($request->Input('sol_address'))) {
                    $arrData['sol_address'] = trim($request->Input('sol_address'));
                }

                if (!empty($request->Input('usdt_trc20_address'))) {
                    $arrData['usdt_trc20_address'] = trim($request->Input('usdt_trc20_address'));
                }
                 if (!empty($request->Input('usdt_erc20_address'))) {
                    $arrData['usdt_erc20_address'] = trim($request->Input('usdt_erc20_address'));
                }
                if (!empty($request->Input('country'))) {
                    $arrData['country'] = trim($request->Input('country'));
                }
                if (!empty($request->Input('account_no'))) {
                    $arrData['account_no'] = trim($request->Input('account_no'));
                }
                if (!empty($request->Input('holder_name'))) {
                    $arrData['holder_name'] = trim($request->Input('holder_name'));
                }
                if (!empty($request->Input('pan_no'))) {
                    $arrData['pan_no'] = trim($request->Input('pan_no'));
                }
                if (!empty($request->Input('bank_name'))) {
                    $arrData['bank_name'] = trim($request->Input('bank_name'));
                }

                if (!empty($request->Input('city'))) {
                    $arrData['city'] = trim($request->Input('city'));
                }

                /*if (!empty($request->Input('perfect_money_address'))) {
				$arrData['perfect_money_address'] = trim($request->Input('perfect_money_address'));
				}
				if (!empty($request->Input('facebook_link'))) {
				$arrData['facebook_link'] = trim($request->Input('facebook_link'));
				}
				if (!empty($request->Input('linkedin_link'))) {
				$arrData['linkedin_link'] = trim($request->Input('linkedin_link'));
				}
				if (!empty($request->Input('twitter_link'))) {
				$arrData['twitter_link'] = trim($request->Input('twitter_link'));
				}
				if (!empty($request->Input('instagram_link'))) {
				$arrData['instagram_link'] = trim($request->Input('instagram_link'));
				}*/

                if (!empty($request->Input('address'))) {
                    $arrData['address'] = trim($request->Input('address'));
                }

                if (!empty($request->Input('ifsc_code'))) {
                    $arrData['ifsc_code'] = trim($request->Input('ifsc_code'));
                }
                if (!empty($request->Input('branch_name'))) {
                    $arrData['branch_name'] = trim($request->Input('branch_name'));
                }
                if (!empty($request->Input('mobile'))) {
                    $arrData['mobile'] = trim($request->Input('mobile'));
                }
                if (!empty($request->file('profile_image'))) {
                    $arrData['profile_image'] = trim($request->Input('profile_image'));
                    $arrData['profile_image']        = $request->file('profile_image');
                }

                if (!empty($arrData)) {
                    //UserInfo
                    $oldUserData = $arrData;


                    //-----iget old user data and inset----------------
                    $oldUserData = DB::table('tbl_users')
                        ->select('id', 'fullname', 'address', 'country', 'holder_name', 'pan_no', 'bank_name', 'ifsc_code', 'user_id', 'mobile', 'btc_address', 'bnb_address', 'trn_address', 'ltc_address', 'sol_address', 'doge_address', 'usdt_trc20_address', 'ethereum', 'email')
                        ->where('id', $getuser_id)
                        ->first();

                    $oldUserData->ip         = $request->ip();
                    $oldUserData->updated_by = $getuser_id;
                    //$count = 1;
                    $check_id_exist = UserUpdateProfileCount::where('id', $getuser_id)->first();
                    //dd($check_id_exist);
                    $old_data_content = "";
                    $new_data_content = "";
                    if ($check_id_exist == null) {

                        $newData       = new UserUpdateProfileCount;
                        $newData['id'] = $getuser_id;
                        $newData->save();
                    }
                    if ($request->fullname != $oldUserData->fullname) {
                        $updateData2['fullname'] = DB::raw('fullname +1');
                        $updateOtpSta            = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData2);
                        $old_data_content .= "<br>Name: " . $oldUserData->fullname;
                        $new_data_content .= "<br>Name: " . $request->fullname;
                    }
                    if ($request->email != $oldUserData->email) {
                        $updateData3['email'] = DB::raw('email +1');
                        $updateOtpSta         = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData3);
                        $old_data_content .= "<br>Email: " . $oldUserData->email;
                        $new_data_content .= "<br>Email: " . $request->email;
                    }
                    if ($request->mobile != $oldUserData->mobile) {

                        $updateData1['mobile'] = DB::raw('mobile +1');
                        $updateOtpSta          = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData1);
                        $old_data_content .= "<br>Mobile: " . $oldUserData->mobile;
                        $new_data_content .= "<br>Mobile: " . $request->mobile;
                    }
                    if ($request->country != $oldUserData->country) {
                        $updateData4['country'] = DB::raw('country +1');
                        $updateOtpSta           = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData4);
                    }

                    if ($request->btc_address != $oldUserData->btc_address) {
                        $updateData5['btc_address'] = DB::raw('btc_address +1');
                        $updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData5);
                    }
                    if ($request->bnb_address != $oldUserData->bnb_address) {
                        $updateData6['bnb_address'] = DB::raw('bnb_address +1');
                        $updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData6);
                    }
                    if ($request->trn_address != $oldUserData->trn_address) {
                        $updateData7['trn_address'] = DB::raw('trn_address +1');
                        $updateOtpSta               = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData7);
                    }


                    if ($request->ethereum != $oldUserData->ethereum) {
                        $updateData8['ethereum'] = DB::raw('ethereum +1');
                        $updateOtpSta = UserUpdateProfileCount::where('id', $getuser_id)->update($updateData8);
                        $res = verify_address($getuser_id);
                    }

                    if ($oldUserData->btc_address != null || $oldUserData->btc_address != '') {
                        if ($request->btc_address != $oldUserData->btc_address) {
                            $arrData['btc_address'] = trim($request->btc_address);
                        }
                    }

                    if(!empty($getuser_id)){
                        $addData = array();
                        $addData['id'] = $getuser_id;
                        $addData['status'] = 1;
                        $addData['updated_by'] = $getuser_id;
                        $currency_update_info="";
                        if (!empty($request->Input('btc_address'))) {
                            $flag = 2;
                            $addData['currency'] = "BTC";
                            $addData['currency_address'] = trim($request->Input('btc_address'));
                            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                            $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                            if (!empty($request->btc_address)) {
                                $checkAddress =  $this->checkcurrencyvalidaion('BTC',$request->input('btc_address'));    
                                if ($checkAddress != '') 
                                {
                                    toastr()->error($checkAddress);
                                    return redirect()->back();
                                }

                            }
                            $new_time = \Carbon\Carbon::now()->addDays(1);
                            $token = md5(Auth::user()->user_id.$new_time);
                            $addData['block_user_date_time'] = $new_time;
                            $addData['token'] = $token;
                            $addData['token_status'] = 0;
                            if(empty($addBTCStatus)){
                                $addressStatus = UserWithdrwalSetting::create($addData);
                            }else if($addBTCStatus->currency_address != trim($request->Input('btc_address'))){
                                $updateAddress['block_user_date_time'] = $new_time;
                                $updateAddress['token'] = $token;
                                $updateAddress['token_status'] = 0;
                                $updateAddress['currency_address'] = trim($request->Input('btc_address'));
                                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            }else{
                                $flag = 0;
                            }
                            
                            if($flag == 2){
                                if($addressStatus){
                                    $currency_update_info.="<br>Bitcoin Address: ".$request->Input('btc_address');
                                    $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                                }
                            }
                        }
                    }

                    if ($oldUserData->trn_address != null || $oldUserData->trn_address != '') {
                        if ($request->trn_address != $oldUserData->trn_address) {
                            $arrData['trn_address'] = trim($request->trn_address);
                        }
                    }

                    if (!empty($request->Input('trn_address'))) {
                        $flag = 1;
                        $addData['currency'] = "TRX";
                        $addData['currency_address'] = trim($request->Input('trn_address'));
                        $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addTRXStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                  
                        $checkAddress =  $this->checkcurrencyvalidaion('trn_address',$request->input('trn_address'));
    
                        if ($checkAddress != '') 
                        {
                            toastr()->error($checkAddress);
                            return redirect()->back();
                        }

                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addData['block_user_date_time'] = $new_time;
                        $addData['token'] = $token;
                        $addData['token_status'] = 0;
                        if(empty($addTRXStatus)){
                            $addressStatus = UserWithdrwalSetting::create($addData);
                        }else if($addTRXStatus->currency_address != trim($request->Input('trn_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('trn_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addTRXStatus->srno)->update($updateAddress);
                        }else{
                            $flag = 0;
                        }
                        if($flag == 1){
                            if($addressStatus){
                                $currency_update_info.="<br>Tron Address: ".$request->Input('trn_address');
                                $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);                               
                            }
                        }                       
                    }

                    if ($oldUserData->ethereum != null || $oldUserData->ethereum != '') {
                        if ($request->ethereum != $oldUserData->ethereum) {
                            $arrData['ethereum'] = trim($request->ethereum);
                        }
                    }

                    if (!empty($request->Input('ethereum'))) {
                        $flag = 3;
                        $addData['currency'] = "ETH";
                        $addData['currency_address'] = trim($request->Input('ethereum'));
                        $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                        if(!empty($request->ethereum))
                        {
                            
                            $checkAddress =  $this->checkcurrencyvalidaion('ethereum',$request->input('ethereum'));
        
                            if ($checkAddress != '') 
                            {
                                toastr()->error($checkAddress);
                                return redirect()->back();
                            }

                        }
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addData['block_user_date_time'] = $new_time;
                        $addData['token'] = $token;
                        $addData['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            $addressStatus = UserWithdrwalSetting::create($addData);
                        }else if($addBTCStatus->currency_address != trim($request->Input('ethereum'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('ethereum'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                        }else{
                            $flag = 0;
                        }

                        if($flag == 3){
                            if($addressStatus){
                                $currency_update_info.="<br>Ethereum Address: ".$request->Input('ethereum');
                                $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                            }
                        }
                    }

                    if ($oldUserData->bnb_address != null || $oldUserData->bnb_address != '') {
                        if ($request->bnb_address != $oldUserData->bnb_address) {
                            $arrData['bnb_address'] = trim($request->bnb_address);
                        }
                    }

                    if (!empty($request->Input('usdt_trc20_address')) && $request->usdt_trc20_address != null) {
                        $flag = 4;
                        $addData['currency'] = "USDT-TRC20";
                        $addData['currency_address'] = trim($request->Input('usdt_trc20_address'));
                        $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                        if(strlen(trim($request->Input('usdt_trc20_address'))) >= 26 && strlen(trim($request->Input('usdt_trc20_address'))) <= 50){
                                $split_array = str_split(trim($request->Input('usdt_trc20_address')));
                                if ($split_array[0] == 'T' || $split_array[0] == 't') {

                                } else {
                                toastr()->error('USDT-TRC20 Address should be start with "T or t"');
                                return redirect()->back();
                                
                                }
                        }else{
                            toastr()->error('USDT-TRC20 Address is not valid!');
                            return redirect()->back();
                           
                        }
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addData['block_user_date_time'] = $new_time;
                        $addData['token'] = $token;
                        $addData['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            $addressStatus = UserWithdrwalSetting::create($addData);
                        }else if($addBTCStatus->currency_address != trim($request->Input('usdt_trc20_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('usdt_trc20_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                        }else{
                            $flag = 0;
                        }

                        if($flag == 4){
                            if($addressStatus){
                                $currency_update_info.="<br>USDT-TRC20 Address: ".$request->Input('usdt_trc20_address');
                                $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                            }
                        }                   
                    }

                    if (!empty($request->Input('sol_address'))) {
                        $flag = 10;
                        $addData['currency'] = "SOL";
                        $addData['currency_address'] = trim($request->Input('sol_address'));
                        $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                         $split_array = str_split(trim($request->Input('sol_address')));
                     
                        if (strlen(trim($request->Input('sol_address'))) >= 26 && strlen(trim($request->Input('sol_address'))) <= 50) 
                        {
                        } 
                        else 
                        {   
                            toastr()->error('sol address must be in between 26 to 42 characters!');
                            return redirect()->back();
                           
                        }
               
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addData['block_user_date_time'] = $new_time;
                        $addData['token'] = $token;
                        $addData['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            $addressStatus = UserWithdrwalSetting::create($addData);
                        }else if($addBTCStatus->currency_address != trim($request->Input('sol_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('sol_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                        }else{
                            $flag = 0;
                        }
                        if($flag == 10){
                            if($addressStatus){
                                $currency_update_info.="<br>Solana Address: ".$request->Input('sol_address');
                                $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                            }
                        }
                    }

                    if (!empty($request->Input('ltc_address'))) {
                        $flag = 5;
                        $addData['currency'] = "LTC";
                        $addData['currency_address'] = trim($request->Input('ltc_address'));
                        $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                            if(strlen(trim($request->Input('ltc_address'))) >= 26 && strlen(trim($request->Input('ltc_address'))) <= 50){
                                $split_array = str_split(trim($request->Input('ltc_address')));
                                $split_array1 = str_split(trim($request->Input('ltc_address')),4);
                                if ($split_array[0] == 3)
                                {
                                    
                                } else if ($split_array[0] == 'L') {
                                    
                                } else if ($split_array[0] == 'M') {

                                }else if ($split_array[0] == 'l' && $split_array[1] == 't' && $split_array[2] == 'c'  && $split_array[3] == '1'){
                                
                                } 
                                else {
                                    toastr()->error('Litecoin address should be start with "L or M or ltc1"');
                                    return redirect()->back();
                                    // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address should be start with "L or M or ltc1"', '');
                                }
                            }else{
                                toastr()->error('Litecoin address is not valid!');
                                return redirect()->back();
                                // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address is not valid!', '');
                            }
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addData['block_user_date_time'] = $new_time;
                        $addData['token'] = $token;
                        $addData['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            $addressStatus = UserWithdrwalSetting::create($addData);
                        }else if($addBTCStatus->currency_address != trim($request->Input('ltc_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('ltc_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                        }else{
                            $flag = 0;
                        }

                        if($flag == 5){
                            if($addressStatus){
                                $currency_update_info.="<br>Litecoin Address: ".$request->Input('ltc_address');
                                $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                            }
                        }                       
                    }

                    if (!empty($request->Input('doge_address'))) {
                        $flag = 6;
                        $addData['currency'] = "DOGE";
                        $addData['currency_address'] = trim($request->Input('doge_address'));
                        $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                        if(strlen(trim($request->Input('doge_address'))) >= 26 && strlen(trim($request->Input('doge_address'))) <= 50){

                        }else{
                            toastr()->error('Doge address is not valid!');
                            return redirect()->back();
                            // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Doge address is not valid!', '');
                        }
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addData['block_user_date_time'] = $new_time;
                        $addData['token'] = $token;
                        $addData['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            $addressStatus = UserWithdrwalSetting::create($addData);
                        }else if($addBTCStatus->currency_address != trim($request->Input('doge_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('doge_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                        }else{
                            $flag = 0;
                        }
                        if($flag == 6){
                            if($addressStatus){
                                $currency_update_info.="<br>Dogecoin Address: ".$request->Input('doge_address');
                                $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                            }
                        }
                    }


                    if (!empty($request->Input('usdt_erc20_address')) && $request->usdt_erc20_address != null) {
                        $flag = 11;
                        $addData['currency'] = "USDT-ERC20";
                        $addData['currency_address'] = trim($request->Input('usdt_erc20_address'));
                        $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',$getuser_id],['currency',$addData['currency']],['status',1]])->first();
                        if(!empty($request->ethereum))
                        {

                            $checkAddress =  $this->checkcurrencyvalidaion('usdt_erc20_address',$request->input('usdt_erc20_address'));
        
                            if ($checkAddress != '') 
                            {
                                toastr()->error($checkAddress);
                                return redirect()->back();
                                // $arrStatus = Response::HTTP_NOT_FOUND;
                                // $arrCode   = Response::$statusTexts[$arrStatus];
                                // return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                            }

                        }
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addData['block_user_date_time'] = $new_time;
                        $addData['token'] = $token;
                        $addData['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            $addressStatus = UserWithdrwalSetting::create($addData);
                        }else if($addBTCStatus->currency_address != trim($request->Input('usdt_erc20_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('usdt_erc20_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                        }else{
                            $flag = 0;
                        }

                        if($flag == 11){
                            if($addressStatus){
                                $currency_update_info.="<br>USDT-ERC20 Address: ".$request->Input('usdt_erc20_address');
                                $res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
                            }
                        }                   
                    }

                    if (!empty($addressStatus)) {
                        $new_data_content = "";
                        $pagename = "emails.payment_information_updated";
                        $username = Auth::user()->user_id;
                        $subject='HSCC | Payment information updated.';
                        if ($currency_update_info != "") {

                            $data = array('pagename' => $pagename,'currency_update_info'=>$currency_update_info, 'username' => $username,'name'=>Auth::user()->fullname);
                            $mail = sendMail($data, Auth::user()->email, $subject);
                        }
                    }

                    // unset($oldUserData->blockby_cron);

                    // save old data
                    if ($arrInput['type'] == "photo") {
                        $rules_img = array(
                            'profile_image' => "required|mimes:jpeg,jpg,png|max:130000"
                        );

                        $validator = Validator::make($request->all(), $rules_img);
                        if ($validator->fails()) {
                            $message = messageCreator($validator->errors());
                            // return back()->withErrors($validator)->withInput();
                            toastr()->error($message);
                            return redirect()->back();
                        }
                        $file       = $request->file('profile_image');

                        $extension = $file->getClientOriginalExtension();
                        $newUrl = '';
                        if ($request->hasFile('profile_image')) {
                            $url    = Config::get('constants.settings.aws_url');


                            $s3 = new S3Client([
                                'version' => 'latest',
                                'region'  => env("AWS_REGION"),
                                'credentials' => [
                                    'key'    => env("AWS_KEY"),
                                    'secret' => env("AWS_SECRET"),
                                ],
                            ]);


                            $bucket = env("AWS_BUCKET");
                            $filename = time() . '_' . date('Ymd') . '.' . $extension;
                            $path = 'user_profile/' . $filename;

                            $user_profile = $s3->putObject([
                                'Bucket' => $bucket,
                                'Key'    => $path,
                                'Body'   => fopen($file, 'r'),
                                'ACL'    => 'public-read',
                            ]);



                            // $path = Storage::disk('s3')->putFile('user_profile', $file, 'public');
                            // $url = Storage::disk('s3')->url($path);

                            // $fileName = Storage::disk('s3')->put("user_profile", $file, "public");

                            //$newUrl = $url . $fileName;

                            $arrData['profile_image'] = $user_profile['ObjectURL'];
                            $arrData['user_image'] = $user_profile['ObjectURL'];
                        }
                    }

                    $saveOldData = DB::table('tbl_users_change_data')->insert((array) $oldUserData);



                    $updateData = User::where('id', $getuser_id)->update($arrData);
                    UserWithdrwalSetting::where('id', $getuser_id)->update($arrData);

                    $pagename = "emails.profile_update_notification";
                    $username = $userData->user_id;
                    $subject = 'Your profile has been updated.';
                    if ($new_data_content != "") {

                        $data = array('pagename' => $pagename, 'old_data_content' => $old_data_content, 'new_data_content' => $new_data_content, 'username' => $username, 'name' => $request->fullname);
                        $mail = sendMail($data, $userData->email, $subject);
                        if ($mail) {
                            /*$arrStatus  = Response::HTTP_OK;/
							$arrCode    = Response::$statusTexts[$arrStatus];*/
                        } else {
                            $arrStatus = Response::HTTP_NOT_FOUND;
                            $arrCode = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Failed to send email for profile update';
                            return back()->withErrors($arrMessage);
                            // toastr()->error($arrMessage);
                            // return redirect()->back();
                        }
                    }
                }
                //-------------------------------------------------
                if (!empty($updateData)) {
                    $arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'User data updated successfully';
                    // return redirect()->back()->withSuccess($arrMessage);
                    toastr()->success($arrMessage);
                    return redirect()->back();
                } else {
                    $arrMessage = 'Already updated with same data';
                    // return redirect()->back()->withErrors($arrMessage);
                    toastr()->error($arrMessage);
                    return redirect()->back();
                }
            } catch (Exception $e) {
                dd($e);
                // $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
                // $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Something went wrong,Please try again';
                // return back()->withErrors($arrMessage);
                toastr()->error($arrMessage);
                return redirect()->back();
            }
        } else if ($otpdata['status'] == 403) {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'OTP expired, Resend it..';
            // return back()->withErrors($arrMessage);
            toastr()->error($arrMessage);
            return redirect()->back();
        } else {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Incorrect OTP Or OTP Already Used';
            // return back()->withErrors($arrMessage);
            toastr()->error($arrMessage);
            return redirect()->back();
        }
    }

    function updateUserImage(Request $request)
    {
        $rules_img = array(
            'profile_image' => "required|mimes:jpeg,jpg,png|max:130000"
        );
        /*$img_validator           = Validator::make($arrInput, $rules_img);
						if ($validator->fails()) {
							return setValidationErrorMessage($img_validator);
						}*/
        // $validator = checkvalidation($request->all(), $rules_img, '');
        // if (!empty($validator)) {
        //     return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, '');
        // } else {
        //     return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Valid Image', '');
        // }
        $validator = Validator::make($request->all(), $rules_img);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return back()->withErrors($validator)->withInput();
        }
    }

    /**
     * Change password
     *
     * @return \Illuminate\Http\Response
     */

    function changePassword(Request $request)
    {
        // check user is from same browser or not

        try {
            $messsages = array(
                'new_password.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *',
            );
            //|regex:/^[a-zA-Z](?=.*\d)(?=.*[a-zA-Z])[0-9A-Za-z!@#$%]{6,50}$/
            $rules = array(
                // 'current_password' => 'required',
                // 'new_password'     => ['string'],
                'current_password' => 'required',
                'new_password'     => [
                    'string',
                    'min:8', // must be at least 10 characters in length
                    'max:15',
                    'regex:/[a-z]/', // must contain at least one lowercase letter
                    'regex:/[A-Z]/', // must contain at least one uppercase letter
                    'regex:/[0-9]/', // must contain at least one digit
                    'regex:/[@$!%*#?&]/', // must contain a special character',
                ],
                'confirm_password' => 'required|same:new_password',
                'otp' => 'required|numeric',
            );

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return back()->withErrors($validator)->withInput();
                toastr()->error($message);
            }
            $check_useractive = Auth::User();
            $profile_status = verifyOtpStatus::select('changepassword_update_status')
                ->where('statusID', '=', 1)->get();
            if ($profile_status[0]->changepassword_update_status == 1) {
                $arrInput            = $request->all();
                $arrInput['user_id'] = Auth::user()->id;
                $arrRules            = ['otp' => 'required|min:6|max:6'];
                $validator           = Validator::make($arrInput, $arrRules);
                if ($validator->fails()) {
                    // return setValidationErrorMessage($validator);
                    $message = messageCreator($validator->errors());
                    return back()->withErrors($validator)->withInput();
                }
                $otpdata         = verify_Otp($arrInput);
            } else {
                $otpdata['status'] = 200;
            }
            $result = isPasswordValid($request->new_password);

            if ($result['status'] == false) {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = $result['message'];
                // return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                // return back()->withErrors($arrMessage);
                toastr()->error($arrMessage);
                return redirect()->back();
            }

            // if (!empty($validator)) {
            // 	$arrStatus  = Response::HTTP_NOT_FOUND;
            // 	$arrCode    = Response::$statusTexts[$arrStatus];
            // 	$arrMessage = $validator;
            // 	return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            //     return back()->withErrors($arrMessage);
            // }
            $check_useractive = Auth::User();
            $arrInput            = $request->all();
            // dd($arrInput);
            if (!empty($check_useractive)) {
                $data = array();
                $data['user_id'] = $check_useractive->id;
                $data['otp'] = $request->otp;
                $otpdata = verify_Otp($data);
                $userData = User::where('id', $check_useractive->id)->first();
                if ($userData->google2fa_status == 'enable') {
                    $arrIn  = array();

                    $arrIn['id'] = $check_useractive->id;
                    $arrIn['otp'] = $request->otp_2fa;
                    $arrIn['google2fa_secret'] = $userData->google2fa_secret;
                    if (empty($arrIn['otp'])) {
                        $arrOutputData = [];
                        $arrMessage = "Google 2FA code Required";
                        $intCode = Response::HTTP_NOT_FOUND;
                        $strStatus = Response::$statusTexts[$intCode];
                        // return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                        // return back()->withErrors($strMessage);
                        toastr()->error($arrMessage);
                        return redirect()->back();
                    }
                    $res = $this->google2facontroller->validateGoogle2FA($arrIn);
                    if ($res == false) {
                        $arrOutputData = [];
                        $arrMessage = "Invalid Google 2FA code";
                        $intCode = Response::HTTP_UNAUTHORIZED;
                        $strStatus = Response::$statusTexts[$intCode];
                        // return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                        // return back()->withErrors($strMessage);
                        toastr()->error($arrMessage);
                        return redirect()->back();
                    }
                } else {
                    if ($otpdata['status'] === 200) {
                    } else if ($otpdata['status'] === 403) {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode = Response::$statusTexts[$arrStatus];
                        $arrMessage = $otpdata['msg'];
                        // return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                        // return back()->withErrors($arrMessage);
                        toastr()->error($arrMessage);
                        return redirect()->back();
                    } else {
                        $arrStatus  = Response::HTTP_NOT_FOUND;
                        $arrCode    = Response::$statusTexts[$arrStatus];
                        $arrMessage = 'Invalid OTP';
                        // return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                        // return back()->withErrors($arrMessage);
                        toastr()->error($arrMessage);
                        return redirect()->back();
                    }
                }

                if (Hash::check($request->input('new_password'), $check_useractive->bcrypt_password)) {
                    $arrStatus = Response::HTTP_NOT_FOUND;
                    $arrCode = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Current and new password is same';
                    // return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    // return back()->withErrors($arrMessage);
                    toastr()->error($arrMessage);
                    return redirect()->back();
                }


                if (Hash::check($request->Input('current_password'), $check_useractive->bcrypt_password)) {

                    /*if ($request->Input('verify') == '') {

								if (!empty($check_useractive) && $check_useractive->google2fa_status == 'enable') {
								// verify google authentication
								$arrData = array();
								$arrData['remember_token'] = $check_useractive->remember_token;
								$arrData['otpmode'] = 'FALSE';
								$arrData['google2faauth'] = 'TRUE';

								$arrStatus   = Response::HTTP_OK;
								$arrCode     = Response::$statusTexts[$arrStatus];
								$arrMessage  = 'Please enter your 2FA verification code';
								return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);

								} else if (!empty($check_useractive) && $check_useractive->google2fa_status == 'disable') {
								if (!empty($check_useractive) && $request->Input('otp') == '') {
								$arrData = array();
								$arrData['user_id'] = $check_useractive->user_id;
								$arrData['password'] = $request->Input('current_pwd');
								$arrData['remember_token'] = $check_useractive->remember_token;
								$arrData['otpmode'] = 'TRUE';
								$arrData['google2faauth'] = 'FALSE';
								if ($check_useractive->mobile != '' && $check_useractive->mobile != '0') {
								$mask_mobile = maskmobilenumber($check_useractive->mobile);
								$arrData['mobile'] = $mask_mobile;
								}
								$mask_email = maskEmail($check_useractive->email);
								$arrData['email'] = $mask_email;

								$arrStatus   = Response::HTTP_OK;
								$arrCode     = Response::$statusTexts[$arrStatus];
								$arrMessage  = 'Please select otp mode';
								return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);
								}
								} else {

								$arrStatus   = Response::HTTP_NOT_FOUND;
								$arrCode     = Response::$statusTexts[$arrStatus];
								$arrMessage  = 'Invalid user';
								return sendResponse($arrStatus,$arrCode,$arrMessage,'');
								}
								 */

                    $updateData                    = array();
                    $updateData['password']        = encrypt($request->Input('confirm_password'));
                    $updateData['bcrypt_password'] = bcrypt($request->Input('confirm_password'));
                    $updateOtpSta                  = User::where('id', $check_useractive->id)->update($updateData);


                    $old_data_content = "<br>Old Password: " . $request->Input('current_password');
                    $new_data_content = "<br>New Password: " . $request->Input('new_password');
                    $pagename = "emails.profile_update_notification";
                    $username = $check_useractive->user_id;
                    $subject = 'Your password has been updated.';


                    $data = array('pagename' => $pagename, 'old_data_content' => $old_data_content, 'new_data_content' => $new_data_content, 'username' => $username, 'name' => $check_useractive->fullname);
                    $mail = sendMail($data, $check_useractive->email, $subject);
                    if ($mail) {
                        /*$arrStatus  = Response::HTTP_OK;
										$arrCode    = Response::$statusTexts[$arrStatus];*/
                        // $arrMessage = 'Password changed successfully';
                        // return back()->withSuccess($arrMessage);
                    } else {
                        // $arrStatus = Response::HTTP_NOT_FOUND;
                        // $arrCode = Response::$statusTexts[$arrStatus];
                        $arrMessage = 'Failed to send email for profile update';
                        toastr()->error($arrMessage);
                        return redirect()->back();
                        // return back()->withErrors($arrMessage);
                    }
                    if (!empty($updateOtpSta)) {

                        $arrStatus  = Response::HTTP_OK;
                        $arrCode    = Response::$statusTexts[$arrStatus];
                        $arrMessage = 'Password changed successfully';
                        // return back()->withSuccess($arrMessage);
                        toastr()->success($arrMessage);
                        return redirect()->back();
                    } else {

                        $arrStatus  = Response::HTTP_NOT_FOUND;
                        $arrCode    = Response::$statusTexts[$arrStatus];
                        $arrMessage = 'Current and new password is same';
                        // return back()->withErrors($arrMessage);
                        toastr()->error($arrMessage);
                        return redirect()->back();
                    }
                    /*} else {
							$arrStatus   = Response::HTTP_NOT_FOUND;
							$arrCode     = Response::$statusTexts[$arrStatus];
							$arrMessage  = 'Something went wrong,Please try again';
							return sendResponse($arrStatus,$arrCode,$arrMessage,'');
							 */
                } else {
                    // $arrStatus  = Response::HTTP_NOT_FOUND;
                    // $arrCode    = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Current password not matched';
                    // return back()->withErrors($arrMessage);
                    toastr()->error($arrMessage);
                    return redirect()->back();
                }
            } else {

                // $arrStatus  = Response::HTTP_NOT_FOUND;
                // $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                // return back()->withErrors($arrMessage);
                toastr()->error($arrMessage);
                return redirect()->back();
            }
        } catch (Exception $e) {
            // dd($e);
            // $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            // $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            // return back()->withErrors($arrMessage);
            toastr()->error($arrMessage);
            return redirect()->back();
        }
    }
    public function checkUserExistAuth(Request $request)
    {
        try {
            $arrInput = $request->user_id;
            //validate the info, create rules for the inputs
            $rules = array(
                'user_id' => 'required',

            );
            // run the validation rules on the inputs from the form
            $validator = Validator::make($request->all(), $rules);
            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message    = $validator->errors();
                $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Sponsor ID required';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            } else {
                //check wether user exist or not by user_id
                $checkUserExist = User::where('user_id', trim($request->user_id))->where('type', '!=', 'Admin')->select('id', 'user_id', 'fullname', 'remember_token')->first();

                if (!empty($checkUserExist)) {

                    $arrObject['id']             = $checkUserExist->id;
                    $arrObject['user_id']        = $checkUserExist->user_id;
                    $arrObject['curr_user_id']        = Auth::User()->user_id;
                    $arrObject['fullname']       = $checkUserExist->fullname;
                    $arrObject['remember_token'] = $checkUserExist->remember_token;

                    $arrStatus  = Response::HTTP_OK;
                    $arrCode    = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'User available';
                    return sendResponse($arrStatus, $arrCode, $arrMessage, $arrObject);
                } else {
                    $arrStatus  = Response::HTTP_NOT_FOUND;
                    $arrCode    = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Invalid user';
                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');
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
    public function getToolData(Request $request)
    {  $tool_type=$request->tool_type;
        try {
            $user = Auth::user();


            if (!empty($user->user_id)) {

             /*   if (!empty($request->tool_type)) {
                    $getToolData = MarketTool::where('tool_type', $request->tool_type)->orderBy('srno', 'asc')->get();
                } else {
                 $getToolData = MarketTool::orderBy('srno', 'asc')->get();
                }
                */
                $getToolData = MarketTool::orderBy('srno', 'asc')->get();
                if (empty($getToolData) ) {
                    $arrStatus = Response::HTTP_NOT_FOUND;
                    $arrCode = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Data not found';
                    return back()->withErrors($arrMessage);
                } else {
                    $arrData = $getToolData;
                  /*  echo '<pre>';
                    print_r($arrData->toArray());*/

                    return view('user.marketing-tools')->with(compact('arrData','tool_type'));
                }
            } else {
                $arrStatus = Response::HTTP_UNAUTHORIZED;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'User Unaunthenticated';
                return back()->withErrors($arrMessage);
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return back()->withErrors($arrMessage);
        }
    }
}
