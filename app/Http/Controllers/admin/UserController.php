<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\adminapi\CommonController;
use App\Http\Controllers\adminapi\LevelController;
use App\Http\Controllers\Controller;
use App\Models\Activitynotification;
use App\Models\AddFaq;
use App\Models\AddFailedLoginAttempt;
use App\Models\AddRemoveBusiness;
use App\Models\AddRemoveBusinessUpline;
use App\Models\AddressTransaction;
use App\Models\AddressTransactionPending;
use App\Models\AllTransaction;
use App\Models\Country;
use App\Models\Currency;
use App\Models\CurrentAmountDetails;
use App\Models\UserWithdrwalSetting;

use App\Models\AddPowerToParticularId;

use App\Models\Dashboard;
use App\Models\Depositaddress;
use App\Models\LevelView;
use App\Models\Otp as Otp;
use App\Models\PowerBV;
use App\Models\ProjectSetting;
use App\Models\Rank;
use App\Models\Representative;
use App\Models\SettingGallery;
use App\Models\Template;

use App\Models\TodayDetails;
use App\Models\Topup;

use App\Models\UserBulkUpdate;
use App\Models\UserContestAchievement;
use App\Models\UsersChangeData;
use App\Models\UserSettingFund;

use App\Models\WithdrawPending;
use App\Models\verifyAdminOtpStatus;
use App\User;
use App\Traits\CurrencyValidation;

use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    use  CurrencyValidation;

    /**
     * define property variable
     *
     * @return
     */
    public $statuscode, $commonController, $levelController;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CommonController $commonController, LevelController $levelController)
    {
        $this->statuscode       = Config::get('constants.statuscode');
        $this->OTP_interval     = Config::get('constants.settings.OTP_interval');
        $this->sms_username     = Config::get('constants.settings.sms_username');
        $this->sms_pwd          = Config::get('constants.settings.sms_pwd');
        $this->sms_route        = Config::get('constants.settings.sms_route');
        $this->senderId         = Config::get('constants.settings.senderId');
        $this->commonController = $commonController;
        $this->levelController  = $levelController;
    }

    public function sendotpOnMobile(Request $request)
    {
        try {
            $arrOutputData                     = [];
            $strStatus                         = trans('user.error');
            $arrOutputData['mailverification'] = $arrOutputData['google2faauth'] = $arrOutputData['mailotp'] = $arrOutputData['mobileverification'] = $arrOutputData['otpmode'] = 'FALSE';

            $arrInput = $request->all();
            //$baseUrl = URL::to('/');

            $validator = Validator::make($arrInput, [
                'user_id'  => 'required',
                'password' => 'required',
            ]);
            // check for validation
            if ($validator->fails()) {
                return setValidationErrorMessage($validator);
            }
            // check for the master password
            $arrWhere   = [];
            $arrWhere[] = ['user_id', $arrInput['user_id']];

            $userData = User::select('bcrypt_password')
                ->where($arrWhere)
                ->whereIn('type', ['Admin', 'Subadmin', 'sub-admin'])
                ->first();

            //	dd($userData, $arrInput['user_id']);
            //$master_pwd = MasterpwdModel::where([['password','=',md5($arrInput['password'])]])->first();
            if (empty($userData)) {
                $intCode    = Response::HTTP_UNAUTHORIZED;
                $strStatus  = Response::$statusTexts[$intCode];
                $strMessage = 'Invalid username';
                return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
            } else if (!Hash::check($request->Input('password'), $userData->bcrypt_password)) {
                $intCode    = Response::HTTP_UNAUTHORIZED;
                $strStatus  = Response::$statusTexts[$intCode];
                $strMessage = 'Invalid password';
                return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
            } else {
                // check user status
                $arrWhere       = [['user_id', $arrInput['user_id']], ['status', 'Active']];
                $userDataActive = User::select('bcrypt_password')->where($arrWhere)->first();
                if (empty($userDataActive)) {
                    $intCode    = Response::HTTP_UNAUTHORIZED;
                    $strStatus  = Response::$statusTexts[$intCode];
                    $strMessage = 'User is inactive,Please contact to admin';
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
                // if master passport matched with input password then replace the password by user password
                /*if(!empty($master_pwd)){
			$arrInput['password'] = Crypt::decrypt($userData->encryptpass);
			//dd($arrInput);
			 */
            }
            $users         = User::where('user_id', '=', $arrInput['user_id'])->first();
            $username      = $users->fullname;
            $checotpstatus = Otp::where([['id', '=', $users->id]])->orderBy('entry_time', 'desc')->first();

            if (!empty($checotpstatus)) {
                $entry_time   = $checotpstatus->entry_time;
                $out_time     = $checotpstatus->out_time;
                $checkmin     = date('Y-m-d H:i:s', strtotime($this->OTP_interval, strtotime($entry_time)));
                $current_time = date('Y-m-d H:i:s');
            }

            if (false/* !empty($checotpstatus) && $entry_time!='' && strtotime($checkmin)>=strtotime($current_time) && $checotpstatus->otp_status!='1' */) {
                $updateData               = array();
                $updateData['otp_status'] = 0;

                $updateOtpSta = Otp::where('user_id', $users->id)->update($updateData);

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'OTP already sent to your mobile no', $this->emptyArray);
            } else {

                $random = rand(100000, 999999);
                $link   = Config::get('constants.settings.link');

                $numbers  = urlencode($users->mobile);
                $username = urlencode($this->sms_username);
                $pass     = urlencode($this->sms_pwd);
                $route    = urlencode($this->sms_route);
                $senderid = urlencode($this->senderId);
                $OTP      = $random;
                $msg      = 'Dear Admin, your page authentication OTP is' . ' ' . $OTP . '\n' . $link;
                $message  = urlencode($msg);

                $temp_data        = Template::where('title', '=', 'Otp')->first();
                $project_set_data = ProjectSetting::select('icon_image', 'domain_name')->first();

                //send otp to mail
                $pagename = "emails.otpsend";
                // $subject = "OTP sent successfully";
                $subject     = $temp_data->subject;
                $content     = $temp_data->content;
                $domain_name = $project_set_data->domain_name;
                $data        = array('pagename' => $pagename, 'otp' => $random, 'tomail' => $users->email, 'username' => $users->email, 'content' => $content, 'domain_name' => $domain_name);
                // $mail = sendMail($data, $username, $subject);
                //end send otp to mail

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL            => "http://173.45.76.227/send.aspx?username=" . $username . "&pass=" . $pass . "&route=" . $route . "&senderid=" . $senderid . "&numbers=" . $numbers . "&message=" . $message,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => "",
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => "GET",
                    CURLOPT_POSTFIELDS     => "",
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ));

                $response = curl_exec($curl);
                $err      = curl_error($curl);

                curl_close($curl);

                /*  if ($err) {
				// echo "cURL Error #:" . $err;
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', $this->emptyArray);
				 */
                //echo $response;
                $insertotp               = array();
                $insertotp['id']         = $users->id;
                $insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
                $insertotp['otp']        = md5($random);
                $insertotp['otp_status'] = 0;
                $insertotp['type']       = 'mobile';
                $sendotp                 = Otp::create($insertotp);

                $arrData = array();
                // $arrData['id']   = $users->id;
                $arrData['remember_token']     = $users->remember_token;
                $arrData['mailverification']   = 'TRUE';
                $arrData['google2faauth']      = 'FALSE';
                $arrData['mailotp']            = 'TRUE';
                $arrData['mobileverification'] = 'TRUE';
                $arrData['otpmode']            = 'FALSE';
                // $mask_mobile = maskmobilenumber($users->mobile);
                $mask_email       = maskEmail($users->email);
                $arrData['email'] = $mask_email;
                // $arrData['mobile'] = $mask_mobile;

                $intCode    = Response::HTTP_OK;
                $strMessage = 'Otp sent successfully to your mobile no.';
                $strStatus  = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, $strMessage, $arrData);
                // return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your mobile no. and registered email Id ', $arrData);

                return $sendotp;
                // }

            } // end of users
        } catch (\Exception $e) {
            dd($e);
            $intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
            $strMessage = trans('admin.defaultexceptionmessage');
            $strStatus  = Response::$statusTexts[$intCode];
            return sendResponse($intCode, $strStatus, $strMessage, $e);
            // return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Send failed',$e);
            //return true;
        }
    }

    /**
     * get all user list with reference user id
     *
     * @return \Illuminate\Http\Response
     */


    public function edituserProfileBlade(Request $request){

        $arrInput['id'] =  $request->id;

        $getuserdata = User::select('tbl_users.*', 'cn.iso_code', 'tu.user_id as ref_user_id', 'tu.fullname as sponser_id', DB::raw('(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" WHEN 3 THEN "Right" ELSE "" END) as position'), 'tbl_users.btc_address','tbl_users.etn_address','tbl_users.trn_address','tbl_users.ethereum','tbl_users.sol_address','tbl_users.ltc_address','tbl_users.doge_address','tbl_users.usdt_trc20_address','tbl_users.beam_address','tbl_users.btg_address','tbl_users.bch_address','tbl_users.xmr_address','tbl_users.xvg_address','tbl_users.firo_address','tbl_users.omni_address','tbl_users.zen_address','cn.country', DB::raw('DATE_FORMAT(tbl_users.entry_time,"%Y/%m/%d %H:%i:%s") as entry_time'))
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbl_users.country')
            ->where('tbl_users.id', $arrInput['id'])
            ->first();

        $btcadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'BTC')->select('currency_address')->first();
        $ethadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'ETH')->select('currency_address')->first();
        $bnbadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'BNB')->select('currency_address')->first();
        $trxadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'TRX')->select('currency_address')->first();
        $usdtadd = UserWithdrwalSetting::where('id',$arrInput['id'])->where('currency', 'USDT.TRC20')->select('currency_address')->first();
        $ltcadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'LTC')->select('currency_address')->first();
        $soladd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'SOL')->select('currency_address')->first();
        $dogeadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'DOGE')->select('currency_address')->first();

        $getuser = array();

        if(empty($btcadd))	{
            $getuser['btc_address'] = '';
        }
        else {
            $getuser['btc_address'] = $btcadd->currency_address;
        }

        if(empty($ethadd))	{
            $getuser['ethereum'] = '';
        }
        else {
            $getuser['ethereum'] = $ethadd->currency_address;
        }

        if(empty($bnbadd))	{
            $getuser['bnb_address'] = '';
        }
        else {
            $getuser['bnb_address'] = $bnbadd->currency_address;
        }

        if(empty($trxadd))	{
            $getuser['trn_address'] = '';
        }
        else {
            $getuser['trn_address'] = $trxadd->currency_address;
        }

        if(empty($usdtadd))	{
            $getuser['usdt_trc20_address'] = '';
        }
        else {
            $getuser['usdt_trc20_address'] = $usdtadd->currency_address;
        }
        if(empty($ltcadd))	{
            $getuser['ltc_address'] = '';
        }
        else {
            $getuser['ltc_address'] = $ltcadd->currency_address;
        }

        if(empty($soladd))	{
            $getuser['sol_address'] = '';
        }
        else {
            $getuser['sol_address'] = $soladd->currency_address;
        }

        if(empty($dogeadd))	{
            $getuser['doge_address'] = '';
        }
        else {
            $getuser['doge_address'] = $dogeadd->currency_address;
        }
// dd($getuserdata);
        $getuser['id'] =  $arrInput['id'];
        $getuser['sponsor_id'] = $getuserdata->sponsor_id;
        $getuser['entry_time'] = $getuserdata->entry_time;
        $getuser['user_id'] = $getuserdata->user_id;
        $getuser['ref_user_id'] = $getuserdata->ref_user_id;
        $getuser['mobile'] = $getuserdata->mobile;
        $getuser['email'] = $getuserdata->email;
        $getuser['fullname'] = $getuserdata->fullname;
        $getuser['position'] = $getuserdata->position;
        $getuser['btc_address'] = $getuserdata->btc_address;
        $getuser['ltc_address'] = $getuserdata->ltc_address;
        $getuser['trn_address'] = $getuserdata->trn_address;
        $getuser['sol_address'] = $getuserdata->sol_address;

        $getuser['bch_address'] = $getuserdata->bch_address;
        $getuser['bnb_address'] = $getuserdata->bnb_address;
        $getuser['beam_address'] = $getuserdata->beam_address;
        $getuser['btg_address'] = $getuserdata->btg_address;
        $getuser['xmr_address'] = $getuserdata->xmr_address;
        $getuser['xvg_address'] = $getuserdata->xvg_address;
        $getuser['firo_address'] = $getuserdata->firo_address;
        $getuser['omni_address'] = $getuserdata->omni_address;
        $getuser['zen_address'] = $getuserdata->zen_address;
        $getuser['etn_address'] = $getuserdata->etn_address;

        $getuser['usdt_trc20_address'] = $getuserdata->usdt_trc20_address;
        $getuser['ethereum'] = $getuserdata->ethereum;
        $getuser['doge_address'] = $getuserdata->doge_address;
        $getuser['usdt_erc20_address'] = $getuserdata->usdt_erc20_address;
        $getuser['country'] = $getuserdata->country;




        $editUser = $getuser;

        $cntry= Country::all();
        // print_r($editUser->toArray());
        return view('admin.ManageUser.EditUserProfile')->with(compact('editUser','cntry'));


    }
    public function getUsers(Request $request)
    {
        $arrInput = $request->all();

        $query = User::join('tbl_users as tu2', 'tu2.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbl_users.country')
            /*->leftjoin('tbl_topup as tp', 'tp.pin', '=', 'tbl_users.id')*/
            ->where('tbl_users.type', '!=', 'Admin');
        /*if(!empty($this->userType->type) && $this->userType->type=='area_admin'){
		$query = $query->where('tbl_users.area_admin',$this->userType->id);
		 */
        if (isset($arrInput['id'])) {
            $query = $query->where('tbl_users.user_id', $arrInput['id']);
        }
        if (isset($arrInput['sponser_user_id'])) {
            $queryspons = User::select('id')->where('user_id', '=', $arrInput['sponser_user_id'])->first();
            // echo $queryspons['id'];
            $query = $query->where('tbl_users.ref_user_id', $queryspons['id']);
        }

        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (!empty($arrInput['status']) && isset($arrInput['status'])) {
            $query = $query->where('tbl_users.status', $arrInput['status']);
        }
        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
            //searching loops on fields
            //$fields = getTableColumns('tbl_users');
            $fields = ['tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.status', 'tu2.user_id', 'cn.country', 'tbl_users.mobile', 'tbl_users.google2fa_status', 'tbl_users.btc_address'];
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry     = $query;
            $qry     = $qry->select('tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile', 'tu2.user_id as sponser_id', 'cn.country', 'cn.iso_code', DB::raw('(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" ELSE "" END) as position'), 'tbl_users.status', 'tbl_users.entry_time');
            $records = $qry->get();
            $res     = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res, "AllUsers");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
        }
        $masterPwd = DB::table('tbl_master_password')->pluck('tran_password')->first();
        $url       = Config::get('constants.settings.domainpath-vue');

        $query       = $query->select('tbl_users.id','tbl_users.withdraw_block_by_admin','tbl_users.amount', 'tbl_users.user_id', 'tbl_users.paypal_address', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.entry_time', 'tbl_users.status', 'tu2.user_id as sponser_id', 'cn.country', 'cn.iso_code', 'tbl_users.type', 'tbl_users.mobile', DB::raw('(CASE tbl_users.verifyaccountstatus WHEN 0 THEN "Unverified" WHEN 1 THEN "Verified" ELSE "" END) as verifyaccountstatus'), DB::raw('(CASE tbl_users.mobileverify_status WHEN 0 THEN "Unverified" WHEN 1 THEN "Verified" ELSE "" END) as mobileverify_status'), DB::raw('(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" ELSE "" END) as position'), 'tbl_users.remember_token', DB::raw('"' . $url . '/login?password=' . $masterPwd . '&user_id=" as login_url'));
        $totalRecord = $query->count('tbl_users.id');
        $query       = $query->orderBy('tbl_users.id', 'desc');
        // $totalRecord = $query->count();
        $arrUserData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrUserData;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }


    public function directSignUpReportBlade(){
        return view('admin.User.DirectSignupReport');
    }


    public function getDirectSignupReport(Request $request)
    {
        $arrInput = $request->all();
        $query = User::select('tbl_users.id','tbl_users.entry_time','tbl_users.user_id','tbl_users.fullname','tbl_users.email','tbl_users.mobile',DB::raw('(select COALESCE(sum(amount),0) from tbl_topup as tp where tp.id = tbl_users.id) as total_topup'),DB::raw('(select product_name from tbl_topup as tp where tp.id = tbl_users.id order by srno desc limit 1) as last_topup'),DB::raw('CASE WHEN tbl_users.topup_status = "1" AND tbl_users.three_x_achieve_status = "0" AND tbl_users.status = "Active" THEN "Active" WHEN tbl_users.status = "Inactive" THEN "Blocked by Admin" ELSE "Inactive" END as user_status'))
            // ->join('tbl_topup as ttp', 'ttp.id', '=', 'tbl_users.id')
            ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_users.ref_user_id')
            ->where('tbl_users.type', '!=', 'Admin')->where('tu2.user_id', '=', $arrInput['user_id']);
        /*->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup.franchise_id');*/

        //(CASE tbl_topup.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END) as binary_pass_status,(CASE tbl_topup.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END) as level_pass_status,(CASE tbl_topup.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END) as direct_pass_status

        if (isset($arrInput['user_id'])) {
            $queryspons = User::select('id')->where('user_id', '=', $arrInput['user_id'])->first();
            // echo $queryspons['id'];
            $query = $query->where('tbl_users.ref_user_id', $queryspons['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }

        $totalRecord = $query->count('tbl_users.id');
        $query       = $query->orderBy('tbl_users.id', 'desc');
        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry     = $query;
            $qry     = $qry->select('tbl_users.entry_time','tbl_users.user_id','tbl_users.fullname','tbl_users.email','tbl_users.mobile',DB::raw('(select sum(amount) from tbl_topup as tp where tp.id = tbl_users.id) as total_topup'),DB::raw('(select product_name from tbl_topup as tp where tp.id = tbl_users.id order by srno desc limit 1) as last_topup'))
                ->join('tbl_topup as ttp', 'ttp.id', '=', 'tbl_users.id')
                ->where('tbl_users.type', '!=', 'Admin');
            $records = $qry->get();
            $res     = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res, "DirectSignupUsers");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
        }
        $totalRecord = $query->count();

        $arrTopup    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrTopup;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function influencerdirectsignupblade(){
        return view('admin.User.InfluencerDirectSignupReport');
    }
    public function getInfluencerDirectSignupReport(Request $request)
    {
        $arrInput = $request->all();
        $query = User::select('tbl_users.id','tbl_users.entry_time','tbl_users.user_id','tbl_users.fullname','tbl_users.email','tbl_users.mobile',DB::raw('(select COALESCE(sum(amount),0) from tbl_topup as tp where tp.id = tbl_users.id) as total_topup'),DB::raw('(select product_name from tbl_topup as tp where tp.id = tbl_users.id order by srno desc limit 1) as last_topup'),DB::raw('CASE WHEN tbl_users.topup_status = "1" AND tbl_users.three_x_achieve_status = "0" AND tbl_users.status = "Active" THEN "Active" WHEN tbl_users.status = "Inactive" THEN "Blocked by Admin" ELSE "Inactive" END as user_status'))
            ->join('tbl_topup as ttp', 'ttp.id', '=', 'tbl_users.ref_user_id')
            ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_users.ref_user_id')
            ->where('ttp.type', '=', '7')->where('tbl_users.type', '!=', 'Admin')
            ->where('tu2.user_id', '=', $arrInput['user_id']);
        /*->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup.franchise_id');*/

        //(CASE tbl_topup.binary_pass_status WHEN 0 THEN "Binary Not Pass" WHEN 1 THEN "Binary Pass" ELSE "" END) as binary_pass_status,(CASE tbl_topup.level_pass_status WHEN 0 THEN "Level Not Pass" WHEN 1 THEN "Level Pass" ELSE "" END) as level_pass_status,(CASE tbl_topup.direct_pass_status WHEN 0 THEN "Direct Not Pass" WHEN 1 THEN "Direct Pass" ELSE "" END) as direct_pass_status

        if (isset($arrInput['user_id'])) {
            $queryspons = User::select('id')->where('user_id', '=', $arrInput['user_id'])->first();
            // echo $queryspons['id'];
            $query = $query->where('tbl_users.ref_user_id', $queryspons['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }

        $totalRecord = $query->count('tbl_users.id');
        $query       = $query->orderBy('tbl_users.id', 'desc');
        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry     = $query;
            $qry     = $qry->select('tbl_users.entry_time','tbl_users.user_id','tbl_users.fullname','tbl_users.email','tbl_users.mobile',DB::raw('(select sum(amount) from tbl_topup as tp where tp.id = tbl_users.id) as total_topup'),DB::raw('(select product_name from tbl_topup as tp where tp.id = tbl_users.id order by srno desc limit 1) as last_topup'))
                ->join('tbl_topup as ttp', 'ttp.id', '=', 'tbl_users.id')
                ->where('tbl_users.type', '!=', 'Admin');
            $records = $qry->get();
            $res     = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res, "DirectSignupUsers");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
        }
        $totalRecord = $query->count();

        $arrTopup    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrTopup;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    /**
     * get all franchise user list with reference user id
     *
     * @return \Illuminate\Http\Response
     */
    public function getNewFranchiseUsers(Request $request)
    {
        $arrInput = $request->all();

        $query = User::join('tbl_users as tu2', 'tu2.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbl_users.country')
            ->select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile', 'tbl_users.entry_time', 'tbl_users.status', 'tu2.user_id as sponser_id', 'cn.country', 'cn.iso_code', 'tbl_users.type', 'tbl_users.mobile', /* 'tbl_users.google2fa_status', 'tbl_users.btc_address', 'tbl_users.l_c_count', 'tbl_users.r_c_count', 'tbl_users.l_bv', 'tbl_users.r_bv',*/ DB::raw('(CASE tbl_users.verifyaccountstatus WHEN 0 THEN "Unverified" WHEN 1 THEN "Verified" ELSE "" END) as verifyaccountstatus'), DB::raw('(CASE tbl_users.mobileverify_status WHEN 0 THEN "Unverified" WHEN 1 THEN "Verified" ELSE "" END) as mobileverify_status'), DB::raw('(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" ELSE "" END) as position'), 'tbl_users.remember_token')
            ->where('tbl_users.is_franchise', '1');
        /*if(!empty($this->userType->type) && $this->userType->type=='area_admin'){
		$query = $query->where('tbl_users.area_admin',$this->userType->id);
		 */
        if (isset($arrInput['id'])) {
            $query = $query->where('tbl_users.user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (!empty($arrInput['status']) && isset($arrInput['status'])) {
            $query = $query->where('tbl_users.status', $arrInput['status']);
        }
        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
            //searching loops on fields
            //$fields = getTableColumns('tbl_users');
            $fields = ['tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.status', 'tu2.user_id', 'cn.country', 'tbl_users.mobile', 'tbl_users.google2fa_status', 'tbl_users.btc_address'];
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $totalRecord = $query->count('tbl_users.id');
        $query       = $query->orderBy('tbl_users.id', 'desc');
        // $totalRecord = $query->count();
        $arrUserData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrUserData;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }

    public function makeAsFranchise(Request $request)
    {
        $arrInput = $request->all();

        try {

            $rules = array(
                'user_id' => 'required',
            );
            // run the validation rules on the inputs from the form
            $validator = Validator::make($arrInput, $rules);
            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = $validator->errors();
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User Id required', $message);
            }

            $user = User::select('is_franchise')->where('user_id', '=', $arrInput['user_id'])
                ->where('is_franchise', '=', '1')
                ->first();
            //dd($user);
            if (!empty($user)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'already franchise user', $user);
            } else {
                $user = User::select('is_franchise', 'id')
                    ->where('user_id', '=', $arrInput['user_id'])
                    ->first();

                //dd($user->id);

                User::where('id', '=', $user->id)->update(array('is_franchise' => '3'));

                //$updateUser = User::where('id', $user->id)->update(['is_franchise' => '1','income_per'=>3]);

                // /dd($dd,$updateUser,$arrInput['user_id']);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'successfully Make franchise', '');
            }
            // dd($user,222);

        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Server Error', '');
        }
    }

    /**
     * get userDetails
     *
     * @return \Illuminate\Http\Response
     */
    public function userDetails(Request $request)
    {
        $user = User::select('tbl_users.*', 'tu.user_id as ref_user_id', 'tu.fullname as ref_fullname')->join('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')->first();
        if (!empty($user)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '', $user);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
        }
    }
    /**
     * get userDetails
     *
     * @return \Illuminate\Http\Response
     */
    public function findUser(Request $request)
    {
        $user = User::select('tbl_users.user_id')
            ->where('tbl_users.user_id', '=', $request->user_id)
            ->first();
        if (!empty($user)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '', $user);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
        }
    }

    /**
     * get all user list with reference user id
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserDesignation(Request $request)
    {
        $arrInput = $request->all();

        $query = User::select('id', 'user_id', 'fullname', 'designation')
            ->where('type', '')
            ->where('status', 'Active');
        if (isset($arrInput['id'])) {
            $query = $query->where('user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = ['user_id', 'fullname', 'designation'];
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $totalRecord = $query->count('id');
        $query       = $query->orderBy('id', 'desc');
        // $totalRecord = $query->count();
        $arrUserData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrUserData;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }
    /**
     * get all user list with reference user id
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserAddresses(Request $request)
    {
        $arrInput = $request->all();

        $query = User::select('id', 'user_id', 'fullname', 'btc_address', 'entry_time')->where('type', '')->where('status', 'Active');
        if (isset($arrInput['id'])) {
            $query = $query->where('user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        // if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
        // 	//searching loops on fields
        // 	$fields = ['user_id', 'fullname', 'btc_address', 'ethereum', 'bch_address', 'ltc_address'];
        // 	$search = $arrInput['search']['value'];
        // 	$query = $query->where(function ($query) use ($fields, $search) {
        // 		foreach ($fields as $field) {
        // 			$query->orWhere($field, 'LIKE', '%' . $search . '%');
        // 		}
        // 	});
        // }

        $query       = $query->orderBy('id', 'desc');
        $totalRecord = $query->count('id');
        $arrPendings = $query->skip($request->input('start'))->take($request->input('length'))->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrPendings;
        /*	dd($arrData);*/
        if (!empty($arrPendings) && count($arrPendings) > 0) {
            $arrStatus  = Response::HTTP_OK;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Data Found';
            return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
        } else {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Data not Found';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function blockUserBlade(){
        return view('admin.BlockUsersReport');
    }
    /**
     * get all user list with reference user id
     *
     * @return \Illuminate\Http\Response
     */
    public function getBlockUsers(Request $request)
    {
        $arrInput = $request->all();
        //dd($arrInput);
        $query = User::select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile', 'tbl_users.btc_address', 'tbl_users.entry_time', 'tu.user_id as sponsor_id', 'tcn.country', 'tbl_users.block_entry_time')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_country_new as tcn', 'tcn.iso_code', '=', 'tbl_users.country')
            ->where('tbl_users.type', '')
            ->where('tbl_users.status', 'Inactive');
        //dd($query);
        if (isset($arrInput['id'])) {
            $query = $query->where('tbl_users.user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        // if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
        // 	//searching loops on fields
        // 	$fields = ['tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile'];
        // 	$search = $arrInput['search']['value'];
        // 	$query = $query->where(function ($query) use ($fields, $search) {
        // 		foreach ($fields as $field) {
        // 			$query->orWhere($field, 'LIKE', '%' . $search . '%');
        // 		}
        // 	});
        // }
        $query = $query->orderBy('tbl_users.id', 'desc');
        if (isset($arrInput['start']) && isset($arrInput['length'])) {
            $arrData = setPaginate1($query, $arrInput['start'], $arrInput['length']);
        } else {
            $arrData = $query->get();
        }

        //$totalRecord  = $query->count();
        //$arrUserData  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        // $arrData['recordsTotal']    = $totalRecord;
        // $arrData['recordsFiltered'] = $totalRecord;
        // $arrData['records']         = $arrUserData;

        // if($arrData['recordsTotal'] > 0){
        if ((isset($arrData['totalRecord']) > 0) || (count($arrData) > 0)) {

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }

    public function getUnblockUsers(Request $request)
    {
        $arrInput = $request->all();

        $query = User::select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile', 'tbl_users.btc_address', 'tbl_users.entry_time', 'tu.user_id as sponsor_id', 'tcn.country', 'tu.status')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_country_new as tcn', 'tcn.iso_code', '=', 'tbl_users.country')
            ->where('tbl_users.type', '')
            ->where('tbl_users.status', 'Active');
        if (isset($arrInput['id'])) {
            $query = $query->where('tbl_users.user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = ['tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile'];
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $query = $query->orderBy('tbl_users.id', 'desc');
        if (isset($arrInput['start']) && isset($arrInput['length'])) {
            $arrData = setPaginate1($query, $arrInput['start'], $arrInput['length']);
        } else {
            $arrData = $query->get();
        }

        // $totalRecord  = $query->count();
        // $arrUserData  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        // $arrData['recordsTotal']    = $totalRecord;
        // $arrData['recordsFiltered'] = $totalRecord;
        // $arrData['records']         = $arrUserData;

        // if($arrData['recordsTotal'] > 0){
        if ((isset($arrData['totalRecord']) > 0) || (count($arrData) > 0)) {

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }

    public function getFranchiseUsersReport(Request $request)
    {
        $arrInput = $request->all();

        $query = User::select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile', 'tbl_users.btc_address', 'tbl_users.entry_time', 'tu.user_id as sponsor_id', 'tcn.country')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_country_new as tcn', 'tcn.iso_code', '=', 'tbl_users.country')
            ->where('tbl_users.type', '')
            ->where('tbl_users.is_franchise', '1');
        if (isset($arrInput['id'])) {
            $query = $query->where('tbl_users.user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = ['tbl_users.user_id', 'tbl_users.fullname', 'tbl_users.email', 'tbl_users.mobile'];
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $totalRecord = $query->count('tbl_users.id');
        $query       = $query->orderBy('tbl_users.id', 'desc');

        // $totalRecord = count($query->get());
        $arrUserData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrUserData;

        if ($arrData['recordsTotal'] > 0) {

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }
    /**
     * update user data with new data by keeping its old logs
     *
     * @return void
     */
    public function updateUser(Request $request)
    {

        $arrInput = $request->all();



        $rules = array(
            'user_id'       => 'required',
            'fullname' => 'required|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/',
            'email'    => 'required|email|max:50',
            'mobile'   => 'required',
            /*'city' => 'required',
			'address' => 'required',*/
            'country' => 'required',
            // 'otp' => 'required',
            //'btc_address'   => 'required',
        );
        $ruleMessages = array(
            'fullname.regex' => 'Special characters not allowed in fullname.',
        );

        $adminOtpStatusData = verifyAdminOtpStatus::select('profile_update_status')->first();
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else if ($adminOtpStatusData->profile_update_status == 1) {

            if (!isset($arrInput['otp']) && empty($arrInput['otp'])) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
            }
            $arrInput['user_id'] = Auth::User()->id;
            $arrInput['remark'] = 'admin edit profile';
            $arrInput['otp'] = $request->otp;
            $verify_otp = verify_Admin_Withdraw_Otp($arrInput);

            if (!empty($verify_otp)) {
                if ($verify_otp['status'] == 200) {
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            }
        }

        $check = checkUserIdIsAdmin(Auth::User()->id);




        if ($check == false) {


            $updated_by = Auth::user()->id;
            $ipAdd = getIpAddrss();

            $array = [];
            $array['user_id'] =  isset(Auth::user()->user_id) ? Auth::user()->user_id : NULL;
            $array['ip_address'] = $ipAdd;
            $array['api_url'] = url()->full();
            $array['request_data'] = json_encode($request->all());
            $array['panel'] = 'admin';
            $array['entry_time'] = \Carbon\Carbon::now();
            $result = api_access_store($array);

            /* $arrUpdateHack = [
					  'id' => $oldUserData->id,
				   'user_id' => $oldUserData->user_id,
					'fullname' => $arrInput['fullname'],
					'email' => $arrInput['email'],
					'mobile' => $arrInput['mobile'],
					'country'       => $arrInput['country'],
					'btc_address' => $arrInput['btc_address'],
					'ethereum' => $arrInput['ethereum'],
					'trn_address' => $arrInput['trn_address'],
					'bnb_address' => $arrInput['bnb_address'],
					'paypal_address' => $arrInput['paypal_address'],
					'perfect_money_address' => $arrInput['perfect_money_address'],
					'perfect_money_address' => $arrInput['perfect_money_address'],
					'ip' => $ipAdd,
					 'updated_by' => $updated_by,
					'entry_time' => now(),
					'from_api' =>'api/4P8Sr5Xf83lq/updateuser'
					//'ethereum'      => $arrInput['ethereum'],
					//'ref_user_id' =>
				];
	   UsersChangeDataHack::insertGetId($arrUpdateHack);*/
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Unauthenticated user', '');
        }
        //get old data by remember_token
        $oldUserData = User::select('id', 'user_id', 'fullname', 'email', 'mobile', 'city', 'address', 'country', 'ltc_address', 'btc_address', 'trn_address', 'ethereum', 'doge_address', 'sol_address', 'usdt_trc20_address','usdt_erc20_address', 'ethereum')->where('user_id', trim($request->user_id))->first();


        // $withdrawal_currency = Currency::where('tbl_currency.status','1')->get();
        // foreach ($withdrawal_currency as $key)
        // {
        // 	$curr_address = UserWithdrwalSetting::where([['id',$oldUserData->id], ['currency',$key['currency']],['status',1]])->pluck('currency_address')->first();
        // 	if(!empty($curr_address)){
        // 		$arrData[''.str_replace("-","_",strtolower($key['currency'])).'_address'] = $curr_address;
        // 		// dd($curr_address);
        // 	}else{
        // 		$arrData[''.str_replace("-","_",strtolower($key['currency'])).'_address'] = "";
        // 	}
        // }

        $btcadd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'BTC')->select('currency_address')->first();
        $ethadd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'ETH')->select('currency_address')->first();
        $bnbadd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'BNB')->select('currency_address')->first();
        $trxadd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'TRX')->select('currency_address')->first();
        $usdtadd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'USDT.TRC20')->select('currency_address')->first();
        $usdt_erc_add = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'USDT.ERC20')->select('currency_address')->first();
        $ltcadd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'LTC')->select('currency_address')->first();
        $soladd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'SOL')->select('currency_address')->first();
        $dogeadd = UserWithdrwalSetting::where('id', $oldUserData->id)->where('currency', 'DOGE')->select('currency_address')->first();

        if(empty($btcadd))	{
            $arrData['btc_address'] = '';
        }
        else {
            $arrData['btc_address'] = $btcadd->currency_address;
        }

        if(empty($ethadd))	{
            $arrData['ethereum'] = '';
        }
        else {
            $arrData['ethereum'] = $ethadd->currency_address;
        }

        if(empty($bnbadd))	{
            $arrData['bnb_address'] = '';
        }
        else {
            $arrData['bnb_address'] = $bnbadd->currency_address;
        }

        if(empty($trxadd))	{
            $arrData['trn_address'] = '';
        }
        else {
            $arrData['trn_address'] = $trxadd->currency_address;
        }
        if(empty($usdtadd))	{
            $arrData['usdt_trc20_address'] = '';
        }
        else {
            $arrData['usdt_trc20_address'] = $usdtadd->currency_address;
        }
        if(empty($usdt_erc_add))	{
            $arrData['usdt_erc20_address'] = '';
        }
        else {
            $arrData['usdt_erc20_address'] = $usdt_erc_add->currency_address;
        }
        if(empty($ltcadd))	{
            $arrData['ltc_address'] = '';
        }
        else {
            $arrData['ltc_address'] = $ltcadd->currency_address;
        }

        if(empty($soladd))	{
            $arrData['sol_address'] = '';
        }
        else {
            $arrData['sol_address'] = $soladd->currency_address;
        }

        if(empty($dogeadd))	{
            $arrData['doge_address'] = '';
        }
        else {
            $arrData['doge_address'] = $dogeadd->currency_address;
        }

        if (!empty($request->Input('trx_address'))) {
            $flag = 2;
            $addData['currency'] = "TRX";
            $addData['currency_address'] = trim($request->Input('trx_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if (!empty($request->trx_address)) {
                if (strlen(trim($request->Input('trx_address'))) >= 26 && strlen(trim($request->Input('trx_address'))) <= 50) {
                    $split_array = str_split(trim($request->Input('trx_address')));
                    if ($split_array[0] == 'T')
                    {

                    } elseif ($split_array[0] == 't') {

                    }
                    // elseif ($split_array[0] == 'b') {

                    // }
                    else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'TRON address is not valid!', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'TRON address is not valid!', '');
                }
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('trx_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('trx_address'));

                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 2){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }
        if (!empty($request->Input('btc_address'))) {
            $flag = 2;
            $addData['currency'] = "BTC";
            $addData['currency_address'] = trim($request->Input('btc_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if (!empty($request->btc_address)) {
                if (strlen(trim($request->Input('btc_address'))) >= 26 && strlen(trim($request->Input('btc_address'))) <= 50) {
                    $split_array = str_split(trim($request->Input('btc_address')));
                    if ($split_array[0] == 3)
                    {

                    } elseif ($split_array[0] == 1) {

                    } elseif ($split_array[0] == 'b') {

                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Bitcoin address is not valid!', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Bitcoin address is not valid!', '');
                }
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('btc_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('btc_address'));

                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 2){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }
        if (!empty($request->Input('eth_address'))) {
            $flag = 2;
            $addData['currency'] = "ETH";
            $addData['currency_address'] = trim($request->Input('eth_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if (!empty($request->eth_address)) {
                if (strlen(trim($request->Input('eth_address'))) >= 26 && strlen(trim($request->Input('eth_address'))) <= 50) {
                    $split_array = str_split(trim($request->Input('eth_address')));
                    if ($split_array[0] == 0 && $split_array[1] == 'x')
                    {

                        /*} elseif ($split_array[0] == 1) {

							} elseif ($split_array[0] == 'b') {*/

                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Etherium address is not valid!', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Etherium address is not valid!', '');
                }
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('eth_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('eth_address'));

                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 2){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }
        if (!empty($request->Input('usdt_erc20_address'))) {
            $flag = 2;
            $addData['currency'] = "USDT-ERC20";
            $addData['currency_address'] = trim($request->Input('usdt_erc20_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if (!empty($request->usdt_erc20_address)) {
                if (strlen(trim($request->Input('usdt_erc20_address'))) >= 26 && strlen(trim($request->Input('usdt_erc20_address'))) <= 50) {
                    $split_array = str_split(trim($request->Input('usdt_erc20_address')));
                    if ($split_array[0] == 0 && $split_array[1] == 'x')
                    {

                        /*} elseif ($split_array[0] == 1) {

							} elseif ($split_array[0] == 'b') {*/

                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-ERC20 address is not valid!', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-ERC20 address is not valid!', '');
                }
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('usdt_erc20_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('usdt_erc20_address'));

                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 2){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }
        if (!empty($request->Input('usdt_trc20_address'))) {
            $flag = 4;
            $addData['currency'] = "USDT-TRC20";
            $addData['currency_address'] = trim($request->Input('usdt_trc20_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if(strlen(trim($request->Input('usdt_trc20_address'))) >= 26 && strlen(trim($request->Input('usdt_trc20_address'))) <= 42){
                $split_array = str_split(trim($request->Input('usdt_trc20_address')));
                if ($split_array[0] == 'T') {

                }
                elseif ($split_array[0] == 't') {

                }
                else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 Address should be start with "T or t"', '');
                }
            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'USDT-TRC20 address is not valid!', '');
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('usdt_trc20_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('usdt_trc20_address'));
                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 4){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }
        if (!empty($request->Input('doge_address'))) {
            $flag = 2;
            $addData['currency'] = "DOGE";
            $addData['currency_address'] = trim($request->Input('doge_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if (!empty($request->doge_address)) {
                if (strlen(trim($request->Input('doge_address'))) >= 26 && strlen(trim($request->Input('doge_address'))) <= 42) {
                    $split_array = str_split(trim($request->Input('doge_address')));
                    /*if ($split_array[0] == 3)
							{

							} elseif ($split_array[0] == 1) {

							} elseif ($split_array[0] == 'b') {

							} else {
								return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Dogecoin address is not valid!', '');
							}*/
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Dogecoin address is not valid!', '');
                }
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('doge_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('doge_address'));

                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 2){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }
        if (!empty($request->Input('ltc_address'))) {
            $flag = 2;
            $addData['currency'] = "LTC";
            $addData['currency_address'] = trim($request->Input('ltc_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if (!empty($request->btc_address)) {
                if (strlen(trim($request->Input('ltc_address'))) >= 26 && strlen(trim($request->Input('ltc_address'))) <= 50) {
                    $split_array = str_split(trim($request->Input('ltc_address')));
                    if ($split_array[0] == 3)
                    {

                    } elseif ($split_array[0] == 'L') {

                    } elseif ($split_array[0] == 'M') {

                    } elseif ($split_array1[0] == 'ltc1') {

                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address should be start with "L or M or ltc1 or 3"', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Litecoin address is not valid!', '');
                }
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('ltc_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('ltc_address'));

                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 2){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }
        if (!empty($request->Input('sol_address'))) {
            $flag = 4;
            $addData['currency'] = "SOL";
            $addData['currency_address'] = trim($request->Input('sol_address'));
            $addData['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $addBTCStatus = UserWithdrwalSetting::where([['id',$oldUserData->id],['currency',$addData['currency']],['status',1]])->first();
            if(strlen(trim($request->Input('sol_address'))) >= 26 && strlen(trim($request->Input('sol_address'))) <= 42){

            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Solana address is not valid!', '');
            }
            $new_time = \Carbon\Carbon::now()->addDays(1);
            // $token = md5(Auth::user()->user_id.$new_time);
            $addData['block_user_date_time'] = $new_time;
            /*$addData['block_user_date_time'] = $new_time;
					$addData['token'] = $token;
					$addData['token_status'] = 0;*/
            $addData['id'] = $oldUserData->id;
            if(empty($addBTCStatus)){
                $addressStatus = UserWithdrwalSetting::create($addData);
            }else if($addBTCStatus->currency_address != trim($request->Input('sol_address'))){
                /*$updateAddress['block_user_date_time'] = $new_time;
						$updateAddress['token'] = $token;
						$updateAddress['token_status'] = 0;*/
                $updateAddress['currency_address'] = trim($request->Input('sol_address'));
                $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
            }else{
                $flag = 0;
            }

            /*if($flag == 4){
						if($addressStatus){
							$res_mail = addr_updateWithdraw_stop_mail($addData['currency'],$token,Auth::user()->user_id,Auth::user()->email);
						}
					}*/
        }


        if (!empty($request->Input('bch_address')))
                {
                    $arrData['bch_address'] = trim($request->Input('bch_address'));
                    $flag = 2;
                        $addDataC['currency'] = "BCH";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('bch_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('bch_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('bch_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }


                if (!empty($request->Input('beam_address')))
                {
                    $arrData['beam_address'] = trim($request->Input('beam_address'));
                    $flag = 2;
                        $addDataC['currency'] = "BEAM";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('beam_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('beam_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('beam_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }



                if (!empty($request->Input('btg_address')))
                {
                    $arrData['btg_address'] = trim($request->Input('btg_address'));
                    $flag = 2;
                        $addDataC['currency'] = "BTG";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('btg_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('btg_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('btg_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }


                if (!empty($request->Input('xmr_address')))
                {
                    $arrData['xmr_address'] = trim($request->Input('xmr_address'));
                    $flag = 2;
                        $addDataC['currency'] = "XMR";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('xmr_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('xmr_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('xmr_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }



                if (!empty($request->Input('xvg_address')))
                {
                    $arrData['xvg_address'] = trim($request->Input('xvg_address'));
                    $flag = 2;
                        $addDataC['currency'] = "XVG";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('xvg_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('xvg_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('xvg_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }


                if (!empty($request->Input('zen_address')))
                {
                    $arrData['zen_address'] = trim($request->Input('zen_address'));
                    $flag = 2;
                        $addDataC['currency'] = "ZEN";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('zen_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('zen_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('zen_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }


                if (!empty($request->Input('etn_address')))
                {
                    $arrData['etn_address'] = trim($request->Input('etn_address'));
                    $flag = 2;
                        $addDataC['currency'] = "ETN";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('etn_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('etn_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('etn_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }


                if (!empty($request->Input('firo_address')))
                {
                    $arrData['firo_address'] = trim($request->Input('firo_address'));
                    $flag = 2;
                        $addDataC['currency'] = "FIRO";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('firo_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('firo_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('firo_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }


                if (!empty($request->Input('omni_address')))
                {
                    $arrData['omni_address'] = trim($request->Input('omni_address'));
                    $flag = 2;
                        $addDataC['currency'] = "OMNI";
                        $addDataC['id'] = Auth::user()->id;
                        $addDataC['currency_address'] = trim($request->Input('omni_address'));
                        $addDataC['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $addBTCStatus = UserWithdrwalSetting::where([['id',Auth::user()->id],['currency',$addDataC['currency']],['status',1]])->first();
                        
                        
                        $new_time = \Carbon\Carbon::now()->addDays(1);
                        $token = md5(Auth::user()->user_id.$new_time);
                        $addDataC['block_user_date_time'] = $new_time;
                        $addDataC['token'] = $token;
                        $addDataC['token_status'] = 0;
                        if(empty($addBTCStatus)){
                            //dd($addData);
                            $addressStatus = UserWithdrwalSetting::create($addDataC);

                        }else if($addBTCStatus->currency_address != trim($request->Input('omni_address'))){
                            $updateAddress['block_user_date_time'] = $new_time;
                            $updateAddress['token'] = $token;
                            $updateAddress['token_status'] = 0;
                            $updateAddress['currency_address'] = trim($request->Input('omni_address'));
                            $addressStatus = UserWithdrwalSetting::where('srno', $addBTCStatus->srno)->update($updateAddress);
                            
                        }else{
                            $flag = 0;
                        }
                }





        if (!empty($oldUserData)) {
            $updated_by = Auth::user()->id;

            $arrInsertLog = [
                'id'       => $oldUserData->id,
                'user_id'  => $oldUserData->user_id,
                'fullname' => $oldUserData->fullname,
                'email'    => $oldUserData->email,
                'mobile'   => $oldUserData->mobile,
                'city'     => $oldUserData->city,
                'address'  => $oldUserData->address,
                'country'  => $oldUserData->country,
                //'status'      => $oldUserData->status,
                'usdt_trc20_address'  => $arrData['usdt_trc20_address'],
                'usdt_erc20_address'  => $arrData['usdt_erc20_address'],
                'btc_address'           =>  $arrData['btc_address'],
                'trn_address'           =>  $arrData['trn_address'],
                'ethereum'              =>  $arrData['ethereum'],
                'ltc_address'        => $arrData['ltc_address'],
                'sol_address'        => $arrData['sol_address'],
                'doge_address' => $arrData['doge_address'],
                
                //'ethereum'    => $oldUserData->ethereum,
                'ip'         => $request->ip(),
                'updated_by' => $updated_by,
                'entry_time' => now(),
                //'ref_user_id' =>
            ];

            //save old data
            $saveOldData = UsersChangeData::insertGetId($arrInsertLog);
            // dd($saveOldData);
            if (!empty($saveOldData)) {
                $arrUpdate = [
                    'fullname' => $arrInput['fullname'],
                    'email'    => $arrInput['email'],
                    'mobile'   => $arrInput['mobile'],
                    /*'city' => $arrInput['city'],
						'address' => $arrInput['address'],*/
                    'country' => $arrInput['country'],
                    //'status'        => $arrInput['status'],
                    'btc_address' => $arrInput['btc_address'],
                    'trn_address' => $arrInput['trn_address'],
                    'ethereum'    => $arrInput['ethereum'],
                    'sol_address' => $arrInput['sol_address'],
                    'ltc_address' => $arrInput['ltc_address'],
                    'doge_address' => $arrInput['doge_address'],
                    'usdt_trc20_address' => $arrInput['usdt_trc20_address'],
                    'usdt_erc20_address' => $arrInput['usdt_erc20_address'],
                    'bch_address' => $arrData['bch_address'],
                    'beam_address' => $arrData['beam_address'],
                    'btg_address' => $arrData['btg_address'],
                    'xmr_address' => $arrData['xmr_address'],
                    'xvg_address' => $arrData['xvg_address'],
                    'zen_address' => $arrData['zen_address'],
                    'etn_address' => $arrData['etn_address'],
                    'firo_address' => $arrData['firo_address'],
                    'omni_address' => $arrData['omni_address'],
                    'bnb_address' => $arrData['bnb_address'],



                    /*'paypal_address' => $arrInput['paypal_address'],
						'perfect_money_address' => $arrInput['perfect_money_address'],*/
                    'ethereum'      => $arrInput['ethereum'],
                    //'ref_user_id' =>
                ];
                //update user with new data
                $updateData = User::where('id', $oldUserData->id)->limit(1)->update($arrUpdate);

                $addData['id'] = $oldUserData->id;
                $addData['status'] = 1;
                $addData['updated_by'] = Auth::user()->id;
                if (!empty($request->input('btc_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('btc_address',$request->input('btc_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }
                if (!empty($request->input('ethereum')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('ethereum',$request->input('ethereum'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }
                if (!empty($request->input('trn_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('trn_address',$request->input('trn_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }

                if (!empty($request->input('bnb_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('bnb_address',$request->input('bnb_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }
                if (!empty($request->input('doge_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('doge_address',$request->input('doge_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }
                if (!empty($request->input('ltc_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('ltc_address',$request->input('ltc_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }
                if (!empty($request->input('sol_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('sol_address',$request->input('sol_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }
                if (!empty($request->input('usdt_trc20_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('usdt_trc20_address',$request->input('usdt_trc20_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }
                if (!empty($request->input('usdt_erc20_address')))
                {
                    $checkAddress =  $this->checkcurrencyvalidaion('usdt_erc20_address',$request->input('usdt_erc20_address'));

                    if ($checkAddress != '')
                    {
                        $arrStatus = Response::HTTP_NOT_FOUND;
                        $arrCode   = Response::$statusTexts[$arrStatus];
                        return sendResponse($arrStatus, $arrCode, $checkAddress, '');
                    }
                }

                //update levels of user
                /*if(!empty($arrInput['ref_user_id']) && !empty($user_id)){
					$this->levelController->updateLevelView($arrInput['ref_user_id'],$user_id,1);
					 */
                if (!empty($updateData)) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User data updated successfully.', '');
                } else {
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Data already existed with given inputs.', '');
                }
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong. Please try later.', '');
            }
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
        }
    }

    public function editDate(Request $request)
    {
        $arrInput = $request->all();

        $rules     = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            $editNews = Topup::select('pin')->where('id', $arrInput['id'])->first();
            dd($editNews);
            if (!empty($editNews)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $editNews);
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'No record available', '');
            }
        }
    }

    public function updateprojectsettings(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            //'id' => 'required',
            //'fullname' => 'required|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/',
            'email' => 'required|email|max:50',
            // 'password' => 'required',
            //'mobile' => 'required',
            /*'city' => 'required',
			'address' => 'required',*/
            //'country'       => 'required',
            //'btc_address'   => 'required',
        );
        $ruleMessages = array(
            //'fullname.regex' => 'Special characters not allowed in fullname.',
        );
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            $master_pwd = User::where([['tr_passwd', '=', md5($arrInput['password'])], ['id', '=', 1]])->first();
            // dd($master_pwd);
            if (empty($master_pwd)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Incorrect Password', '');
            }

            $arrUpdate = [
                'project_name' => $arrInput['project_name'],
                'email'        => $arrInput['email'],
                'domain_name'  => $arrInput['domain_name'],
                /*'city' => $arrInput['city'],
				'address' => $arrInput['address'],*/
                'network_type' => $arrInput['network_type'],
                //'status'        => $arrInput['status'],
                'registation_plan'       => $arrInput['registation_plan'],
                'level_plan'             => $arrInput['level_plan'],
                'binary_plan'            => $arrInput['binary_plan'],
                'direct_plan'            => $arrInput['direct_plan'],
                'leadership_plan'        => $arrInput['leadership_plan'],
                'working_percentage'     => $arrInput['working_percentage'],
                'purchase_percentage'    => $arrInput['purchase_percentage'],
                'withdraw_status'        => $arrInput['withdraw_status'],
                'login_status'           => $arrInput['login_status'],
                'registration_status'    => $arrInput['registration_status'],
                'auto_withdrawal_status' => $arrInput['auto_withdrawal_status'],
                'otp_status'             => $arrInput['otp_status'],
                'sms_status'             => $arrInput['sms_status'],
                'mail_status'            => $arrInput['mail_status'],
                'copyright_at'           => $arrInput['copyright_at'],
                'app_link'               => $arrInput['app_link'],
                // 'stastic_email'               => $arrInput['stastic_email'],
                /*'paypal_address' => $arrInput['paypal_address'],
				'perfect_money_address' => $arrInput['perfect_money_address'],*/
                //'ethereum'      => $arrInput['ethereum'],
                //'ref_user_id' =>
            ];
            //update user with new data
            $updateData = ProjectSetting::where('id', 1)->limit(1)->update($arrUpdate);
            //update levels of user
            /*if(!empty($arrInput['ref_user_id']) && !empty($user_id)){
			$this->levelController->updateLevelView($arrInput['ref_user_id'],$user_id,1);
			 */
            if (!empty($updateData)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Project data updated successfully.', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Data already existed with given inputs.', '');
            }
        }
    }

    public function updateprojectsettings_other(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            //'id' => 'required',
            //'fullname' => 'required|max:30|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/',
            'email' => 'required|email|max:50',
            // 'password' => 'required',
            //'mobile' => 'required',
            /*'city' => 'required',
			'address' => 'required',*/
            //'country'       => 'required',
            //'btc_address'   => 'required',
        );
        $ruleMessages = array(
            //'fullname.regex' => 'Special characters not allowed in fullname.',
        );
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        else
        {

            // $master_pwd = User::where([['tr_passwd', '=', md5($arrInput['password'])],['id', '=',1]])->first();
            // // dd($master_pwd);
            // if (empty($master_pwd)) {
            // 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Incorrect Password', '');
            // }

            $arrUpdate = [

                'login_msg'        => $arrInput['login_msg'],
                'login_status'        => $arrInput['login_status'],
                'registration_msg'        => $arrInput['registration_msg'],
                'registration_status'        => $arrInput['registration_status'],
                'topup_msg'        => $arrInput['topup_msg'],
                'topup_status'        => $arrInput['topup_status'],
                'add_fund_msg'        => $arrInput['add_fund_msg'],
                'add_fund_status'        => $arrInput['add_fund_status'],
                'country_block_status'        => $arrInput['country_block_status'],
                'admin_side_topup_on_off_status'        => $arrInput['admin_side_topup_on_off_status'],
            ];
            //update user with new data
            $updateData = ProjectSetting::where('id', 1)->limit(1)->update($arrUpdate);
            //update levels of user
            /*if(!empty($arrInput['ref_user_id']) && !empty($user_id)){
			$this->levelController->updateLevelView($arrInput['ref_user_id'],$user_id,1);
			 */
            if (!empty($updateData)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Project data updated successfully.', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Data already existed with given inputs.', '');
            }
        }
    }
    /**
     * get all country list from commonController
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllCountry(Request $request)
    {
        $arrInput = $request->all();

        $query = Country::query();
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }

        $totalRecord = $query->count('country_id');
        $query       = $query->orderBy('country_id', 'desc');
        // $totalRecord = $query->count();
        $arrCountryData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrCountryData;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }

    /**
     * get specific user data by post parameter
     *
     * @return \Illuminate\Http\Response
     */
    public function getSpecificUserData(Request $request)
    {
        $arrInput = $request->all();
        //get user data by post data
        $arrUserData = $this->commonController->getSpecificUserData($arrInput);

        if (count($arrUserData) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User found', $arrUserData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
        }
    }

    public function changeUserBlockStatus(Request $request) {
        $id = Auth::user()->id;

        $admin = Auth::user();

            $adminaccess = $admin->admin_access;

            if (empty($admin->email)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please set email id', '');
            }

            if($adminaccess == 0 && $admin->type == "Admin")
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You Dont Have Access For This Functionality', '');
            }


        $arrInput = $request->all();
        $rules = array(
            'id' => 'required',
            'status' => 'required',
        );
       
        /** @var [ins into Change History table] */
        $user = AddFailedLoginAttempt::select('status')->where('user_id', $arrInput['id'])->where('status', $arrInput['status'])->first();
        if (empty($user)) {
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], "User Not Found", '')	;
        }

        $msg = "";
        if ($user->status == 0 || $user->status == 2) {
            $status = 1;
            $msg = 'Unblock by Admin';

            $updateStatus = array();
            $updateStatus['status'] = 1;
            $updateStatus['remark'] =  $msg;
            $unblock = AddFailedLoginAttempt::where('user_id',$arrInput['id'])->where('status',$arrInput['status'])->update($updateStatus);

            $updateUserStatus = array();
            $updateUserStatus['invalid_login_attempt'] = 0;
            $updateUserStatus['ublock_ip_address_time'] =  null;
            $updateUserStatus['login_allow_status'] =  1;
           
           $unblock_user = User::where('user_id',$arrInput['id'])->update($updateUserStatus);
        }
        if (!empty($unblock_user)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error occured while unblocking user', '');
        }
    }

    /**
     * check user excited or not by passing parameter
     *
     * @return \Illuminate\Http\Response
     */
    public function checkUserExist(Request $request)
    {
        try {
            $arrInput = $request->all();

            //validate the info, create rules for the inputs
            $rules = array(
                'user_id' => 'required',
                // 'new_user_id' => 'required',
            );
            // run the validation rules on the inputs from the form
            $validator = Validator::make($arrInput, $rules);
            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = $validator->errors();
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Input credentials is invalid or required', $message);
            } else {
                //check wether user exist or not by user_id
                $flag = false;
                if (trim(strtolower($arrInput['user_id'])) == 'admin') {
                    $check_data = User::where([['type', '!=', 'Admin'], ['ref_user_id', '!=', 0]])->count();
                    if ($check_data >= 1) {
                        return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User not available!!', '');
                    } else {
                        $flag = true;
                    }
                }
                $checkUserExist = $this->commonController->getSpecificUserData(['user_id' => $arrInput['user_id']], $flag);
                /*dd($checkUserExist);*/

                if (!empty($checkUserExist)) {
                    if ($checkUserExist->type != 'sub-admin' || $checkUserExist->type != 'sub-admin') {

                        $dash = Dashboard::where('id', $checkUserExist->id)->first();
                        // $dash = DB::table('tbl_dashboard')->where('id', $checkUserExist->id)->first();
                        // dd($dash);
                        $arrObject['topup_percentage'] = 0;

                        $fund = UserSettingFund::select('topup_percentage')->where('user_id', $checkUserExist->id)->orderBy('entry_time', 'desc')->first();
                        if (!empty($fund)) {
                            $arrObject['topup_percentage'] = $fund->topup_percentage;
                        }

                        $bvcount = CurrentAmountDetails::select('user_id', 'left_bv', 'right_bv')->where('user_id', $checkUserExist->id)->first();
                        if (!empty($bvcount)) {
                            $arrObject['balance'] = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
                            $arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
                            $arrObject['setting_fund_wallet_balance'] = $dash->setting_fund_wallet - $dash->setting_fund_wallet_withdraw;
                            $arrObject['acc_wallet'] = $dash->working_wallet - $dash->working_wallet_withdraw;
                            $arrObject['id'] = $checkUserExist->id;
                            $arrObject['remember_token'] = $checkUserExist->remember_token;
                            $arrObject['fullname'] = $checkUserExist->fullname;
                            $arrObject['username'] = $checkUserExist->fullname;
                            $arrObject['email'] = $checkUserExist->email;
                            $arrObject['power_lbv'] = $checkUserExist->power_l_bv;
                            $arrObject['power_rbv'] = $checkUserExist->power_r_bv;
                            $arrObject['l_bv'] = $checkUserExist->l_bv;
                            $arrObject['r_bv'] = $checkUserExist->r_bv;
                        } else {


                            if(!empty($dash->top_up_wallet))
                            {
                                $arrObject['balance'] = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
                            }
                            else{
                                $arrObject['balance'] = 0;

                            }

                            if(!empty($dash->fund_wallet))
                            {
                                $arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
                            }
                            else{
                                $arrObject['fund_wallet_balance'] = 0;

                            }

                            if(!empty($dash->setting_fund_wallet))
                            {
                                $arrObject['setting_fund_wallet_balance'] = $dash->setting_fund_wallet - $dash->setting_fund_wallet_withdraw;
                            }
                            else{
                                $arrObject['setting_fund_wallet_balance'] = 0;

                            }

                            $arrObject['user_id'] = $arrInput['user_id'];
                            $arrObject['id'] = $checkUserExist->id;
                            $arrObject['fullname'] = $checkUserExist->fullname;
                            $arrObject['username'] = $checkUserExist->fullname;
                            $arrObject['email'] = $checkUserExist->email;
                            $arrObject['l_bv'] = $checkUserExist->l_bv;
                            $arrObject['r_bv'] = $checkUserExist->r_bv;
                        }
                    }
                    /* else
			{*/

                    $arrObject['user_id']      = $arrInput['user_id'];
                    $arrObject['id']           = $checkUserExist->id;
                    $arrObject['fullname']     = $checkUserExist->fullname;
                    $arrObject['username']     = $checkUserExist->fullname;
                    $arrObject['l_business']   = $checkUserExist->l_bv;

                    if(!empty($dash->coin))
                    {
                        $arrObject['coin_balance'] = $dash->coin - $dash->coin_withdrawal;
                    }
                    else{
                        $arrObject['coin_balance'] = 0;

                    }
                    /*  } */

                    // /dd($arrInput['user_id'], $dash->coin, $dash->coin_withdraw);

                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User available', $arrObject);
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
                }
            }
        } catch (\Exception $e) {

            dd($e);
        }
    }

    public function checkBulkUserExist(Request $request)
    {

        try {
            $arrInput = $request->all();
            $rules = array(
                'user_id' => 'required',
            );
            // run the validation rules on the inputs from the form
            $validator = Validator::make($arrInput, $rules);
            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = $validator->errors();
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Input credentials is invalid or required', $message);
            } else {
                $userid_arr = array_filter(array_map('trim',explode(',',$request->user_id)));
               
                // $contains_empty = in_array("", $userid_arr, true);
                /*if ($contains_empty) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Empty values not allowed', $userid_arr);
				}*/
                $dups = array();
                $invalid_id_arr = array();
                $valid_id_arr = array();
                foreach(array_count_values($userid_arr) as $val => $c){
                    if($c > 1) $dups[] = $val;
                }
                $duplicate_users=implode(',',$dups);
                if (!empty($dups)) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Duplicate Users found:'.$duplicate_users, $duplicate_users);
                } else {
                    foreach ($userid_arr as $value) {
                        $checkUserExist = User::where(array('user_id' => $value))->whereNotIn('type', ['Admin','sub-admin'])->whereNotIn('ref_user_id', ['','0'])->select('id', 'user_id', 'fullname','login_allow_status','topup_status')->first();
                        
                        if (!empty($checkUserExist)) {
                            if (($checkUserExist->topup_status == '0' && $checkUserExist->login_allow_status == '1') || ($checkUserExist->topup_status == '1' && $checkUserExist->login_allow_status == '0')) {
                                array_push($valid_id_arr,$value);
                            } else {
                                $message="User ID not applicable for this topup";
                                array_push($invalid_id_arr,$value);
                            }
                        } else {
                            $message="Users Not Available";
                            array_push($invalid_id_arr,$value);
                        }
                    }
                    $invalid_users=implode(',',$invalid_id_arr);
                    $valid_users=implode(',',$valid_id_arr);
                    if (!empty($invalid_id_arr)) {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message.": ".$invalid_users, $invalid_users);
                    } else {
                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Users Available', $valid_users);
                    }

                }

                // dd($userid_arr,implode(',',$dups));
            }
        } catch (\Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong!', '');
        }
    }




    public function checkUplineUserExist(Request $request)
    {

        try {
            $arrInput = $request->all();
            //	print_r($arrInput);
            //validate the info, create rules for the inputs
            $rules = array(
                'user_id' => 'required',
                'upline_user_id' => 'required',
            );

            // run the validation rules on the inputs from the form
            $validator = Validator::make($request->all(), $rules);
            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = $validator->errors();
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Input credentials is invalid or required', $message);
            } else {
                //check wether user exist or not by user_id
                $checkUserExist = $this->commonController->getSpecificUserData(['user_id' => $arrInput['user_id']]);
                $checkUpUserExist = $this->commonController->getSpecificUserData(['user_id' => $arrInput['upline_user_id']]);

                if (!empty($checkUserExist) && !empty($checkUpUserExist)) {
                    $checkUpline = TodayDetails::where('tbl_today_details.to_user_id', $checkUpUserExist->id)->where('tbl_today_details.from_user_id', $checkUserExist->id)->count('today_id');
                    if ($checkUpline == 1) {

                        if ($checkUserExist->type != 'sub-admin' || $checkUpUserExist->type != 'sub-admin') {

                            $dash = Dashboard::where('id', $checkUpUserExist->id)->first();
                            // $stopstatus = User::select('stop_roi_status')->where('id', $checkUpUserExist->id)->first();
                            // $withstatus = User::select('auth_status')->where('id', $checkUpUserExist->id)->first();

                            // $bvcount = CurrentAmountDetails::select('user_id','left_bv','right_bv')->where('user_id', $checkUpUserExist->id)->first();
                            // if(!empty($bvcount))
                            // {
                            $arrObject['balance'] = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
                            $arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
                            $arrObject['acc_wallet'] = $dash->working_wallet - $dash->working_wallet_withdraw;
                            $arrObject['id'] = $checkUpUserExist->id;
                            $arrObject['remember_token'] = $checkUpUserExist->remember_token;
                            $arrObject['fullname'] = $checkUpUserExist->fullname;
                            $arrObject['username'] = $checkUpUserExist->fullname;
                            $arrObject['email'] = $checkUpUserExist->email;
                            $arrObject['power_lbv'] = $checkUpUserExist->power_l_bv;
                            $arrObject['power_rbv'] = $checkUpUserExist->power_r_bv;
                            $arrObject['l_bv'] = $checkUpUserExist->curr_l_bv;
                            $arrObject['r_bv'] = $checkUpUserExist->curr_r_bv;

                            // }else
                            // {
                            // 	$arrObject['balance'] = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
                            // 	$arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
                            // 	$arrObject['user_id'] = $arrInput['user_id'];
                            // 	$arrObject['id'] = $checkUpUserExist->id;
                            // 	$arrObject['fullname'] = $checkUpUserExist->fullname;
                            // 	$arrObject['username'] = $checkUpUserExist->fullname;
                            // 	$arrObject['email'] = $checkUpUserExist->email;
                            // 	$arrObject['l_bv'] = 0;
                            // 	$arrObject['r_bv'] = 0;
                            // }
                        }
                        /* else
					 	{*/

                        $arrObject['user_id'] = $checkUpUserExist->user_id;
                        $arrObject['id'] = $checkUpUserExist->id;
                        $arrObject['fullname'] = $checkUpUserExist->fullname;
                        $arrObject['username'] = $checkUpUserExist->fullname;
                        $arrObject['l_business'] = $checkUpUserExist->l_bv;
                        $arrObject['r_business'] = $checkUpUserExist->r_bv;
                        $arrObject['coin_balance'] = $dash->coin - $dash->coin_withdrawal;
                        // $arrObject['stop_roi_status'] = $stopstatus->stop_roi_status;
                        // $arrObject['auth_status'] = $withstatus->auth_status;

                        /*  } */

                        // /dd($arrInput['user_id'], $dash->coin, $dash->coin_withdraw);

                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User available', $arrObject);
                    }else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid upline ID', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
                }
            }
        } catch (\Exception $e) {


            dd($e);
        }
    }




    public function getAdminCoinDetails(Request $request)
    {
        try {
            $arrInput = $request->all();
            //dd($arrInput);
            //validate the info, create rules for the inputs
            $rules = array(
                'user_id' => 'required',
            );
            // run the validation rules on the inputs from the form

            $validator = Validator::make($request->all(), $rules);

            //$validator = Validator::make($request->all(), $rules);
            // dd($validator);
            // if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = $validator->errors();
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Input credentials is invalid or required', $message);
            } else {
                //check wether user exist or not by user_id
                $checkUserExist = $this->commonController->getSpecificUserData(['user_id' => $arrInput['user_id']]);
                //dd($checkUserExist->id);
                if (!empty($checkUserExist)) {
                    // if (($checkUserExist->type == "Super Admin") || ($checkUserExist->type == "Admin") || ($checkUserExist->type == "sub-admin")) {
                    // 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
                    // } else {

                    $dash = Dashboard::where('id', $checkUserExist->id)->first();
                    //dd($dash);
                    if (!empty($dash)) {
                        // $stopstatus = User::select('stop_roi_status')->where('id', $checkUserExist->id)->first();
                        // $withstatus = User::select('auth_status')->where('id', $checkUserExist->id)->first();

                        // $bvcount = CurrentAmountDetails::select('user_id','left_bv','right_bv')->where('user_id', $checkUserExist->id)->first();
                        // if(!empty($bvcount))
                        // {
                        $arrObject['balance']             = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
                        $arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
                        $arrObject['acc_wallet']          = $dash->working_wallet - $dash->working_wallet_withdraw;
                        $arrObject['id']                  = $checkUserExist->id;
                        $arrObject['remember_token']      = $checkUserExist->remember_token;
                        $arrObject['fullname']            = $checkUserExist->fullname;
                        $arrObject['username']            = $checkUserExist->fullname;
                        $arrObject['email']               = $checkUserExist->email;
                        $arrObject['power_lbv']           = $checkUserExist->power_l_bv;
                        $arrObject['power_rbv']           = $checkUserExist->power_r_bv;
                        $arrObject['l_bv']                = $checkUserExist->curr_l_bv;
                        $arrObject['r_bv']                = $checkUserExist->curr_r_bv;
                        $arrObject['coin_balance']        = $dash->coin - $dash->coin_withdrawal;
                        $arrObject['user_id']             = $arrInput['user_id'];
                        $arrObject['id']                  = $checkUserExist->id;
                        $arrObject['fullname']            = $checkUserExist->fullname;
                        $arrObject['username']            = $checkUserExist->fullname;
                        $arrObject['l_business']          = $checkUserExist->l_bv;
                        $arrObject['r_business']          = $checkUserExist->r_bv;
                        // }else
                        // {
                        // 	$arrObject['balance'] = $dash->top_up_wallet - $dash->top_up_wallet_withdraw;
                        // 	$arrObject['fund_wallet_balance'] = $dash->fund_wallet - $dash->fund_wallet_withdraw;
                        // 	$arrObject['user_id'] = $arrInput['user_id'];
                        // 	$arrObject['id'] = $checkUserExist->id;
                        // 	$arrObject['fullname'] = $checkUserExist->fullname;
                        // 	$arrObject['username'] = $checkUserExist->fullname;
                        // 	$arrObject['email'] = $checkUserExist->email;
                        // 	$arrObject['l_bv'] = 0;
                        // 	$arrObject['r_bv'] = 0;
                        // }
                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User available', $arrObject);
                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
                    }
                    // }

                    /* else
					{*/

                    // $arrObject['stop_roi_status'] = $stopstatus->stop_roi_status;
                    // $arrObject['auth_status'] = $withstatus->auth_status;

                    /*  } */

                    // /dd($arrInput['user_id'], $dash->coin, $dash->coin_withdraw);

                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
                }
            }
        } catch (Exception $e) {

            dd($e);
        }
    }

    public function editprofilereportBlade(){
        return view('admin.User.EditUserProfileReportComponent');
    }


    public function getUserUpdatedLog(Request $request)
    {
        $arrInput = $request->all();

        //get user data by post data
        $query = UsersChangeData::join('tbl_users as tbu', 'tbu.id', '=', 'tbl_users_change_data.updated_by')
            ->join('tbl_users as tbu1', 'tbu1.id', '=', 'tbl_users_change_data.id')
            ->join('tbl_users as tbu2', 'tbu2.id', '=', 'tbu1.ref_user_id')
            ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbl_users_change_data.country')
            ->selectRaw('tbu1.id,tbu1.user_id,tbu2.user_id as sponser_id,tbu2.fullname as sponser,tbl_users_change_data.fullname,tbl_users_change_data.mobile,tbl_users_change_data.email,cn.country,tbl_users_change_data.ip,tbu.user_id as updated_by,tbl_users_change_data.created_at,tbl_users_change_data.entry_time,
				tbu1.btc_address as new_btc_address,
				tbu1.bnb_address as new_bnb_address,
				tbu1.ethereum as new_ethereum,
				tbu1.trn_address as new_trn_address,
				tbu1.ltc_address as new_ltc_address,
				tbu1.doge_address as new_doge_address,
				tbu1.sol_address as new_sol_address,
				tbu1.usdt_trc20_address as new_usdt_trc20_address,
				tbu1.usdt_erc20_address as new_usdt_erc20_address,
				tbl_users_change_data.btc_address as old_btc_address,
				tbl_users_change_data.bnb_address as old_bnb_address,
				tbl_users_change_data.ethereum as old_ethereum,
				tbl_users_change_data.trn_address as old_trn_address,
				tbl_users_change_data.ltc_address as old_ltc_address,
				tbl_users_change_data.doge_address as old_doge_address,
				tbl_users_change_data.sol_address as old_sol_address,
				tbl_users_change_data.usdt_trc20_address as old_usdt_trc20_address,
				tbl_users_change_data.usdt_erc20_address as old_usdt_erc20_address
				')
            ->where('tbl_users_change_data.type', '');
        if (isset($arrInput['id'])) {
            $query = $query->where('tbl_users_change_data.user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users_change_data.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = ['tbu1.user_id', 'tbu2.user_id', 'tbu2.fullname', 'tbl_users_change_data.fullname', 'tbl_users_change_data.mobile', 'tbl_users_change_data.email', 'cn.country', 'tbl_users_change_data.trn_address', 'tbl_users_change_data.ethereum', 'tbl_users_change_data.ip', 'tbu.user_id'];
            $search = $arrInput['search']['value'];
            $query = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry = $query;
            $qry = $qry->select('tbu1.user_id', 'tbu1.fullname', 'tbl_users_change_data.mobile', 'tbl_users_change_data.trn_address as tron address', 'tbl_users_change_data.btc_address as old_btc_address', 'tbl_users_change_data.btc_address as new_btc_address', 'tbl_users_change_data.bnb_address as new_bnb_address', 'tbl_users_change_data.ethereum as old_ethereum', 'tbl_users_change_data.ethereum as new_ethereum', 'tbl_users_change_data.trn_address as old_trn_address', 'tbl_users_change_data.trn_address as new_trn_address', 'tbl_users_change_data.entry_time');
            // $qry = $qry->selectRaw('tbu1.user_id', 'tbu1.fullname','tbl_users_change_data.mobile', 'tbl_users_change_data.trn_address as tron address','tbl_users_change_data.email','cn.country','tbl_users_change_data.btc_address','tbl_users_change_data.trn_address as new_trn_address','tbl_users_change_data.ip','tbu.user_id as updated_by','tbl_users_change_data.created_at','tbl_users_change_data.entry_time','tbl_users_change_data.trn_address', 'tbl_users_change_data.bnb_address', 'tbl_users_change_data.btc_address as old_btc_address', 'tbl_users_change_data.btc_address as new_btc_address', 'tbl_users_change_data.bnb_address as old_bnb_address', 'tbl_users_change_data.bnb_address as new_bnb_address', 'tbl_users_change_data.ethereum as old_ethereum','tbl_users_change_data.ethereum as new_ethereum', 'tbl_users_change_data.trn_address as old_trn_address');
            $records = $qry->get();
            $res = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res, "AllUsers");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
        }

        $query = $query->whereRaw('tbl_users_change_data.sr_no IN (select MAX(sr_no) FROM tbl_users_change_data GROUP BY id)');
        $query = $query->orderBy('tbl_users_change_data.sr_no', 'desc');

        if (isset($arrInput['start']) && isset($arrInput['length'])) {
            $arrData = setPaginate1($query, $arrInput['start'], $arrInput['length']);
        } else {
            $arrData = $query->get();
        }

        /*$totalRecord    = $query->get()->count();
			         $arrUserlogData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

			         $arrData['recordsTotal']    = $totalRecord;
			         $arrData['recordsFiltered'] = $totalRecord;
		*/

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }


    /**
     * get user updated data log
     *
     * @return \Illuminate\Http\Response
     */
    // public function getUserUpdatedLog(Request $request)
    // {
    // 	$arrInput = $request->all();

    // 	//get user data by post data
    // 	$query = DB::table('tbl_users_change_data as tbucd')
    // 		->join('tbl_users as tbu', 'tbu.id', '=', 'tbucd.updated_by')
    // 		->join('tbl_users as tbu1', 'tbu1.id', '=', 'tbucd.id')
    // 		->join('tbl_users as tbu2', 'tbu2.id', '=', 'tbu1.ref_user_id')
    // 		->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbucd.country')
    // 		->selectRaw('tbu1.id,tbu1.user_id,tbu2.user_id as sponser_id,tbu2.fullname as sponser,tbucd.fullname,tbucd.mobile,tbucd.email,cn.country,tbu1.btc_address as new_btc_address,tbu1.bnb_address as new_bnb_address,tbu1.trn_address as new_trn_address,tbu1.ethereum as new_ethereum,tbucd.btc_address as old_btc_address,tbucd.bnb_address as old_bnb_address,tbucd.trn_address as old_trn_address,tbucd.ethereum as old_ethereum,tbucd.ip,tbu.user_id as updated_by,tbucd.created_at,tbucd.entry_time')
    // 		->where('tbucd.type', '');
    // 		//dd($query);
    // 	if (isset($arrInput['id'])) {
    // 		$query = $query->where('tbu1.user_id', $arrInput['id']);
    // 	}
    // 	if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
    // 		$arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
    // 		$arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
    // 		$query = $query->whereBetween(DB::raw("DATE_FORMAT(tbucd.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
    // 	}
    // 	if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
    // 		$qry = $query;
    // 		//$qry = $qry->selectRaw('tbu1.id,tbu1.user_id,tbu2.user_id as sponser_id,tbu2.fullname as sponser,tbucd.fullname,tbucd.mobile,tbucd.email,cn.country,tbu1.btc_address as new_btc_address,tbu1.bnb_address as new_bnb_address,tbu1.trn_address as new_trn_address,tbu1.ethereum as new_ethereum,tbucd.btc_address as old_btc_address,tbucd.bnb_address as old_bnb_address,tbucd.trn_address as old_trn_address,tbucd.ethereum as old_ethereum,tbucd.ip,tbu.user_id as updated_by,tbucd.created_at,tbucd.entry_time');
    // 		//dd($qry);
    // 		$qry = $qry->select('tbu1.id','tbu1.user_id', 'tbu2.user_id as sponser_id','tbu2.fullname as sponser', 'tbucd.fullname', 'tbucd.mobile', 'tbucd.email', 'cn.country', 'tbu1.btc_address as new_btc_address', 'tbu1.bnb_address as new_bnb_address', 'tbu1.trn_address as new_trn_address', 'tbu1.ethereum as new_ethereum', 'tbucd.btc_address as old_btc_address', 'tbucd.bnb_address as old_bnb_address', 'tbucd.trn_address as old_trn_address', 'tbucd.ethereum as old_ethereum', 'tbucd.ip', 'tbu.user_id as updated_by', 'tbucd.created_at', 'tbucd.entry_time');
    // 		$records = $qry->get();
    // 		$res = $records->toArray();

    // 		dd($res);
    // 		if (count($res) <= 0) {
    // 			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
    // 		}
    // 		$var = $this->commonController->exportToExcel($res,"AllUsers");
    // 		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
    // 	}

    // 	// if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
    // 	// 	//searching loops on fields
    // 	// 	$fields = ['tbu1.user_id', 'tbu2.user_id', 'tbu2.fullname', 'tbucd.fullname', 'tbucd.mobile', 'tbucd.email', 'cn.country', 'tbucd.btc_address', 'tbucd.ethereum', 'tbucd.ip', 'tbu.user_id'];
    // 	// 	$search = $arrInput['search']['value'];
    // 	// 	$query = $query->where(function ($query) use ($fields, $search) {
    // 	// 		foreach ($fields as $field) {
    // 	// 			$query->orWhere($field, 'LIKE', '%' . $search . '%');
    // 	// 		}
    // 	// 	});
    // 	// }
    // 	$query = $query->whereRaw('tbucd.sr_no IN (select MAX(sr_no) FROM tbl_users_change_data GROUP BY id)');
    // 	$query = $query->orderBy('tbucd.sr_no', 'desc');

    // 	if (isset($arrInput['start']) && isset($arrInput['length'])) {
    // 		$arrData = setPaginate1($query, $arrInput['start'], $arrInput['length']);
    // 	} else {
    // 		$arrData = $query->get();
    // 	}

    // 	/*$totalRecord    = $query->get()->count();
    // 		         $arrUserlogData = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

    // 		         $arrData['recordsTotal']    = $totalRecord;
    // 		         $arrData['recordsFiltered'] = $totalRecord;
    // 	*/

    // 	if ($arrData['recordsTotal'] > 0) {
    // 		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
    // 	} else {
    // 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
    // 	}
    // }

    /**
     * get user password details
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserPassword(Request $request)
    {
        $arrInput = $request->all();

        $rules     = array('user_id' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
        } else {
            $objPasswordData = $this->commonController->getSpecificUserData(['user_id' => $arrInput['user_id']]);

            if (count($objPasswordData) > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User available', $objPasswordData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
            }
        }
    }

    /**
     * update user password with new password
     *
     * @return \Illuminate\Http\Response
     */
    public function updateUserPassword(Request $request)
    {
        $arrInput = $request->all();
        // validate the info, create rules for the inputs
        $rules = array(
            'id'       => 'required',
            'password' => [
                'string',
                'min:6', // must be at least 10 characters in length
                'regex:/[a-z]/', // must contain at least one lowercase letter
                'regex:/[A-Z]/', // must contain at least one uppercase letter
                'regex:/[0-9]/', // must contain at least one digit
                'regex:/[@$!%*#?&]/'
            ], // regex:/^[a-zA-Z](?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%]{6,50}$/|min:6|max:50
            'confirm_password' => 'required|same:password',
            'otp' => 'required|min:10|max:10',
        );

        // $ruleMessages = array(
        //     'password.regex' => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *'
        // );
        // run the validation rules on the inputs form the form
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            $arrInput['user_id'] = Auth::User()->id;
            $arrInput['remark'] = 'admin_change_password';
            $arrInput['otp'] = $request->otp;
            $verify_otp = verify_Admin_Withdraw_Otp($arrInput);

            if (!empty($verify_otp)) {
                if ($verify_otp['status'] == 200) {
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            }

            $arrUpdate = [
                'password'        => Crypt::encrypt($arrInput['password']),
                'bcrypt_password' => bcrypt($arrInput['password']),
            ];
            $updatePass = User::where('id', $arrInput['id'])->update($arrUpdate);

            if (!empty($updatePass)) {
                $arrSendMail = [
                    'to_mail'  => $this->commonController->getSpecificUserData(['id'  => $arrInput['id']])->email,
                    'pagename' => 'emails.admin-emails.updateuserpassreply',
                    'msg'      => 'Password has been updated by Administrator. Please contact for any query',
                    'subject'  => 'Password update alert',
                ];
                sendMail($arrSendMail, $arrSendMail['to_mail'], $arrSendMail['subject']);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Password updated successfully', '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data already existed with given inputs', '');
            }
        }
    }

    /**
     * get user profile details
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserProfileDetails(Request $request)
    {
        $arrInput = $request->all();

        $id = Auth::user()->id;

        //get user about data (personal data)
        $userProfile = DB::table('tbl_users as tu1')
            ->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tu1.ref_user_id')
            ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu1.country')
            ->where([['tu1.type', '=', ''], ['tu1.id', '=', $arrInput['id']]])
            ->select('tu1.*', 'tu2.user_id as sponser_id', 'cn.country', 'cn.iso_code')
            ->first();

        $userProfiledata = array();



        $btcadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'BTC')->select('currency_address')->first();
        $ethadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'ETH')->select('currency_address')->first();
        $bnbadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'BNB')->select('currency_address')->first();
        $trxadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'TRX')->select('currency_address')->first();
        $usdtadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'USDT-TRC20')->select('currency_address')->first();
        $usdt_erc_add = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'USDT-ERC20')->select('currency_address')->first();
        $ltcadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'LTC')->select('currency_address')->first();
        $soladd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'SOL')->select('currency_address')->first();
        $dogeadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'DOGE')->select('currency_address')->first();

        if(empty($btcadd))	{
            $userProfiledata['btc_address'] = '';
        }
        else {
            $userProfiledata['btc_address'] = $btcadd->currency_address;
        }

        if(empty($ethadd))	{
            $userProfiledata['ethereum'] = '';
        }
        else {
            $userProfiledata['ethereum'] = $ethadd->currency_address;
        }

        if(empty($trxadd))	{
            $userProfiledata['trn_address'] = '';
        }
        else {
            $userProfiledata['trn_address'] = $trxadd->currency_address;
        }

        if(empty($bnbadd))	{
            $userProfiledata['bnb_address'] = '';
        }
        else {
            $userProfiledata['bnb_address'] = $bnbadd->currency_address;
        }

        if(empty($usdtadd))	{
            $userProfiledata['usdt_trc20_address'] = '';
        }
        else {
            $userProfiledata['usdt_trc20_address'] = $usdtadd->currency_address;
        }
        if(empty($usdt_erc_add))	{
            $userProfiledata['usdt_erc20_address'] = '';
        }
        else {
            $userProfiledata['usdt_erc20_address'] = $usdt_erc_add->currency_address;
        }
        if(empty($ltcadd))	{
            $userProfiledata['ltc_address'] = '';
        }
        else {
            $userProfiledata['ltc_address'] = $ltcadd->currency_address;
        }

        if(empty($soladd))	{
            $userProfiledata['sol_address'] = '';
        }
        else {
            $userProfiledata['sol_address'] = $soladd->currency_address;
        }

        if(empty($dogeadd))	{
            $userProfiledata['doge_address'] = '';
        }
        else {
            $userProfiledata['doge_address'] = $dogeadd->currency_address;
        }


        // dd($userProfile);

        $userProfiledata['entry_time'] = $userProfile->entry_time;
        $userProfiledata['user_id'] = $userProfile->user_id;
        $userProfiledata['ref_user_id'] = $userProfile->ref_user_id;
        $userProfiledata['mobile'] = $userProfile->mobile;
        $userProfiledata['email'] = $userProfile->email;
        $userProfiledata['fullname'] = $userProfile->fullname;
        $userProfiledata['position'] = $userProfile->position;
        $userProfiledata['sponser_id'] = $userProfile->sponser_id;
        $userProfiledata['country'] = $userProfile->country;
        $userProfiledata['iso_code'] = $userProfile->iso_code;




        //get user data by post data
        $getUserLogs = DB::table('tbl_users_change_data as tbucd')
            ->selectRaw('tbucd.id,tbucd.fullname,tbucd.mobile,tbucd.btc_address,tbucd.ethereum,tbucd.sol_address,tbucd.trn_address,tbucd.ltc_address,tbucd.doge_address,tbucd.usdt_trc20_address,tbucd.usdt_erc20_address,tbucd.ref_user_id as sponser_id,tbucd.ip,tu1.fullname as updated_by,tbucd.created_at,tbucd.entry_time,cn.country,tu2.user_id as sponser')
            ->leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbucd.updated_by')
            ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbucd.country')
            ->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbucd.ref_user_id')
            ->orderBy('tbucd.entry_time', 'desc')
            ->get();

        //get user activity notifications
        $userActivityNotify = Activitynotification::where([['id', $arrInput['id']], ['status', 1]])->orderBy('entry_time', 'desc')->get();

        //get user deposite addresses
        $userDepositeAddr = Depositaddress::where([['id', $arrInput['id']], ['status', 1]])->orderBy('entry_time', 'desc')->get();

        //get user deposite addresses Transactions
        $userDepositeAddrTransConfrm = AddressTransaction::where('id', $arrInput['id'])->orderBy('entry_time', 'desc')->get();

        //get user deposite addresses Transactions pending
        $userDepositeAddrTransPend = AddressTransactionPending::where('id', $arrInput['id'])->orderBy('entry_time', 'desc')->get();

        //get user all Transactions
        $coin_name        = $this->commonController->getProjectSettings()->coin_name;
        $userAllTransCoin = AllTransaction::where([['id', '=', $arrInput['id']], ['status', '=', 1], ['network_type', '=', $coin_name]])->orderBy('entry_time', 'desc')->get();

        //get user all Transactions
        $userAllTransBTC = AllTransaction::where([['id', '=', $arrInput['id']], ['status', '=', 1], ['network_type', '=', 'BTC']])->orderBy('entry_time', 'desc')->get();

        //get user all Transactions
        $userAllTransUSD = AllTransaction::where([['id', '=', $arrInput['id']], ['status', '=', 1], ['network_type', '=', 'USD']])->orderBy('entry_time', 'desc')->get();

        //get user dashboard data
        $userDashboard = Dashboard::where('id', $arrInput['id'])->first();

        $arrFinalData['userProfile']             = $userProfiledata;
        $arrFinalData['userDashboard']           = $userDashboard;
        $arrFinalData['userLogs']                = $getUserLogs;
        $arrFinalData['userActivityNotifi']      = $userActivityNotify;
        $arrFinalData['depositeAddr']            = $userDepositeAddr;
        $arrFinalData['depositeAddrTransConfrm'] = $userDepositeAddrTransConfrm;
        $arrFinalData['depositeAddrTransPend']   = $userDepositeAddrTransPend;
        $arrFinalData['allTransactionCoin']      = $userAllTransCoin;
        $arrFinalData['allTransactionBTC']       = $userAllTransBTC;
        $arrFinalData['allTransactionUSD']       = $userAllTransUSD;

        if (count($arrFinalData) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrFinalData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function getProjectDetails(Request $request)
    {
        $arrInput = $request->all();

        //get user dashboard data
        $pro_setting = ProjectSetting::where('id', 1)->first();

        $arrFinalData['projectsetting'] = $pro_setting;

        if (count($arrFinalData) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrFinalData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    /**
     * get admin profile details
     *
     * @return \Illuminate\Http\Response
     */
    public function getAdminProfileDetails(Request $request)
    {

        $objAdminDetails = User::select('user_id', 'fullname', 'email', 'mobile', 'gender', 'tcn.country')
            ->leftjoin('tbl_country_new as tcn', 'tcn.iso_code', '=', 'tbl_users.country')
            ->where('remember_token', $request->remember_token)
            ->where('type', 'Admin')
            ->first();

        if (!empty($objAdminDetails)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $objAdminDetails);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    /**
     * get 2fa status of users
     *
     * @return \Illuminate\Http\Response
     */
    public function update2faUserStatus(Request $request)
    {
        $arrInput = $request->all();

        // validate the info, create rules for the inputs
        $rules     = array('id' => 'required', '2fa_status' => 'required');
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
        } else {
            $arrUpdateData = [
                'google2fa_status' => $arrInput['2fa_status'] == 'true' ? 'enable' : 'disable',
            ];
            $update = User::where('id', $arrInput['id'])->update($arrUpdateData);

            if (!empty($update)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record updated succesfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error occured while updating status', '');
            }
        }
    }

    /**
     * [ip_track description]  Admin API Service
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function ipTrack(Request $request)
    {
        $rules = array(
            'user_id' => 'required',
        );
        $messsages = array(
            'user_id.required' => 'Please enter user Id.',
        );

        $validator = Validator::make($request->all(), $rules, $messsages);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err     = '';
            foreach ($message->all() as $error) {
                if (count($message->all()) > 1) {
                    $err = $err . ' ' . $error;
                } else {
                    $err = $error;
                }
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
        }

        $user_id     = trim($request->get("user_id"));
        $isExistUser = User::Select("id")->Where("id", $user_id)->first();
        if (!is_null($isExistUser)) {
            $query = IpTrack::select('users.user_id As user', 'users.id As user_id', 'ip_tack.hostname', 'ip_tack.ipaddress', 'ip_tack.rec_date', 'ip_tack.forward', 'ip_tack.status')
                ->leftJoin('users', 'users.id', '=', 'ip_tack.user_id')
                ->where('ip_tack.user_id', $user_id);

            $data = setPaginate($query, $request->start, $request->length);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Successful!', $data);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[401]['status'], 'User Id does not exist.', '');
        }
    }

    /**
     * [ip_track description]  Admin API Service
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function ip_track(Request $request)
    {
        $rules = array(
            'user_id' => 'required',
        );
        $messsages = array(
            'user_id.required' => 'Please enter user Id.',
        );

        $validator = Validator::make($request->all(), $rules, $messsages);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err     = '';
            foreach ($message->all() as $error) {
                if (count($message->all()) > 1) {
                    $err = $err . ' ' . $error;
                } else {
                    $err = $error;
                }
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
        }

        $user_id     = trim($request->get("user_id"));
        $isExistUser = User::Select("id")->Where("id", $user_id)->first();
        if (!is_null($isExistUser)) {
            $query = IpTrack::select('users.user_id As user', 'users.id As user_id', 'ip_tack.hostname', 'ip_tack.ipaddress', 'ip_tack.rec_date', 'ip_tack.forward', 'ip_tack.status')
                ->leftJoin('tbl_users.users', 'users.id', '=', 'ip_tack.user_id')
                ->where('ip_tack.user_id', $user_id);

            $data = setPaginate($query, $request->start, $request->length);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Successful!', $data);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[401]['status'], 'User Id does not exist.', '');
        }
    }
    /**
     * to store representative data
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function storeRepresentative(Request $request)
    {
        $rules = array(
            'user_name' => 'required',
            'name'      => 'required',
            'email'     => 'required',
            'mobile'    => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {
            $arrInsert = [
                'user_name'     => ($request->user_name) ? $request->user_name : '',
                'mobile'        => ($request->mobile) ? $request->mobile : '',
                'name'          => ($request->name) ? $request->name : '',
                'email'         => ($request->email) ? $request->email : '',
                'country'       => ($request->country) ? $request->country : '',
                'language'      => ($request->language) ? $request->language : '',
                'facebook_id'   => ($request->facebook_id) ? $request->facebook_id : '',
                'sky_d'         => ($request->sky_d) ? $request->sky_d : '',
                'twitter_id'    => ($request->twitter_id) ? $request->twitter_id : '',
                'telegram_id'   => ($request->telegram_id) ? $request->telegram_id : '',
                'instagram_id'  => ($request->instagram_id) ? $request->instagram_id : '',
                'admin_status'  => 'Approved',
                'entry_time'    => now(),
                'approved_time' => now(),
                'status'        => '0',
            ];
            $storeId = Representative::insertGetId($arrInsert);

            if (!empty($storeId)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Representative added successfully', '');
            } else {
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Error occured while adding record', '');
            }
        }
    }
    /**
     * to update representative data
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updateRepresentative(Request $request)
    {
        $rules = array(
            'id'        => 'required',
            'user_name' => 'required',
            'mobile'    => 'required',
            'name'      => 'required',
            'email'     => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {
            $arrUpdate = [
                'user_name'    => ($request->user_name) ? $request->user_name : '',
                'mobile'       => ($request->mobile) ? $request->mobile : '',
                'name'         => ($request->name) ? $request->name : '',
                'email'        => ($request->email) ? $request->email : '',
                'country'      => ($request->country) ? $request->country : '',
                'language'     => ($request->language) ? $request->language : '',
                'facebook_id'  => ($request->facebook_id) ? $request->facebook_id : '',
                'sky_d'        => ($request->sky_d) ? $request->sky_d : '',
                'twitter_id'   => ($request->twitter_id) ? $request->twitter_id : '',
                'telegram_id'  => ($request->telegram_id) ? $request->telegram_id : '',
                'instagram_id' => ($request->instagram_id) ? $request->instagram_id : '',
                /*'admin_status'      => 'Approved',
			'entry_time'        => now(),
			'approved_time'     => now(),
			 */
            ];
            $update = Representative::where('id', trim($request->id))->update($arrUpdate);

            if (!empty($update)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Representative updated successfully', '');
            } else {
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Error occured while updating record', '');
            }
        }
    }
    /**
     * to delete representative data
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function deleteRepresentative(Request $request)
    {
        $rules = array(
            'id' => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {
            $arrUpdate = [
                'status' => '1',
            ];
            $update = Representative::where('id', trim($request->id))->update($arrUpdate);

            if (!empty($update)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Representative deleted successfully', '');
            } else {
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Error occured while deleteing record', '');
            }
        }
    }
    /**
     * get representative report
     *
     * @return \Illuminate\Http\Response
     */
    public function showRepresentative(Request $request)
    {
        $arrInput = $request->all();

        //get user data by post data
        $query = Representative::leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbl_representative.country')
            ->select('tbl_representative.*', 'cn.country', 'cn.iso_code')
            ->where('tbl_representative.status', '0');

        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_representative.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = getTableColumns('tbl_representative');
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere('tbl_representative.' . $field, 'LIKE', '%' . $search . '%');
                }
                $query->orWhere('cn.country', 'LIKE', '%' . $search . '%');
            });
        }
        $totalRecord = $query->count('tbl_representative.id');
        $query       = $query->orderBy('tbl_representative.id', 'desc');
        // $totalRecord = $query->count();
        $arrRepresentative = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrRepresentative;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }
    /**
     * get user dashboard details
     *
     * @return \Illuminate\Http\Response
     */
    public function getActiveInactiveUsers(Request $request)
    {
        $arrInput = $request->all();

        $query = Dashboard::join('tbl_users as tu', 'tu.id', '=', 'tbl_dashboard.id')
            ->select('tbl_dashboard.srno', 'tbl_dashboard.total_investment', 'tu.user_id', 'tu.fullname', 'tu.mobile', 'tu.email');
        if (isset($arrInput['id'])) {
            $query = $query->where('tu.user_id', $arrInput['id']);
        }
        if (isset($arrInput['status'])) {
            if ($arrInput['status'] == 'Active') {
                $query = $query->where('tbl_dashboard.total_investment', '>', 0);
            } else if ($arrInput['status'] == 'Inactive') {
                $query = $query->where('tbl_dashboard.total_investment', '=', 0);
            }
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_dashboard.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = ['tbl_dashboard.total_investment', 'tu.user_id', 'tu.fullname', 'tu.mobile', 'tu.email'];
            $search = $arrInput['search']['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $totalRecord = $query->count('tbl_dashboard.id');
        $query       = $query->orderBy('tbl_dashboard.entry_time', 'desc');
        // $totalRecord = $query->count();
        $arrDashboard = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrDashboard;

        if (count($arrDashboard) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function imageAdd(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            'file' => 'max:4',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        // $fileName = 'userpanellogo'.'.'.$request->file->getClientOriginalExtension();
        // $request->file->move(public_path('logos/Userpanellogo/'), $fileName);
        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User panel logo added', '');
        //return response()->json(['success'=>'You have successfully upload file.']);
    }

    public function iconAdd(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            'file' => 'max:4',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        // $fileName = 'userpanelicon'.'.'.$request->file->getClientOriginalExtension();
        // $request->file->move(public_path('icons/userpanelicon/'), $fileName);
        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User panel icon added', '');
        //return response()->json(['success'=>'You have successfully upload file.']);
    }

    public function adminiconAdd(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            'file' => 'max:4',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        // $fileName = 'adminicon'.'.'.$request->file->getClientOriginalExtension();
        // $request->file->move(public_path('icons/adminpanelicon/'), $fileName);
        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Admin panel icon added', '');
    }

    public function adminimageAdd(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            'file' => 'max:4',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        // $fileName = 'adminlogo'.'.'.$request->file->getClientOriginalExtension();
        // $request->file->move(public_path('logos/Adminpanellogo/'), $fileName);
        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Admin panel logo added', '');

        //return response()->json(['success'=>'You have successfully upload file.']);
    }

    /**
     * [userProfile as per userid]
     * @param  Request $request [user_id]
     * @return [Array]           [User Data]
     */
    public function userProfile(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            'id' => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        $getuserdata = User::select('tbl_users.*', 'cn.iso_code', 'tu.user_id as ref_user_id', 'tu.fullname as sponser_id', DB::raw('(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" WHEN 3 THEN "Right" ELSE "" END) as position'), 'tbl_users.btc_address','tbl_users.trn_address','tbl_users.ethereum','tbl_users.sol_address','tbl_users.ltc_address','tbl_users.doge_address','tbl_users.usdt_trc20_address','cn.country', DB::raw('DATE_FORMAT(tbl_users.entry_time,"%Y/%m/%d %H:%i:%s") as entry_time'))
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tbl_users.country')
            ->where('tbl_users.id', $arrInput['id'])
            ->first();

        $btcadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'BTC')->select('currency_address')->first();
        $ethadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'ETH')->select('currency_address')->first();
        $bnbadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'BNB')->select('currency_address')->first();
        $trxadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'TRX')->select('currency_address')->first();
        $usdtadd = UserWithdrwalSetting::where('id',$arrInput['id'])->where('currency', 'USDT.TRC20')->select('currency_address')->first();
        $ltcadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'LTC')->select('currency_address')->first();
        $soladd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'SOL')->select('currency_address')->first();
        $dogeadd = UserWithdrwalSetting::where('id', $arrInput['id'])->where('currency', 'DOGE')->select('currency_address')->first();

        $getuser = array();

        if(empty($btcadd))	{
            $getuser['btc_address'] = '';
        }
        else {
            $getuser['btc_address'] = $btcadd->currency_address;
        }

        if(empty($ethadd))	{
            $getuser['ethereum'] = '';
        }
        else {
            $getuser['ethereum'] = $ethadd->currency_address;
        }

        if(empty($bnbadd))	{
            $getuser['bnb_address'] = '';
        }
        else {
            $getuser['bnb_address'] = $bnbadd->currency_address;
        }

        if(empty($trxadd))	{
            $getuser['trn_address'] = '';
        }
        else {
            $getuser['trn_address'] = $trxadd->currency_address;
        }

        if(empty($usdtadd))	{
            $getuser['usdt_trc20_address'] = '';
        }
        else {
            $getuser['usdt_trc20_address'] = $usdtadd->currency_address;
        }
        if(empty($ltcadd))	{
            $getuser['ltc_address'] = '';
        }
        else {
            $getuser['ltc_address'] = $ltcadd->currency_address;
        }

        if(empty($soladd))	{
            $getuser['sol_address'] = '';
        }
        else {
            $getuser['sol_address'] = $soladd->currency_address;
        }

        if(empty($dogeadd))	{
            $getuser['doge_address'] = '';
        }
        else {
            $getuser['doge_address'] = $dogeadd->currency_address;
        }
// dd($getuserdata);
        $getuser['entry_time'] = $getuserdata->entry_time;
        $getuser['user_id'] = $getuserdata->user_id;
        $getuser['ref_user_id'] = $getuserdata->ref_user_id;
        $getuser['mobile'] = $getuserdata->mobile;
        $getuser['email'] = $getuserdata->email;
        $getuser['fullname'] = $getuserdata->fullname;
        $getuser['position'] = $getuserdata->position;
        $getuser['btc_address'] = $getuserdata->btc_address;
        $getuser['ltc_address'] = $getuserdata->ltc_address;
        $getuser['trn_address'] = $getuserdata->trn_address;
        $getuser['sol_address'] = $getuserdata->sol_address;
        $getuser['usdt_trc20_address'] = $getuserdata->usdt_trc20_address;
        $getuser['ethereum'] = $getuserdata->ethereum;
        $getuser['doge_address'] = $getuserdata->doge_address;






        if (!empty($getuser)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $getuser);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    } // fn userProfile

    /**
     * Block User
     *
     * @return void
     */
    public function blockUser(Request $request)
    {
        $id       = Auth::user()->id;

            $admin = Auth::user();

            $adminaccess = $admin->admin_access;

            if($adminaccess == 0 && $admin->type == "Admin")
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You Dont Have Access For This Functionality', '');
            }

        $arrInput = $request->all();
        $rules    = array(
            'id'     => 'required',
            'status' => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            /** @var [ins into Change History table] */
            if ($arrInput['status'] == 'Active') {
                $do     = 'block';
                $status = 'Inactive';
                $msg    = 'User  blocked successfully';
            } else {
                $do     = 'unblock';
                $status = 'Active';
                $msg    = 'User  unblocked successfully';
            }

            $block = User::where('id', $arrInput['id'])->update(['status' => $status, 'block_entry_time' => now()]);
            if (!empty($block)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error occured while blocking user', '');
            }
        }
    }

    /**
     * Block User
     *
     * @return void
     */
    public function verifyUser(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            'id'     => 'required',
            'verify' => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            /** @var [ins into Change History table] */
            if ($arrInput['verify'] == 0) {
                $do     = 'verify';
                $status = 1;
                $msg    = 'User  verifyed successfully';
            } else {
                $do     = 'unverify';
                $status = 0;
                $msg    = 'User  unverify';
            }

            $block = WithdrawPending::where('sr_no', $arrInput['id'])->update(['verify' => $status]);
            if (!empty($block)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error occured while blocking user', '');
            }
        }
    }

    /**
     ** Change Withdraw Status
     **/

    public function changeUserWithdrawStatus(Request $request) {
        $id = Auth::user()->id;
        $arrInput = $request->all();
        $rules = array(
            'id' => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            /** @var [ins into Change History table] */
            $user = User::select('withdraw_block_by_admin')->where('id', $arrInput['id'])->first();
            if (empty($user)) {
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], "User Not Found", '');
            }
            if ($user->withdraw_block_by_admin == 1) {
                $status = 0;
                $msg = 'Withdraw status updated';
            } else {
                $status = 1;
                $msg = 'Withdraw status updated';
            }

            $block = User::where('id', $arrInput['id'])->update(['withdraw_block_by_admin' => $status]);
            if (!empty($block)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Something went wrong', '');
            }
        }
    }

    /**
     ** Add Power
     ** @param Request obj
     **
     ** @return json
     **/
    public function addPowerBlade(){

        return view('admin.AddPower.index');


    }

    public function addPower(Request $request)
    {

        $arrInput = $request->all();

        $rules     = array('id' => 'required', 'position' => 'required', 'power_bv' => 'required|integer', 'otp' => 'required',);
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {
            $arrInput['user_id'] = Auth::User()->id;
            $arrInput['remark'] = 'admin power';
            $arrInput['otp'] = $request->otp;
            $verify_otp = verify_Admin_Withdraw_Otp($arrInput);
            if (!empty($verify_otp)) {
                if ($verify_otp['status'] == 200) {
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            }



            $user       = User::where('id', $arrInput['id'])->first();
            $before_lbv = 0;
            $before_rbv = 0;
            if (!empty($user)) {

                // if ($user->power_l_bv > 0 && $request->position == 2) {
                // 	return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Cannot add power to right', '');
                // } else if ($user->power_r_bv > 0 && $request->position == 1) {
                // 	return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Cannot add power to left', '');
                // }

                $before_lbv       = $user->l_bv;
                $before_rbv       = $user->r_bv;
                $before_power_lbv = $user->manual_power_lbv;
                $before_power_rbv = $user->manual_power_rbv;
                $position         = $arrInput['position'];
                $powerbv          = $arrInput['power_bv'];
                $new_lbv          = 0;
                $new_rbv          = 0;

                if ($position == 1) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {
                        if ($before_rbv >= $powerbv && $before_power_rbv >= $powerbv) {
                            $new_lbv       = $before_lbv - $powerbv;
                            $new_rbv       = $before_rbv;
                            $new_power_lbv = $before_power_lbv - $powerbv;
                            $new_power_rbv = $before_power_rbv;
                        } else {
                            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User dont have too much power to remove ', '');
                        }
                    } else {
                        $new_lbv       = $before_lbv + $powerbv;
                        $new_rbv       = $before_rbv;
                        $new_power_lbv = $before_power_lbv + $powerbv;
                        $new_power_rbv = $before_power_rbv;
                    }
                } elseif ($position == 2) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {
                        if ($before_rbv >= $powerbv && $before_power_rbv >= $powerbv) {
                            $new_lbv       = $before_lbv;
                            $new_rbv       = $before_rbv - $powerbv;
                            $new_power_lbv = $before_power_lbv;
                            $new_power_rbv = $before_power_rbv - $powerbv;
                        } else {
                            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User dont have too much power to remove ', '');
                        }
                    } else {
                        $new_lbv       = $before_lbv;
                        $new_rbv       = $before_rbv + $powerbv;
                        $new_power_rbv = $before_power_rbv + $powerbv;
                        $new_power_lbv = $before_power_lbv;
                    }
                }

                $user->l_bv       = $new_lbv;
                $user->r_bv       = $new_rbv;
                $user->manual_power_lbv = $new_power_lbv;
                $user->manual_power_rbv = $new_power_rbv;

                // dd($user->l_bv,$user->r_bv,$user->power_l_bv,$user->power_r_bv);
                // $user->save();

                $before_curr_lbv = 0;
                $before_curr_rbv = 0;
                $new_curr_lbv    = 0;
                $new_curr_rbv    = 0;

                $before_curr_lbv = $user->curr_l_bv;
                $before_curr_rbv = $user->curr_r_bv;
                $position        = $arrInput['position'];
                $powerbv         = $arrInput['power_bv'];
                $new_curr_lbv    = 0;
                $new_curr_rbv    = 0;

                if ($position == 1) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {
                        if ($before_curr_lbv >= $powerbv) {
                            $new_curr_lbv = $before_curr_lbv - $powerbv;
                            $new_curr_rbv = $before_curr_rbv;
                        } else {

                            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User dont have too much power to remove ', '');
                        }
                    } else {
                        $new_curr_lbv = $before_curr_lbv + $powerbv;
                        $new_curr_rbv = $before_curr_rbv;
                    }
                } elseif ($position == 2) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {
                        if ($before_curr_rbv >= $powerbv) {
                            $new_curr_lbv = $before_curr_lbv;
                            $new_curr_rbv = $before_curr_rbv - $powerbv;
                        } else {

                            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User dont have too much power to remove ', '');
                        }
                    } else {
                        $new_curr_lbv = $before_curr_lbv;
                        $new_curr_rbv = $before_curr_rbv + $powerbv;
                    }
                }

                $user->curr_l_bv = $new_curr_lbv;
                $user->curr_r_bv = $new_curr_rbv;
                $user->save();
                // dd($user->l_bv,$user->r_bv, $user->curr_l_bv,$user->curr_r_bv);

                //Insert inn power Bv Table
                $power                  = new PowerBV;
                $power->user_id         = $arrInput['id'];
                $power->position        = $arrInput['position'];
                $power->power_bv        = $powerbv;
                $power->type            = $arrInput['type'];
                $power->before_lbv      = $before_lbv;
                $power->before_rbv      = $before_rbv;
                $power->after_lbv       = $new_lbv;
                $power->after_rbv       = $new_rbv;
                $power->before_curr_lbv = $before_curr_lbv;
                $power->before_curr_rbv = $before_curr_rbv;
                $power->after_curr_lbv  = $new_curr_lbv;
                $power->after_curr_rbv  = $new_curr_rbv;
                $power->entry_time      = \Carbon\Carbon::now();
                $power->save();
                $query = $power->toSql();
                //dd($query);


                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Power will update after 10 min', '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
            }
        }
    }

    public function AddPowerLevel(Request $request)
    {
        try{
            $arrInput = $request->all();

            $rules     = array('position' => 'required', 'power_bv' => 'required|integer', 'powerfromid'=>'required','poweruptoid'=>'required');
            $validator = Validator::make($arrInput, $rules);

            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
            }
            else
            {
                $data=array('from_power_id'=>$request->fromid,'up_to_id'=>$request->toid,'position'=>$request->position,'amount'=>$request->power_bv,'entry_time'=>\Carbon\Carbon::now());

                $result=DB::table('tbl_addPower_to_levels')
                    ->insert($data);

                if(!empty($result))
                {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Power will update afther some time', '');
                }
            }
        }
        catch (Exception $e) {
            dd($e);
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again';
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }

    }
    public function powerReportBlade(){
        return view('admin.AddPower.PowerReport');
    }
    /**
     ** Power Report
     **
     ** @param request
     **
     ** @return Json
     **/

    public function powerReport(Request $request)
    {

        $arrInput = $request->all();

        $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["sub-admin","Admin"])->first();

        if (!empty($userExist)) {
            $query = PowerBV::join('tbl_users as tu', 'tu.id', '=', 'tbl_power_bv.user_id')
                ->select('tbl_power_bv.*', 'tu.user_id as user', 'tu.fullname', DB::raw('DATE_FORMAT(tbl_power_bv.entry_time,"%Y/%m/%d") as entry_time'));

            if (isset($arrInput['user_id'])) {
                $query = $query->where('tu.user_id', $arrInput['user_id']);
            }
            if (isset($arrInput['amount'])) {
                $query = $query->where('tbl_power_bv.amount', $arrInput['amount']);
            }
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_power_bv.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
            }
            if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_power_bv');
                $search = $arrInput['search']['value'];
                $query  = $query->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhere('tbl_power_bv.' . $field, 'LIKE', '%' . $search . '%');
                    }
                    $query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('tu.fullname', 'LIKE', '%' . $search . '%');
                });
            }
            $query       = $query->orderBy('tbl_power_bv.entry_time', 'desc');
            $totalRecord = $query->count();
            // $powerbv     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
            $powerbv     = $query->get();
            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $powerbv;

            if (count($powerbv) > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Unauthorised User.', '');
        }
    }

    public function GetPowerLevelReport(Request $request)
    {
        $arrInput = $request->all();

        $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        if (!empty($userExist)) {

            $query = AddPowerToParticularId::join('tbl_users as tu', 'tu.id', '=', 'tbl_addPower_to_levels.from_power_id')
                ->join('tbl_users as tu1', 'tu1.id', '=', 'tbl_addPower_to_levels.up_to_id')
                ->select('tbl_addPower_to_levels.*', 'tu.user_id as fromuser','tu1.user_id as uptouser','tu.fullname', DB::raw('DATE_FORMAT(tbl_addPower_to_levels.entry_time,"%Y/%m/%d") as entry_time'));

            $query       = $query->orderBy('tbl_addPower_to_levels.entry_time', 'desc');
            $totalRecord = $query->count();
            $powerbv     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $powerbv;

            if (count($powerbv) > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Unauthorised User.', '');
        }
    }


    public function getFranchiseUsers(Request $request)
    {
        $user = Auth::user();
        if (!empty($user)) {
            $users_list = User::select('id', 'user_id', 'fullname')
                ->where('is_franchise', '1')
                ->where('country', '=', $request->country)
                ->where('income_per', '=', '3')
                ->get();
            if (!empty($users_list)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $users_list);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
            }
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User Unaunthenticated', '');
        }
    }
    public function getMasterFranchiseUsers(Request $request)
    {
        $user = Auth::user();
        if (!empty($user)) {
            $users_list = User::select('id', 'user_id', 'fullname')
                ->where('is_franchise', '1')
                ->where('income_per', '=', '2')
                ->get();
            if (!empty($users_list)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $users_list);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
            }
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User Unaunthenticated', '');
        }
    }

    /**
     * show downline users reports
     *
     * @return \Illuminate\Http\Response
     */
    public function getDownlineUsers(Request $request)
    {
        $arrInput = $request->all();

        $query = LevelView::join('tbl_transaction_invoices as tri', 'tri.id', '=', 'tbl_level_view.down_id')
            ->join('tbl_users', 'tbl_level_view.down_id', '=', 'tbl_users.id')
            ->selectRaw('tbl_users.id,tbl_users.user_id,tbl_users.fullname,tri.invoice_id,tri.status_url,tri.entry_time,tri.payment_mode,tri.address,tri.hash_unit');
        if (isset($arrInput['user_id'])) {
            $query = $query->where('tbl_users.user_id', $arrInput['user_id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_transaction_invoices.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }

        if (isset($request->user_id)) {
            $data = User::where('tbl_users.user_id', $request->user_id)->first();
            if (empty($data)) {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
            }
            $id    = $data->id;
            $query = $query->where('tbl_level_view.id', $id);
        } else {

            $query = $query->where('tbl_level_view.id', '=', '1');
        }
        $query = $query->where('tri.in_status', '=', '1');

        if (isset($request->frm_date) && isset($request->to_date)) {
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_users.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($request->frm_date)), date('Y-m-d', strtotime($request->to_date))]);
        }
        // if(isset($request->search['value']) && !empty($request->search['value'])){
        //     //searching loops on fields
        //     $fields = ['tbl_users.user_id','tbl_users.fullname','tri.invoice_id','tri.status_url','tri.entry_time','tri.payment_mode','tri.address','tri.hash_unit'];
        //     $search = $request->search['value'];
        //     $query  = $query->where(function ($query) use ($fields, $search){
        //         foreach($fields as $field){
        //             $query->orWhere($field,'LIKE','%'.$search.'%');
        //         }
        //     });
        // }

        $query       = $query->orderBy('tbl_level_view.level', 'asc');
        $totalRecord = $query->count('tri.srno');
        // $totalRecord  = $query->count();
        $arrUserData = $query->skip($request->start)->take($request->length)->get();
        //dd($arrUserData);
        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrUserData;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }

    public function getteamviewsUsersNew(Request $request)
    {
        $arrInput = $request->all();

        $myarray = [];

        $user_id = User::select('id')->where([['user_id', '=', $arrInput['user_id']]])->first();
        $array   = [
            // 'left_id'   => $user_id->l_c_count,
            // 'right_id'  => $user_id->r_c_count,
            // 'left_bv'   => $user_id->l_bv,
            // 'right_bv'  => $user_id->r_bv
        ];

        $query = TodayDetails::select('tu.id', 'tu.user_id', 'tf.payment_mode', 'tf.price_in_usd', 'tf.in_status', 'tf.status_url', 'tu.fullname', 'tu1.user_id as sponser_id', 'tu2.user_id as upline_id', DB::raw('(CASE tbl_today_details.position WHEN "1" THEN "Left" WHEN "2" THEN "Right" ELSE "" END) as position'), DB::raw('DATE_FORMAT(tu.entry_time,"%Y/%m/%d %H:%i:%s") as joining_date'), 'tu.l_bv as left_bv', 'tu.r_bv as right_bv')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_today_details.from_user_id')
            ->join('tbl_users as tu1', 'tu1.id', '=', 'tu.ref_user_id')
            ->join('tbl_transaction_invoices as tf', 'tf.id', '=', 'tu.id')
            ->join('tbl_users as tu2', 'tu2.id', '=', 'tu.virtual_parent_id')
            ->where('tbl_today_details.to_user_id', $user_id['id']);

        if (isset($arrInput['payment_mode'])) {
            $query = $query->where('tf.payment_mode', $arrInput['payment_mode']);
        }
        if (isset($arrInput['tran_status'])) {
            $query = $query->where('tf.in_status', $arrInput['tran_status']);
        }

        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_today_details.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
        }
        // if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
        //     //searching loops on fields
        //     $fields = ['tu.user_id', 'tu.fullname', 'tu1.user_id', 'tu2.user_id', 'tu.l_c_count', 'tu.r_c_count', 'tu.l_bv', 'tu.r_bv', 'tu.pin_number'];
        //     $search = $arrInput['search']['value'];
        //     $query  = $query->where(function ($query) use ($fields, $search) {
        //         foreach ($fields as $field) {
        //             $query->orWhere($field, 'LIKE', '%' . $search . '%');
        //         }
        //         $query->orWhereRaw('(CASE tbl_today_details.position WHEN "1" THEN "Left" WHEN "2" THEN "Right" ELSE "" END) LIKE "%' . $search . '%"');
        //     });
        // }

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry     = $query;
            $qry     = $qry->select(/*'tu.id', */'tu.user_id', 'tf.payment_mode', 'tf.price_in_usd', DB::raw('(CASE tf.in_status WHEN 0 THEN "Pending" WHEN 1 THEN "Auto Confirmed" WHEN "2" THEN "Expired" ELSE "" END) as status'), 'tf.status_url', 'tu.fullname', 'tu1.user_id as sponser_id', DB::raw('DATE_FORMAT(tu.entry_time,"%Y/%m/%d %H:%i:%s") as joining_date')/* 'tu.l_bv as left_bv', 'tu.r_bv as right_bv' */);
            $records = $qry->get();
            $res     = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res, "AllUsers");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
        }

        $query                  = $query->orderBy('tbl_today_details.today_id', 'desc');
        $totalRecord            = $query->count('tbl_today_details.today_id');
        $arrData                = setPaginate1($query, $arrInput['start'], $arrInput['length'], '');
        $arrData['user_binary'] = $array;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function getDownlineFundAddReport(Request $request)
    {
        $arrInput = $request->all();

        $myarray = [];

        $user_id = User::select('id')->where([['user_id', '=', $arrInput['user_id']]])->first();
        $array   = [
            // 'left_id'   => $user_id->l_c_count,
            // 'right_id'  => $user_id->r_c_count,
            // 'left_bv'   => $user_id->l_bv,
            // 'right_bv'  => $user_id->r_bv
        ];

        $query = TodayDetails::select('tu.id', 'tu.user_id', 'tf.payment_mode', 'tf.price_in_usd', 'tf.in_status', 'tf.status_url', 'tu.fullname', 'tu1.user_id as sponser_id', 'tu2.user_id as upline_id', DB::raw('(CASE tbl_today_details.position WHEN "1" THEN "Left" WHEN "2" THEN "Right" ELSE "" END) as position'), DB::raw('DATE_FORMAT(tu.entry_time,"%Y/%m/%d %H:%i:%s") as joining_date'), 'tu.l_bv as left_bv', 'tu.r_bv as right_bv')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_today_details.from_user_id')
            ->join('tbl_users as tu1', 'tu1.id', '=', 'tu.ref_user_id')
            ->join('tbl_fundtransaction_invoices as tf', 'tf.id', '=', 'tu.id')
            ->join('tbl_users as tu2', 'tu2.id', '=', 'tu.virtual_parent_id')
            ->where('tbl_today_details.to_user_id', $user_id['id']);

        if (isset($arrInput['payment_mode'])) {
            $query = $query->where('tf.payment_mode', $arrInput['payment_mode']);
        }
        if (isset($arrInput['tran_status'])) {
            $query = $query->where('tf.in_status', $arrInput['tran_status']);
        }

        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_today_details.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
        }
        // if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
        //     //searching loops on fields
        //     $fields = ['tu.user_id', 'tu.fullname', 'tu1.user_id', 'tu2.user_id', 'tu.l_c_count', 'tu.r_c_count', 'tu.l_bv', 'tu.r_bv', 'tu.pin_number'];
        //     $search = $arrInput['search']['value'];
        //     $query  = $query->where(function ($query) use ($fields, $search) {
        //         foreach ($fields as $field) {
        //             $query->orWhere($field, 'LIKE', '%' . $search . '%');
        //         }
        //         $query->orWhereRaw('(CASE tbl_today_details.position WHEN "1" THEN "Left" WHEN "2" THEN "Right" ELSE "" END) LIKE "%' . $search . '%"');
        //     });
        // }

        if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
            $qry     = $query;
            $qry     = $qry->select(/*'tu.id', */'tu.user_id', 'tf.payment_mode', 'tf.price_in_usd', DB::raw('(CASE tf.in_status WHEN 0 THEN "Pending" WHEN 1 THEN "Auto Confirmed" WHEN "2" THEN "Expired" ELSE "" END) as status'), 'tf.status_url', 'tu.fullname', 'tu1.user_id as sponser_id', DB::raw('DATE_FORMAT(tu.entry_time,"%Y/%m/%d %H:%i:%s") as joining_date')/* 'tu.l_bv as left_bv', 'tu.r_bv as right_bv' */);
            $records = $qry->get();
            $res     = $records->toArray();
            if (count($res) <= 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
            }
            $var = $this->commonController->exportToExcel($res, "AllUsers");
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
        }

        $query                  = $query->orderBy('tbl_today_details.today_id', 'desc');
        $totalRecord            = $query->count('tbl_today_details.today_id');
        $arrData                = setPaginate1($query, $arrInput['start'], $arrInput['length'], '');
        $arrData['user_binary'] = $array;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
    /**
     * show downline users reports
     *
     * @return \Illuminate\Http\Response
     */
    public function businessSetting(Request $request)
    {

        $query = DB::table('tbl_business_setting')->join('tbl_users', 'tbl_business_setting.user_id', '=', 'tbl_users.id')
            ->selectRaw('tbl_users.id,tbl_users.user_id,tbl_users.fullname,tbl_business_setting.amount,tbl_business_setting.remark,tbl_business_setting.entry_time');

        $business_setting = 0;
        if (isset($request->user_id)) {
            $data = User::where('tbl_users.user_id', $request->user_id)->first();
            if (empty($data)) {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
            }
            $id = $data->id;

            $query = $query->where('tbl_business_setting.user_id', $id);
        } else {

            //$query = $query->where('tbl_business_setting.user_id', '=', '1');
        }
        // $query = $query->where('tri.in_status', '=','1');
        // dd($query->toSql());
        if (isset($request->frm_date) && isset($request->to_date)) {
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_business_setting.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($request->frm_date)), date('Y-m-d', strtotime($request->to_date))]);
        }
        // if (isset($request->search['value']) && !empty($request->search['value'])) {
        // 	//searching loops on fields
        // 	$fields = ['tbl_users.user_id', 'tbl_users.fullname'];
        // 	$search = $request->search['value'];
        // 	$query  = $query->where(function ($query) use ($fields, $search) {
        // 		foreach ($fields as $field) {
        // 			$query->orWhere($field, 'LIKE', '%' . $search . '%');
        // 		}
        // 	});
        // }
        $totalRecord = $query->count('tbl_business_setting.id');
        $query       = $query->orderBy('tbl_business_setting.id', 'desc');
        //dd($query->sum('hash_unit'));
        // $totalRecord  = $query->count();

        $arrUserData = $query->skip($request->start)->take($request->length)->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrUserData;

        //$arrData['records']         = $arrUserData;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }
    /**
     * show downline users reports
     *
     * @return \Illuminate\Http\Response
     */
    public function findDownlineUsersBusiness(Request $request)
    {

        $query = TodayDetails::join('tbl_transaction_invoices as tri', 'tri.id', '=', 'tbl_today_details.from_user_id')
            ->join('tbl_users', 'tbl_today_details.from_user_id', '=', 'tbl_users.id')
            ->selectRaw('tbl_users.id,tbl_users.user_id,tbl_users.fullname,tri.invoice_id,tri.status_url,tri.entry_time,tri.payment_mode,tri.address,tri.hash_unit');

        $business_setting = 0;
        if (isset($request->user_id)) {
            $data = User::where('tbl_users.user_id', $request->user_id)->first();
            if (empty($data)) {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
            }
            $id = $data->id;

            $business_setting = DB::table('tbl_business_setting')->where('user_id', '=', $id)->sum('amount');

            $query = $query->where('tbl_today_details.to_user_id', $id);
        } else {

            $query = $query->where('tbl_today_details.today_id', '=', '1');
        }
        $query = $query->where('tri.in_status', '=', '1');
        //dd($request->frm_date);
        // if (isset($request->frm_date) && isset($request->to_date)) {
        // 	$query = $query->whereBetween(DB::raw("DATE_FORMAT(tri.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($request->frm_date)), date('Y-m-d', strtotime($request->to_date))]);
        // }
        $arrInput = $request->all();
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tri.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        //	dd($query->toSql());
        if (isset($request->search['value']) && !empty($request->search['value'])) {
            //searching loops on fields
            $fields = ['tbl_users.user_id', 'tbl_users.fullname', 'tri.invoice_id', 'tri.status_url', 'tri.entry_time', 'tri.payment_mode', 'tri.address', 'tri.hash_unit'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $totalRecord = $query->count('tri.srno');
        $query       = $query->orderBy('tbl_today_details.level', 'asc');
        //dd($query->sum('hash_unit'));
        // $totalRecord  = $query->count();

        $arrUserData = $query->skip($request->start)->take($request->length)->get();

        $arrData['recordsTotal']     = $totalRecord;
        $arrData['recordsFiltered']  = $totalRecord;
        $arrData['records']          = $arrUserData;
        $arrData['total_business']   = $query->sum('hash_unit');
        $arrData['business_setting'] = $business_setting;
        //$arrData['records']         = $arrUserData;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }

    public function saveBusinessSetting(Request $request)
    {

        $arrInput = $request->all();

        $rules     = array('user_id' => 'required', 'amount' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            $user = User::select('id')->where('user_id', '=', $arrInput['user_id'])->first();

            $arrInput['user_id'] = $user->id;

            //  dd($arrInput);

            if (!empty($user)) {

                DB::table('tbl_business_setting')->insert($arrInput);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'successfully updated', '');
            } else {

                return sendresponse($this->statuscode[400]['code'], $this->statuscode[400]['status'], 'User Not Found', '');
            }
        }
    }

    public function useridUpdate(Request $request)
    {
        //$id = Auth::user();
        $arrInput = $request->all();
        //dd($arrInput);

        $query = DB::table('tbl_user_update')->select('id', 'old_user_id', 'new_user_id', 'status', 'entry_time');

        if (isset($arrInput['id'])) {
            $query = $query->where('new_user_id', $arrInput['id']);
        }
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $query                = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }
        // if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
        // 	//searching loops on fields
        // 	$fields = ['id', 'old_user_id', 'new_user_id','status'];
        // 	$search = $arrInput['search']['value'];
        // 	$query = $query->where(function ($query) use ($fields, $search) {
        // 		foreach ($fields as $field) {
        // 			$query->orWhere($field, 'LIKE', '%' . $search . '%');
        // 		}
        // 	});
        // }

        $query       = $query->orderBy('id', 'desc');
        $totalRecord = $query->count('id');
        // $totalRecord = $query->count();
        $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrDirectInc;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function updateUserid(Request $request)
    {
        $id         = Auth::user();
        $arrData    = $request->all();
        $insert     = DB::table('tbl_user_update')->insert($arrData);
        $new_userid = $arrData['new_user_id'];
        // dd($new_userid);
        $arrUpdate = ['user_id' => $new_userid];

        $updateUser = User::where('id', $request->id)->update($arrUpdate);
        // dd($updateUser);
        if (!empty($updateUser)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Userid Updated successfully', '');
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'userid not updated', '');
        }
    }

    /**
     ** Add Power
     ** @param Request obj
     **
     ** @return json
     **/
    public function addBussiness(Request $request)
    {
        $arrInput = $request->all();

        $rules     = array('id' => 'required', 'position' => 'required', 'otp' => 'required', 'power_bv' => 'required|integer');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }

        $adminOtpStatusData = verifyAdminOtpStatus::select('add_bussiness_otp_status')->first();
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
        } else if ($adminOtpStatusData->add_bussiness_otp_status == 1) {
            if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
            }


            $arrInput['user_id'] = Auth::User()->id;
            $arrInput['remark'] = 'Add Busniess';
            $arrInput['otp'] = $request->otp;
            $verify_otp = verify_Admin_Withdraw_Otp($arrInput);
            if (!empty($verify_otp)) {
                if ($verify_otp['status'] == 200) {
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            }
        }




        else {

            // $arrInput['user_id'] = Auth::User()->id;
            // $arrInput['remark'] = 'Add Busniess';
            // $verify_otp = verify_Admin_Withdraw_Otp($arrInput);
            // if (!empty($verify_otp)) {
            // 	if ($verify_otp['status'] == 200) {
            // 	} else {
            // 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
            // 	}
            // } else {
            // 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            // }

            // sssssssssssssssssssssssssssssssssssssssssssssssssssss

            $user       = User::where('id', $arrInput['id'])->first();
            $before_lbv = 0;
            $before_rbv = 0;
            if (!empty($user)) {

                $before_lbv       = $user->l_bv;
                $before_rbv       = $user->r_bv;
                $before_power_lbv = $user->power_l_bv;
                $before_power_rbv = $user->power_r_bv;
                $before_curr_l_bv = $user->curr_l_bv;
                $before_curr_r_bv = $user->curr_r_bv;
                $position         = $arrInput['position'];
                $powerbv          = $arrInput['power_bv'];
                $new_lbv          = 0;
                $new_rbv          = 0;
                $new_curr_lbv     = 0;
                $new_curr_rbv     = 0;

                if ($position == 1) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {

                        $new_lbv       = $before_lbv - $powerbv;
                        $new_rbv       = $before_rbv;
                        $new_curr_lbv  = $before_curr_l_bv - $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv;
                        $new_power_lbv = $before_power_lbv - $powerbv;
                        $new_power_rbv = $before_power_rbv;
                    } else {
                        $new_lbv       = $before_lbv + $powerbv;
                        $new_rbv       = $before_rbv;
                        $new_curr_lbv  = $before_curr_l_bv + $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv;
                        $new_power_lbv = $before_power_lbv + $powerbv;
                        $new_power_rbv = $before_power_rbv;
                    }
                } elseif ($position == 2) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {
                        $new_lbv       = $before_lbv;
                        $new_rbv       = $before_rbv - $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv - $powerbv;
                        $new_curr_lbv  = $before_curr_l_bv;
                        $new_power_lbv = $before_power_lbv;
                        $new_power_rbv = $before_power_rbv - $powerbv;
                    } else {
                        $new_lbv       = $before_lbv;
                        $new_rbv       = $before_rbv + $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv + $powerbv;
                        $new_curr_lbv  = $before_curr_l_bv;
                        $new_power_rbv = $before_power_rbv + $powerbv;
                        $new_power_lbv = $before_power_lbv;
                    }
                }

                $user->l_bv      = $new_lbv;
                $user->r_bv      = $new_rbv;
                $user->curr_l_bv = $new_curr_lbv;
                $user->curr_r_bv = $new_curr_rbv;

                $user->save();

                //Insert inn power Bv Table
                $power                  = new AddRemoveBusiness;
                $power->user_id         = $arrInput['id'];
                $power->position        = $arrInput['position'];
                $power->power_bv        = $powerbv;
                $power->type            = $arrInput['type'];
                $power->before_lbv      = $before_lbv;
                $power->before_rbv      = $before_rbv;
                $power->after_lbv       = $new_lbv;
                $power->after_rbv       = $new_rbv;
                $power->before_curr_lbv = $before_curr_l_bv;
                $power->before_curr_rbv = $before_curr_r_bv;
                $power->after_curr_lbv  = $new_curr_lbv;
                $power->after_curr_rbv  = $new_curr_rbv;
                $power->entry_time      = \Carbon\Carbon::now();
                $power->save();
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Bussiness will update after 10 min', '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
            }
        }
    }

    /**
     ** Power Report
     **
     ** @param request
     **
     ** @return Json
     **/

    public function buinessReport(Request $request)
    {

        $arrInput = $request->all();

        $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        if (!empty($userExist)) {
            $query = AddRemoveBusiness::join('tbl_users as tu', 'tu.id', '=', 'tbl_add_remove_business.user_id')
                ->select(
                    'tbl_add_remove_business.power_bv',
                    'tbl_add_remove_business.position',
                    'tbl_add_remove_business.before_lbv',
                    'tbl_add_remove_business.before_rbv',
                    'tbl_add_remove_business.after_lbv',
                    'tbl_add_remove_business.after_rbv',
                    'tbl_add_remove_business.before_curr_lbv',
                    'tbl_add_remove_business.before_curr_rbv',
                    'tbl_add_remove_business.after_curr_lbv',
                    'tbl_add_remove_business.after_curr_rbv',
                    'tbl_add_remove_business.remark',
                    'tbl_add_remove_business.type',

                    'tu.user_id as user',
                    'tu.fullname',
                    DB::raw('DATE_FORMAT(tbl_add_remove_business.entry_time,"%Y/%m/%d") as entry_time')
                );

            /*->select('tbl_add_remove_business.*', 'tu.user_id as user', 'tu.fullname', DB::raw('DATE_FORMAT(tbl_add_remove_business.entry_time,"%Y/%m/%d") as entry_time'));*/

            if (isset($arrInput['user_id'])) {
                $query = $query->where('tu.user_id', $arrInput['user_id']);
            }
            if (isset($arrInput['amount'])) {
                $query = $query->where('tbl_add_remove_business.amount', $arrInput['amount']);
            }
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_add_remove_business.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
            }
            if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_add_remove_business');
                $search = $arrInput['search']['value'];
                $query  = $query->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhere('tbl_add_remove_business.' . $field, 'LIKE', '%' . $search . '%');
                    }
                    $query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('tu.fullname', 'LIKE', '%' . $search . '%');
                });
            }

            if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
                $qry = $query;
                $qry = $qry->select(
                    'tbl_add_remove_business.power_bv',
                    'tbl_add_remove_business.position',
                    'tbl_add_remove_business.before_lbv',
                    'tbl_add_remove_business.before_rbv',
                    'tbl_add_remove_business.after_lbv',
                    'tbl_add_remove_business.after_rbv',
                    'tbl_add_remove_business.before_curr_lbv',
                    'tbl_add_remove_business.before_curr_rbv',
                    'tbl_add_remove_business.after_curr_lbv',
                    'tbl_add_remove_business.after_curr_rbv',
                    'tbl_add_remove_business.remark',
                    'tbl_add_remove_business.type',

                    'tu.user_id as user',
                    'tu.fullname',
                    DB::raw('DATE_FORMAT(tbl_add_remove_business.entry_time,"%Y/%m/%d") as entry_time')
                );
                // $qry = $qry->select('tu.user_id as user', 'tu.fullname', 'tbl_add_remove_business.power_bv','tbl_add_remove_business.position' , 'tbl_add_remove_business.before_lbv' , 'tbl_add_remove_business.before_rbv' , 'tbl_add_remove_business.after_lbv' , 'tbl_add_remove_business.after_rbv' , 'tbl_add_remove_business.before_curr_lbv' , 'tbl_add_remove_business.before_curr_rbv' , 'tbl_add_remove_business.after_curr_lbv' , 'tbl_add_remove_business.after_curr_rbv' , 'tbl_add_remove_business.remark' , 'tbl_add_remove_business.cron_status' ,  DB::raw('DATE_FORMAT(tbl_add_remove_business.entry_time,"%Y/%m/%d") as entry_time'));
                $records = $qry->get();
                $res     = $records->toArray();
                if (count($res) <= 0) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
                }
                $var = $this->commonController->exportToExcel($res, "AllUsers");
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
            }

            $totalRecord = $query->count('tbl_add_remove_business.user_id');
            $query       = $query->orderBy('tbl_add_remove_business.entry_time', 'desc');
            // $totalRecord = $query->count();
            $powerbv = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $powerbv;

            if (count($powerbv) > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Unauthorised User.', '');
        }
    }


    public function UplineReportBlade(){
        return view('admin.AddPower.UplinePowerReport');
    }

    public function businessUplineReport(Request $request)
    {

        $arrInput = $request->all();
        $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["sub-admin","Admin"])->first();
        if (!empty($userExist)) {
            $query = AddRemoveBusinessUpline::join('tbl_users as tu', 'tu.id',  '=', 'tbl_add_remove_business_upline.user_id')
                ->join('tbl_users as tu1','tu1.id', '=', 'tbl_add_remove_business_upline.upline_id')
                ->select(
                //  'tbl_add_remove_business_upline.upline_id',
                    'tbl_add_remove_business_upline.power_bv',
                    'tbl_add_remove_business_upline.position',
                    'tbl_add_remove_business_upline.before_lbv',
                    'tbl_add_remove_business_upline.before_rbv',
                    'tbl_add_remove_business_upline.after_lbv',
                    'tbl_add_remove_business_upline.after_rbv',
                    'tbl_add_remove_business_upline.before_curr_lbv',
                    'tbl_add_remove_business_upline.before_curr_rbv',
                    'tbl_add_remove_business_upline.after_curr_lbv',
                    'tbl_add_remove_business_upline.after_curr_rbv',
                    'tbl_add_remove_business_upline.remark',
                    'tbl_add_remove_business_upline.type',
                    'tu.user_id as user',
                    'tu.fullname',
                    'tu1.user_id as uplineuser',
                    DB::raw('DATE_FORMAT(tbl_add_remove_business_upline.entry_time,"%Y/%m/%d") as entry_time')
                );

            /*->select('tbl_add_remove_business_upline.*', 'tu.user_id as user', 'tu.fullname', DB::raw('DATE_FORMAT(tbl_add_remove_business_upline.entry_time,"%Y/%m/%d") as entry_time'));*/

            if (isset($arrInput['to_user_id'])) {
                $query = $query->where('tu.user_id', $arrInput['to_user_id']);
            }
            // if (isset($arrInput['amount'])) {
            // 	$query = $query->where('tbl_add_remove_business_upline.power_bv', $arrInput['amount']);
            // }
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_add_remove_business_upline.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
            }
            if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_add_remove_business_upline');
                $search = $arrInput['search']['value'];
                $query  = $query->where(function ($query) use ($fields, $search) {
                    foreach ($fields as $field) {
                        $query->orWhere('tbl_add_remove_business_upline.' . $field, 'LIKE', '%' . $search . '%');
                    }
                    $query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('tu.fullname', 'LIKE', '%' . $search . '%');
                });
            }

            if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
                $qry = $query;
                $qry = AddRemoveBusinessUpline::join('tbl_users as tu', 'tu.id',  '=', 'tbl_add_remove_business_upline.user_id')
                    ->join('tbl_users as tu1','tu1.id', '=', 'tbl_add_remove_business_upline.upline_id')
                    ->select(
                    //  'tbl_add_remove_business_upline.upline_id',
                        'tbl_add_remove_business_upline.power_bv',
                        'tbl_add_remove_business_upline.position',
                        'tbl_add_remove_business_upline.before_lbv',
                        'tbl_add_remove_business_upline.before_rbv',
                        'tbl_add_remove_business_upline.after_lbv',
                        'tbl_add_remove_business_upline.after_rbv',
                        'tbl_add_remove_business_upline.before_curr_lbv',
                        'tbl_add_remove_business_upline.before_curr_rbv',
                        'tbl_add_remove_business_upline.after_curr_lbv',
                        'tbl_add_remove_business_upline.after_curr_rbv',
                        'tbl_add_remove_business_upline.remark',
                        'tbl_add_remove_business_upline.type',
                        'tu.user_id as user',
                        'tu.fullname',
                        'tu1.user_id as uplineuser',
                        DB::raw('DATE_FORMAT(tbl_add_remove_business_upline.entry_time,"%Y/%m/%d") as entry_time')
                    );
                $records = $qry->get();
                $res     = $records->toArray();
                if (count($res) <= 0) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
                }
                $var = $this->commonController->exportToExcel($res, "AllUsers");
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
            }

            $totalRecord = $query->count('tbl_add_remove_business_upline.user_id');
            $query       = $query->orderBy('tbl_add_remove_business_upline.entry_time', 'desc');
            // $totalRecord = $query->count();
            $powerbv = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $powerbv;

            if (count($powerbv) > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Unauthorised User.', '');
        }
    }

    /**
     ** getSummaryByUserid
     **
     ** @param request
     **
     ** @return Json
     **/
    public function getSummaryByUserid(Request $request)
    {

        //$id = Auth::user()->id;
        $arrInput  = $request->all();
        $rules     = array('user_id' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        $user = User::selectRaw('id,l_bv,r_bv,l_c_count,r_c_count')->where('user_id', '=', $request->user_id)->get();
        //dd($user);
        if (!empty($user)) {

            $topup_count = Topup::join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_topup.id')->where('ttd.to_user_id', $user[0]->id)->count('tbl_topup.id');
            //dd($topup_count);
            // <<<<<<< HEAD
            // 			/*$topup_amount = Topup::join('tbl_today_details as ttd','ttd.from_user_id','=','tbl_topup.id')->where('ttd.to_user_id',$user->id)->sum('tbl_topup.amount');*/
            // 			$topup_amount = $user[0]->l_bv + $user[0]->r_bv;
            // 			$total_register =  $user[0]->l_c_count + $user[0]->r_c_count;
            // 			$confirm_withdraw = WithdrawPending::join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_withdrwal_pending.id')->where('ttd.to_user_id', $user[0]->id)->where('tbl_withdrwal_pending.status', '1')->sum('tbl_withdrwal_pending.amount');
            // 			$total_dex_wallet = Dashboard::join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_dashboard.id')->where('ttd.to_user_id', $user[0]->id)->sum(DB::raw('ROUND(tbl_dashboard.working_wallet - tbl_dashboard.working_wallet_withdraw,2)'));
            // 			$total_purchase_wallet = Dashboard::join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_dashboard.id')->where('ttd.to_user_id', $user[0]->id)->sum(DB::raw('ROUND(tbl_dashboard.top_up_wallet - tbl_dashboard.top_up_wallet_withdraw,2)'));
            // 			//dd($total_register,$confirm_withdraw,$total_dex_wallet,$total_purchase_wallet);
            // 			$arrData = array();
            // 			$arrData['downline_topup_count'] = $topup_count;
            // 			$arrData['total_register'] = $total_register;
            // 			$arrData['total_confirm_withdraw'] = $confirm_withdraw;
            // 			$arrData['total_dex_wallet'] = $total_dex_wallet;
            // 			$arrData['total_purchase_wallet'] = $total_purchase_wallet;
            // =======
            if (!empty($topup_count)) {
                $topup_amount          = $user[0]->l_bv + $user[0]->r_bv;
            } else {
                $topup_amount = 0;
            }
            /*$topup_amount = Topup::join('tbl_today_details as ttd','ttd.from_user_id','=','tbl_topup.id')->where('ttd.to_user_id',$user->id)->sum('tbl_topup.amount');*/

            $total_register        = $user[0]->l_c_count + $user[0]->r_c_count;
            $confirm_withdraw      = WithdrawPending::join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_withdrwal_pending.id')->where('ttd.to_user_id', $user[0]->id)->where('tbl_withdrwal_pending.status', '1')->sum('tbl_withdrwal_pending.amount');
            $total_dex_wallet      = Dashboard::join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_dashboard.id')->where('ttd.to_user_id', $user[0]->id)->sum(DB::raw('ROUND(tbl_dashboard.working_wallet - tbl_dashboard.working_wallet_withdraw,2)'));
            $total_purchase_wallet = Dashboard::join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_dashboard.id')->where('ttd.to_user_id', $user[0]->id)->sum(DB::raw('ROUND(tbl_dashboard.top_up_wallet - tbl_dashboard.top_up_wallet_withdraw,2)'));
            //dd($total_register,$confirm_withdraw,$total_dex_wallet,$total_purchase_wallet);
            $arrData                           = array();
            $arrData['downline_topup_count']   = $topup_count;
            $arrData['total_register']         = $total_register;
            $arrData['total_confirm_withdraw'] = $confirm_withdraw;
            $arrData['total_dex_wallet']       = $total_dex_wallet;
            $arrData['total_purchase_wallet']  = $total_purchase_wallet;

            if (!empty($arrData)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
            // }
            // else {
            // 	return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'User not paid.', '');
            // }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'User not found.', '');
        }
    }

    public function getRankByUserid(Request $request)
    {

        $id = Auth::user()->id;

        $arrInput  = $request->all();
        $rules     = array('user_id' => 'required', 'rank' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }

        $ranks = Rank::select('rank')->get();
        if (!empty($arrInput['rank']) && isset($arrInput['rank'])) {
            $ranks            = $ranks->where('rank', $arrInput['rank']);
            $totalrankrecords = array();
            foreach ($ranks as $rank) {
                $rankname = strtolower($rank->rank);
                if ($rankname == 'immortal') {
                    $l_position = "l_lmmortal";
                    $r_position = "r_immortal";
                } else {
                    $l_position = "l_" . $rankname;
                    $r_position = "r_" . $rankname;
                }
                $userdata         = User::select($l_position . " as left_count", $r_position . " as right_count")->where('user_id', '=', $request->user_id)->first()->toArray();
                $userdata['rank'] = $rank->rank;
                array_push($totalrankrecords, $userdata);
            }
        }

        $arrData['recordsTotal']    = count($totalrankrecords);
        $arrData['recordsFiltered'] = count($totalrankrecords);
        $arrData['records']         = $totalrankrecords;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function AddRankPower(Request $request)
    {

        $id       = Auth::user()->id;
        $arrInput = $request->all();
        //dd($arrInput);
        $rules     = array('id' => 'required', 'position' => 'required', 'power' => 'required|integer', 'rank' => 'required');
        $validator = Validator::make($arrInput, $rules);

        $adminOtpStatusData = verifyAdminOtpStatus::select('add_rank_power_otp_status')->first();
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
        } else if ($adminOtpStatusData->add_rank_power_otp_status == 1) {
            if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
            }


            $arrInput['user_id'] = Auth::User()->id;
            $arrInput['remark'] = 'Add Rank';
            $arrInput['otp'] = $request->otp;
            $verify_otp = verify_Admin_Withdraw_Otp($arrInput);
            if (!empty($verify_otp)) {
                if ($verify_otp['status'] == 200) {
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            }
        }

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        $rank = $request->position . "_" . strtolower($request->rank);
        $user = User::select('user_id', 'l_ace_check_status', 'r_ace_check_status', $rank)->where('id', '=', $request->id)->first();

        if (!empty($user)) {

            $arrUpdate        = array();
            $arrUpdate[$rank] = DB::raw($rank . " + " . $request->power);

            // if($request->type == "add"){
            // 	$arrUpdate[$rank] = DB::raw($rank." + ".$request->power);
            // }elseif($request->type == "remove"){
            // 	$arrUpdate[$rank] = DB::raw($rank." - ".$request->power);
            // }

            $pos = "";
            if ($request->position == 'l') {
                $pos = '1';
            } else {
                $pos = '2';
            }

            $res = $user->toArray();
            //dd($res);
            $before_power = $res[$rank];
            $power        = $request->power;
            $after_power  = $res[$rank] + $power;

            if ($request->position == 'l') {
                $ace_check_status = $res['l_ace_check_status'] + 1;
                $updateData       = array('l_ace_check_status' => $ace_check_status);
            }

            if ($request->position == 'r') {
                $ace_check_status = $res['r_ace_check_status'] + 1;
                $updateData       = array('r_ace_check_status' => $ace_check_status);
            }
            // $after_bv = $res[$rank] - $power_bv;

            // if($request->type == 'add')
            // {
            //     $after_bv = $res[$rank] + $power_bv;
            // }
            // if($request->type == 'remove')
            // {
            //     $after_bv = $res[$rank] - $power_bv;
            // }

            $updateData[$rank] = $after_power;
            $userupdate        = User::where('id', $request->id)->update($updateData);
            $current_time      = date('Y-m-d H:i:s');
            $arrInsert         = array(
                'user_id'      => $request->id,
                'rank'         => $arrInput['rank'],
                'position'     => $pos,
                'before_bv'    => $res[$rank],
                'after_bv'     => $after_power,
                'bussiness_bv' => $request->power,
                'power_bv'     => $request->power,
                'entry_time'   => $current_time,
            );

            $addbussiness = DB::table('tbl_add_remove_rank_business')->insert($arrInsert);

            if (!empty($arrInput)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Bussiness added Successfully!', $arrInput);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'User not found.', '');
        }
    }

    public function AddRankPowerUpline(Request $request)
    {

        $id       = Auth::user()->id;
        $arrInput = $request->all();
        //dd($arrInput);
        $rules     = array('id' => 'required', 'position' => 'required', 'power' => 'required|integer', 'rank' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        $adminOtpStatusData = verifyAdminOtpStatus::select('add_rank_power_otp_status')->first();
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
        } else if ($adminOtpStatusData->add_rank_power_otp_status == 1) {
            if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
            }


            $arrInput['user_id'] = Auth::User()->id;
            $arrInput['remark'] = 'Add Rank Power';
            $arrInput['otp'] = $request->otp;
            $verify_otp = verify_Admin_Withdraw_Otp($arrInput);
            if (!empty($verify_otp)) {
                if ($verify_otp['status'] == 200) {
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            }
        }

        $ranks = '';
        if ($arrInput['rank'] == 'Ace') {
            $ranks = 'Ace';
        }

        if ($arrInput['rank'] == 'Herald') {
            $ranks = 'Herald';
        }

        if ($arrInput['rank'] == 'Guardian') {
            $ranks = 'Guardian';
        }

        if ($arrInput['rank'] == 'Crusader') {
            $ranks = 'Crusader';
        }

        if ($arrInput['rank'] == 'Commander') {
            $ranks = 'Commander';
        }

        if ($arrInput['rank'] == 'Valorant') {
            $ranks = 'Valorant';
        }

        if ($arrInput['rank'] == 'Legend') {
            $ranks = 'Legend';
        }

        if ($arrInput['rank'] == 'Relic') {
            $ranks = 'Relic';
        }

        if ($arrInput['rank'] == 'Almighty') {
            $ranks = 'Almighty';
        }

        if ($arrInput['rank'] == 'Conqueror') {
            $ranks = 'Conqueror';
        }

        if ($arrInput['rank'] == 'Titan') {
            $ranks = 'Titan';
        }
        // if ($arrInput['rank'] == 'Noble') {
        // 	$ranks = 'Ace';
        // }

        // if ($arrInput['rank'] == 'Eques') {
        // 	$ranks = 'Herald';
        // }

        // if ($arrInput['rank'] == 'Baron') {
        // 	$ranks = 'Guardian';
        // }

        // if ($arrInput['rank'] == 'Comes') {
        // 	$ranks = 'Crusader';
        // }

        // if ($arrInput['rank'] == 'Earl') {
        // 	$ranks = 'Commander';
        // }

        // if ($arrInput['rank'] == 'Marchio') {
        // 	$ranks = 'Valorant';
        // }

        // if ($arrInput['rank'] == 'Prorex') {
        // 	$ranks = 'Legend';
        // }

        // if ($arrInput['rank'] == 'Knight') {
        // 	$ranks = 'Relic';
        // }

        // if ($arrInput['rank'] == 'Archidux') {
        // 	$ranks = 'Almighty';
        // }

        // if ($arrInput['rank'] == 'Magnus') {
        // 	$ranks = 'Conqueror';
        // }

        // if ($arrInput['rank'] == 'Rexus') {
        // 	$ranks = 'Titan';
        // }

        $rank      = $request->position . "_" . strtolower($ranks);
        $up_rank_l = "l_" . strtolower($ranks);
        $up_rank_r = "r_" . strtolower($ranks);
        //dd($up_rank_l,$up_rank_r);
        if ($arrInput['rank'] == 'Imperator') {
            if ($request->position == 'l') {
                $rank = 'l_lmmortal';
            } else {
                $rank = 'r_immortal';
            }
            $up_rank_l = "l_lmmortal";
            $up_rank_r = "r_immortal";
        }

        // dd($rank);
        $user = User::select('user_id', 'l_ace_check_status', 'r_ace_check_status', $rank)->where('id', '=', $request->id)->first();

        if (!empty($user)) {

            $arrUpdate        = array();
            $arrUpdate[$rank] = DB::raw($rank . " + " . $request->power);

            // if($request->type == "add"){
            // 	$arrUpdate[$rank] = DB::raw($rank." + ".$request->power);
            // }elseif($request->type == "remove"){
            // 	$arrUpdate[$rank] = DB::raw($rank." - ".$request->power);
            // }

            $pos = "";
            if ($request->position == 'l') {
                $pos = '1';
            } else {
                $pos = '2';
            }

            $res = $user->toArray();

            $before_power = $res[$rank];
            $power        = $request->power;
            $after_power  = $res[$rank] + $power;

            if ($request->position == 'l') {
                $ace_check_status = $res['l_ace_check_status'] + 1;
                $updateData       = array('l_ace_check_status' => $ace_check_status);
            }

            if ($request->position == 'r') {
                $ace_check_status = $res['r_ace_check_status'] + 1;
                $updateData       = array('r_ace_check_status' => $ace_check_status);
            }

            $updateLCountArr             = array();
            $updateLCountArr[$up_rank_l] = DB::raw($up_rank_l . ' + ' . $request->power);

            DB::table('tbl_today_details as a')
                ->join('tbl_users as b', 'a.to_user_id', '=', 'b.id')
                ->where('a.from_user_id', '=', $request->id)
                ->where('a.position', '=', 1)
                ->update($updateLCountArr);

            $updateRCountArr             = array();
            $updateRCountArr[$up_rank_r] = DB::raw($up_rank_r . ' + ' . $request->power);

            DB::table('tbl_today_details as a')
                ->join('tbl_users as b', 'a.to_user_id', '=', 'b.id')
                ->where('a.from_user_id', '=', $request->id)
                ->where('a.position', '=', 2)
                ->update($updateRCountArr);

            $updateData[$rank] = $after_power;
            $userupdate        = User::where('id', $request->id)->update($updateData);
            $current_time      = date('Y-m-d H:i:s');
            $arrInsert         = array(
                'user_id'  => $request->id,
                'position' => $pos,
                /*'type'=>ucfirst($request->type),*/
                'rank'       => $request->rank,
                'before_bv'  => $res[$rank],
                'power_bv'   => $request->power,
                'after_bv'   => $after_power,
                'entry_time' => $current_time,
            );

            $addpower = DB::table('tbl_add_remove_rank_business_upline')->insert($arrInsert);

            if (!empty($arrInput)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Rank Power added Successfully!', $arrInput);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'User not found.', '');
        }
    }
    public function GetAdminOtpStatus(Request $request)
    {
        try{
            $data = verifyAdminOtpStatus::where('statusID', 1)->first();
            if(!empty($data))
            {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'otp status Data found', $data);
            }
            else{
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'otp status Data not found', '');
            }
        }
        catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again!!', '');
        }
    }

    public function AddRankPowerUplineold(Request $request)
    {

        $id       = Auth::user()->id;
        $arrInput = $request->all();
        //dd($arrInput);
        $rules     = array('id' => 'required', 'position' => 'required', 'power' => 'required|integer', 'rank' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }

        $ranks = '';
        if ($arrInput['rank'] == 'Ace') {
            $ranks = 'Ace';
        }

        if ($arrInput['rank'] == 'Herald') {
            $ranks = 'Herald';
        }

        if ($arrInput['rank'] == 'Guardian') {
            $ranks = 'Guardian';
        }

        if ($arrInput['rank'] == 'Crusader') {
            $ranks = 'Crusader';
        }

        if ($arrInput['rank'] == 'Commander') {
            $ranks = 'Commander';
        }

        if ($arrInput['rank'] == 'Valorant') {
            $ranks = 'Valorant';
        }

        if ($arrInput['rank'] == 'Legend') {
            $ranks = 'Legend';
        }

        if ($arrInput['rank'] == 'Relic') {
            $ranks = 'Relic';
        }

        if ($arrInput['rank'] == 'Almighty') {
            $ranks = 'Almighty';
        }

        if ($arrInput['rank'] == 'Conqueror') {
            $ranks = 'Conqueror';
        }

        if ($arrInput['rank'] == 'Titan') {
            $ranks = 'Titan';
        }
        // if ($arrInput['rank'] == 'Noble') {
        // 	$ranks = 'Ace';
        // }

        // if ($arrInput['rank'] == 'Eques') {
        // 	$ranks = 'Herald';
        // }

        // if ($arrInput['rank'] == 'Baron') {
        // 	$ranks = 'Guardian';
        // }

        // if ($arrInput['rank'] == 'Comes') {
        // 	$ranks = 'Crusader';
        // }

        // if ($arrInput['rank'] == 'Earl') {
        // 	$ranks = 'Commander';
        // }

        // if ($arrInput['rank'] == 'Marchio') {
        // 	$ranks = 'Valorant';
        // }

        // if ($arrInput['rank'] == 'Prorex') {
        // 	$ranks = 'Legend';
        // }

        // if ($arrInput['rank'] == 'Knight') {
        // 	$ranks = 'Relic';
        // }

        // if ($arrInput['rank'] == 'Archidux') {
        // 	$ranks = 'Almighty';
        // }

        // if ($arrInput['rank'] == 'Magnus') {
        // 	$ranks = 'Conqueror';
        // }

        // if ($arrInput['rank'] == 'Rexus') {
        // 	$ranks = 'Titan';
        // }

        $rank      = $request->position . "_" . strtolower($ranks);
        $up_rank_l = "l_" . strtolower($ranks);
        $up_rank_r = "r_" . strtolower($ranks);
        //dd($up_rank_l,$up_rank_r);
        if ($arrInput['rank'] == 'Imperator') {
            if ($request->position == 'l') {
                $rank = 'l_lmmortal';
            } else {
                $rank = 'r_immortal';
            }
            $up_rank_l = "l_lmmortal";
            $up_rank_r = "r_immortal";
        }

        // dd($rank);
        $user = User::select('user_id', 'l_ace_check_status', 'r_ace_check_status', $rank)->where('id', '=', $request->id)->first();

        if (!empty($user)) {

            $arrUpdate        = array();
            $arrUpdate[$rank] = DB::raw($rank . " + " . $request->power);

            // if($request->type == "add"){
            // 	$arrUpdate[$rank] = DB::raw($rank." + ".$request->power);
            // }elseif($request->type == "remove"){
            // 	$arrUpdate[$rank] = DB::raw($rank." - ".$request->power);
            // }

            $pos = "";
            if ($request->position == 'l') {
                $pos = '1';
            } else {
                $pos = '2';
            }

            $res = $user->toArray();

            $before_power = $res[$rank];
            $power        = $request->power;
            $after_power  = $res[$rank] + $power;

            if ($request->position == 'l') {
                $ace_check_status = $res['l_ace_check_status'] + 1;
                $updateData       = array('l_ace_check_status' => $ace_check_status);
            }

            if ($request->position == 'r') {
                $ace_check_status = $res['r_ace_check_status'] + 1;
                $updateData       = array('r_ace_check_status' => $ace_check_status);
            }

            $updateLCountArr             = array();
            $updateLCountArr[$up_rank_l] = DB::raw($up_rank_l . ' + ' . $request->power);

            DB::table('tbl_today_details as a')
                ->join('tbl_users as b', 'a.to_user_id', '=', 'b.id')
                ->where('a.from_user_id', '=', $request->id)
                ->where('a.position', '=', 1)
                ->update($updateLCountArr);

            $updateRCountArr             = array();
            $updateRCountArr[$up_rank_r] = DB::raw($up_rank_r . ' + ' . $request->power);

            DB::table('tbl_today_details as a')
                ->join('tbl_users as b', 'a.to_user_id', '=', 'b.id')
                ->where('a.from_user_id', '=', $request->id)
                ->where('a.position', '=', 2)
                ->update($updateRCountArr);

            $updateData[$rank] = $after_power;
            $userupdate        = User::where('id', $request->id)->update($updateData);
            $current_time      = date('Y-m-d H:i:s');
            $arrInsert         = array(
                'user_id'  => $request->id,
                'position' => $pos,
                /*'type'=>ucfirst($request->type),*/
                'rank'       => $request->rank,
                'before_bv'  => $res[$rank],
                'power_bv'   => $request->power,
                'after_bv'   => $after_power,
                'entry_time' => $current_time,
            );

            $addpower = DB::table('tbl_add_remove_rank_business_upline')->insert($arrInsert);

            if (!empty($arrInput)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Rank Power added Successfully!', $arrInput);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'User not found.', '');
        }
    }

    /*public function AddBussinessUpline(Request $request) {

	$arrInput = $request->all();

	$rules = array('id' => 'required', 'position' => 'required', 'bussiness' => 'required|integer');
	$validator = Validator::make($arrInput, $rules);

	if ($validator->fails()) {
	$message = messageCreator($validator->errors());
	return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
	} else {
	$user = User::where('id', $arrInput['id'])->first();
	$before_lbv = 0;
	$before_rbv = 0;
	}
	if (!empty($user)) {

	$before_lbv = $user->l_bv;
	$before_rbv = $user->r_bv;
	$before_power_lbv = $user->power_l_bv;
	$before_power_rbv = $user->power_r_bv;
	$before_curr_l_bv = $user->curr_l_bv;
	$before_curr_r_bv = $user->curr_r_bv;
	$position = $arrInput['position'];
	$powerbv = $arrInput['bussiness'];
	$new_lbv = 0;
	$new_rbv = 0;
	$new_curr_lbv = 0;
	$new_curr_rbv = 0;

	$pos="";
	if($request->position =='l'){
	$pos = '1';
	$new_lbv = $before_lbv - $powerbv;
	$new_rbv = $before_rbv;
	$new_curr_lbv = $before_curr_l_bv - $powerbv;
	$new_curr_rbv = $before_curr_r_bv;
	$new_power_lbv = $before_power_lbv - $powerbv;
	$new_power_rbv = $before_power_rbv;
	}
	else{
	$pos = '2';
	$new_lbv = $before_lbv;
	$new_rbv = $before_rbv - $powerbv;
	$new_curr_rbv = $before_curr_r_bv - $powerbv;
	$new_curr_lbv = $before_curr_l_bv;
	$new_power_lbv = $before_power_lbv;
	$new_power_rbv = $before_power_rbv - $powerbv;
	}

	$res = $user->toArray();
	$bussiness = $request->bussiness;

	$before_power = $powerbv;

	$after_power = $powerbv + $bussiness;

	$updateLCountArr = array();
	$updateLCountArr[$before_lbv] = DB::raw($before_lbv.' + '.$request->bussiness);

	DB::table('tbl_today_details as a')
	->join('tbl_users as b','a.to_user_id', '=','b.id')
	->where('a.from_user_id','=',$request->id)
	->where('a.position','=',1)
	->update($updateLCountArr);

	$updateRCountArr = array();
	$updateRCountArr[$before_rbv] = DB::raw($before_rbv.' + '.$request->bussiness);

	DB::table('tbl_today_details as a')
	->join('tbl_users as b','a.to_user_id', '=','b.id')
	->where('a.from_user_id','=',$request->id)
	->where('a.position','=',2)
	->update($updateRCountArr);

	$updateData[$powerbv] = $after_power;
	$userupdate =User::where('id', $request->id)->update($updateData);
	$current_time = date('Y-m-d H:i:s');
	$arrInsert = array(
	'user_id'=> $request->id,
	'position'=>$pos,
	'before_bv'=>$res[$powerbv],
	'bussiness_bv'=>$request->power,
	'after_bv'=>$after_power,
	'entry_time'=>$current_time,
	);

	$addpower = DB::table('tbl_add_remove_business_upline')->insert($arrInsert);

	if (!empty($arrInput)) {
	return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Rank Power added Successfully!', $arrInput);
	} else {
	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
	}
	} else {
	return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'User not found.', '');
	}
	}

	}*/
    public function addBussinessUplineBlade(){


        return view('admin.AddPower.addUplinePower');

    }
    public function addBussinessUpline(Request $request)
    {

        $arrInput = $request->all();

        //	print_r($arrInput);

        $rules     = array(
            'id' => 'required',
            'upline_id'=> 'required',
            'position' => 'required',
            'power_bv' => 'required'
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            //	dd($message);
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            $adminOtpStatusData = verifyAdminOtpStatus::select('add_bussiness_upline_otp_status')->first();
            $validator = Validator::make($arrInput, $rules);
            if ($validator->fails()) {
                $message = $validator->errors();


                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input field is required or invalid', $message);
            } else if ($adminOtpStatusData->add_bussiness_upline_otp_status == 1) {
                if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
                }


                $arrInput['user_id'] = Auth::User()->id;
                $arrInput['remark'] = 'admin power';
                $arrInput['otp'] = $request->otp;
                $verify_otp = verify_Admin_Withdraw_Otp($arrInput);
                if (!empty($verify_otp)) {
                    if ($verify_otp['status'] == 200) {
                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
                }
            }


            // $arrInput['user_id'] = Auth::User()->id;
            // $arrInput['remark'] = 'Add Upline Busniess';
            // $verify_otp = verify_Admin_Withdraw_Otp($arrInput);
            // if (!empty($verify_otp)) {
            // 	if ($verify_otp['status'] == 200) {
            // 	} else {
            // 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $verify_otp['msg'], '');
            // 	}
            // } else {
            // 	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Otp Request!', '');
            // }

            $user       = User::where('id', $arrInput['id'])->first();
            $before_lbv = 0;
            $before_rbv = 0;
            if (!empty($user)) {

                /*	if ($user->business_l_bv > 0 && $request->position == 2) {
				return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Cannot add power to right', '');
				} else if ($user->business_r_bv > 0 && $request->position == 1) {
				return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Cannot add power to left', '');
				}*/

                $before_lbv       = $user->l_bv;
                $before_rbv       = $user->r_bv;
                $before_power_lbv = $user->manual_power_lbv;
                $before_power_rbv = $user->manual_power_rbv;
                $before_curr_l_bv = $user->curr_l_bv;
                $before_curr_r_bv = $user->curr_r_bv;
                $position         = $arrInput['position'];
                $powerbv          = $arrInput['power_bv'];
                $new_lbv          = 0;
                $new_rbv          = 0;
                $new_curr_lbv     = 0;
                $new_curr_rbv     = 0;

                if ($position == 1) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {
                        /*if ($before_rbv >= $powerbv && $before_power_rbv >= $powerbv) {*/
                        $new_lbv       = $before_lbv - $powerbv;
                        $new_rbv       = $before_rbv;
                        $new_curr_lbv  = $before_curr_l_bv - $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv;
                        $new_power_lbv = $before_power_lbv - $powerbv;
                        $new_power_rbv = $before_power_rbv;
                        /*} else {
					return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User dont have too much Business to remove ', '');
					}*/
                    } else {
                        $new_lbv       = $before_lbv + $powerbv;
                        $new_rbv       = $before_rbv;
                        $new_curr_lbv  = $before_curr_l_bv + $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv;
                        $new_power_lbv = $before_power_lbv + $powerbv;
                        $new_power_rbv = $before_power_rbv;
                    }
                } elseif ($position == 2) {
                    if ($arrInput['type'] == 3 || $arrInput['type'] == 4) {
                        /*	if ($before_rbv >= $powerbv && $before_power_rbv >= $powerbv) {*/
                        $new_lbv       = $before_lbv;
                        $new_rbv       = $before_rbv - $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv - $powerbv;
                        $new_curr_lbv  = $before_curr_l_bv;
                        $new_power_lbv = $before_power_lbv;
                        $new_power_rbv = $before_power_rbv - $powerbv;
                        /*	} else {
					return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'User dont have too much Business to remove ', '');
					}*/
                    } else {
                        $new_lbv       = $before_lbv;
                        $new_rbv       = $before_rbv + $powerbv;
                        $new_curr_rbv  = $before_curr_r_bv + $powerbv;
                        $new_curr_lbv  = $before_curr_l_bv;
                        $new_power_rbv = $before_power_rbv + $powerbv;
                        $new_power_lbv = $before_power_lbv;
                    }
                }

                $user->l_bv      = $new_lbv;
                $user->r_bv      = $new_rbv;
                $user->curr_l_bv = $new_curr_lbv;
                $user->curr_r_bv = $new_curr_rbv;
                /*$user->power_l_bv = $new_power_lbv;
				$user->power_r_bv = $new_power_rbv;
				$user->business_l_bv = $new_power_lbv;
				$user->business_r_bv = $new_power_rbv;*/
                //$user->save();

                /*$updateLCountArr              = array();
				$updateLCountArr["l_bv"]      = DB::raw('l_bv + ' . $powerbv);
				$updateLCountArr["curr_l_bv"] = DB::raw('curr_l_bv + ' . $powerbv);

				DB::table('tbl_today_details as a')
					->join('tbl_users as b', 'a.to_user_id', '=', 'b.id')
					->where('a.from_user_id', '=', $request->id)
					->where('a.position', '=', 1)
					->update($updateLCountArr);

				$updateRCountArr              = array();
				$updateRCountArr["r_bv"]      = DB::raw('r_bv + ' . $powerbv);
				$updateRCountArr["curr_r_bv"] = DB::raw('curr_r_bv + ' . $powerbv);

				DB::table('tbl_today_details as a')
					->join('tbl_users as b', 'a.to_user_id', '=', 'b.id')
					->where('a.from_user_id', '=', $request->id)
					->where('a.position', '=', 2)
					->update($updateRCountArr);*/

                //Insert inn power Bv Table
                $power                    = array();
                $power['user_id']         = $arrInput['id'];
                $power['upline_id']         = $arrInput['upline_id'];
                $power['position']        = $arrInput['position'];
                $power['remark']          = $arrInput['remark'];
                $power['power_bv']        = $powerbv;
                $power['type']            = $arrInput['type'];
                $power['before_lbv']      = $before_lbv;
                $power['before_rbv']      = $before_rbv;
                $power['after_lbv']       = $new_lbv;
                $power['after_rbv']       = $new_rbv;
                $power['before_curr_lbv'] = $before_curr_l_bv;
                $power['before_curr_rbv'] = $before_curr_r_bv;
                /*$power['cron_status']     = "1";*/
                $power['after_curr_lbv']  = $new_curr_lbv;
                $power['after_curr_rbv']  = $new_curr_rbv;
                $power['entry_time']      = \Carbon\Carbon::now();
                DB::table('tbl_add_remove_business_upline')->insert($power);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Bussiness added successfully', '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
            }
        }
    }


    public function getRankCount(Request $request)
    {

        $id               = Auth::user()->id;
        $arrInput         = $request->all();
        $col_name         = $request->rank_name;
        $query            = User::select(DB::raw($col_name))->where('user_id', $request->user_id)->first();
        $arr['rank_name'] = $query[$col_name];
        if (!empty($arr)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Rank Power added Successfully!', $arr);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function updateBulkUsers(Request $request)
    {
        $arrInput = $request->all();

        $rules = array(
            'user_ids' => 'required',
            'fullname' => 'nullable|required_if:email,null|required_if:mobile,null|required_if:password,null|regex:/^[A-Za-z0-9 _]*[A-Za-z0-9][A-Za-z0-9 _]*$/',
            'email'    => 'nullable|required_if:fullname,null|required_if:mobile,null|required_if:password,null|required_if:country,null|email',
            'mobile'   => 'nullable|required_if:email,null|required_if:fullname,null|required_if:password,null|required_if:country,null|numeric',
            'password' => 'nullable|required_if:email,null|required_if:mobile,null|required_if:fullname,null|required_if:country,null|min:6',
            'country'  => 'nullable|required_if:email,null|required_if:mobile,null|required_if:fullname,null|required_if:password,null',
        );
        $ruleMessages = array(
            'fullname.regex' => 'Special characters not allowed in fullname.',
        );
        $validator = Validator::make($arrInput, $rules);
        // if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {
            $oldUsercount = User::whereIn('user_id', explode(',', $request->user_ids))->count('id');
            if ($oldUsercount > 0) {
                $updated_by = Auth::user()->id;

                $arrUpdate = $arrInsert = array();
                if (isset($arrInput['fullname']) && $arrInput['fullname'] != '') {
                    $arrUpdate['fullname'] = $arrInsert['fullname'] = $arrInput['fullname'];
                }
                if (isset($arrInput['email']) && $arrInput['email'] != '') {
                    $arrUpdate['email'] = $arrInsert['email'] = $arrInput['email'];
                }
                if (isset($arrInput['mobile']) && $arrInput['mobile'] != '') {
                    $arrUpdate['mobile'] = $arrInsert['mobile'] = $arrInput['mobile'];
                }
                if (isset($arrInput['country']) && $arrInput['country'] != '') {
                    $arrUpdate['country'] = $arrInsert['country'] = $arrInput['country'];
                }
                if (isset($arrInput['password']) && $arrInput['password'] != '') {
                    $arrUpdate['password']        = Crypt::encrypt($arrInput['password']);
                    $arrUpdate['bcrypt_password'] = bcrypt($arrInput['password']);
                    $arrInsert['password']        = md5($arrInput['password']);
                }

                $arrInsert['updated_by'] = $updated_by;
                $arrInsert['user_ids']   = $request->user_ids;
                $arrInsert['entry_time'] = \Carbon\Carbon::now();

                $userupdate = User::whereIn('user_id', explode(',', $request->user_ids))->update($arrUpdate);

                if (!empty($userupdate)) {
                    $insertlog = UserBulkUpdate::insert($arrInsert);
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'User data updated successfully.', '');
                } else {
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Data already existed with given inputs.', '');
                }
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'User not found', '');
            }
        }
    }

    public function BulkEditReport(Request $request)
    {

        $arrInput = $request->all();

        $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        if (!empty($userExist)) {
            $query = DB::table('tbl_user_bulk_update as bu')->select('bu.user_ids', 'bu.email', 'bu.mobile', 'bu.fullname', 'cn.country', DB::raw('DATE_FORMAT(bu.entry_time,"%Y/%m/%d") as entry_time'))->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'bu.country');

            if (isset($arrInput['user_id'])) {
                $query = $query->where('user_ids', 'LIKE', "%" . $arrInput['user_id'] . "%");
            }
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(bu.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
            }
            // if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
            // 	//searching loops on fields
            // 	$fields = getTableColumns('tbl_user_bulk_update');
            // 	$search = $arrInput['search']['value'];
            // 	$query = $query->where(function ($query) use ($fields, $search) {
            // 		foreach ($fields as $field) {
            // 			$query->orWhere($field, 'LIKE', '%' . $search . '%');
            // 		}
            // 	});
            // }

            if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
                $qry     = $query;
                $records = $qry->get();
                $res     = $records->toArray();
                if (count($res) <= 0) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
                }
                $var = $this->commonController->exportToExcel($res, "AllUsers");
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
            }

            $totalRecord = $query->count('bu.id');
            $query       = $query->orderBy('bu.entry_time', 'desc');
            // $totalRecord = $query->count();
            $powerbv = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $powerbv;

            if (count($powerbv) > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Unauthorised User.', '');
        }
    }

    public function getContestachievementreport(Request $request)
    {
        //$Checkexist = Auth::User();
        $arrInput = $request->all();

        $userdata = UserContestAchievement::select('tu.user_id', 'tu.fullname', 'cs.contest_prize', DB::raw('(CASE tbl_user_contest_achievment.claim_status WHEN 0 THEN "Not claimed" WHEN 1 THEN "Claimed" WHEN 3 THEN "Claimed Other" ELSE "Rejected" END) as claim_status'), 'tbl_user_contest_achievment.entry_time')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_user_contest_achievment.user_id')
            ->join('tbl_contest_setttings as cs', 'cs.id', '=', 'tbl_user_contest_achievment.contest_id');

        if (isset($arrInput['id'])) {
            $userdata = $userdata->where('tu.user_id', $arrInput['id']);
        }

        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
            $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
            $userdata             = $userdata->whereBetween(DB::raw("DATE_FORMAT(tbl_user_contest_achievment.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
        }

        $userdata    = $userdata->orderBy('tbl_user_contest_achievment.entry_time', 'desc');
        $totalRecord = $userdata->count('tbl_user_contest_achievment.id');

        $arrPendings = $userdata->skip($request->input('start'))->take($request->input('length'))->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrPendings;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }
    public function ManageUpdateProfile(Request $request)
    {
        $arrInput = $request->all();

        $query = DB::table('tbl_manage_update_profile');

        $query = $query->orderBy('srno', 'desc');
        if (isset($arrInput['start']) && isset($arrInput['length'])) {
            $arrData = setPaginate1($query, $arrInput['start'], $arrInput['length']);
        } else {
            $arrData = $query->get();
        }

        if ((isset($arrData['totalRecord']) > 0) || (count($arrData) > 0)) {

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Records not found', '');
        }
    }

    public function addfaq(Request $request)
    {
        $rules = array(
            'upload_file' => 'required',
            'name'        => 'required',
        );
        $messages = array(
            'photo.required' => 'Please select photo.',
            'name.required'  => 'Please enter name.',
        );

        $arrInput = $request->all();
        //dd($arrInput);
        $rules = array(
            // 'name' => 'required',
            // 'amount' => 'required',
            'category_id' => 'required',

        );
        if (!empty($request->upload_file)) {
            $imageName = time() . '.' . $request->upload_file->getClientOriginalExtension();
        }

        $power               = new AddFaq;
        $power->category_id  = $arrInput['category_id'];
        $power->faq_question = $arrInput['faq_question'];
        $power->faq_answer   = $arrInput['faq_answer'];
        $power->save();
        if (!empty($power)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'FAQAdd added successfully', '');
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding FAQ', '');
        }
    }
    public function AddGallery(Request $request)
    {
        $rules = array(
            'upload_file' => 'required',
            'name'        => 'required',
        );
        $messages = array(
            'photo.required' => 'Please select photo.',
            'name.required'  => 'Please enter name.',
        );

        $arrInput = $request->all();
        //dd($arrInput);
        $rules = array(
            // 'name' => 'required',
            // 'amount' => 'required',
            'name' => 'required',

        );
        if (!empty($request->upload_file)) {
            $imageName = time() . '.' . $request->upload_file->getClientOriginalExtension();
        }

        $power              = new SettingGallery;
        $power->name        = $arrInput['name'];
        $power->description = $arrInput['description'];
        $power->attachment  = $arrInput['attachment'];
        $power->save();
        if (!empty($power)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'SettingGallery added successfully', '');
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error while adding SettingGallery', '');
        }
    }

    public function reportaddfaq(Request $request)
    {
        // $getuser = Auth::user();
        $arrInput = $request->all();

        // $query  = AddFaq::select('tbl_faq_questions.*', 'tu.user_id as ref_user_id', 'tu.fullname as ref_fullname')->join('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')->first();

        $query = AddFaq::select('tbl_faq_questions.id', 'tcn.faq_category', 'tbl_faq_questions.faq_question', 'tbl_faq_questions.faq_answer', 'tbl_faq_questions.status')
            ->join('tbl_faq_category as tcn', 'tcn.category_id', '=', 'tbl_faq_questions.category_id')->where('status', '=', 'Active');
        $totalRecord  = $query->count('tbl_faq_questions.id');
        $query        = $query->orderBy('tbl_faq_questions.id', 'desc');
        $totalRecord  = $query->count();
        $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
        // dd($arrDirectInc);
        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrDirectInc;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function getreportgallery(Request $request)
    {

        $query = SettingGallery::select('*', DB::raw('DATE_FORMAT(created_at,"%Y/%m/%d %H:%i:%s") as created_at'))->where('status', 'Active');

        if (isset($request->frm_date) && isset($request->to_date)) {
            $query = $query->whereBetween(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"), [date('Y-m-d', strtotime($request->frm_date)), date('Y-m-d', strtotime($request->to_date))]);
        }
        if (isset($request->search['value']) && !empty($request->search['value'])) {
            //searching loops on fields
            $fields = getTableColumns('tbl_setting_gallery');
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $query       = $query->orderBy('id', 'desc');
        $totalRecord = $query->count();
        if (isset($request->start)) {
            $query = $query->skip($request->start);
        }
        if (isset($request->length)) {
            $query = $query->take($request->length);
        }
        $arrPushNotify = $query->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $arrPushNotify;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }

        // $arrInput = $request->all();
        // $query = SettingGallery::select('id','name','description','attachment','status')->where('status','=', 'Active');
        //     $totalRecord = $query->count('id');
        //     $query = $query->orderBy('id', 'desc');
        //     $totalRecord = $query->count();
        //     $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
        //     // dd($arrDirectInc);
        //     $arrData['recordsTotal'] = $totalRecord;
        //     $arrData['recordsFiltered'] = $totalRecord;
        //     $arrData['records'] = $arrDirectInc;

        //     if ($arrData['recordsTotal'] > 0) {
        //         return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        //     } else {
        //         return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        //     }
    }

    public function editaddfaq(Request $request)
    {

        $arrInput = $request->all();
        // dd($arrInput);
        //get user about data (personal data)
        $userProfile = DB::table('tbl_faq_questions')
            ->where('id', '=', $arrInput['id'])
            ->first();
        // dd($userProfile);
        //get user data by post data
        $getUserLogs = DB::table('tbl_faq_questions')
            ->selectRaw('id,category_id,faq_question,faq_answer')

            // ->orderBy('entry_time', 'desc')
            ->get();

        $arrFinalData['userProfile'] = $userProfile;

        $arrFinalData['userLogs'] = $getUserLogs;

        if (count($arrFinalData) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrFinalData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
    public function getupdategallery(Request $request)
    {

        $arrInput = $request->all();
        // dd($arrInput);
        //get user about data (personal data)
        $userProfile = DB::table('tbl_setting_gallery')
            ->where('id', '=', $arrInput['id'])
            ->first();
        // dd($userProfile);
        //get user data by post data
        $getUserLogs = DB::table('tbl_setting_gallery')
            ->selectRaw('id,name,description,attachment')

            // ->orderBy('entry_time', 'desc')
            ->get();

        $arrFinalData['userProfile'] = $userProfile;

        $arrFinalData['userLogs'] = $getUserLogs;

        if (count($arrFinalData) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrFinalData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
    public function geteditaddfaq(Request $request)
    {
        $arrInput = $request->all();
        $rules    = array(
            'category_id'  => 'required',
            'faq_question' => 'required',
            'faq_answer'   => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        try {
            $updateMemeber                 = array();
            $updateMemeber['category_id']  = $request->category_id;
            $updateMemeber['faq_question'] = $request->faq_question;
            $updateMemeber['faq_answer']   = $request->faq_answer;
            $updateMemeber                 = AddFaq::where('id', $arrInput['id'])->limit(1)->update($updateMemeber);
            $arrStatus                     = Response::HTTP_OK;
            $arrCode                       = Response::$statusTexts[$arrStatus];
            $arrMessage                    = 'UpdateFaq Data Updated';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        } catch (Exception $e) {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong. Please try agains';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function getSettingEditGallry(Request $request)
    {
        $arrInput = $request->all();
        // dd($arrInput);
        $rules = array(
            'name' => 'required',
            // 'description' => 'required',
            // 'photo' => 'required',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        try {
            $fileName = null;
            if ($request->hasFile('attachment')) {
                $file     = $request->file('attachment');
                // $fileName = time().'.'.$file->getClientOriginalExtension();
                // $file->move(public_path('/admin_assets/uploads/gallery'), $fileName);
                $request->merge(['filename' => $fileName]);
            }

            $updateMemeber                = array();
            $updateMemeber['name']        = $request->name;
            $updateMemeber['description'] = $request->description;
            $updateMemeber['attachment']  = $fileName;
            $updateMemeber                = SettingGallery::where('id', $arrInput['editUser'])->limit(1)->update($updateMemeber);
            $arrStatus                    = Response::HTTP_OK;
            $arrCode                      = Response::$statusTexts[$arrStatus];
            $arrMessage                   = 'EditSettingGallery Data Updated';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong. Please try agains';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    //   public function deleteFaq(Request $request)
    // 	{
    //   	$arrInput = $request->all();
    //   	$rules = array(
    // 	'id' => 'required',
    // );
    // $validator = Validator::make($request->all(), $rules);
    // if ($validator->fails()) {
    // 	$message = messageCreator($validator->errors());
    // 	return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
    // }
    //   	try{
    //           $updateFaq = array();
    //           $updateFaq['status'] = "Inactive";
    //           $updateMemeber = AddFaq::where('id', $arrInput['id'])->limit(1)->update($updateFaq);
    //           $arrStatus = Response::HTTP_OK;
    //           $arrCode = Response::$statusTexts[$arrStatus];
    //           $arrMessage = 'Deleted Faq Successful';
    //           return sendResponse($arrStatus, $arrCode, $arrMessage, '');
    //       }
    //       catch(Exception $e){
    //           $arrStatus = Response::HTTP_NOT_FOUND;
    //           $arrCode = Response::$statusTexts[$arrStatus];
    //           $arrMessage = 'Something went wrong. Please try agains';
    //           return sendResponse($arrStatus, $arrCode, $arrMessage, '');
    //       }
    //   }

    public function UpdateProfileCount(Request $request)
    {
        try {
            $user_id = Auth::user()->id;
            if (!empty($user_id)) {
                $id                  = $request->Input('srno');
                $updateData['count'] = $request->Input('count');

                $updateOtpSta = DB::table('tbl_manage_update_profile')->where('srno', $id)->update($updateData);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Updated Successfully', '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user!! ', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong!', '');
        }
    }

    public function UserAuthStatus(Request $request)
    {
        $arrInput = $request->all();
        // dd($arrInput);
        $rules = array(
            'id' => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        $chkstatus = User::select('auth_status')->where('id', $arrInput['id'])->first();
        // dd($chkstatus);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            /** @var [ins into Change History table] */
            if ($arrInput['auth_status'] == 'Active') {
                // $do = 'block';
                $status = 'Inactive';
                $msg    = 'User  Inactive successfully';
            } else {
                // $do = 'unblock';
                $status = 'Active';
                $msg    = 'User Auth status change successfully';
            }
            // dd($status);
            $block = User::where('id', $arrInput['id'])->update(['auth_status' => $status]);
            if (!empty($block)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Error occured while blocking user', '');
            }
        }
    }

    public function UserAuthStatusReport(Request $request)
    {

        $arrInput = $request->all();

        // $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        $query = User::select('user_id', 'auth_status', 'id')->where('auth_status', '=', 'Inactive');

        $query       = $query->orderBy('tbl_users.entry_time', 'desc');
        $totalRecord = $query->count();
        $powerbv     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $powerbv;

        if (count($powerbv) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function StopDirectIncome(Request $request)
    {
        // dd($request);
        try {
            // $user_id = Auth::user()->id;asSSSzxz
            // dd($user_id);
            $user_id = $request->id;
            if (!empty($user_id)) {
                // $stop_user_id = $request->Input('id');
                $status     = User::select('direct_income_status')->where('id', '=', $user_id)->first();
                $updateData = array();
                if ($status->direct_income_status == 0) {
                    $updateData['direct_income_status'] = 1;
                    $msg                                = "Stop Successfully";
                } else {
                    $updateData['direct_income_status'] = 0;
                    $msg                                = "Start Successfully";
                }

                $updateOtpSta = User::where('id', $user_id)->update($updateData);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user!! ', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong!', '');
        }
    }
    public function FundBlockReport(Request $request)
    {
        // dd($request);
        try {
            // $user_id = Auth::user()->id;asSSSzxz
            // dd($user_id);
            $user_id = $request->id;
            if (!empty($user_id)) {
                // $stop_user_id = $request->Input('id');
                $status     = User::select('transfer_block_by_admin')->where('id', '=', $user_id)->first();
                $updateData = array();
                if ($status->transfer_block_by_admin == "0") {
                    $updateData['transfer_block_by_admin'] = "1";
                    $msg                                   = "Stop Fund Transfer Successfully";
                } else {
                    $updateData['transfer_block_by_admin'] = "0";
                    $msg                                   = "Start Fund Transfer Successfully";
                }

                $updateOtpSta = User::where('id', $user_id)->update($updateData);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user!! ', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong!', '');
        }
    }
    public function WithdrawBlockReport(Request $request)
    {
        // dd($request);
        try {
            // $user_id = Auth::user()->id;asSSSzxz
            // dd($user_id);
            $user_id = $request->id;
            if (!empty($user_id)) {
                // $stop_user_id = $request->Input('id');
                $status     = User::select('withdraw_block_by_admin')->where('id', '=', $user_id)->first();
                $updateData = array();
                if ($status->transfer_block_by_admin == "0") {
                    $updateData['withdraw_block_by_admin'] = "1";
                    $msg                                   = "Stop Fund withdraw Successfully";
                } else {
                    $updateData['withdraw_block_by_admin'] = "0";
                    $msg                                   = "Start Fund withdraw Successfully";
                }

                $updateOtpSta = User::where('id', $user_id)->update($updateData);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user!! ', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong!', '');
        }
    }
    public function BlockFundTransfer(Request $request)
    {
        // dd($request);
        try {
            // $user_id = Auth::user()->id;asSSSzxz
            // dd($user_id);
            $user_id = $request->id;
            if (!empty($user_id)) {
                //dd($user_id);
                $stop_user_id = $request->Input('id');
                $status       = User::select('transfer_block_by_admin')->where('id', '=', $user_id)->first();
                $updateData   = array();
                if ($status->transfer_block_by_admin == "0" || $status->transfer_block_by_admin == 0) {
                    $updateData['transfer_block_by_admin'] = "1";
                    $msg                                   = "Stop Fund Transfer Successfully";
                } else {
                    $updateData['transfer_block_by_admin'] = "0";
                    $msg                                   = "Start Fund Transfer Successfully";
                }

                // $status = User::select('transfer_block_by_admin')->where('id', '=', $user_id)->first();
                // $updateData = array();
                // //dd($status);
                // if ($status->transfer_block_by_admin == "0") {

                // 	$updateData['transfer_block_by_admin'] = "1";
                // 	$msg = "Stop Successfully";
                // } else {
                // 	//$updateData['transfer_block_by_admin'] = "0";
                // 	$msg = "Alreday stopped by admin";
                // }

                $updateOtpSta = User::where('id', $user_id)->update($updateData);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user!! ', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong!', '');
        }
    }
    public function BlockFundWithdraw(Request $request)
    {
        // dd($request);
        try {
            // $user_id = Auth::user()->id;asSSSzxz
            // dd($user_id);
            $user_id = $request->id;
            if (!empty($user_id)) {
                //dd($user_id);
                $stop_user_id = $request->Input('id');
                $status       = User::select('withdraw_block_by_admin')->where('id', '=', $user_id)->first();
                $updateData   = array();
                if ($status->withdraw_block_by_admin == "0" || $status->withdraw_block_by_admin == 0) {
                    $updateData['withdraw_block_by_admin'] = "1";
                    $msg                                   = "Stop Fund Withdraw Successfully";
                } else {
                    $updateData['withdraw_block_by_admin'] = "0";
                    $msg                                   = "Start Fund Withdraw Successfully";
                }

                // $status = User::select('transfer_block_by_admin')->where('id', '=', $user_id)->first();
                // $updateData = array();
                // //dd($status);
                // if ($status->transfer_block_by_admin == "0") {

                // 	$updateData['transfer_block_by_admin'] = "1";
                // 	$msg = "Stop Successfully";
                // } else {
                // 	//$updateData['transfer_block_by_admin'] = "0";
                // 	$msg = "Alreday stopped by admin";
                // }

                $updateOtpSta = User::where('id', $user_id)->update($updateData);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user!! ', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong!', '');
        }
    }

    public function StopDirectReport(Request $request)
    {
        $arrInput = $request->all();

        // $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        $query = User::select('user_id', 'direct_income_status', 'id')->where('direct_income_status', '=', 1);

        $query       = $query->orderBy('tbl_users.entry_time', 'desc');
        $totalRecord = $query->count();
        $powerbv     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $powerbv;

        if (count($powerbv) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
    public function BlockFundReport(Request $request)
    {
        $arrInput = $request->all();

        // $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        $query = User::select('user_id', 'transfer_block_by_admin', 'id')->where([['transfer_block_by_admin', '=', "1"]]);
        //dd($query->toSql());

        $query       = $query->orderBy('tbl_users.entry_time', 'desc');
        $totalRecord = $query->count();
        $powerbv     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $powerbv;

        if (count($powerbv) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
    public function BlockwithdrawReport(Request $request)
    {
        $arrInput = $request->all();

        // $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        $query = User::select('user_id', 'withdraw_block_by_admin', 'id')->where([['withdraw_block_by_admin', '=', "1"]]);
        //dd($query->toSql());

        $query       = $query->orderBy('tbl_users.entry_time', 'desc');
        $totalRecord = $query->count();
        $powerbv     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $powerbv;

        if (count($powerbv) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function StopBinaryIncome(Request $request)
    {
        // dd($request);
        try {
            // $user_id = Auth::user()->id;asSSSzxz
            // dd($user_id);
            $user_id = $request->id;
            if (!empty($user_id)) {
                // $stop_user_id = $request->Input('id');
                $status     = User::select('binary_income_status')->where('id', '=', $user_id)->first();
                $updateData = array();
                if ($status->binary_income_status == 0) {
                    $updateData['binary_income_status'] = 1;
                    $msg                                = "Stop Successfully";
                } else {
                    $updateData['binary_income_status'] = 0;
                    $msg                                = "Start Successfully";
                }

                $updateOtpSta = User::where('id', $user_id)->update($updateData);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $msg, '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user!! ', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong!', '');
        }
    }

    public function StopBinaryReport(Request $request)
    {
        $arrInput = $request->all();

        // $userExist = User::where('id', '=', Auth::user()->id)->whereIn("type", ["admin"])->first();

        $query = User::select('user_id', 'binary_income_status', 'id')->where('binary_income_status', '=', 1);

        $query       = $query->orderBy('tbl_users.entry_time', 'desc');
        $totalRecord = $query->count();
        $powerbv     = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

        $arrData['recordsTotal']    = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records']         = $powerbv;

        if (count($powerbv) > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
    public function GetUserTreeImages(Request $request)
    {

        $objPasswordData = DB::table('tbl_tree_imges')->where([['type', '=', $request->input('type')]])->get();

        if (!empty($objPasswordData)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Images Found', $objPasswordData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Images Not Found', '');
        }
    }
    public function sendOtp(Request $request)
    {
        // $checotpstatus = Otp::where('id', '=', $users)->orderBy('entry_time', 'desc')->first();
        // //dd($checotpstatus);
        // if (!empty($checotpstatus)) {
        // 	$entry_time = $checotpstatus->entry_time;
        // 	$out_time = $checotpstatus->out_time;
        // 	$checkmin = date('Y-m-d H:i:s', strtotime('+10 minutes', strtotime($entry_time)));
        // 	$current_time = date('Y-m-d H:i:s');
        // }
        
        $user_exists = DB::table('tbl_users')
            ->select('id', 'email')
            ->where('user_id', $request->user_id)
            ->first();



        if (!empty($user_exists)) {
            $pagename = "emails.admin-emails.otp-mail";
            $subject = "OTP sent successfully";
            $random = rand(100000, 999999);
            $data = array('pagename' => $pagename, 'otp' => $random, 'username' => $request->user_id);

            //$mail = sendMail($data, $user_exists->email, $subject);

            if (!empty($user_exists->email)) {
                $email = explode(',', trim($user_exists->email));
            } else {
                // dd("Please Set Senders Email Id!!");
            }

            $mail = sendMail($data, $email, $subject);

            $insertotp = array();
            ///date_default_timezone_set("Asia/Kolkata");

            $otpExpireMit = Config::get('constants.settings.otpExpireMit');
            $mytime_new = \Carbon\Carbon::now();
            $expire_time = \Carbon\Carbon::now()->addMinutes($otpExpireMit)->toDateTimeString();
            $current_time_new = $mytime_new->toDateTimeString();
            $insertotp['entry_time'] = $current_time_new;
            $insertotp['id'] = $user_exists->id;
            $insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
            $insertotp['otp'] = md5($random);
            $insertotp['otp_status'] = 0;
            $insertotp['otpexpire'] = $expire_time;
            $insertotp['type'] = 'email';
            $sendotp = Otp::create($insertotp);
            

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid User', '');
        }
    }

    public function sendOtpWithdrawMailold(Request $request)
    {
        try {
            // dd($request->type);
            $admin = Auth::user();

            $adminaccess = $admin->admin_access;

            if (empty($admin->email)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please set email id', '');
            }

            if($adminaccess == 0)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You Dont Have Access For This Functionality', '');
            }

            $checotpstatus = Otp::where([['id', '=', $admin->id],])->orderBy('otp_id', 'desc')->first();
            // $users, $username, $type=null
            if (!empty($checotpstatus)) {
                $entry_time = $checotpstatus->entry_time;
                $out_time = $checotpstatus->out_time;
                $checkmin = date('Y-m-d H:i:s', strtotime('+2 hours', strtotime($entry_time)));
                $current_time = date('Y-m-d H:i:s');
            }
            //$temp_data = Template::where('title', '=', 'Otp')->first();
            //$project_set_data = ProjectSetting::select('icon_image','domain_name')->first();

            $pagename = "emails.admin-emails.otp-mail";
            $subject = "withdraw OTP"; //$temp_data->subject;
            $content = ''; //$temp_data->content;
            $domain_name = ''; //$project_set_data->domain_name;
            // $subject = "OTP sent successfully";
            $random = rand(1000000000, 9999999999);
            //$data = array('pagename' => $pagename, 'otp' => $random, 'username' => $admin->user_id,'content'=>$content,'domain_name' =>$domain_name);
            $data = array('pagename' => $pagename, 'otp' => $random, 'username' => $admin->user_id);
            if (!empty($admin->email)) {
                $email = explode(',', trim($admin->email));
            } else {
                dd("Please Set Senders Email Id!!");
            }

            //dd($data, $email, $subject);

            $mail = sendMail($data, $email, $subject);

            $insertotp = array();
            $insertotp['id'] = $admin->id;
            $insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
            $insertotp['otp'] = md5($random);
            $insertotp['otp_status'] = 0;
            $insertotp['type'] = 'email';
            $insertotp['otpexpire'] = \Carbon\Carbon::now()->addHours(2)->format('Y-m-d H:i:s');
            $insertotp['entry_time'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            if ($request->type == 1) {

                $insertotp['remark'] = 'admin withdraw';
            } else if ($request->type == 2) {

                $insertotp['remark'] = 'admin topup';
                // dd($request->type);
            } else if ($request->type == 3) {

                $insertotp['remark'] = 'admin edit profile';
            } else {

                $insertotp['remark'] = '';
            }
            // dd($insertotp['remark']);
            $sendotp = Otp::create($insertotp);
            //dd($mail , $sendotp);
            if ($sendotp) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please Try again!', '');
            }

            //}  // end of users
        } catch (\Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again!!', '');
        }
    }
    public function sendOtpWithdrawMail(Request $request)
    {
        try {
            $admin = Auth::user();

            if (empty($admin->email)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please set email id', '');
            }

            $adminaccess = $admin->admin_access;

            if($adminaccess == 0 && $admin->type == "Admin")
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You Dont Have Access For This Functionality', '');
            }

            $checotpstatus = Otp::where([['id', '=', $admin->id],])->orderBy('otp_id', 'desc')->first();
            // $users, $username, $type=null
            if (!empty($checotpstatus)) {
                $entry_time = $checotpstatus->entry_time;
                $out_time = $checotpstatus->out_time;
                $checkmin = date('Y-m-d H:i:s', strtotime('+2 hours', strtotime($entry_time)));
                $current_time = date('Y-m-d H:i:s');
            }
            //$temp_data = Template::where('title', '=', 'Otp')->first();
            //$project_set_data = ProjectSetting::select('icon_image','domain_name')->first();

            $pagename = "emails.admin-emails.otp-mail";
            $subject = "OTP"; //$temp_data->subject;
            $content = ''; //$temp_data->content;
            $domain_name = ''; //$project_set_data->domain_name;
            // $subject = "OTP sent successfully";
            $random = rand(1000000000, 9999999999);
            /*$random = 1234567890;*/
            //$data = array('pagename' => $pagename, 'otp' => $random, 'username' => $admin->user_id,'content'=>$content,'domain_name' =>$domain_name);
            $data = array('pagename' => $pagename, 'otp' => $random, 'username' => $admin->user_id);
            if (!empty($admin->email)) {
                $email = explode(',', trim($admin->email));
            } else {
                dd("Please Set Senders Email Id!!");
            }

            //dd($data, $email, $subject);

            $mail = sendMail($data, $email, $subject);

            $admin_expire_hr = Config::get('constants.settings.adminOtpExpireHr');
            $insertotp = array();
            $insertotp['id'] = $admin->id;
            $insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
            // $insertotp['otp'] = md5($random);
            $insertotp['otp'] = hash('sha256',$random);
            $insertotp['otp_status'] = 0;
            $insertotp['type'] = 'email';
            $insertotp['otpexpire'] = \Carbon\Carbon::now()->addHours($admin_expire_hr)->format('Y-m-d H:i:s');
            // $insertotp['otpexpire'] = \Carbon\Carbon::now()->addHours(2)->format('Y-m-d H:i:s');
            $insertotp['entry_time'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            if ($request->type == 1) {

                $insertotp['remark'] = 'admin withdraw';
            } else if ($request->type == 2) {

                $insertotp['remark'] = 'admin topup';
            } else if ($request->type == 3) {

                $insertotp['remark'] = 'admin edit profile';
            } else if ($request->type == 4) {

                $insertotp['remark'] = 'admin fund';
            } else if ($request->type == 5) {

                $insertotp['remark'] = 'admin remove fund';
            } else if ($request->type == 6) {

                $insertotp['remark'] = 'admin power';
            } else if ($request->type == 7) {

                $insertotp['remark'] = 'admin_verify_withdraw';
            } else if ($request->type == 8) {

                $insertotp['remark'] = 'admin_change_password';
            } else if ($request->type == 9) {

                $insertotp['remark'] = 'Add Busniess';
            }
            else if ($request->type == 10) {

                $insertotp['remark'] = 'Add Upline Busniess';
            }
            else if ($request->type == 11) {

                $insertotp['remark'] = 'Add Rank';
            }
            else if ($request->type == 12) {

                $insertotp['remark'] = 'Add Rank Power';
            }
            else {

                $insertotp['remark'] = '';
            }

            $sendotp = Otp::create($insertotp);
            //dd($mail , $sendotp);
            if ($sendotp) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please Try again!', '');
            }

            //}  // end of users
        } catch (\Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again!!', '');
        }
    }
    public function ManageUserAccountBlade(Request $request){

        return view('admin.ManageUser.ManageUserAccount');
    }

    public function changePasswordBlade(Request $request){

        return view('admin.ManageUser.changePassword');
    }


    
    // public function sendOtpWithdrawMail(Request $request)
    // {
    // 	try {
    // 		$admin = Auth::user();

    // 		if (empty($admin->email)) {
    // 			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please set email id', '');
    // 		}
    // 		$checotpstatus = Otp::where([['id', '=', $admin->id],])->orderBy('otp_id', 'desc')->first();
    // 		// $users, $username, $type=null
    // 		if (!empty($checotpstatus)) {
    // 			$entry_time = $checotpstatus->entry_time;
    // 			$out_time = $checotpstatus->out_time;
    // 			$checkmin = date('Y-m-d H:i:s', strtotime('+2 hours', strtotime($entry_time)));
    // 			$current_time = date('Y-m-d H:i:s');
    // 		}
    // 		//$temp_data = Template::where('title', '=', 'Otp')->first();
    // 		//$project_set_data = ProjectSetting::select('icon_image','domain_name')->first();

    // 		$pagename = "emails.admin-emails.otp-mail";
    // 		$subject = "OTP"; //$temp_data->subject;
    // 		$content = ''; //$temp_data->content;
    // 		$domain_name = ''; //$project_set_data->domain_name;
    // 		// $subject = "OTP sent successfully";
    // 		$random = rand(1000000000, 9999999999);
    // 		//$data = array('pagename' => $pagename, 'otp' => $random, 'username' => $admin->user_id,'content'=>$content,'domain_name' =>$domain_name);
    // 		$data = array('pagename' => $pagename, 'otp' => $random, 'username' => $admin->user_id);
    // 		if (!empty($admin->email)) {
    // 			$email = explode(',', trim($admin->email));
    // 		} else {
    // 			dd("Please Set Senders Email Id!!");
    // 		}

    // 		//dd($data, $email, $subject);

    // 		$mail = sendMail($data, $email, $subject);

    // 		$admin_expire_hr = Config::get('constants.settings.adminOtpExpireHr');
    // 		$insertotp = array();
    // 		$insertotp['id'] = $admin->id;
    // 		$insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
    // 		$insertotp['otp'] = md5($random);
    // 		$insertotp['otp_status'] = 0;
    // 		$insertotp['type'] = 'email';
    // 		$insertotp['otpexpire'] = \Carbon\Carbon::now()->addHours($admin_expire_hr)->format('Y-m-d H:i:s');
    // 		// $insertotp['otpexpire'] = \Carbon\Carbon::now()->addHours(2)->format('Y-m-d H:i:s');
    // 		$insertotp['entry_time'] = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
    // 		if ($request->type == 1) {

    // 			$insertotp['remark'] = 'admin withdraw';
    // 		} else if ($request->type == 2) {

    // 			$insertotp['remark'] = 'admin topup';
    // 		} else if ($request->type == 3) {

    // 			$insertotp['remark'] = 'admin edit profile';
    // 		} else if ($request->type == 4) {

    // 			$insertotp['remark'] = 'admin fund';
    // 		} else if ($request->type == 5) {

    // 			$insertotp['remark'] = 'admin remove fund';
    // 		} else if ($request->type == 6) {

    // 			$insertotp['remark'] = 'admin power';
    // 		} else if ($request->type == 7) {

    // 			$insertotp['remark'] = 'admin_verify_withdraw';
    // 		} else if ($request->type == 8) {

    // 			$insertotp['remark'] = 'admin_change_password';
    // 		} else if ($request->type == 9) {

    // 			$insertotp['remark'] = 'Add Busniess';
    // 		} else if ($request->type == 10) {

    // 			$insertotp['remark'] = 'Add Upline Busniess';
    // 		} else {

    // 			$insertotp['remark'] = '';
    // 		}

    // 		$sendotp = Otp::create($insertotp);
    // 		//dd($mail , $sendotp);
    // 		if ($sendotp) {
    // 			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
    // 		} else {
    // 			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Please Try again!', '');
    // 		}

    // 		//}  // end of users
    // 	} catch (\Exception $e) {
    // 		dd($e);
    // 		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again!!', '');
    // 	}
    // }

}
