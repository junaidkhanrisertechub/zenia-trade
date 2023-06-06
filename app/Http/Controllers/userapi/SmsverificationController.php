<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use Config;
use App\User;

class SmsverificationController extends Controller {

    public function __construct() {

        $this->authKey = Config::get('constants.settings.authKey');
        $this->senderId = Config::get('constants.settings.senderId');
        $this->statuscode = Config::get('constants.statuscode');
        $this->emptyArray = (object) array();
    }

    public function sendsmsverification($mobileno, $otp, $insertid, $remember_token) {


        //Your authentication key
        $authKey = $this->authKey;

        //Multiple mobiles numbers separated by comma
        $mobileNumber = $mobileno;

        //Sender ID,While using route4 sender id should be 6 characters long.
        $senderId = $this->senderId;

        //Your message to send, Add URL encoding here.
        $OTP = $otp;
        $msg = 'Dear user,your verification code is ' . $OTP . '';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://control.msg91.com/api/sendotp.php?authkey=$authKey&mobile=$mobileNumber&message=$msg&sender=$senderId&otp=$OTP",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            // echo "cURL Error #:" . $err;
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
        } else {
            //echo $response;

            $updateData = array();
            $arrData = array();
            $updateData['mobile_otp'] = $OTP;
            $updateVeriSta = User::where('id', $insertid)->update($updateData);
            //$arrData['uid']   = $insertid;
            $arrData['remember_token'] = $remember_token;
            $arrData['mobileverification'] = 'FALSE';
            $arrData['mailverification'] = 'TRUE';
            $arrData['google2faauth'] = 'FALSE';
            $arrData['mailotp'] = 'FALSE';
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Verfication code sent successfully to your mobile no', $arrData);
        }
    }

}
?>











