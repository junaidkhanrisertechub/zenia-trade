<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\User;
use App\Models\Activitynotification;
use Config;

class MailverifyController extends Controller {

    public function __construct() {

        $this->statuscode = Config::get('constants.statuscode');
        $this->emptyArray = (object) array();
    }

    public function mailverify($email, $insertid) {

        //========================== send verification mail=================================
        $subjectacnt = "Account Verification";
        $pagenameact = "emails.accountverification";
        $CheckTokenExist = User::where([['id', '=', $insertid]])->pluck('verifytoken')->first();

        if (!empty($CheckTokenExist)) {
            $verify_token = $CheckTokenExist;
        } else {
            $verify_token = md5(uniqid(rand(), true));
        }

        $account = array('pagename' => $pagenameact, 'username' => $email, 'user_id' => $insertid, 'verify_token' => $verify_token);

        $account_mail = sendMail($account, $email, $subjectacnt);
        $actdata = array();
        $actdata['id'] = $insertid;
        $actdata['message'] = 'Verification link sent to your email id';
        $actdata['status'] = 1;
        $actDta = Activitynotification::create($actdata);
        if (empty($account_mail)) {
            if (empty($CheckTokenExist)) {
                $updateData = array();
                $updateData['verifytoken'] = $verify_token;
                $updateData['verifyaccountstatus'] = 0;
                $updateVeriSta = User::where('id', $insertid)->update($updateData);
            }
            // if(!empty($updateVeriSta)){

            $arrData = array();

            $arrData['mailverification'] = 'FALSE';
            $arrData['google2faauth'] = 'FALSE';
            $arrData['mailotp'] = 'FALSE';
            $arrData['mobileverification'] = 'FALSE';

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Verification link sent to your email id', $arrData);
            //}else{
            //	  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong,Please try again', $this->emptyArray);
            // }
        } else {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Something went wrong,Please try again', $this->emptyArray);
        }
    }

}
