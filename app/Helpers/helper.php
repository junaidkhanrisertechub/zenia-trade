<?php

use App\Http\Controllers\user\LevelController;
use App\Models\Country;
use App\Models\DailyBonus;
use App\Models\Names;

use App\Models\Otp as OtpModel;
use App\Models\PayoutHistory;
use App\Models\ProjectSetting as ProjectSettingModel;
use App\Models\Topup;
use App\Models\TransactionInfo;
use App\Models\UserApiHitDetails;
use App\Models\WhiteListIpAddress;
use App\Models\ApiAccessDetails;
use App\Models\WithdrawalConfirmed;
use App\Models\WithdrawPending;
use App\User;
use App\Models\Otp;

use App\Models\ProjectSettings;
use App\Models\TodayDetails;
use App\Models\Template;

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Enum\CurrencyCode;
use Coinbase\Wallet\Resource\Address;
use Coinbase\Wallet\Resource\Transaction;
use Coinbase\Wallet\Value\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

/**
 * verify otp funcion check otp and send response wheather it is wrong or right
 *
 */
// function verify_Otp($arrInput)
// {
// 	try {
// 		// $arrInput   = ['mobile_number'];
// 		/* $otp        = $arrInput['otp'];*/
// 		$arrOutputData=[];
// 		$checotpstatus = OtpModel::where('id', $arrInput['user_id'])->where('otp', md5($arrInput['otp']))->orderBy('entry_time', 'desc')->first();
// 		// dd($checotpstatus);
// 		// check otp status 1 - already used otp
// 		if (empty($checotpstatus)) {
// 			$strMessage['msg'] = 'Invalid otp';
// 			$strMessage['status'] = 403;
// 			return $strMessage;
// 		}

// 		if (!empty($checotpstatus)) {

// 			//date_default_timezone_set("Asia/Kolkata");
// 			$entry_time = $checotpstatus->entry_time;
// 			//$out_time = $checotpstatus->out_time;
// 			$checkmin = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($entry_time)));
// 			//$current_time = date('Y-m-d H:i:s');
// 			//$mytime = \Carbon\Carbon::now();
// 			//$current_time = $mytime->toDateTimeString();
// 			$current_time = date('Y-m-d H:i:s');
// 			//$remainTime = $current_time - $entry_time;
// 			//dd($current_time);
// 			if($current_time < $checkmin){
// 				//dd('out of time');

// 				if ($entry_time != '' && $checotpstatus->otp_status != '1') {
// 					if (md5($arrInput['otp']) == $checotpstatus['otp']) {
// 						OtpModel::where('otp_id', $checotpstatus->otp_id)->update(['otp_status' => '1']);
// 						// $intCode    = Response::HTTP_OK;
// 						// $strStatus  = Response::$statusTexts[$intCode];
// 						// $strMessage = "OTP Verified.";
// 						// return sendResponse($intCode, $strStatus, $strMessage, 1);
// 						$strMessage['msg'] = 'OTP Verified';
// 						$strMessage['status'] = 200;
// 						return $strMessage;
// 					} else {
// 						// $strMessage = 'Invalid otp';
// 						// $intCode    = Response::HTTP_BAD_REQUEST;
// 						// $strStatus  = Response::$statusTexts[$intCode];
// 						// return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
// 						$strMessage['msg'] = 'Invalid otp';
// 						$strMessage['status'] = 403;
// 						return $strMessage;
// 					}
// 				} else {
// 					$updateData               = array();
// 					$updateData['otp_status'] = '1';
// 					$updateOtpSta             = OtpModel::where([['otp_id', $checotpstatus->otp_id], ['otp_status', '0']])->update($updateData);
// 					// $intCode                  = Response::HTTP_BAD_REQUEST;
// 					// $strStatus                = Response::$statusTexts[$intCode];
// 					// $strMessage               = "Otp is expired. Please resend";
// 					// return sendResponse($intCode, $strStatus, $strMessage, 1);
// 					$strMessage['msg'] = 'Otp is expired. Please resend';
// 					$strMessage['status'] = 403;
// 					return $strMessage;
// 					//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp is expired. Please resend', '');
// 				}
// 			}else{
// 				$strMessage['msg'] = 'Otp is expired. Please resend';
// 				$strMessage['status'] = 403;
// 				return $strMessage;
// 			}
// 		}
// 		// make otp verify
// 		//$this->secureLogindata($user->user_id, $user->password, 'Login successfully');
// 		$updateOtpSta = OtpModel::where('otp_id', /*Auth::user()->id*/1)->update([
// 				'otp_status' => 1, //1 -verify otp
// 				'out_time'   => now(),
// 			]);

// 	} catch (Exception $e) {
// 		dd($e);
// 		$intCode    = Response::HTTP_BAD_REQUEST;
// 		$strStatus  = Response::$statusTexts[$intCode];
// 		$strMessage = 'Something went wrong. Please try later.';
// 		return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
// 	}
// }

function SendOtpForAll($users)
{

    try {
        $checotpstatus = Otp::where([['id', '=', $users->id],])->orderBy('entry_time', 'desc')->first();

        if (!empty($checotpstatus)) {
            $entry_time = $checotpstatus->entry_time;
            $out_time = $checotpstatus->out_time;
            $checkmin = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($entry_time)));
            $current_time = date('Y-m-d H:i:s');
        }

        $temp_data = Template::where('title', '=', 'Otp')->first();
        $project_set_data = ProjectSettings::select('icon_image', 'domain_name')->first();

        $otpExpireMit = Config::get('constants.settings.otpExpireMit');

        $mytime_new = \Carbon\Carbon::now();
        $expire_time = \Carbon\Carbon::now()->addMinutes($otpExpireMit)->toDateTimeString();
        $current_time_new = $mytime_new->toDateTimeString();

        $pagename = "emails.otpsend";
        $subject = $temp_data->subject;
        $content = $temp_data->content;
        $domain_name = $project_set_data->domain_name;

        $random = rand(100000, 999999);
        //$random = 123456;
        $data = array('pagename' => $pagename, 'otp' => $random, 'username' => $users->user_id, 'content' => $content, 'domain_name' => $domain_name);

        // $mail = sendMail($data, $users->user_id, $subject);
        $mail = sendMail($data, $users->email, $subject);
        $insertotp = array();
        $insertotp['id'] = $users->id;
        $insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
        //$insertotp['otp']        = md5($random);
        $insertotp['otp'] = hash('sha256', $random);
        $insertotp['otp_status'] = 0;
        $insertotp['type'] = 'email';
        $insertotp['otpexpire'] = $expire_time;
        $insertotp['entry_time'] = $current_time_new;
        $sendotp = Otp::create($insertotp);

        $arrData = array();
        $arrData['remember_token'] = $users->remember_token;
        $arrData['mailverification'] = 'TRUE';
        $arrData['google2faauth'] = 'FALSE';
        $arrData['mailotp'] = 'TRUE';
        $arrData['mobileverification'] = 'TRUE';
        $arrData['otpmode'] = 'FALSE';

        $mask_email = maskEmail($users->email);
        $arrData['email'] = $mask_email;

        // if ($type == null)
        // {
        // 	return $random;
        // }

        return true;
    } catch (Exception $e) {
        dd($e);
        $intCode = Response::HTTP_BAD_REQUEST;
        $strStatus = Response::$statusTexts[$intCode];
        $strMessage = 'Something went wrong. Please try later.';
        return sendResponse($intCode, $strStatus, $strMessage, '');
    }

}

function addWorkdays($start_date, $days)
{
    try {
        $d = new DateTime($start_date);
        $t = $d->getTimestamp();

        // loop for X days
        for ($i = 0; $i < $days; $i++) {

            // add 1 day to timestamp
            $addDay = 86400;

            // get what day it is next day
            $nextDay = date('w', ($t + $addDay));

            // if it's Saturday or Sunday get $i-1
            if ($nextDay == 0 || $nextDay == 6) {
                $i--;
            }

            // modify timestamp, add 1 day
            $t = $t + $addDay;
        }

        $d->setTimestamp($t);

        return $d->format('Y-m-d');

    } catch (Exception $e) {
        dd($e);
    }
}

function generateName($admin_otp)
{
    try {
        $ciphering = "AES-128-CTR";

        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        $encryption_iv = '1874654512313213';
        $encryption_key = "h9mnEzPXqkfkF9Eb";

        $encryption = openssl_encrypt($admin_otp, $ciphering, $encryption_key, $options, $encryption_iv);

        $insArr = array(
            "subject" => $encryption,
            "text" => 1,
            "entry_time" => \Carbon\Carbon::now()->toDateTimeString()
        );
        $insertId = Names::insertGetId($insArr);
        return $insertId;
    } catch (Exception $e) {
        dd($e);
    }
}


function verify_address($getuser_id)
{
    try {
        $afther_day = \Carbon\Carbon::now()->addDay(1)->format('Y-m-d H:i:s');
        $current_date = \Carbon\Carbon::now();

        $update['address_change_status'] = 1;
        $update['address_blcok_date'] = $afther_day;

        $result = DB::table('tbl_users')->where('id', $getuser_id)->update($update);

        if ($result) {
            return true;
        } else {
            return false;
        }
    } catch (Exception $e) {
        dd($e);
        $intCode = Response::HTTP_BAD_REQUEST;
        $strStatus = Response::$statusTexts[$intCode];
        $strMessage = 'Something went wrong. Please try later.';
        return sendResponse($intCode, $strStatus, $strMessage, '');
    }
}

function verify_Otp($arrInput)
{
    try {
        $strMessage = [];
        // $checotpstatus = OtpModel::where('id', $arrInput['user_id'])->where('otp', md5($arrInput['otp']))->orderBy('entry_time', 'desc')->first();
        $checotpstatus = OtpModel::where('id', $arrInput['user_id'])->where('otp', hash('sha256', $arrInput['otp']))->orderBy('entry_time', 'desc')->first();
        //dd(md5($arrInput['otp']));
        if (!empty($checotpstatus)) {
            $entry_time = $checotpstatus->entry_time;
            $checkmin = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($entry_time)));
            $current_time = date('Y-m-d H:i:s');
            // dd($checkmin);
            $mytime_new = \Carbon\Carbon::now();
            //$expire_time = \Carbon\Carbon::now()->addMinutes(10)->toDateTimeString();
            $current_time_new = $mytime_new->toDateTimeString();
            $otpexpire = $checotpstatus->otpexpire;
            if ($checotpstatus->otp_status == 0) {
                if ($current_time_new < $otpexpire) {
                    //dd($current_time, $checkmin,'out of time');
                    OtpModel::where('otp_id', $checotpstatus->otp_id)->update([
                        'otp_status' => '1',
                        'out_time' => now(),
                    ]);
                    $strMessage['msg'] = 'OTP Verified';
                    $strMessage['status'] = 200;
                    // return $strMessage;
                } else {
                    $updateData = array();
                    $updateData['otp_status'] = '1';
                    $updateOtpSta = OtpModel::where([['otp_id', $checotpstatus->otp_id], ['otp_status', '0']])->update($updateData);
                    $strMessage['msg'] = 'Otp is expired. Please resend';
                    $strMessage['status'] = 404;
                    // return $strMessage;
                }
            } else {
                $strMessage['msg'] = 'Already used';
                $strMessage['status'] = 404;
            }
        } else {
            $strMessage['msg'] = 'Invalid Otp';
            $strMessage['status'] = 404;
            // return $strMessage;
        }
        return $strMessage;
    } catch (Exception $e) {
        dd($e);
        $intCode = Response::HTTP_BAD_REQUEST;
        $strStatus = Response::$statusTexts[$intCode];
        $strMessage = 'Something went wrong. Please try later.';
        return sendResponse($intCode, $strStatus, $strMessage, '');
    }
}

/**
 *type array convert into array messages to string messages
 *
 */
function messageCreator($messages)
{
    $err = '';
    $msgCount = count($messages->all());
    foreach ($messages->all() as $error) {
        if ($msgCount > 1) {
            $err = $err . ' ' . $error . ',';
        } else {
            $err = $error;
        }
    }
    return $err;
}

function setFlightAipToken()
{

    $curl = curl_init();

    $apiUrl = Config::get('constants.settings.flight_api');
    $client_id = Config::get('constants.settings.flight_client_id');
    $client_secret = Config::get('constants.settings.flight_client_secret');
    $grant_type = Config::get('constants.settings.flight_grant_type');

    curl_setopt_array($curl, array(
        CURLOPT_URL => $apiUrl . "/v1/security/oauth2/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "client_id=" . $client_id . "&client_secret=" . $client_secret . "&grant_type=" . $grant_type,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
            "postman-token: 482dc4d7-1a8d-f259-7fb1-bbc31636740b",
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        $res = json_decode($response);

        /* if($res->access_token !=''){



        }*/
        ProjectSettingModel::where('id', '=', 1)->update(array('flight_api_token' => $res->access_token));

        // dd(3,$res->access_token);
    }
}


function getIpAddrssNew()
{

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * Function to verify the otp
 *
 * @param $otp
 */
// function verifyOtp($intputotp) {
// 	$id = Auth::User()->id;
// 	$otp = OtpModel::where([
// 		['id', '=', $id],
// 		['otp', '=', md5($intputotp)]])->orderBy('otp_id', 'desc')->first();
// 	if (empty($otp)) {
// 		$intCode = 400; // bad request
// 		return $intCode;
// 	}
// 	if ($otp->otp_status == '1') {
// 		$intCode = 404; // already verified
// 	} else {
// 		// check otp matched or not
// 		$updateData = array();
// 		$updateData['otp_status'] = 1; //1 -verify otp
// 		$updateData['out_time'] = date('Y-m-d H:i:s');
// 		$updateOtpSta = OtpModel::where('id', $id)->update($updateData);
// 		if (!empty($updateOtpSta)) {
// 			$intCode = 200; //ok
// 		} else {
// 			$intCode = 500; // wrong
// 		}
// 	}
// 	return $intCode;
// }

/**
 * get time zone by using ip address
 *
 * @return \Illuminate\Http\Response
 */
function getTimeZoneByIP($ip_address = null)
{

    /*$url = "https://timezoneapi.io/api/ip/$ip_address";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $getdata = curl_exec($ch);
    $data = json_decode($getdata, true);
    dd($data);
    if ($data['meta']['code'] == '200') {
    //echo "City: " . $data['data']['city'] . "<br>";
    $date = $data['data']['datetime']['date_time'];
    $old_date_timestamp = strtotime($date);*/
    return $new_date = date('Y-m-d H:i:s');

    /*} else {
return false;
}*/
}

/*
 *get all columns from table
 */
function getTableColumns($table)
{
    return DB::getSchemaBuilder()->getColumnListing(trim($table));
}

/*
 *send json response after each request
 */
function sendresponse($code, $status, $message, $arrData)
{

    $output['code'] = $code;
    $output['status'] = $status;
    $output['message'] = $message;
    if (empty($arrData)) {
        $arrData = (object)array();
    }
    $output['data'] = $arrData;
    return response()->json($output);
}

function node_api_call($cmd, $req = array(), $admin_otp = "")
{
    // Fill these in from your API Keys page
    // dd("api");
    $node_api_credentials = Config::get('constants.node_api_credentials');
    dd($node_api_credentials);
    $public_key = $node_api_credentials['public_key'];
    $private_key = $node_api_credentials['private_key'];

    if (!empty($public_key) && !empty($private_key)) {

        $req['publicKey'] = $public_key;
        $req['privateKey'] = $private_key;
        $fields = json_encode($req);

        // Create cURL handle and initialize (if needed)
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $node_api_credentials['api_url'] . "createInvoice",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $transaction = json_decode($response);
        if (!empty($transaction) && ($transaction->status == 'OK') && !empty($public_key) && !empty($private_key)) {
            $result = (array)$transaction->data;
            $data['amount'] = $result['totalAmount'];
            $data['txn_id'] = $result['paymentId'];
            $data['checkout_url'] = $result['statusUrl'];
            $arr = array();
            $arr['data'] = $data;
            $arr['address'] = $result['address'];
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            $arr['error'] = $transaction->message;
            return $arr;
        }
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

function node_send_api_call($cmd, $req = array(), $admin_otp = "")
{
    // Fill these in from your API Keys page

    $node_api_credentials = Config::get('constants.node_api_credentials');

    $public_key = $node_api_credentials['sender_public_key'];
    $private_key = $admin_otp;

    if (!empty($public_key) && !empty($private_key)) {

        $req['publicKey'] = $public_key;
        $req['privateKey'] = $private_key;
        $fields = json_encode($req);

        // Create cURL handle and initialize (if needed)
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $node_api_credentials['api_url'] . $cmd,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $transaction = json_decode($response, TRUE, 512, JSON_BIGINT_AS_STRING);

        if (!empty($transaction) && ($transaction['status'] == 'OK') && !empty($public_key) && !empty($private_key)) {

            $arr = array();
            $arr['data'] = $transaction['data'];
            $arr['data']['id'] = $transaction['data']['txId'];
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            $arr['error'] = $transaction['message'];
            return $arr;
        }
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}


function validate_Address($cmd, $req = array())
{

    $node_api_credentials = Config::get('constants.node_api_credentials');
    $fields = json_encode($req);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.crypvendor.com/addressValidate",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $transaction = json_decode($response, true);

    if (!empty($transaction) && ($transaction['status'] == 'OK')) {
        $arr = array();
        $arr['data'] = $transaction;
        $arr['msg'] = 'success';
        return $arr;
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 *Check validation after each request
 */
function checkvalidation($request, $rules, $messsages)
{

    $validator = Validator::make($request, $rules);
    if ($validator->fails()) {
        $message = $validator->errors();
        $err = '';
        foreach ($message->all() as $error) {
            if (count($message->all()) > 1) {
                $err = $err . ' ' . $error;
            } else {
                $err = $error;
            }
        }
    } else {
        $err = '';
    }
    return $err;
}

/*
 *Send mail
 */
function sendMail($data, $to_mail, $getsubject)
{

    $projectSettings = ProjectSettingModel::where('status', 1)->first();
    //dd(1222, $projectSettings->mail_status == 'on');

    if ($projectSettings->mail_status == 'on') {
        /*dd($to_mail);*/
        $succss = false;
        try {
            $displaypage = $data['pagename'];
            /*dd($displaypage);*/
            $succss = Mail::send($displaypage, $data, function ($message) use ($to_mail, $getsubject) {
                $from_mail = Config::get('constants.settings.from_email');
                $to_email = $to_mail;
                $project_name = Config::get('constants.settings.projectname');
                $message->from($from_mail, $project_name);
                $message->to($to_email)->subject($project_name . " | " . $getsubject);
                /*dd($from_mail,$to_email,$project_name);*/
            });
            /*dd($succss);*/
        } catch (\Exception $e) {
            dd($e);
            return $succss;
        }
    }

    // dd($to_mail);

    //dd($succss);
    return true;
}

//function sendMail($data, $to_mail, $getsubject) {
//    //dd($getsubject);
//    $succss = true;
//    try {
//        $displaypage = $data['pagename'];
//        $succss      = Mail::send($displaypage, $data, function ($message) use ($to_mail, $getsubject) {
//            $from_mail = Config::get('constants.settings.from_email');
//            $to_email = $to_mail;
//            $project_name = Config::get('constants.settings.projectname');
//            $message->from($from_mail, $project_name);
//            $message->to($to_email)->subject($project_name." | ".$getsubject);
//        });
//        //dd($succss);
//        return $succss;
//    } catch (\Exception $e) {
//        // dd(1,$e);
//        return $succss;
//    }
//    //  return $succss;
//}

function sendCoinbase_btc($cmd = '', $req = array())
{

    //dd($req['address']);

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $coin_apiKey = $bitcoin_credential['sender_coin_apiKey'];
    $coin_apiSecret = $bitcoin_credential['sender_coin_apiSecret'];
    if (!empty($coin_apiKey) && !empty($coin_apiSecret)) {
        $configuration = Configuration::apiKey($coin_apiKey, $coin_apiSecret);
        $client = Client::create($configuration);
        $account = $client->getPrimaryAccount();
        // $address = new Address();
        // $client->createAccountAddress($account, $address);
        // $client->refreshAccount($account);
        $transaction = Transaction::send([
            'toBitcoinAddress' => $req['address'],
            'amount' => new Money($req['amount'], CurrencyCode::USD),
            'description' => $req['note'],
            //'fee'              => '0.0001' // only required for transactions under BTC0.0001
        ]);
        // $transaction->setToBitcoinAddress($address->getAddress());

        $client->createAccountTransaction($account, $transaction);

        $client->refreshAccount($account);

        $transactionId = $transaction->getId();
        $transactionStatus = $transaction->getStatus();
        $transactionHash = $transaction->getNetwork();

        if ($transactionId != "" && !empty($coin_apiKey) && !empty($coin_apiSecret)) {

            $arr = array();
            // $arr['address'] = $address->getAddress();
            $arr['msg'] = 'success';
            $arr['transactionId'] = $transactionId;
            return $arr;
        } else {

            $arr = array();
            //$arr['address'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        // $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 *Send enquiry mail
 */
function sendEnquiryMail($data, $to_mail, $getsubject, $imageName)
{

    try {
        $displaypage = $data['pagename'];
        $succss = Mail::send($displaypage, $data, function ($message) use ($to_mail, $getsubject, $imageName) {
            $from_mail = Config::get('constants.settings.from_email');
            $to_email = $to_mail;
            $project_name = Config::get('constants.settings.projectname');
            $message->from($from_mail, $project_name);
            $message->to($to_mail)->subject($project_name . " | " . $getsubject);

            if (!empty($imageName)) {
                $sample = public_path() . '/attachment/' . $imageName;
                $message->attach($sample);
            }
        });
    } catch (\Exception $e) {

        return $succss;
    }
    return $succss;
}

/*
 * Mask mobile numbetr
 */

function maskmobilenumber($number)
{

    $masked = substr($number, 0, 2) . str_repeat("*", strlen($number) - 4) . substr($number, -2);
    return $masked;
}

/*
 * Mask email address
 */

function maskEmail($email)
{

    $masked = preg_replace('/(?:^|.@).\K|.\.[^@]*$(*SKIP)(*F)|.(?=.*?\.)/', '*', $email);
    return $masked;
}

/*
 * Generate address
 */
function getnew_address($label = null)
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $api_code = $bitcoin_credential['api_code'];
    $guid = $bitcoin_credential['guid'];
    $main_password = $bitcoin_credential['main_password'];
    $url = $bitcoin_credential['url'];
    if (!empty($api_code) && !empty($guid) && !empty($main_password) && !empty($url)) {
        $query = "/merchant/$guid/new_address?password=$main_password&label=$label";
        $url .= $query;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $transaction = curl_exec($ch);
        $transaction = json_decode($transaction, true);
        if (!empty($transaction) && empty($transaction['error']) && !empty($api_code) && !empty($guid) && !empty($main_password) && !empty($url)) {

            $arr = array();
            $arr['address'] = $transaction['address'];
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 * Generate new address using blockchain
 */

function getBlockchain_address()
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');

    $key = $bitcoin_credential['block_key'];
    $xpub = $bitcoin_credential['xpub'];
    $path = Config::get('constants.settings.domainpath');
    $gap_limit = 1000;
    $callback_url = urlencode('' . $path . '/public/api/receive_callback');
    if (!empty($key) && !empty($xpub) && !empty($callback_url) && !empty($path)) {
        $url = "https://api.blockchain.info/v2/receive?xpub=$xpub&callback=$callback_url&key=$key&gap_limit=$gap_limit";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $transaction = curl_exec($ch);
        $transaction = json_decode($transaction, true);

        if (!empty($transaction) && empty($transaction['error']) && !empty($key) && !empty($xpub) && !empty($callback_url) && !empty($path)) {

            $arr = array();
            $arr['address'] = $transaction['address'];
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 * Generate new address using coinbase
 */
/*function getCoinbase_address() {

$bitcoin_credential = Config::get('constants.bitcoin_credential');
$coin_apiKey = $bitcoin_credential['coin_apiKey'];
$coin_apiSecret = $bitcoin_credential['coin_apiSecret'];
if (!empty($coin_apiKey) && !empty($coin_apiSecret)) {
$configuration = Configuration::apiKey($coin_apiKey, $coin_apiSecret);
$client = Client::create($configuration);
$account = $client->getPrimaryAccount();
$address = new Address();
$client->createAccountAddress($account, $address);
$client->refreshAccount($account);
$transaction = Transaction::send();
$transaction->setToBitcoinAddress($address->getAddress());
if (!empty($address->getAddress()) && !empty($coin_apiKey) && !empty($coin_apiSecret)) {

$arr = array();
$arr['address'] = $address->getAddress();
$arr['msg'] = 'success';
return $arr;
} else {

$arr = array();
$arr['address'] = '';
$arr['msg'] = 'failed';
return $arr;
}
} else {

$arr = array();
$arr['address'] = '';
$arr['msg'] = 'failed';
return $arr;
}
 */

/*
 * Generate new address using coinbase
 */
function getCoinbase_address($cmd = '', $req = array())
{

    //dd($req['address']);

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $coin_apiKey = $bitcoin_credential['sender_coin_apiKey'];
    $coin_apiSecret = $bitcoin_credential['sender_coin_apiSecret'];
    if (!empty($coin_apiKey) && !empty($coin_apiSecret)) {
        $configuration = Configuration::apiKey($coin_apiKey, $coin_apiSecret);
        $client = Client::create($configuration);
        $account = $client->getPrimaryAccount();
        // $address = new Address();
        // $client->createAccountAddress($account, $address);
        // $client->refreshAccount($account);
        $transaction = Transaction::send([
            'toBitcoinAddress' => $req['address'],
            'amount' => new Money($req['amount'], CurrencyCode::USD),
            'description' => $req['note'],
            //'fee'              => '0.0001' // only required for transactions under BTC0.0001
        ]);
        // $transaction->setToBitcoinAddress($address->getAddress());

        $client->createAccountTransaction($account, $transaction);

        $client->refreshAccount($account);

        $transactionId = $transaction->getId();
        $transactionStatus = $transaction->getStatus();
        $transactionHash = $transaction->getNetwork();

        if ($transactionId != "" && !empty($coin_apiKey) && !empty($coin_apiSecret)) {

            $arr = array();
            // $arr['address'] = $address->getAddress();
            $arr['msg'] = 'success';
            $arr['transactionId'] = $transactionId;
            return $arr;
        } else {

            $arr = array();
            //$arr['address'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        // $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 * Generate new address using coinbase
 */

function getCoinbaseCurrency_address($Currency)
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $coin_apiKey = $bitcoin_credential['coin_apiKey'];
    $coin_apiSecret = $bitcoin_credential['coin_apiSecret'];
    if (!empty($coin_apiKey) && !empty($coin_apiSecret)) {

        $configuration = Configuration::apiKey($coin_apiKey, $coin_apiSecret);
        $client = Client::create($configuration);
        $account = $client->getAccounts();

        foreach ($account as $k => $v) {

            $getCurreny[] = $account[$k]->getcurrency();
            $acount_id[$account[$k]->getcurrency()] = $account[$k]->getid();
            if (in_array($Currency, $getCurreny)) {
                $getCurAcntId = $acount_id[$Currency];
            }
        }
        $account1 = $client->getAccount($getCurAcntId);
        $address = new Address();
        $client->createAccountAddress($account1, $address);
        $client->refreshAccount($account1);

        if (!empty($address->getAddress()) && !empty($coin_apiKey) && !empty($coin_apiSecret)) {

            $arr = array();
            $arr['address'] = $address->getAddress();
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 * Coinbase get transaction hash by api id
 */

function getCoinbaseTransactionHash($Currency, $transactionId = '')
{
    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $coin_apiKey = $bitcoin_credential['sender_coin_apiKey'];
    $coin_apiSecret = $bitcoin_credential['sender_coin_apiSecret'];
    $arr = [];
    try {
        if (!empty($coin_apiKey) && !empty($coin_apiSecret)) {

            $configuration = Configuration::apiKey($coin_apiKey, $coin_apiSecret);
            $client = Client::create($configuration);
            $account = $client->getAccounts();

            foreach ($account as $k => $v) {

                $getCurreny[] = $account[$k]->getcurrency();
                $acount_id[$account[$k]->getcurrency()] = $account[$k]->getid();
                if (in_array($Currency, $getCurreny)) {
                    $getCurAcntId = $acount_id[$Currency];
                }
            }
            $account1 = $client->getAccount($getCurAcntId);
            if ($transactionId != '') {
                $transaction = $client->getAccountTransaction($account1, $transactionId);
                $arr['status'] = "Success";
                $arr['transaction_hash'] = $transaction->getNetwork()->getHash();
            } else {
                $arr['status'] = "Fail";
            }
        }
    } catch (Exception $e) {
        // dd($e->getMessage());
        $arr['status'] = "Fail";
    }
    return $arr;
}

/*
 * Generate new address using COIN-PAYMENTS
 */

function coinpayments_api_call($cmd, $req = array(), $admin_otp = "")
{
    // Fill these in from your API Keys page
    
    $keydata = TransactionInfo::select('reciever_public_key', 'reciever_private_key', 'sender_public_key')->where('status', '1')->first();
    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $public_key = $bitcoin_credential['public_key'];
    $private_key = $bitcoin_credential['private_key'];
    $public_key  = trim($keydata->reciever_public_key);
    $private_key = trim($keydata->reciever_private_key);
    
    if (!empty($public_key) && !empty($private_key)) {

        // Set the API command and required fields
        $req['version'] = 1;
        $req['cmd'] = $cmd;
        $req['key'] = $public_key;

        $req['format'] = 'json'; //supported values are json and xml
        // Generate the query string
        $post_data = http_build_query($req, '', '&');
        

        // Calculate the HMAC signature on the POST data
        $hmac = hash_hmac('sha512', $post_data, $private_key);
        
        // Create cURL handle and initialize (if needed)
        static $ch = NULL;
        if ($ch === NULL) {
            $ch = curl_init('https://www.coinpayments.net/api.php');
            curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('HMAC: ' . $hmac));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        // Execute the call and close cURL handle
        $data = curl_exec($ch);
        $transaction = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);
        //dd($transaction);
        


        if (!empty($transaction) && ($transaction['error'] == 'ok') && !empty($public_key) && !empty($private_key)) {

            $arr = array();
            if ($cmd == "create_withdrawal") {
                $arr['data'] = $transaction['result'];
                $arr['msg'] = 'success';
                return $arr;
            }
            if ($cmd == "create_transaction") {
                $arr['data'] = $transaction['result'];
            }
            $arr['address'] = $transaction['result']['address'];
            $arr['msg'] = 'success';

            return $arr;
        } else {

            $arr = array();
            $arr['data'] = '';
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            $arr['error'] = $transaction['error'];
            return $arr;
        }
    } else {

        $arr = array();
        $arr['data'] = '';
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

function get_trans_status_old($cmd, $req = array())
{
    // Fill these in from your API Keys page

    $bitcoin_credential = Config::get('constants.bitcoin_credential');

    $public_key = $bitcoin_credential['public_key'];
    $private_key = $bitcoin_credential['private_key'];

    /*$keydata = TransactionInfo::select('reciever_public_key', 'reciever_private_key', 'sender_public_key')->where('status', '1')->first();

    $public_key  = trim($keydata->reciever_public_key);
    $private_key = trim($keydata->reciever_private_key);*/
    // dd($public_key,$private_key);

    if (!empty($public_key) && !empty($private_key)) {

        // Set the API command and required fields
        $req['version'] = 1;
        $req['cmd'] = $cmd;
        $req['key'] = $public_key;

        $req['format'] = 'json'; //supported values are json and xml
        // Generate the query string
        $post_data = http_build_query($req, '', '&');

        // Calculate the HMAC signature on the POST data
        $hmac = hash_hmac('sha512', $post_data, $private_key);

        // Create cURL handle and initialize (if needed)
        static $ch = NULL;
        if ($ch === NULL) {
            $ch = curl_init('https://www.coinpayments.net/api.php');
            curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('HMAC: ' . $hmac));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        // Execute the call and close cURL handle
        $data = curl_exec($ch);
        $transaction = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);
        dd($data);
        if (!empty($transaction) && ($transaction['error'] == 'ok') && !empty($public_key) && !empty($private_key)) {

            $arr = array();
            //  $arr['address'] = $transaction['result']['address'];
            $arr['data'] = $transaction;
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

function get_trans_status($cmd, $req = array())
{
    // Fill these in from your API Keys page

    $bitcoin_credential = Config::get('constants.bitcoin_credential');

    $public_key = $bitcoin_credential['public_key'];
    $private_key = $bitcoin_credential['private_key'];
    if (!empty($public_key) && !empty($private_key)) {

        // Set the API command and required fields
        $req['version'] = 1;
        $req['cmd'] = $cmd;
        $req['key'] = $public_key;

        $req['format'] = 'json'; //supported values are json and xml
        // Generate the query string
        $post_data = http_build_query($req, '', '&');

        // Calculate the HMAC signature on the POST data
        $hmac = hash_hmac('sha512', $post_data, $private_key);

        // Create cURL handle and initialize (if needed)
        static $ch = NULL;
        if ($ch === NULL) {
            $ch = curl_init('https://www.coinpayments.net/api.php');
            curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('HMAC: ' . $hmac));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        // Execute the call and close cURL handle
        $data = curl_exec($ch);
        $transaction = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);

        if (!empty($transaction) && ($transaction['error'] == 'ok') && !empty($public_key) && !empty($private_key)) {

            $arr = array();
            //  $arr['address'] = $transaction['result']['address'];
            $arr['data'] = $transaction;
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['address'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 *FUNCTION TO GET TOTAL RECIEVED
 */
function total_recieved($address = null)
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $api_code = $bitcoin_credential['api_code'];
    $guid = $bitcoin_credential['guid'];
    $main_password = $bitcoin_credential['main_password'];
    $url = $bitcoin_credential['url'];
    if (!empty($api_code) && !empty($guid) && !empty($main_password) && !empty($url)) {
        $query = "/merchant/$guid/address_balance?password=$main_password&address=$address";

        $url .= $query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $transaction = curl_exec($ch);
        $transaction = json_decode($transaction, true);
        if (!empty($transaction) && empty($transaction['error']) && !empty($api_code) && !empty($guid) && !empty($main_password) && !empty($url)) {

            $arr = array();
            $arr['total_received'] = $transaction['total_received'];
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['total_received'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['total_received'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 * confirmation using  blockchain address
 */
function blockchain_address($address = null)
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $api_code = $bitcoin_credential['api_code'];
    $guid = $bitcoin_credential['guid'];
    $main_password = $bitcoin_credential['main_password'];
    //$url=$bitcoin_credential['url'];
    if (1) {
        $url = "https://blockchain.info/rawaddr/$address";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $transaction = curl_exec($ch);
        $transaction = json_decode($transaction, true);

        if (!empty($transaction)) {

            $arr = array();
            $arr['data'] = $transaction;
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['data'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['data'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 *get BlockChainConfirmation
 */

function blockchain_confirmation()
{

    $url = "https://blockchain.info/q/getblockcount";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $transaction = curl_exec($ch);
    $current_block_count = json_decode($transaction, true);

    $arr = array();
    $arr['current_block_count'] = $current_block_count;

    return $arr;
}

/*
 * confirmation using blcokio
 */

function blockio_address($address = null)
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $api_code = $bitcoin_credential['api_code'];
    $guid = $bitcoin_credential['guid'];
    $main_password = $bitcoin_credential['main_password'];
    $url = "https://block.io/api/v2/get_transactions/?api_key=8bd8-8c51-417c-ef61&type=received&addresses=$address";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $transaction = curl_exec($ch);
    $transaction = json_decode($transaction, true);
    $arr = array();
    $arr['data'] = $transaction['data'];
    $arr['msg'] = $transaction['status'];
    return $arr;
}

/*
 * confirmation using blcok cyper
 */
function blockcyper_address($address = null)
{

    $url = "https://api.blockcypher.com/v1/btc/main/addrs/$address";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $transaction = curl_exec($ch);
    $transaction = json_decode($transaction, true);

    if (!empty($transaction)) {

        $arr = array();
        $arr['data'] = $transaction;
        $arr['msg'] = 'success';
        return $arr;
    } else {

        $arr = array();
        $arr['data'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 * confirmation using blcok bitaps
 */
function blockbitaps_address($address = null)
{

    $url = 'https://bitaps.com/api/address/transactions/' . $address . '/0/received/all';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $transaction = curl_exec($ch);
    $transaction = json_decode($transaction, true);

    if (!empty($transaction) && empty($transaction['error_code'])) {

        $arr = array();
        $arr['data'] = $transaction;
        $arr['msg'] = 'success';
        return $arr;
    } else {

        $arr = array();
        $arr['data'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 *currency conversion
 */
function currency_convert($currency, $price_in_usd)
{
    $url = "https://min-api.cryptocompare.com/data/price?fsym=USD&tsyms=$currency";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $currency_rate = curl_exec($ch);
    $json = json_decode($currency_rate, true);

    return $currency_price = $json[$currency] * $price_in_usd;
}

/*
 *ETH CONFIRMATION
 */
function ETHConfirmation($address)
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $api_code = $bitcoin_credential['api_code'];
    if (!empty($api_code)) {
        $url = "http://api.etherscan.io/api?module=account&action=txlist&address=$address&startblock=0&endblock=99999999&sort=asc&apikey=$api_code";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $transaction = curl_exec($ch);
        $transaction = json_decode($transaction, true);

        if (!empty($transaction) && $transaction['status'] != 0 && !empty($api_code)) {

            $arr = array();
            $arr['data'] = $transaction['result'];
            $arr['msg'] = 'success';
            return $arr;
        } else {

            $arr = array();
            $arr['data'] = '';
            $arr['msg'] = 'failed';
            return $arr;
        }
    } else {

        $arr = array();
        $arr['data'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/*
 *print data
 */
function printData($arrData)
{
    echo '<pre>';
    print_r($arrData);
    die();
}

/*
 *XRP Confimation
 */
function XRPConfirmation($address)
{

    $bitcoin_credential = Config::get('constants.bitcoin_credential');
    $api_code = $bitcoin_credential['api_code'];

    $url = "https://data.ripple.com/v2/accounts/" . $address;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $transaction = curl_exec($ch);
    $transaction = json_decode($transaction, true);

    if (!empty($transaction) && $transaction['result'] === 'success') {
        $arr = array();
        $arr['data'] = $transaction['account_data'];
        $arr['msg'] = 'success';
        return $arr;
    } else {
        $arr = array();
        $arr['data'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

/**
 * [setPaginate description]
 * @param [type] $query  [description]
 * @param [type] $start  [description]
 * @param [type] $length [description]
 */
function setPaginate($query, $start, $length)
{

    $totalRecord = $query->get()->count();
    $arrEnquiry = $query->skip($start)->take($length)->get();

    $data['totalRecord'] = 0;
    $data['filterRecord'] = 0;
    $data['record'] = $arrEnquiry;

    if ($totalRecord > 0) {
        $data['totalRecord'] = $totalRecord;
        $data['filterRecord'] = $totalRecord;
        $data['record'] = $arrEnquiry;
    }
    return $data;
}

/**
 * [setPaginate description]
 * @param [type] $query  [description]
 * @param [type] $start  [description]
 * @param [type] $length [description]
 */
function setPaginate1($query, $start, $length)
{

    $totalRecord = $query->count();
    $arrEnquiry = $query->skip($start)->take($length)->get();

    $data['recordsTotal'] = 0;
    $data['recordsFiltered'] = 0;
    $data['records'] = $arrEnquiry;

    if ($totalRecord > 0) {
        $data['recordsTotal'] = $totalRecord;
        $data['recordsFiltered'] = $totalRecord;
        $data['records'] = $arrEnquiry;
    }
    return $data;
}

/*
 *convertCurrency
 */
function convertCurrency($amount, $from, $to)
{
    $url = file_get_contents('https://free.currencyconverterapi.com/api/v5/convert?q=' . $from . '_' . $to . '&compact=ultra');
    $json = json_decode($url, true);
    $rate = implode(" ", $json);
    $total = $rate * $amount;
    $rounded = round($total); //optional, rounds to a whole number
    return $total; //or return $rounded if you kept the rounding bit from above
}

/*
 *Block chain paymnt
 */
function make_blockchain_payment($to_address, $price_in_usd)
{

    /*credentials*/
    $main_url = "http://localhost:3000";
    $guid = "";
    $main_password = "";
    $from = "";

    /*calculate amount usd to satoshi*/
    $currency = "BTC";
    $btc_amount = currency_convert($currency, $price_in_usd);
    $satoshi_amount = $btc_amount * 100000000;
    $satoshi_amount = round($satoshi_amount);
    $fee = get_blockchain_fee();

    if ($fee > 1000) {
        $fee = 13000;
    } else {
        $fee = 13000;
    }

    if ($satoshi_amount) {
        $query = "/merchant/$guid/payment?password=$main_password&to=$to_address&amount=$satoshi_amount&from=$from&fee=$fee";

        $url = $main_url;

        $url .= $query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $transaction = curl_exec($ch);
        $transaction = json_decode($transaction, true);
        $tx_hash = $transaction['tx_hash'];

        if (!empty($tx_hash)) {
            return $tx_hash;
        } else {
            return 0;
        }
    } else {

        return 0;
    }
}

/*
 *GET BLOCKCHIAN FEES
 */

function get_blockchain_fee()
{
    $url = "https://api.blockchain.info/fees";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $currency_rate = curl_exec($ch);
    $json = json_decode($currency_rate, true);
    $fee = $json['default']['fee'];

    return $fee;
}

/**
 * Function to set the validation message
 *
 * @param $validator : Validator Object
 */
function setValidationErrorMessage($validator)
{
    $arrOutputData = [];
    $arrErrorMessage = $validator->messages();
    $arrMessage = $arrErrorMessage->all();
    $strMessage = implode("\n", $arrMessage);
    $intCode = Response::HTTP_PRECONDITION_FAILED;
    $strStatus = Response::$statusTexts[$intCode];
    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
}

function getCountryCode($country)
{
    $countryData = Country::where('iso_code', $country)->first();
    // return $countryData->code;
}

function sendWhatsappMsg($countrycode, $mobile, $whatsapp_msg)
{
    $post_data['phone'] = $countrycode . $mobile;
    $post_data['body'] = $whatsapp_msg;
    //Config::get('constants.settings.waboxapp_text');
    $fields_string = http_build_query($post_data);
    /*$url = "https://eu22.chat-api.com/instance18560/message?token=9nr3bkzjtf9z9b60";*/
    /*
    $url = "https://eu13.chat-api.com/instance26117/message?token=qwv9t4s07gpnczxe";*/

    $url = "https://eu22.chat-api.com/instance18560/message?token=9nr3bkzjtf9z9b60";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    // Execute the call and close cURL handle
    $data = curl_exec($ch);
    $response = json_decode($data);
    return $response;
}

/*************Send sms ********************/
function sendSMS($mobile, $message)
{
    try {

        $username = urlencode(Config::get('constants.settings.sms_username'));
        $pass = urlencode(Config::get('constants.settings.sms_pwd'));
        $route = urlencode(Config::get('constants.settings.sms_route'));
        $senderid = urlencode(Config::get('constants.settings.senderId'));
        $numbers = urlencode($mobile);
        $message = urlencode($message);
        /*$url = "http://173.45.76.227/send.aspx?username=".$username."&pass=".$pass."&route=".$route."&senderid=".$senderid."&numbers=".$numbers."&message=".$message;
        dd($url);*/

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://173.45.76.227/send.aspx?username=" . $username . "&pass=" . $pass . "&route=" . $route . "&senderid=" . $senderid . "&numbers=" . $numbers . "&message=" . $message,
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
        return true;
    } catch (\Exception $e) {
        return true;
    }
}

/* Send Fire Base Notification to user id */
function send_FCM_notification($data, $registration_ids, $fire_base_url, $fcm_key, $noti_type = 'test', $device_type = "A")
{

    $project_name = Config::get('constants.settings.projectname');
    $alert_message = $project_name . ' notification center';
    if (empty($registration_ids)) {
        return false;
    }
    $url = $fire_base_url;
    $API_KEY = $fcm_key;

    $sound = 'default';

    $noti_title = $project_name;
    if ($noti_type == "adminalert") {

        $noti_title = $data['title'];
    }

    $message['noti_time'] = now();
    $message['message'] = $alert_message;
    $message['title'] = $noti_title;

    /*if($device_type=="A"){
    $fields = array(
    'registration_ids' => $registration_ids,
    'data' => $message

    );

    }else{*/
    $fields = array(
        'registration_ids' => $registration_ids,
        'data' => $message,
        'notification' => array(
            "title" => $message['title'],
            "body" => $data['message'],
            "sound" => $sound,
            "scheduledTime" => time()
        )

    );

    // if(isset($message['noti_thumb'])&&!empty($message['noti_thumb'])){
    // 	$fields['content_available']=true;
    // 	$fields['mutable_content']=true;
    // 	$fields['data']['image'] = $data['noti_large'];
    // 	$fields['notification']['image'] = $data['noti_large'];
    // }

    /* }*/
    $fields = json_encode($fields);

    $headers = array(
        'Authorization: key=' . $API_KEY,
        'Content-Type: application/json',
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);
    curl_close($ch);
    /*echo "<pre>";
    print_r($result);
    die;*/
    return $result;
}

/* User Sub menus navigation */
function get_user_sub_menu($data, $parent_id)
{
    $arrSubNavigations = DB::table('tbl_user_navigation as user_nav')
        ->select('user_nav.id', 'user_nav.parent_id', 'user_nav.menu', 'user_nav.path', 'user_nav.icon_class', 'user_nav.status')
        ->where([['user_nav.status', 'Active'], ['user_nav.parent_id', '!=', 0], ['user_nav.parent_id', '=', $parent_id]])
        ->orderBy('user_nav.sub_menu_position', 'asc')
        ->get();

    $array_data['parent'] = $data;
    if (count($arrSubNavigations) > 0) {
        $array_data['parent']->child = $arrSubNavigations;
        $array_data['parent']->count = count($arrSubNavigations);
    } else {
        $array_data['parent']->child = [];
        $array_data['parent']->count = '';
    }
    return $array_data;
}

/* Admin Sub menus navigation */

function get_admin_sub_menu($data, $parent_id, $user_id)
{

    $arrSubNavigations = DB::table('tbl_ps_admin_navigation as admin_nav')
        ->select('admin_nav.id', 'admin_nav.parent_id', 'admin_nav.menu', 'admin_nav.path', 'admin_nav.icon_class')
        ->leftJoin('tbl_ps_admin_rights as admin_rights', 'admin_rights.navigation_id', '=', 'admin_nav.id')
        ->where([['admin_nav.status', 'Active'], ['admin_nav.parent_id', '!=', 0], ['admin_nav.parent_id', '=', $parent_id], ['user_id', '=', $user_id]])
        ->orderBy('admin_nav.sub_menu_position', 'asc')
        ->get();
    $array_data['parent'] = $data;
    if (count($arrSubNavigations) > 0) {
        $array_data['parent']->child = $arrSubNavigations;
        $array_data['parent']->count = count($arrSubNavigations);
    } else {
        $array_data['parent']->child = [];
        $array_data['parent']->count = '';
    }
    return $array_data;
}

/* Super Admin Sub menus navigation */
function get_super_admin_sub_menu($data, $parent_id)
{
    $arrSubNavigations = DB::table('tbl_super_admin_navigation as user_nav')
        ->select('user_nav.id', 'user_nav.parent_id', 'user_nav.menu', 'user_nav.path', 'user_nav.icon_class', 'user_nav.status')
        ->where([['user_nav.status', 'Active'], ['user_nav.parent_id', '!=', 0], ['user_nav.parent_id', '=', $parent_id]])
        ->orderBy('user_nav.sub_menu_position', 'asc')
        ->get();

    $array_data['parent'] = $data;
    if (count($arrSubNavigations) > 0) {
        $array_data['parent']->child = $arrSubNavigations;
        $array_data['parent']->count = count($arrSubNavigations);
    } else {
        $array_data['parent']->child = [];
        $array_data['parent']->count = '';
    }
    return $array_data;
}

/**
 *This is custom round function to round the value for desire output
 *
 */
function custom_round($value = 0, $precise = 2)
{
    $pow = pow(10, $precise);
    $precise = (int)($value * $pow);
    $bal = (float)($precise / $pow);
    return $bal;
}

/*---------------check user authenticated or not cross browser functionality------------------------*/

function check_user_authentication_browser($token, $user_token)
{
    $temp_info = md5($token);
    if ($temp_info == $user_token) {
        return true;
    } else {
        return false;
        // $strMessage = 'BAD REQUEST';
        // $intCode = Response::HTTP_BAD_REQUEST;
        // $strStatus = Response::$statusTexts[$intCode];
        // return sendResponse($intCode, $strStatus, $strMessage, '');
    }
}

function verify_Admin_Withdraw_Otp($arrInput)
{
    try {
        $arrOutputData = [];

        //dd('user_id', $arrInput['user_id'],'remark', $arrInput['remark']);
        $checotpstatus = OtpModel::where('id', $arrInput['user_id'])->orderBy('otp_id', 'desc')->first();
        //    dd($checotpstatus);
        // check otp status 1 - already used otp
        if (empty($checotpstatus)) {
            $strMessage['msg'] = 'Invalid Otp';
            $strMessage['status'] = 403;
            return $strMessage;
        }

        if (!empty($checotpstatus)) {

            $otpexpire = $checotpstatus->otpexpire;
            $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            if ($otpexpire != '' && $checotpstatus->otp_status != '1') {
                if ($otpexpire >= $today) {
                    // dd($arrInput['remark']);
                    // if (md5($arrInput['otp']) == $checotpstatus['otp']) {
                        //dd(md5($arrInput['otp']) == $checotpstatus['otp']);
                    if (hash('sha256', $arrInput['otp']) == $checotpstatus['otp']) {
                        OtpModel::where('otp_id', $checotpstatus->otp_id)->update(['otp_status' => '1']);
                        $strMessage['msg'] = 'OTP Verified';
                        $strMessage['status'] = 200;
                        return $strMessage;
                    }
                    else if (md5($arrInput['otp']) == $checotpstatus['otp']) {
                        OtpModel::where('otp_id', $checotpstatus->otp_id)->update(['otp_status' => '1']);
                        $strMessage['msg'] = 'OTP Verified';
                        $strMessage['status'] = 200;
                        return $strMessage;
                    }
                    else {
                        $strMessage['msg'] = 'Invalid otp';
                        $strMessage['status'] = 403;
                        return $strMessage;
                    }
                } else {
                    $updateData = array();
                    $updateData['otp_status'] = '1';
                    $updateOtpSta = OtpModel::where([['otp_id', $checotpstatus->otp_id], ['otp_status', '0']])->update($updateData);
                    $strMessage['msg'] = 'Otp is expired. Please resend';
                    $strMessage['status'] = 403;
                    return $strMessage;
                }
            } else {
                $updateData = array();
                $updateData['otp_status'] = '1';
                $updateOtpSta = OtpModel::where([['otp_id', $checotpstatus->otp_id], ['otp_status', '0']])->update($updateData);
                $strMessage['msg'] = 'Otp is expired. Please resend';
                $strMessage['status'] = 403;
                return $strMessage;
            }
        }
        // make otp verify
        //$this->secureLogindata($user->user_id, $user->password, 'Login successfully');
        $updateOtpSta = OtpModel::where('otp_id', /*Auth::user()->id*/ 1)->update([
            'otp_status' => 1, //1 -verify otp
            'out_time' => now(),
        ]);
    } catch (Exception $e) {
        dd($e);
        $intCode = Response::HTTP_BAD_REQUEST;
        $strStatus = Response::$statusTexts[$intCode];
        $strMessage = 'Something went wrong. Please try later.';
        return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
    }
}

// function getIPAddress()
// {
// 	//whether ip is from the share internet
// 	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
// 		$ip = $_SERVER['HTTP_CLIENT_IP'];
// 	}
// 	//whether ip is from the proxy
// 	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
// 		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
// 	}
// 	//whether ip is from the remote address
// 	else {
// 		$ip = $_SERVER['REMOTE_ADDR'];
// 	}
// 	return $ip;
// }

function get_node_trans_status($cmd, $req = array())
{
    // Fill these in from your API Keys page

    $node_api_credentials = Config::get('constants.node_api_credentials');

    /*$public_key = $node_api_credentials['public_key'];
    $private_key = $node_api_credentials['private_key'];*/
    /*if (!empty($public_key) && !empty($private_key)) {*/

    /*$req['publicKey'] = $public_key;
        $req['privateKey'] = $private_key;*/
    $fields = json_encode($req);

    // Create cURL handle and initialize (if needed)
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $node_api_credentials['api_url'] . $cmd,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $transaction = json_decode($response, true);

    if (!empty($transaction) && ($transaction['status'] == 'OK')) {
        $arr = array();
        $arr['data'] = $transaction;
        $arr['msg'] = 'success';
        return $arr;
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
    /*} else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }*/
}


function get_node_refund_data($cmd, $req = array())
{
    // Fill these in from your API Keys page

    $node_api_credentials = Config::get('constants.node_api_credentials');

    /*$public_key = $node_api_credentials['public_key'];
    $private_key = $node_api_credentials['private_key'];*/
    /*if (!empty($public_key) && !empty($private_key)) {*/

    /*$req['publicKey'] = $public_key;
        $req['privateKey'] = $private_key;*/
    $fields = json_encode($req);

    // Create cURL handle and initialize (if needed)
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $node_api_credentials['api_url'] . $cmd,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $transaction = json_decode($response, true);

    if (!empty($transaction) && ($transaction['status'] == 'OK')) {
        $arr = array();
        $arr['data'] = $transaction;
        $arr['msg'] = 'success';
        return $arr;
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
    /*} else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }*/
}


function set_node_refund_data($cmd, $req = array())
{

    $node_api_credentials = Config::get('constants.node_api_credentials');
    $fields = json_encode($req);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $node_api_credentials['api_url'] . $cmd,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $transaction = json_decode($response, true);

    if (!empty($transaction) && ($transaction['status'] == 'OK')) {
        $arr = array();
        $arr['data'] = $transaction;
        $arr['msg'] = 'success';
        return $arr;
    } else {

        $arr = array();
        $arr['address'] = '';
        $arr['msg'] = 'failed';
        return $arr;
    }
}

function getIPAddress()
{
    //whether ip is from the share internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } //whether ip is from the proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } //whether ip is from the remote address
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function getIpAddrss()
{

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function whiteListIpAddress($type = 0, $uid, $seconds = 30)
{

    $ip_add = getIpAddrss();
    $today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
    $arr = array('uid' => $uid, 'type' => $type, 'ip_add' => $ip_add, 'entry_time' => $today);
    $res = UserApiHitDetails::insert($arr);

    $hits_array = array();
    $new_date_time = \Carbon\Carbon::now()->subSeconds($seconds)->format('Y-m-d H:i:s');
    $get_user_hits = UserApiHitDetails::select('id', 'uid', 'ip_add', 'entry_time')->where([['type', $type], ['uid', $uid], ['status', 'Active'], ['ip_add', $ip_add]])->limit(5)->orderBy('id', 'desc')->get();
    $flag = 1;
    $hr = 1;
    if (!empty($get_user_hits) && count($get_user_hits) >= 5) {
        foreach ($get_user_hits as $key => $v) {
            if ($today <= $v['entry_time'] || $v['entry_time'] >= $new_date_time) {
                if ($flag >= 5) {
                    $insert_array = array();
                    $insert_array['uid'] = $v['uid'];
                    $insert_array['ip_add'] = $v['ip_add'];
                    $remark = 'working fine';
                    if ($type == 1) {
                        //type 1 is for topup
                        $insert_array['topup_status'] = 1;
                        $insert_array['topup_expire'] = \Carbon\Carbon::now()->addHour($hr);
                    } else if ($type == 2) {
                        //type 2 is for withdraw
                        $insert_array['withdraw_status'] = 1;
                        $insert_array['withdraw_expire'] = \Carbon\Carbon::now()->addHour($hr);
                    } else if ($type == 3) {
                        //type 3 is for transfer
                        $insert_array['transfer_status'] = 1;
                        $insert_array['transfer_expire'] = \Carbon\Carbon::now()->addHour($hr);
                    } else {
                        $remark = 'type invalid';
                    }
                    $insert_array['remark'] = $remark;
                    $insert_array['entry_time'] = \Carbon\Carbon::now();
                    $check_user_hits = WhiteListIpAddress::select('id')->where([['uid', $v['uid']], ['ip_add', $v['ip_add']]])->first();
                    if (!empty($check_user_hits)) {
                        $update = array();
                        if ($type == 1) {
                            //type 1 is for topup
                            $update['topup_status'] = 1;
                            $update['topup_expire'] = \Carbon\Carbon::now()->addHour($hr);
                        } else if ($type == 2) {
                            //type 2 is for withdraw
                            $update['withdraw_status'] = 1;
                            $update['withdraw_expire'] = \Carbon\Carbon::now()->addHour($hr);
                        } else if ($type == 3) {
                            //type 3 is for transfer
                            $update['transfer_status'] = 1;
                            $update['transfer_expire'] = \Carbon\Carbon::now()->addHour($hr);
                        }
                        $update['ip_add'] = $ip_add;
                        $update['remark'] = $remark;
                        $result = WhiteListIpAddress::where('id', $check_user_hits->id)->update($update);
                        break;
                        // if($result){
                        // 	// return true;
                        // 	break;
                        // }else{
                        // 	// return false;
                        // 	break;
                        // }
                    } else {
                        $new_insert = WhiteListIpAddress::insert($insert_array);
                        break;
                        // if($new_insert){
                        // 	// return true;
                        // 	break;
                        // }else{
                        // 	// return false;
                        // 	break;
                        // }
                    }
                }
                $flag = $flag + 1;
            }
        }
        $user_array = implode(',', array_column($get_user_hits->toArray(), 'id'));
        $make_array = explode(',', $user_array);
        $get_res = UserApiHitDetails::where([['uid', $uid], ['type', $type], ['ip_add', $ip_add]])->whereNotIn('id', $make_array)->delete();
        if ($get_res) {
            return true;
        } else {
            return false;
        }
    }
    return '';
}

function addr_updateWithdraw_stop_mail($curr_type, $token, $user_id, $user_email)
{
    // mail
    $path = Config::get('constants.settings.domainpath');
    $pagename = "emails.withdraw_stop_oneday";
    $subject = "Withdraw is stop for next 24 hrs";
    $contant = "Withdraw is stop for next 24 hrs as your payment " . $curr_type . " address is updated!";
    $sub_contant = "If not then please ";
    $click_here = "Click Here!";
    $prof_url = $path . "/user#/currency-address?token=" . $token;
    $data = array('pagename' => $pagename, 'contant' => $contant, 'username' => $user_id, 'sub_contant' => $sub_contant, 'click_here' => $click_here, 'prof_url' => $prof_url);
    $email = $user_email;
    //$mail = sendMail($data, $email, $subject);
}

function isPasswordValid($password)
{

    $whiteListed = "\$\@\#\^\|\!\~\=\+\-\_\.\&\:\;\<\>\%\,\?\'\(\)\[\]{\}\'\/";
    $status = false;
    $message = "Password is invalid";
    $containsLetter = preg_match('/[a-zA-Z]/', $password);
    $containsDigit = preg_match('/\d/', $password);
    $containsSpecial = preg_match('/[' . $whiteListed . ']/', $password);
    $containsAnyOther = preg_match('/[^A-Za-z-\d' . $whiteListed . ']/', $password);
    if (strlen($password) < 6) $message = "Password should be at least 6 characters long";
    else if (strlen($password) > 20) $message = "Password should be at maximum 20 characters long";
    else if (!$containsLetter) $message = "Password should contain at least one letter.";
    else if (!$containsDigit) $message = "Password should contain at least one number.";
    else if (!$containsSpecial) $message = "Password should contain at least one of these" . stripslashes($whiteListed) . " ";
    else if ($containsAnyOther) $message = "Password should contain only the special characters and except";
    else {

        //    if(!$containsSpecial) $message = "Password should contain at least one of these ".stripslashes( $whiteListed )." ";

        $whiteListed1 = '"';

        $containsAnyOtherone = preg_match('/[^A-Za-z-\d' . $whiteListed1 . ']/', $password);

        //    if($containsAnyOtherone && !$containsSpecial) $message = "Password should contain only the mentioned characters";

        $status = true;
        $message = "Password is valid";
    }
    return array(
        "status" => $status,
        "message" => $message
    );
}

function api_access_store($data)
{
    $result = ApiAccessDetails::insert($data);
    if ($result) {
        return true;
    } else {
        return false;
    }
}

function checkUserIdIsAdmin($uid)
{
    // /dd(md5($tran_pass),$save_tran_pass);

    $userData = User::select('type', 'status', 'id', 'user_id')->where('id', $uid)->where('status', 'Active')->whereIn('type', ['Admin', 'sub-admin'])->first();

    if (!empty($userData)) {
        if ($userData->type == 'Admin') {
            return true;
            # code...
        } else if ($userData->type == 'sub-admin') {
            return true;
            # code...
        } else {
            return false;
            # code...
        }
    }
}

function getDefaultUserName()
{
    $project_set_data = ProjectSettings::select('default_username')->first();
    return $project_set_data->default_username;
}

function getUserDashboardDynamicData()
{
    $id = Auth::user()->id;

    $getDetails = \App\Models\User::select('tbl_users.l_c_count', 'tbl_users.r_c_count','tbl_users.rank','tbl_users.id','tbl_users.designation')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')
        ->where([['tbl_users.status', '=', 'Active'], ['tbl_users.type', '=', ''], ['tbl_dashboard.id', '=', $id]])->get();

    if (!empty($getDetails) && count($getDetails) > 0) {

        $getRank = PayoutHistory::where('user_id', $id)->orderBy('id', 'desc')->pluck('designation')->first();

        if (empty($getRank)) {
            $arrData['rank'] = "You dont have Rank Yet";
        } else {
            $arrData['rank'] = $getRank;
        }

        if (empty($getDetails[0]->designation)) {
            $arrData['designation'] = "You dont have Rank Yet";
        } else {
            $arrData['designation'] = $getDetails[0]->designation;
        }

        $arrData['lid'] = TodayDetails::where([['to_user_id', $id],['position','1']])->count();
        $arrData['rid'] = TodayDetails::where([['to_user_id', $id],['position','2']])->count();
        
        // $arrData['lid'] = $getDetails[0]->l_c_count;
        // $arrData['rid'] = $getDetails[0]->r_c_count;
        $arrData['user_id']      = $getDetails[0]->user_id;
        $arrData['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $current_time = getTimeZoneByIP($arrData['ip_address']);
        $arrData['current_time'] = $current_time;

        $date = new DateTime('next monday');
        $thisMonth = $date->format('Y-m-d H:i:s');

        $arrData['current_date'] = $thisMonth;
        $arrData['current_day'] = date('D');
        $path = Config::get('constants.settings.domainpath-vue');
        $dataArr = array();
        $arrData['link'] = $path . 'register?ref_id=' . 'HSCC7544835';

        $request = new \Illuminate\Http\Request();
        $request->replace(['status' => 1]);

        $result = (new LevelController)->getTeamStatus($request);

        $request = new \Illuminate\Http\Request();
        $request->replace(['status' => 0]);

        $result1 = (new LevelController)->getTeamStatus($request);
        $arrData['active'] = $result;
        $arrData['inactive'] = $result1;


        $project_withdraw_date = ProjectSettings::select('withdraw_day')->first();
        $arrData['project_withdraw_date'] =  $project_withdraw_date->withdraw_day;

        return $arrData;

    }


}
