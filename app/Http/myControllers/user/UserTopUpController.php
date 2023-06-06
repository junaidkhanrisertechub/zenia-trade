<?php

namespace App\Http\Controllers\user;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response as Response;
use App\Models\Product;
use App\Models\Topup;
use App\Models\Dashboard;
use App\Models\TodayDetails;
use App\Models\Currency;
use App\Models\User;
use App\Models\WithdrawPending;
use App\Models\Withdrawbydate;
use App\Models\WithdrawSettings;
use App\Models\AllTransaction;
use App\Models\Activitynotification;
use App\Models\FundRequest;
use App\Models\Invoice;
use App\Models\Packages;
use App\Models\ProjectSettings;
use App\Models\TopupRequest;
use App\Models\UserWithdrwalSetting;
use App\Models\TransactionInvoice;
use App\Models\UserStructureModel;
use App\Models\WalletTransactionLog;
use App\Models\UserSettingFund;
use App\Traits\Income;
use App\Models\WhiteListIpAddress;
use App\Models\verifyOtpStatus;

use App\Traits\Users;
use Validator;
use Config;
use DB;
use Exception;
use PDOException;
use Auth;
use URL;
use DataTables;

use App\Http\Controllers\user\Google2FAController;

class UserTopUpController extends Controller {
use Income;
use Users;
    public function __construct(Google2FAController $google2facontroller) {

        $this->statuscode = Config::get('constants.statuscode');
        $date = \Carbon\Carbon::now();
        $this->emptyArray      = (object) array();
        $this->today = $date->toDateTimeString();
        $this->google2facontroller = $google2facontroller;

    }

     /**
     * For get product
     *
     * @return \Illuminate\Http\Response
     */

    public function getTopup(){
     

        //->where('cost', '=', 1) 
        $get_all_products =  Product::where('status', '=', 'Active')->where('cost', '=', 1)->get();
        $userid = Auth::user()->id;
        $getBalance=Dashboard::select('fund_wallet', 'fund_wallet_withdraw','roi_wallet','roi_wallet_withdraw','hscc_bonus_wallet','hscc_bonus_wallet_withdraw','working_wallet','working_wallet_withdraw')->where('id',$userid)->first();   
        $data = array("all_products" => $get_all_products, "getBalance" => $getBalance);      
        return view('user.product.topup')->with($data);

    }


    public function withdrawal(){
        // $day = date('D');
        // if ($day != "Mon") {
        //    toastr()->error("Withdrawal are available only on Monday");
        //    return redirect()->back();
        // }
        
        $userid = Auth::user()->id;
        $getBalance=Dashboard::select('fund_wallet', 'fund_wallet_withdraw','roi_wallet','roi_wallet_withdraw','hscc_bonus_wallet','hscc_bonus_wallet_withdraw','working_wallet','working_wallet_withdraw', 'direct_income', 'direct_income_withdraw', 'roi_income_withdraw', 'roi_income', 'binary_income', 'binary_income_withdraw')->where('id',$userid)->first(); 

        $arrCurrency = Currency::where('withdrwal_status', '=', '1')->get();

        $data = array("getBalance" => $getBalance, "getAllCurrency" => $arrCurrency);      
        
      
       
          return view('user.product.withdrawal')->with($data);

    }


    public function getProduct(){
     
       $packages =  Product::where('status', '=', 'Active')->get();     
   
      if (!empty($packages) && count($packages) > 0) {
                $arrStatus = Response::HTTP_OK;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Packages found successfully';
                return sendResponse($arrStatus, $arrCode, $arrMessage, $packages);

            } else {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }

    }

     /**
     * For get downline user
     *
     * @return \Illuminate\Http\Response
     */
    public function checkUserExistDownlineNew(Request $request)
    {
        
        $arrInput = $request->all();
        //validate the info, create rules for the inputs
        $rules = array(
            'user_id' => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            $checkUserExist = User::select('tbl_users.user_id')
                ->join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_users.id')
                ->where(['tbl_users.user_id' => $arrInput['user_id'], 'ttd.to_user_id' => Auth::user()->id])
                ->first();

            if (!empty($checkUserExist)) {
                $arrObject['id'] = $checkUserExist->id;
                $arrObject['user_id'] = $checkUserExist->user_id;
                $arrObject['fullname'] = $checkUserExist->fullname;

                $arrStatus = Response::HTTP_OK;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'User available';
                return sendResponse($arrStatus, $arrCode, $arrMessage, $arrObject);
            } else {
                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Not an downline user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        }
    }

    public function SendOtpForSelfTopup(Request $request)
    {
        // dd($request);

        /* $intCode = Response::HTTP_NOT_FOUND;
        $strStatus = Response::$statusTexts[$intCode];
        $strMessage = 'Your gateway to a strong financial future is going to open soon!';
        return sendResponse($intCode, $strStatus, $strMessage, array());

        dd("stop"); */

        $rules = array(
            'product_id'       => 'required',
            'hash_unit'        => 'required|numeric|min:1',
            'user_id'          => 'required',
            'transcation_type' => 'required',
            /*'masterfranchise_user_id' => 'required',*/
        );
        $validator = checkvalidation($request->all(), $rules, '');
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
        }

        // check user is from same browser or not
        // $req_temp_info = $request->header('User-Agent');
        // $result        = check_user_authentication_browser($req_temp_info, Auth::user()
        //     ->temp_info);
        // if ($result == false) {
        //     return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
        // }

        /*$auth_user = User::select('tbl_dashboard.top_up_wallet','tbl_dashboard.top_up_wallet_withdraw','tbl_dashboard.total_withdraw','tbl_users.id')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();*/
        $users = User::select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.virtual_parent_id', 'tbl_users.ref_user_id', 'tbl_dashboard.total_investment', 'tbl_dashboard.active_investment', 'tbl_users.position')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.user_id', '=', $request->user_id], ['tbl_users.status', '=', 'Active']])->first();
        // dd($users);
        if (empty($users)) {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This User ID is Blocked', $this->emptyArray);
        }

        $check_record = whiteListIpAddress($type = 1, Auth::user()->id);
        // dd($check_record);
        $ip_Address = getIpAddrss();
        $check_user_hits = WhiteListIpAddress::select('id', 'topup_status', 'topup_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
        // dd($check_user_hits);
        if (!empty($check_user_hits)) {
            if ($check_user_hits->topup_status == 1) {
                $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                if ($check_user_hits->topup_expire >= $today) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block for 1 hour!', $this->emptyArray);
                }
            }
        }


        // if ($request->transcation_type != 2 && $request->transaction_type != 1) {

        //  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
        // }

        $checktopup = Topup::where([['id', $users->id]])->count();
        // dd($request->transcation_type);
        // if ($checktopup > 0) {
        //   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one topup allowed for one user', '');
        // }

        /* to check user downline or not */
        $id = $users->id;
        $users_id = Auth::User()->id;
        $userId = $to_user_id = $users_id;
        // dd($to_user_id);
        if ($to_user_id != $id) {
            $todaydetailsexist = TodayDetails::where('to_user_id', $to_user_id)->where('from_user_id', $id)->get();
            // dd($todaydetailsexist);
            if (count($todaydetailsexist) == 0) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User is not Downline User', '');
            }
        }
        $projectSettings = ProjectSettings::where('status', 1)
            ->select('topup_status', 'topup_msg')->first();
        if ($projectSettings->topup_status == "off") {
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = $projectSettings->topup_msg;;
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
        // check user is from same browser or not

        $user = Auth::User();
        $id = Auth::User()->id;
        //dd($request->type);

        $result = SendOtpForAll($user);

        if ($result) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
        }
    }

    // Self Topup by user

    public function userSelfTopup(Request $request) {

        // $intCode = Response::HTTP_NOT_FOUND;
        // $strStatus = Response::$statusTexts[$intCode];
        // $strMessage = 'Your gateway to a strong financial future is going to open soon!';
        // return sendResponse($intCode, $strStatus, $strMessage, array());

        try {
            $rules = array(
                'product_id'       => 'required',
                'hash_unit'        => 'required|numeric|min:1',
                'user_id'          => 'required',
                'transcation_type' => 'required',

            );
            $validator = checkvalidation($request->all(), $rules, '');
            if (!empty($validator)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
            }



            /*$auth_user = User::select('tbl_dashboard.top_up_wallet','tbl_dashboard.top_up_wallet_withdraw','tbl_dashboard.total_withdraw','tbl_users.id')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();*/
            $users = User::select('tbl_users.id','tbl_users.email','tbl_users.topup_status', 'tbl_users.user_id', 'tbl_users.virtual_parent_id', 'tbl_users.ref_user_id', 'tbl_dashboard.total_investment', 'tbl_dashboard.active_investment', 'tbl_users.position')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.user_id', '=', $request->user_id], ['tbl_users.status', '=', 'Active']])->first();
            if (empty($users)) {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This User ID is Blocked', $this->emptyArray);
            }

            $check_record = whiteListIpAddress($type=1,Auth::user()->id);
            // dd($check_record);
            $ip_Address = getIpAddrss();
            $check_user_hits = WhiteListIpAddress::select('id', 'topup_status', 'topup_expire')->where([['uid',Auth::user()->id],['ip_add',$ip_Address]])->first();
            // dd($check_user_hits);
            if(!empty($check_user_hits)){
                if($check_user_hits->topup_status == 1){
                    $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    if($check_user_hits->topup_expire >= $today){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Due to too many request hits, temporary you are block for 1 hour!', $this->emptyArray);
                    }
                }
            }
            
            
            // if ($request->transcation_type != 2 && $request->transaction_type != 1) {

            //  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
            // }

            // dd($request->transcation_type);
            // if ($checktopup > 0) {
            //   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one topup allowed for one user', '');
            // }
            $checktopup = Topup::where([['id', $users->id]])->count();

            /* to check user downline or not */
            $id = $users->id;
            $users_id = Auth::User()->id;
            $userId = $to_user_id = $users_id;
            $user_name = Auth::User()->user_id;
            if($to_user_id != $id){
                $todaydetailsexist = TodayDetails::where('to_user_id', $to_user_id)->where('from_user_id',$id)->get();
                if(count($todaydetailsexist) == 0){
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not Downline User','');
                }
            }
            $getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
            /*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Topup amount should be greater or equal to last topup amount.';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }*/
            $projectSettings = ProjectSettings::where('status', 1)
                ->select('topup_status', 'topup_msg')   ->first();
            if ($projectSettings->topup_status == "off") {
                $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = $projectSettings->topup_msg;
                ;
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }

            /* to check user downline or not */
            $profile_status = verifyOtpStatus::select('topup_update_status')
            ->where('statusID','=',1)->get();
                $arrInput            = $request->all();
            $userData=User::where('id',$users_id)->first();
            if ($profile_status[0]->topup_update_status == 1) 
            {
                if ($userData->google2fa_status=='disable') {

                    $arrInput['user_id'] = Auth::user()->id;
                    $arrRules            = ['otp' => 'required|min:6|max:6'];
                    $validator           = Validator::make($arrInput, $arrRules);
                    if ($validator->fails()) 
                    {
                        return setValidationErrorMessage($validator);
                    }
                    $otpdata         = verify_Otp($arrInput);
                } else {
                    $otpdata['status'] = 200;
                }
                
                
            } else 
            {
                $otpdata['status'] = 200;
            }



            if($userData->google2fa_status=='enable') {
                $arrIn  = array();

                $arrIn['id']=$users_id;
                $arrIn['otp']=$arrInput['otp_2fa'];
                $arrIn['google2fa_secret'] = $userData->google2fa_secret;
                if (empty($arrIn['otp'])) {
                    $arrOutputData = [];
                    $strMessage = "Google 2FA code Required";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
                $res=$this->google2facontroller->validateGoogle2FA($arrIn);
                if ($res == false) {
                    $arrOutputData = [];
                    $strMessage = "Invalid Google 2FA code";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
            }
            
            if ($otpdata['status'] == 200) 
            {
            $getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
            /*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Topup amount should be greater or equal to last topup amount.';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }*/
            /*if ($request->hash_unit < 100) {

            $arrStatus = Response::HTTP_NOT_FOUND;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Please check Topup amount now start from 100';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }*/
            if (!empty($getPrice)) {

                $date     = $getPrice->entry_time;
                $datework = \Carbon\Carbon::parse($date);
                $now      = \Carbon\Carbon::now();
                $testdate = $datework->diffInMinutes($now);
                //$testdate1 = $datework->diffInDays($now);

                //dd($getPrice->entry_time, $now, $this->today, $testdate, $testdate1);
                // if ($testdate <= 2) {

                //  $arrStatus  = Response::HTTP_NOT_FOUND;
                //  $arrCode    = Response::$statusTexts[$arrStatus];
                //  $arrMessage = 'Try Next Topup after 2 Minutes';
                //  return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                // }

            } else {
                //dd(22);
            }

            if (!empty($users)) {
                $packageExist = Packages::where([['id', '=', $request->Input('product_id')],["user_show_status",'=',"Active"]])->first();
                if (!empty($packageExist)) {
                    $pacakgeId = $packageExist->id;
                    $ProductRoi = $packageExist->roi;
                    $TotalRoiPer = $packageExist->total_roi_percentage;
                    $ProductDuration = $packageExist->duration;
                    $Productcost = $request->hash_unit;
                    $direct_income = $packageExist->direct_income;
                    $binary_cap = $packageExist->capping;
                    
                    $percentage = ($ProductRoi * $ProductDuration);//144
                    $binaryPer = $packageExist->binary;
                    $directPer = $packageExist->direct_income;
                    $totalIncomePer = $packageExist->total_income_percentage;
                    $product_name = $packageExist->package_name;
                    $product_type = $packageExist->package_type;
                    $product_range = $packageExist->name;
                    //if($Productcost <= $request->Input('usd')){

                    /*$UserToupWallet = ($auth_user->top_up_wallet - $auth_user->top_up_wallet_withdraw);*/
                    $purchase_deduct = $fund_deduct = 0;
                
                    if ($request->transcation_type == 2) {

                        $topupbalance = Dashboard::where('id', $users_id)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as balance')->pluck('balance')->first();

                        if ($Productcost > $topupbalance) {
                            $arrStatus  = Response::HTTP_NOT_FOUND;
                            $arrCode    = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Insufficient Fund Wallet Balance';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                        }
                    } elseif ($request->transcation_type == 1) {

                        $per = UserSettingFund::select('topup_percentage')->where('user_id',$users_id)->orderBy('id','desc')->first();
                        $topup_per = $per->topup_percentage;
                        $balance = Dashboard::where('id',$userId)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as fundbalance,round(setting_fund_wallet - setting_fund_wallet_withdraw,2) as purchasebalance')->first(); 
                        
                        $half_of_requested_amount = ($Productcost *$topup_per)/100;

                        if($balance->purchasebalance < 1)
                        {
                            $arrMessage = 'Insufficient Setting Fund Balance';
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
                        }elseif ($balance->purchasebalance >= $half_of_requested_amount) {
                            $purchase_deduct = $half_of_requested_amount;
                        }else{
                            $purchase_deduct = $balance->purchasebalance;
                        }                   
                        $half_of_requested_amount = $Productcost - $purchase_deduct;
                        if($half_of_requested_amount > $balance->fundbalance)
                        {
                            $arrMessage = 'Insufficient Fund Wallet Balance';
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                        }else{
                            $fund_deduct = $half_of_requested_amount;
                        }           
        

                    } else {

                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
                    }

                    $getPreviousTopup = Topup::join('tbl_product as tp','tp.id','tbl_topup.type')->select('tp.cost','tbl_topup.*')->where('tbl_topup.id',$users->id)->orderBy('tbl_topup.entry_time','desc')->first();

                    //dd($getPreviousTopup);
                    if(!empty($getPreviousTopup)){
                        $topupno = $getPreviousTopup->topup_no + 1;
                        /*$topupno = $getPreviousTopup->topup_no + 1;
                        if($getPreviousTopup->amount > $request->hash_unit){
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Topup must be greater or equal to previous topup', '');
                        }*/

                        /*return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User have only one topup', '');*/
                    }else{
                        $topupno = 1;
                    }

                    // if ($UserToupWallet >= $Productcost) {

                    /*$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount')->orderBy('srno', 'desc')->first();*/

                    if ($Productcost >= $packageExist->min_hash && $Productcost <= $packageExist->max_hash) {

                        if ($Productcost >= 10000) {
                            $amount = $Productcost;

                        } else {
                            $amount = $Productcost;
                        }

                        $updateCoinData        = array();
                        $updateCoinData['usd'] = custom_round(($users->usd-$Productcost), 7);
                        $logArr                = $fundArr                = $purchaseArr                = array();
                        if ($request->transcation_type == 2) {
                            $updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$Productcost);
                            $fundArr['from_user_id']                = $userId;
                            $fundArr['to_user_id']                  = $users->id;
                            $fundArr['amount']                      = $Productcost;
                            $fundArr['wallet_type']                 = 3;
                            $fundArr['remark']                      = "Topup from fund wallet";
                            $fundArr['entry_time']                  = $this->today;
                        }
                        if ($request->transcation_type == 1) {
                            $updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_deduct);
                            $fundArr['from_user_id']                = $userId;
                            $fundArr['to_user_id']                  = $users->id;
                            $fundArr['amount']                      = $fund_deduct;
                            $fundArr['wallet_type']                 = 3;
                            $fundArr['remark']                      = "Topup from Fund Wallet(min ".(100-$topup_per)."%)";
                            $fundArr['entry_time']                  = $this->today;

                            $updateCoinData['setting_fund_wallet_withdraw'] = DB::raw('setting_fund_wallet_withdraw + '.$purchase_deduct);
                            $purchaseArr['from_user_id']              = $userId;
                            $purchaseArr['to_user_id']                = $users->id;
                            $purchaseArr['amount']                    = $purchase_deduct;
                            $purchaseArr['wallet_type']               = 4;
                            $purchaseArr['remark']                    = "Topup from Setting Fund wallet (max ".$topup_per."%)";
                            $purchaseArr['entry_time']                = $this->today;
                        }
                        (count($fundArr) > 0)?array_push($logArr, $fundArr):'';
                        (count($purchaseArr) > 0)?array_push($logArr, $purchaseArr):'';
                        WalletTransactionLog::insert($logArr);
                        $updateCoinData['total_withdraw'] = DB::raw('round(total_withdraw + '.$Productcost.',2)');

                        $updateCoinData = Dashboard::where('id', $userId)->update($updateCoinData);

                        $updateCoinData1['total_investment']  = DB::raw('round(total_investment + '.$Productcost.',2)');
                        $updateCoinData1['active_investment'] = DB::raw('round(active_investment + '.$Productcost.',2)');

                        $updateCoinData1 = Dashboard::where('id', $users->id)->update($updateCoinData1);
                        $topupfrom       = "-";
                        if ($request->transcation_type == 1) {
                            $topupfrom ="Setting Fund wallet (max ".$topup_per."%) + Fund Wallet (min ".(100-$topup_per)."%)";
                        } elseif ($request->transcation_type == 2) {
                            $topupfrom = "New Fund Wallet";
                        }

                        $check_topup_count = Topup::where('id',$users->id)->count();
                        
                        $random = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
                        $Topupdata = array();
                        $Topupdata['id'] = $users->id;
                        $Topupdata['pin'] = $random;
                        $Topupdata['amount'] = $amount;
                        $Topupdata['amount_roi'] = ($amount*$ProductRoi)/100;
                        $Topupdata['binary_capping'] = $binary_cap;
                        $Topupdata['percentage'] = $ProductRoi;
                        $Topupdata['direct_roi'] = ($amount*$directPer)/100;
                        // $Topupdata['total_roi'] = ($amount * $TotalRoiPer) / 100;
                        $Topupdata['binary_percentage'] = $binaryPer;
                        $Topupdata['total_income'] = ($amount * $totalIncomePer) / 100;
                        $Topupdata['type'] = $pacakgeId;
                        $Topupdata['top_up_by'] = $userId;
                        $Topupdata['duration'] = $packageExist->duration;
                        $Topupdata['product_name'] = $product_name;
                        /*  $Topupdata['franchise_id'] = $franchise_user->id;
                        $Topupdata['master_franchise_id'] = $franchise_user->id;*/
                        // $Topupdata['remark'] = $request->topupfrom;
                        if($users->id == $userId) {
                        $Topupdata['remark'] = "Self Topup";
                        }else{
                        $Topupdata['remark'] = "Topup from ".$user_name."";                         
                        }
                        $Topupdata['usd_rate'] = '0';
                        $Topupdata['topupfrom'] = $topupfrom;
                        $Topupdata['roi_status'] = 'Active';
                        $Topupdata['top_up_type'] = 3;
                        $Topupdata['binary_pass_status'] = '1';
                        $Topupdata['total_usd'] = 0.001;
                        $Topupdata['fund_wallet_usage'] = $amount;
                        $Topupdata['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $Topupdata['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                        $Topupdata['last_roi_entry_time'] = \Carbon\Carbon::now()->toDateTimeString();

                        if ($request->Input('device') != '') {
                            $Topupdata['device'] = $request->Input('device');
                        } else {
                            $Topupdata['device'] = '-';
                        }
                        $storeId = Topup::insertGetId($Topupdata);
                        $this->add_transaction_activity(Auth::User()->id,1,'Activation using Fund Wallet',0,$amount,$topupbalance,custom_round($topupbalance-$amount,2));

                        if (!empty($storeId)) {
                            //-----check which plan is on in setting--------
                            $plan = ProjectSettings::selectRaw('(CASE level_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as level_plan,(CASE binary_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as binary_plan ,(CASE direct_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END ) as direct_plan')->first();

                            if (!empty($plan)) {
                                $level_plan      = $plan->level_plan;
                                $binary_plan     = $plan->binary_plan;
                                $direct_plan     = $plan->direct_plan;
                                $leadership_plan = $plan->leadership_plan;

                                if ($level_plan == '1') {
                                    $getlevel = $this->pay_level($users->id, $Productcost, $pacakgeId);
                                }

                                if ($binary_plan == '1') {

                                    
                                    // update direct business

                                    $updateLCountArrDirectBusiness               = array();
                                    $updateLCountArrDirectBusiness['power_l_bv'] = DB::raw('power_l_bv + '.$Productcost.'');

                                    $updateRCountArrDirectBusiness               = array();
                                    $updateRCountArrDirectBusiness['power_r_bv'] = DB::raw('power_r_bv + '.$Productcost.'');

                                    if ($users->position == 1) {
                                        User::where('id', $users->ref_user_id)->update($updateLCountArrDirectBusiness);
                                    } else if ($users->position == 2) {
                                        User::where('id', $users->ref_user_id)->update($updateRCountArrDirectBusiness);
                                    }

                                    $usertopup = array('amount' => DB::raw('amount + '.$Productcost), 'topup_status' => "1");
                                    User::where('id', $users->id)->update($usertopup);
                                    
                                    if($check_topup_count == 0){
                                        $ref_usertopup = array('bonus_count' => DB::raw('bonus_count + 1'));
                                        User::where('id', $users->ref_user_id)->update($ref_usertopup);
                                    }

                                    $getlevel = $this->pay_binary($users->id, $Productcost,$users->topup_status );
                                    // check rank of vpid

                                    //$this->check_rank_vpid($users->virtual_parent_id);
                                    $this->check_rank_vpid($users->id);
                                    $this->check_rank_vpid($users->ref_user_id);

                                }

                                if ($direct_plan == '1' && $direct_income > 0) {
                                    // check rank for direct user to give direct income

                                    $this->check_rank($users->ref_user_id);

                                    //$getlevel = $this->pay_direct($users->id, $Productcost, $direct_income, $random);

                                    $this->pay_directbulk($users->id, $Productcost, $direct_income, $random, $users->ref_user_id, $users->user_id);

                                }
                                if ($leadership_plan == '1') {
                                    $getlevel = $this->pay_leadership($users->id, $Productcost, $pacakgeId);
                                }
                                // Give franchise income//
                                /*$percentageincome=$franchise_user->income_per;
                                $this->pay_franchise($users->id, $franchise_user->id,$percentageincome, $Productcost, $storeId, $random);*/
                                // Give master franchise income

                                /*$ms_percentageincome=$masterfranchise_user->income_per;
                            $this->pay_franchise($users->id, $masterfranchise_user->id,$ms_percentageincome, $Productcost, $storeId, $random);*/
                            }

                            if($users->id == $userId) {
                                $subject  = $product_type."-".$product_range." purchased Successfully";
                                $pagename = "emails.topup";
                                $daily_roi= ($amount*$ProductRoi)/100;
                                $entry_date= date('d M Y',strtotime($this->today));
                                $get_validity_date = addWorkdays($entry_date,$ProductDuration);
                                $max_benefit_per = $percentage > 210 ? 210 : $percentage;
                                $max_benefit=round(($max_benefit_per*$amount)/100,2);
                                $validity_date= date('d/m/Y',strtotime($get_validity_date));
                                $data     = array('pagename' => $pagename, 'email' => $users->email, 'amount' => $amount, 'username' => $users->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'max_benefit'=>$max_benefit);
                                $email    = $users->email;
                                $mail     = sendMail($data, $email, $subject);

                            }else{                      
                                $subject_to  = "Successfully purchased a plan for another member";
                                $subject_from  = "Someone just purchased a ".$product_type."-".$product_range." for you";
                                $pagename_to = "emails.topup_downline";
                                $pagename_from = "emails.topup_from_other";
                                $daily_roi= ($amount*$ProductRoi)/100;
                                $entry_date= date('d M Y',strtotime($this->today));
                                $get_validity_date = addWorkdays($entry_date,$ProductDuration);
                                $email    = Auth::User()->email;
                                $to_email = User::where('user_id', $users->user_id)->pluck('email')->first();
                                $downline_username = User::where('user_id', $users->user_id)->pluck('user_id')->first();
                                $max_benefit_per = $percentage > 210 ? 210 : $percentage;
                                $max_benefit=round(($max_benefit_per*$amount)/100,2);
                                $validity_date= date('d/m/Y',strtotime($get_validity_date));

                                $data_to     = array('pagename' => $pagename_to, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
                                $data_from    = array('pagename' => $pagename_from, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
                                $mail_to     = sendMail($data_to, $email, $subject_to);
                                $mail_from     = sendMail($data_from, $to_email, $subject_from);
                            }


                            $arrStatus  = Response::HTTP_OK;
                            $arrCode    = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Topup Done successfully';
                             toastr()->success($arrMessage);
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                        } else {
                            $arrStatus  = Response::HTTP_NOT_FOUND;
                            $arrCode    = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Something went wrong,Please try again';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                        }
                    } else {
                        $arrStatus  = Response::HTTP_NOT_FOUND;
                        $arrCode    = Response::$statusTexts[$arrStatus];
                        $arrMessage = 'Amount should be range of package select';
                        toastr()->error($arrMessage);
                        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    }
                } else {

                    $arrStatus  = Response::HTTP_NOT_FOUND;
                    $arrCode    = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Package is not exist';
                    toastr()->error($arrMessage);
                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                }
            } else {

                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');

            }
        }else if ($otpdata['status'] == 403) 
        {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Opt expired, Resend it..';
            toastr()->error($arrMessage);
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
         else 
        {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Incorrect OTP Or OTP Already Used';
            toastr()->error($arrMessage);
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            toastr()->error($arrMessage);
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function selfTopupMultipleWallet(Request $request) {

        /*$intCode = Response::HTTP_NOT_FOUND;
        $strStatus = Response::$statusTexts[$intCode];
        $strMessage = 'Topup are stopped till 12th September';
        return sendResponse($intCode, $strStatus, $strMessage, array());*/

        try {
            $rules = array(
                'product_id'       => 'required',
                'hash_unit'        => 'required|numeric|min:1',
                'fund_amount'        => 'required|numeric|min:1',
                'wallet_amount'        => 'required|numeric|min:1',
                'user_id'          => 'required',
                'transcation_type' => 'required',
                'wallet_type' => 'required',
                /*'masterfranchise_user_id' => 'required',*/
            );
            $validator = checkvalidation($request->all(), $rules, '');
            if (!empty($validator)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, $this->emptyArray);
            }

            
            /*$auth_user = User::select('tbl_dashboard.top_up_wallet','tbl_dashboard.top_up_wallet_withdraw','tbl_dashboard.total_withdraw','tbl_users.id')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.id', '=', Auth::user()->id], ['tbl_users.status', '=', 'Active']])->first();*/
            $users = User::select('tbl_users.id','tbl_users.email','tbl_users.topup_status', 'tbl_users.user_id', 'tbl_users.virtual_parent_id', 'tbl_users.ref_user_id', 'tbl_dashboard.total_investment', 'tbl_dashboard.active_investment', 'tbl_users.position')->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')->where([['tbl_users.user_id', '=', $request->user_id], ['tbl_users.status', '=', 'Active']])->first();
            if (empty($users)) {

                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'This User ID is Blocked', $this->emptyArray);
            }

            $check_record = whiteListIpAddress($type=1,Auth::user()->id);
            // dd($check_record);
            $ip_Address = getIpAddrss();
            $check_user_hits = WhiteListIpAddress::select('id', 'topup_status', 'topup_expire')->where([['uid',Auth::user()->id],['ip_add',$ip_Address]])->first();
            // dd($check_user_hits);
            if(!empty($check_user_hits)){
                if($check_user_hits->topup_status == 1){
                    $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    if($check_user_hits->topup_expire >= $today){
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Due to too many request hits, temporary you are block for 1 hour!', $this->emptyArray);
                    }
                }
            }

            $roi_wallet_amount = $request->roi_wallet_amount;
            if($roi_wallet_amount != 0)
            {
                $arrInput = $request->all();
                $target_business = Topup::where([['id', Auth::User()->id],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
                $total_business= Auth::User()->l_guardian + Auth::User()->r_guardian;
                if (!empty($target_business)) {
                    if ($total_business < $target_business) {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Please achieve your target first to use ROI", ''); 
                    }
                }
            }
            
            
            // if ($request->transcation_type != 2 && $request->transaction_type != 1) {

            //  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
            // }

            // dd($request->transcation_type);
            // if ($checktopup > 0) {
            //   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one topup allowed for one user', '');
            // }
            $checktopup = Topup::where([['id', $users->id]])->count();

            /* to check user downline or not */
            $id = $users->id;
            $users_id = Auth::User()->id;
            $userId = $to_user_id = $users_id;
            $user_name = Auth::User()->user_id;
            if($to_user_id != $id){
                $todaydetailsexist = TodayDetails::where('to_user_id', $to_user_id)->where('from_user_id',$id)->get();
                if(count($todaydetailsexist) == 0){
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not Downline User','');
                }
            }
            $getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
            /*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Topup amount should be greater or equal to last topup amount.';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }*/
            $projectSettings = ProjectSettings::where('status', 1)
                ->select('topup_status', 'topup_msg')   ->first();
            if ($projectSettings->topup_status == "off") {
                $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = $projectSettings->topup_msg;
                ;
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }

            /* to check user downline or not */
            $profile_status = verifyOtpStatus::select('topup_update_status')
            ->where('statusID','=',1)->get();
                $arrInput            = $request->all();
            $userData=User::where('id',$users_id)->first();
            if ($profile_status[0]->topup_update_status == 1) 
            {
                if ($userData->google2fa_status=='disable') {

                    $arrInput['user_id'] = Auth::user()->id;
                    $arrRules            = ['otp' => 'required|min:6|max:6'];
                    $validator           = Validator::make($arrInput, $arrRules);
                    if ($validator->fails()) 
                    {
                        return setValidationErrorMessage($validator);
                    }
                    $otpdata         = verify_Otp($arrInput);
                } else {
                    $otpdata['status'] = 200;
                }
                
                
            } else 
            {
                $otpdata['status'] = 200;
            }


            if($userData->google2fa_status=='enable') {
                $arrIn  = array();

                $arrIn['id']=$users_id;
                $arrIn['otp']=$arrInput['otp_2fa'];
                $arrIn['google2fa_secret'] = $userData->google2fa_secret;
                if (empty($arrIn['otp'])) {
                    $arrOutputData = [];
                    $strMessage = "Google 2FA code Required";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
                $res=$this->google2facontroller->validateGoogle2FA($arrIn);
                if ($res == false) {
                    $arrOutputData = [];
                    $strMessage = "Invalid Google 2FA code";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
            }
            
            $otpdata['status'] = 200;
            if ($otpdata['status'] == 200) 
            {
            $getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount', 'entry_time')->orderBy('srno', 'desc')->first();
            /*if (!empty($getPrice) && $request->hash_unit < $getPrice->amount) {

                $arrStatus = Response::HTTP_NOT_FOUND;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Topup amount should be greater or equal to last topup amount.';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }*/
            /*if ($request->hash_unit < 100) {

            $arrStatus = Response::HTTP_NOT_FOUND;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Please check Topup amount now start from 100';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }*/
            if (!empty($getPrice)) {

                $date     = $getPrice->entry_time;
                $datework = \Carbon\Carbon::parse($date);
                $now      = \Carbon\Carbon::now();
                $testdate = $datework->diffInMinutes($now);
                //$testdate1 = $datework->diffInDays($now);

                //dd($getPrice->entry_time, $now, $this->today, $testdate, $testdate1);
                // if ($testdate <= 2) {

                //  $arrStatus  = Response::HTTP_NOT_FOUND;
                //  $arrCode    = Response::$statusTexts[$arrStatus];
                //  $arrMessage = 'Try Next Topup after 2 Minutes';
                //  return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                // }

            } else {
                //dd(22);
            }

            if (!empty($users)) {
                $packageExist = Packages::where([['id', '=', $request->Input('product_id')],["user_show_status",'=',"Active"]])->first();
                if (!empty($packageExist)) {
                    $pacakgeId = $packageExist->id;
                    $ProductRoi = $packageExist->roi;
                    $TotalRoiPer = $packageExist->total_roi_percentage;
                    $ProductDuration = $packageExist->duration;
                    $Productcost = $request->hash_unit;
                    $direct_income = $packageExist->direct_income;
                    $binary_cap = $packageExist->capping;
                    
                    $percentage = ($ProductRoi * $ProductDuration);//144
                    $binaryPer = $packageExist->binary;
                    $directPer = $packageExist->direct_income;
                    $totalIncomePer = $packageExist->total_income_percentage;
                    $product_name = $packageExist->package_name;
                    $product_type = $packageExist->package_type;
                    $product_range = $packageExist->name;
                    //if($Productcost <= $request->Input('usd')){

                    /*$UserToupWallet = ($auth_user->top_up_wallet - $auth_user->top_up_wallet_withdraw);*/
                    $purchase_deduct = $fund_deduct = 0;

                    $fund_use_amount = ($Productcost * 50)/100;
                    $remaing_amount = $Productcost - $fund_use_amount;

                    $fund_amount = $request->Input('fund_amount');
                    $wallet_amount = $request->Input('wallet_amount');

                    if ($request->transcation_type == 2) {

                        $topupbalance = Dashboard::where('id', $users_id)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as balance')->pluck('balance')->first();
                        $roibalance = Dashboard::where('id', $users_id)->selectRaw('round(roi_wallet - roi_wallet_withdraw,2) as roi_balance')->pluck('roi_balance')->first();
                        $workingbalance = Dashboard::where('id', $users_id)->selectRaw('round(working_wallet - working_wallet_withdraw,2) as working_balance')->pluck('working_balance')->first();
                        $hsccbalance = Dashboard::where('id', $users_id)->selectRaw('round(hscc_bonus_wallet - hscc_bonus_wallet_withdraw,2) as hscc_balance')->pluck('hscc_balance')->first();
                        
                        if ($fund_amount > $topupbalance) {
                            $arrStatus  = Response::HTTP_NOT_FOUND;
                            $arrCode    = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Insufficient Fund Wallet Balance';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                        }else{
                            if ($fund_amount + $wallet_amount == $Productcost) {

                                
                                
                                    /*if ($roibalance<$remaing_amount) {
                                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in ROI Wallet', '');
                                    }*/
                                    if ($roibalance<$arrInput['roi_wallet_amount']) {
                                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in ROI Wallet', '');
                                    }
                                    // $target_business = Topup::where([['id', Auth::User()->id],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
                                    // $total_business= Auth::User()->l_guardian + Auth::User()->r_guardian;
                                    // if (!empty($target_business)) {
                                    //     if ($total_business < $target_business) { 
                                    //         return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Please achieve your target first", ''); 
                                    //     }
                                    // }
                                    /*if ($workingbalance<$remaing_amount) {
                                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in Working Wallet', '');
                                    }*/
                                    //dd($request);
                                    if ($workingbalance<$arrInput['working_wallet_amount']) {
                                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in Working Wallet', '');
                                    }

                                    /*if ($hsccbalance<$remaing_amount) {
                                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in HSCC Bonus Wallet', '');
                                    }*/
                                    if ($hsccbalance<$arrInput['hscc_wallet_amount']) {
                                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient Balance in HSCC Bonus Wallet', '');
                                    }
                                
                            } else {
                                $arrStatus  = Response::HTTP_NOT_FOUND;
                                $arrCode    = Response::$statusTexts[$arrStatus];
                                $arrMessage = 'Fund amount and wallet amount total should match investment amount.';
                                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                            }
                            
                            
                        }
                    } elseif ($request->transcation_type == 1) {

                        $per = UserSettingFund::select('topup_percentage')->where('user_id',$users_id)->orderBy('id','desc')->first();
                        $topup_per = $per->topup_percentage;
                        $balance = Dashboard::where('id',$userId)->selectRaw('round(fund_wallet - fund_wallet_withdraw,2) as fundbalance,round(setting_fund_wallet - setting_fund_wallet_withdraw,2) as purchasebalance')->first(); 
                        
                        $half_of_requested_amount = ($Productcost *$topup_per)/100;

                        if($balance->purchasebalance < 1)
                        {
                            $arrMessage = 'Insufficient Setting Fund Balance';
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
                        }elseif ($balance->purchasebalance >= $half_of_requested_amount) {
                            $purchase_deduct = $half_of_requested_amount;
                        }else{
                            $purchase_deduct = $balance->purchasebalance;
                        }                   
                        $half_of_requested_amount = $Productcost - $purchase_deduct;
                        if($half_of_requested_amount > $balance->fundbalance)
                        {
                            $arrMessage = 'Insufficient Fund Wallet Balance';
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$arrMessage, $this->emptyArray);
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                        }else{
                            $fund_deduct = $half_of_requested_amount;
                        }           
        

                    } else {

                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid transaction type', $this->emptyArray);
                    }

                    $getPreviousTopup = Topup::join('tbl_product as tp','tp.id','tbl_topup.type')->select('tp.cost','tbl_topup.*')->where('tbl_topup.id',$users->id)->orderBy('tbl_topup.entry_time','desc')->first();

                    //dd($getPreviousTopup);
                    if(!empty($getPreviousTopup)){
                        $topupno = $getPreviousTopup->topup_no + 1;
                        /*$topupno = $getPreviousTopup->topup_no + 1;
                        if($getPreviousTopup->amount > $request->hash_unit){
                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Topup must be greater or equal to previous topup', '');
                        }*/

                        /*return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User have only one topup', '');*/
                    }else{
                        $topupno = 1;
                    }

                    // if ($UserToupWallet >= $Productcost) {

                    /*$getPrice = Topup::where([['id', '=', $users->id], ['amount', '>', 0]])->select('amount')->orderBy('srno', 'desc')->first();*/

                    if ($Productcost >= $packageExist->min_hash && $Productcost <= $packageExist->max_hash) {

                        if ($Productcost >= 10000) {
                            $amount = $Productcost;

                        } else {
                            $amount = $Productcost;
                        }

                        $updateCoinData        = array();
                        $updateCoinData['usd'] = custom_round(($users->usd-$Productcost), 7);
                        $logArr                = $fundArr                = $purchaseArr                = array();
                        if ($request->transcation_type == 2) {
                            
                                // $updateCoinData['roi_wallet_withdraw'] = DB::raw('roi_wallet_withdraw + '.$remaing_amount);
                                if($request->roi_wallet_amount == "")
                                {
                                    $roi_wallet_withdraw = 0;
                                }
                                else{
                                    $roi_wallet_withdraw = $request->roi_wallet_amount;
                                }
                                $updateCoinData['roi_wallet_withdraw'] = DB::raw('round(roi_wallet_withdraw + '.$roi_wallet_withdraw.', 2)');
                                $wallet_type=4;
                                $remark="Topup from Multiple";


                                if($request->working_wallet_amount == "")
                                {
                                    $working_wallet_amount = 0;
                                }
                                else{
                                    $working_wallet_amount = $request->working_wallet_amount;
                                }
                                // $updateCoinData['working_wallet_withdraw'] = DB::raw('working_wallet_withdraw + '.$remaing_amount);
                                $updateCoinData['working_wallet_withdraw'] = DB::raw('round(working_wallet_withdraw + '.$working_wallet_amount.', 2)');
                                $wallet_type=1;
                                //$remark="Topup from Working wallet";
                            

                                if($request->hscc_wallet_amount == "")
                                {
                                    $hscc_wallet_amount = 0;
                                }
                                else{
                                    $hscc_wallet_amount = $request->hscc_wallet_amount;
                                }
                                // $updateCoinData['hscc_bonus_wallet_withdraw'] = DB::raw('hscc_bonus_wallet_withdraw + '.$remaing_amount);
                                $updateCoinData['hscc_bonus_wallet_withdraw'] = DB::raw('round(hscc_bonus_wallet_withdraw + '.$hscc_wallet_amount.', 2)');
                                $wallet_type=5;
                               // $remark="Topup from HSCC wallet";
                            
                            // $updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_use_amount);
                            $updateCoinData['fund_wallet_withdraw'] = DB::raw('fund_wallet_withdraw + '.$fund_amount);
                            $fundArr['from_user_id']                = $userId;
                            $fundArr['to_user_id']                  = $users->id;
                            $fundArr['amount']                      = $Productcost;
                            $fundArr['wallet_type']                 = $wallet_type;
                            $fundArr['remark']                      = $remark;
                            $fundArr['entry_time']                  = $this->today;
                        }
                        if ($request->transcation_type == 1) {
                            $updateCoinData['fund_wallet_withdraw'] = 'fund_wallet_withdraw + '.$fund_deduct;
                            $fundArr['from_user_id']                = $userId;
                            $fundArr['to_user_id']                  = $users->id;
                            $fundArr['amount']                      = $fund_deduct;
                            $fundArr['wallet_type']                 = 3;
                            $fundArr['remark']                      = "Topup from Fund Wallet(min ".(100-$topup_per)."%)";
                            $fundArr['entry_time']                  = $this->today;

                            $updateCoinData['setting_fund_wallet_withdraw'] = 'setting_fund_wallet_withdraw + '.$purchase_deduct;
                            $purchaseArr['from_user_id']              = $userId;
                            $purchaseArr['to_user_id']                = $users->id;
                            $purchaseArr['amount']                    = $purchase_deduct;
                            $purchaseArr['wallet_type']               = 4;
                            $purchaseArr['remark']                    = "Topup from Setting Fund wallet (max ".$topup_per."%)";
                            $purchaseArr['entry_time']                = $this->today;
                        }
                        (count($fundArr) > 0)?array_push($logArr, $fundArr):'';
                        (count($purchaseArr) > 0)?array_push($logArr, $purchaseArr):'';
                        WalletTransactionLog::insert($logArr);
                        $updateCoinData['total_withdraw'] = DB::raw('round(total_withdraw + '.$Productcost.',2)');
                       
                        $updateCoinDatao = Dashboard::where('id', $userId)->update($updateCoinData);
                        
                        $updateCoinData1['total_investment']  = DB::raw('round(total_investment + '.$Productcost.',2)');
                        $updateCoinData1['active_investment'] = DB::raw('round(active_investment + '.$Productcost.',2)');

                        $updateCoinData1 = Dashboard::where('id', $users->id)->update($updateCoinData1);
                        $topupfrom       = "-";
                        if ($request->transcation_type == 1) {
                            $topupfrom ="Setting Fund wallet (max ".$topup_per."%) + Fund Wallet (min ".(100-$topup_per)."%)";
                        } elseif ($request->transcation_type == 2) {
                            if ($request->wallet_type == 'roi') {
                                $topupfrom = "ROI Wallet";
                            } elseif ($request->wallet_type == 'working') {
                                $topupfrom = "Working Wallet";
                            } elseif ($request->wallet_type == 'hscc') {
                                $topupfrom = "HSCC Bonus Wallet";
                            }else{

                                $topupfrom = "From Multiple Wallets";
                            }
                            
                        }

                        $check_topup_count = Topup::where('id',$users->id)->count();
                        
                        $random = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
                        $Topupdata = array();
                        $Topupdata['id'] = $users->id;
                        $Topupdata['pin'] = $random;
                        $Topupdata['amount'] = $amount;
                        $Topupdata['amount_roi'] = ($amount*$ProductRoi)/100;
                        $Topupdata['binary_capping'] = $binary_cap;
                        $Topupdata['percentage'] = $ProductRoi;
                        $Topupdata['direct_roi'] = ($amount*$directPer)/100;
                        // $Topupdata['total_roi'] = ($amount * $TotalRoiPer) / 100;
                        $Topupdata['binary_percentage'] = $binaryPer;
                        $Topupdata['total_income'] = ($amount * $totalIncomePer) / 100;
                        $Topupdata['type'] = $pacakgeId;
                        $Topupdata['top_up_by'] = $userId;
                        $Topupdata['duration'] = $packageExist->duration;
                        $Topupdata['product_name'] = $product_name;
                        /*  $Topupdata['franchise_id'] = $franchise_user->id;
                        $Topupdata['master_franchise_id'] = $franchise_user->id;*/
                        // $Topupdata['remark'] = $request->topupfrom;
                        if($users->id == $userId) {
                        $Topupdata['remark'] = "Self Topup";
                        }else{
                        $Topupdata['remark'] = "Topup from ".$user_name."";                         
                        }
                        $Topupdata['usd_rate'] = '0';
                        $Topupdata['topupfrom'] = $topupfrom;
                        $Topupdata['roi_status'] = 'Active';
                        $Topupdata['top_up_type'] = 3;
                        $Topupdata['binary_pass_status'] = '1';
                        $Topupdata['total_usd'] = 0.001;
                        $Topupdata['fund_wallet_usage'] = $fund_amount;
                        
                        $Topupdata['roi_wallet_usage'] = $request->roi_wallet_amount;
                        $Topupdata['working_wallet_usage'] = $request->working_wallet_amount;
                        $Topupdata['hscc_wallet_usage'] = $request->hscc_wallet_amount;
                        
                        $Topupdata['ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $Topupdata['entry_time'] = \Carbon\Carbon::now()->toDateTimeString();
                        $Topupdata['last_roi_entry_time'] = \Carbon\Carbon::now()->toDateTimeString();

                        if ($request->Input('device') != '') {
                            $Topupdata['device'] = $request->Input('device');
                        } else {
                            $Topupdata['device'] = '-';
                        }
                        $storeId = Topup::insertGetId($Topupdata);
                        $this->add_transaction_activity(Auth::User()->id,1,'Activation using Fund wallet',0,$fund_amount,$topupbalance,custom_round($topupbalance-$fund_amount,2));
                        if ($request->transcation_type == 2) {
                            if ($request->wallet_type == 'roi') {
                                $old_balance = $roibalance;
                                $wallet_txn_type = 2;
                            } elseif ($request->wallet_type == 'working') {
                                $old_balance = $workingbalance;
                                $wallet_txn_type = 3;
                            } elseif ($request->wallet_type == 'hscc') {
                                $old_balance = $hsccbalance;
                                $wallet_txn_type = 4;
                            }
                            $this->add_transaction_activity(Auth::User()->id,$wallet_txn_type,'Activation using '.$topupfrom.'',0,$wallet_amount,$old_balance,custom_round($old_balance-$wallet_amount,2));
                        }

                        if (!empty($storeId)) {
                            //-----check which plan is on in setting--------
                            $plan = ProjectSettings::selectRaw('(CASE level_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as level_plan,(CASE binary_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END) as binary_plan ,(CASE direct_plan WHEN "on" THEN 1 WHEN "off" THEN 0 ELSE "" END ) as direct_plan')->first();

                            if (!empty($plan)) {
                                $level_plan      = $plan->level_plan;
                                $binary_plan     = $plan->binary_plan;
                                $direct_plan     = $plan->direct_plan;
                                $leadership_plan = $plan->leadership_plan;

                                if ($level_plan == '1') {
                                    $getlevel = $this->pay_level($users->id, $Productcost, $pacakgeId);
                                }

                                if ($binary_plan == '1') {

                                    
                                    // update direct business

                                    $updateLCountArrDirectBusiness               = array();
                                    $updateLCountArrDirectBusiness['power_l_bv'] = DB::raw('power_l_bv + '.$Productcost.'');

                                    $updateRCountArrDirectBusiness               = array();
                                    $updateRCountArrDirectBusiness['power_r_bv'] = DB::raw('power_r_bv + '.$Productcost.'');

                                    if ($users->position == 1) {
                                        User::where('id', $users->ref_user_id)->update($updateLCountArrDirectBusiness);
                                    } else if ($users->position == 2) {
                                        User::where('id', $users->ref_user_id)->update($updateRCountArrDirectBusiness);
                                    }

                                    $usertopup = array('amount' => DB::raw('amount + '.$Productcost), 'topup_status' => "1");
                                    User::where('id', $users->id)->update($usertopup);
                                    
                                    if($check_topup_count == 0){
                                        $ref_usertopup = array('bonus_count' => DB::raw('bonus_count + 1'));
                                        User::where('id', $users->ref_user_id)->update($ref_usertopup);
                                    }

                                    $getlevel = $this->pay_binary($users->id, $Productcost,$users->topup_status );
                                    // check rank of vpid

                                    //$this->check_rank_vpid($users->virtual_parent_id);
                                    $this->check_rank_vpid($users->id);
                                    $this->check_rank_vpid($users->ref_user_id);

                                }

                                if ($direct_plan == '1' && $direct_income > 0) {
                                    // check rank for direct user to give direct income

                                    $this->check_rank($users->ref_user_id);

                                    //$getlevel = $this->pay_direct($users->id, $Productcost, $direct_income, $random);

                                    $this->pay_directbulk($users->id, $Productcost, $direct_income, $random, $users->ref_user_id, $users->user_id);

                                }
                                if ($leadership_plan == '1') {
                                    $getlevel = $this->pay_leadership($users->id, $Productcost, $pacakgeId);
                                }
                                // Give franchise income//
                                /*$percentageincome=$franchise_user->income_per;
                                $this->pay_franchise($users->id, $franchise_user->id,$percentageincome, $Productcost, $storeId, $random);*/
                                // Give master franchise income

                                /*$ms_percentageincome=$masterfranchise_user->income_per;
                            $this->pay_franchise($users->id, $masterfranchise_user->id,$ms_percentageincome, $Productcost, $storeId, $random);*/
                            }

                            if($users->id == $userId) {
                                $subject  = $product_type."-".$product_range." purchased Successfully";
                                $pagename = "emails.topup";
                                $daily_roi= ($amount*$ProductRoi)/100;
                                $entry_date= date('d M Y',strtotime($this->today));
                                $get_validity_date = addWorkdays($entry_date,$ProductDuration);
                                $max_benefit_per = $percentage > 210 ? 210 : $percentage;
                                $max_benefit=round(($max_benefit_per*$amount)/100,2);
                                $validity_date= date('d/m/Y',strtotime($get_validity_date));
                                $data     = array('pagename' => $pagename, 'email' => $users->email, 'amount' => $amount, 'username' => $users->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'max_benefit'=>$max_benefit);
                                $email    = $users->email;
                                $mail     = sendMail($data, $email, $subject);

                            }else{                      
                                $subject_to  = "Successfully purchased a plan for another member";
                                $subject_from  = "Someone just purchased a ".$product_type."-".$product_range." for you";
                                $pagename_to = "emails.topup_downline";
                                $pagename_from = "emails.topup_from_other";
                                $daily_roi= ($amount*$ProductRoi)/100;
                                $entry_date= date('d M Y',strtotime($this->today));
                                $get_validity_date = addWorkdays($entry_date,$ProductDuration);
                                $email    = Auth::User()->email;
                                $to_email = User::where('user_id', $users->user_id)->pluck('email')->first();
                                $downline_username = User::where('user_id', $users->user_id)->pluck('user_id')->first();
                                $max_benefit_per = $percentage > 210 ? 210 : $percentage;
                                $max_benefit=round(($max_benefit_per*$amount)/100,2);
                                $validity_date= date('d/m/Y',strtotime($get_validity_date));

                                $data_to     = array('pagename' => $pagename_to, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
                                $data_from    = array('pagename' => $pagename_from, 'email' => Auth::User()->email, 'amount' => $amount, 'username' => Auth::User()->user_id, 'Package' => $product_name,'pin'=>$random,'product_range'=>$product_range,'product_type'=>$product_type,'daily_roi'=>$daily_roi,'date'=>$entry_date,'validity_date'=>$validity_date,'name'=>Auth::User()->fullname,'downline_username'=>$downline_username,'max_benefit'=>$max_benefit);
                                $mail_to     = sendMail($data_to, $email, $subject_to);
                                $mail_from     = sendMail($data_from, $to_email, $subject_from);
                            }

                            $arrStatus  = Response::HTTP_OK;
                            $arrCode    = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Topup Done successfully';
                            toastr()->success($arrMessage);
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                        } else {
                            $arrStatus  = Response::HTTP_NOT_FOUND;
                            $arrCode    = Response::$statusTexts[$arrStatus];
                            $arrMessage = 'Something went wrong,Please try again';
                            return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                        }
                    } else {
                        $arrStatus  = Response::HTTP_NOT_FOUND;
                        $arrCode    = Response::$statusTexts[$arrStatus];
                        $arrMessage = 'Amount should be range of package select';
                        return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                    }
                } else {

                    $arrStatus  = Response::HTTP_NOT_FOUND;
                    $arrCode    = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Package is not exist';
                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');

                }
            } else {

                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');

            }
        }else if ($otpdata['status'] == 403) 
        {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Opt expired, Resend it..';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
         else 
        {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Incorrect OTP Or OTP Already Used';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function topupReport(Request $request)
    {
        $data['title'] = 'Self Topup Report | HSCC';
        return view('user.product.topupReport', compact('data'));
    }

    public function withdrawalReport(Request $request)
    {
        $data['title'] = 'Withdrawal Report | HSCC';
        return view('user.product.withdrawReport', compact('data'));
    }


    public function downlineTopupReport (Request $request)
    {
        $data['title'] = 'Downline Topup Report | HSCC';
        return view('user.product.downlineTopupReport', compact('data'));
    }
    
    public function downlineDepositReport (Request $request)
    {
        $data['title'] = 'Downline Deposit Report | HSCC';
        return view('user.product.downlineDepositReport', compact('data'));
    }
    
    public function downlinePurchseReport (Request $request)
    {
        $data['title'] = 'Purchase Topup Report | HSCC';
        return view('user.product.downlinePurchaseReport', compact('data'));
    }

    public function WithdrawalIncomeReport(Request $request)
    {
        try {
            // ini_set('memory_limit', '-1');
            $arrInput    = $request->all();
            $UserExistid = Auth::User()->id;

            if (!empty($UserExistid)) {

                $query = WithdrawPending::select('tbl_withdrwal_pending.ip_address', 'tbl_withdrwal_pending.amount', DB::raw('ROUND(tbl_withdrwal_pending.amount + tbl_withdrwal_pending.deduction, 2) as total_amount'),DB::raw("DATE_FORMAT(tbl_withdrwal_pending.entry_time, '%Y-%m-%d') as entry_time"), 'tbl_withdrwal_pending.id','tbl_withdrwal_pending.network_type','tbl_withdrwal_pending.remark', 'tbl_withdrwal_pending.deduction',DB::raw('(CASE  WHEN tbl_withdrwal_pending.status = 0 THEN "PENDING" WHEN tbl_withdrwal_pending.withdraw_type = 1 THEN "CONFIRMED"  ELSE "REJECTED" END ) as status'), DB::raw('(CASE  WHEN tbl_withdrwal_pending.withdraw_type = 2 THEN "Working" WHEN tbl_withdrwal_pending.withdraw_type = 3 THEN "ROI" WHEN tbl_withdrwal_pending.withdraw_type = 6 THEN "HSCC Bonus" ELSE "Transfer" END ) as withdraw_type'), 'tbl_withdrwal_pending.to_address','tu.user_id', 'tbl_withdrwal_pending.remark')
                    ->join('tbl_users as tu', 'tu.id', '=', 'tbl_withdrwal_pending.id')
                    ->where([['tbl_withdrwal_pending.id', '=', $UserExistid]])
                    ->where([['tbl_withdrwal_pending.withdraw_type', '!=', 8]]);

                  return Datatables::of($query)
                ->addIndexColumn()
                ->make(true);

            } else {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }


    public function getTopupReport(Request $request)
    {
        try {
            $arrInput   = $request->all();
            $Checkexist = Auth::User()->id;
            if (!empty($Checkexist)) {
                $topupReport = Topup::join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
                    ->select('tbl_topup.srno', 'tbl_topup.topupfrom', 'tbl_topup.pin', 'tbl_topup.amount','tbl_topup.fund_wallet_usage','tbl_topup.roi_wallet_usage','tbl_topup.working_wallet_usage','tbl_topup.hscc_wallet_usage', 'tbl_topup.entry_time', 'tbl_topup.remark')
                    ->where('tbl_topup.id', '=', $Checkexist);          

                return Datatables::of($topupReport)
                ->addIndexColumn()
                ->make(true);

            } else {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function getDownlineTopupReport(Request $request)
    {
        try {
            $arrInput   = $request->all();
            $Checkexist = Auth::User()->id; // check use is active or not
            if (!empty($Checkexist)) {
                
                $topupReport = Topup::join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')
                    ->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup.id')
                    ->select('tbl_topup.srno', 'tbl_topup.id', 'tbl_topup.topupfrom', 'tbl_topup.pin', 'tbl_topup.amount','tbl_topup.fund_wallet_usage','tbl_topup.roi_wallet_usage','tbl_topup.working_wallet_usage','tbl_topup.hscc_wallet_usage', 'tbl_topup.top_up_by', 'tbl_topup.entry_time', 'tp.name', 'tu2.user_id', 'tbl_topup.remark')
                    ->where('tbl_topup.top_up_by', '=', $Checkexist)
                    ->where('tbl_topup.id', '!=', $Checkexist);

                 return Datatables::of($topupReport)
                ->addIndexColumn()
                ->make(true);

            } else {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function getDownlineDepositReport(Request $request)
    {

        try {
            $arrInput   = $request->all();
            $Checkexist = Auth::User()->id; // check use is active or not
            // ini_set('memory_limit', '-1');
        if (!empty($Checkexist)) {

                $topupReport = TransactionInvoice::join('tbl_users as tu2', 'tu2.id', '=', 'tbl_transaction_invoices.id')
                ->join('tbl_today_details as td', 'td.from_user_id', '=', 'tbl_transaction_invoices.id')
                    ->select('tbl_transaction_invoices.srno', 'tbl_transaction_invoices.id', 'tbl_transaction_invoices.payment_mode', 'tbl_transaction_invoices.invoice_id', 'tbl_transaction_invoices.price_in_usd', 'tbl_transaction_invoices.in_status', 'tbl_transaction_invoices.entry_time','tu2.ref_user_id', 'tu2.user_id', 'tbl_transaction_invoices.status_url')
                    ->where('td.to_user_id', '=', $Checkexist)
                    ->where('tbl_transaction_invoices.in_status', '=', '1');

                 return Datatables::of($topupReport)
                ->addIndexColumn()
                ->make(true);

            } else {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'User does not exist';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function getDownlinePurchaseReport(Request $request)
    {

        try {
            $arrInput   = $request->all();
            $Checkexist = Auth::User()->id; // check use is active or not
            if (!empty($Checkexist)) {

                $topupReport = Topup::join('tbl_product as tp', 'tp.id', '=', 'tbl_topup.type')/*->leftjoin('tbl_users as tu', 'tu.id', '=', 'tbl_topup.franchise_id')*/
                    ->leftjoin('tbl_users as tu2', 'tu2.id', '=', 'tbl_topup.id')
                    ->join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_topup.id')
                    ->select('tbl_topup.srno', 'tbl_topup.id', 'tbl_topup.topupfrom', 'tbl_topup.pin', 'tbl_topup.amount','tbl_topup.fund_wallet_usage','tbl_topup.roi_wallet_usage','tbl_topup.working_wallet_usage','tbl_topup.hscc_wallet_usage', 'tbl_topup.top_up_by', 'tbl_topup.entry_time', 'tp.name','tp.package_type', 'tu2.user_id', 'tbl_topup.remark')
                    ->where('tbl_topup.id', '!=', $Checkexist)
                    ->where('ttd.to_user_id', $Checkexist);
                  
                  return Datatables::of($topupReport)
                ->addIndexColumn()
                ->make(true);
            } else {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Invalid user';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        } catch (Exception $e) {
            dd($e);
            $arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
    }

    public function SendOtpForWithdraw(Request $request)
    {
        // check user is from same browser or not

        /* $intCode   = Response::HTTP_NOT_FOUND;
        $strStatus = Response::$statusTexts[$intCode];
        return sendResponse($intCode, $strStatus, 'Your gateway to a strong financial future is going to open soon!', '');*/

        $id = Auth::User()->id;

        $message = array('');
        $rules   = array(
            'Currency_type'  => 'required',
        );
        $messages = array(
            'Currency_type.required'  => 'Please select currency',
        );
        $validator = checkvalidation($request->all(), $rules, $messages);
        if (!empty($validator)) {
            $arrStatus  = Response::HTTP_NOT_FOUND;
            $arrCode    = Response::$statusTexts[$arrStatus];
            $arrMessage = $validator;
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }

        if(Auth::User()->topup_status == 0)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Not having any Topup", ''); 
            }

        if(Auth::User()->capping_withdrawal_status == "Inactive")
        {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Not having Active Topup", ''); 
        }        

        $check_record = whiteListIpAddress($type = 2, Auth::user()->id);
        $ip_Address = getIpAddrss();
        $check_user_hits = WhiteListIpAddress::select('id', 'withdraw_status', 'withdraw_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
        if (!empty($check_user_hits)) {
            if ($check_user_hits->withdraw_status == 1) {
                $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                if ($check_user_hits->withdraw_expire >= $today) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block!', $this->emptyArray);
                }
            }
        }

        $projectSettings = ProjectSettings::where('status', 1)
            ->select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->first();

        $day             = \Carbon\Carbon::now()->format('D');
        $date_day        = \Carbon\Carbon::now()->format('d');
        $hrs             = \Carbon\Carbon::now()->format('H');
        $hrs             = (int) $hrs;
        $days            = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");
        $withdrawSetting = Withdrawbydate::select('first_day', 'second_day', 'third_day', 'status')->first();
        if (!empty($withdrawSetting) && $withdrawSetting->status == "Active") {
            if ($date_day == $withdrawSetting->first_day) {
                // dd('first');
            } else if ($date_day == $withdrawSetting->second_day) {
                // dd('second');

            } else if ($date_day == $withdrawSetting->third_day) {
                // dd('third');

            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on ' . $withdrawSetting->first_day . ',' . $withdrawSetting->second_day . ',' . $withdrawSetting->third_day . ' of month', '');
            }
        }
        if($day != $projectSettings->withdraw_day){
            $msg = 'You can place withdrawals only on '.$days[$projectSettings->withdraw_day];
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $msg, '');
        }

        $user_id = Auth::user()->id;
        $arrInput = $request->all();
        $currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
        if (empty($currency_address)) {
            $intCode      = Response::HTTP_NOT_FOUND;
            $strStatus    = Response::$statusTexts[$intCode];
            return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
        }
       
        $id       = Auth::user()->id;
        $user_id  = $id;
        $arrInput = $request->all();
        if ($arrInput['Currency_type'] == "BTC") {
            // dd($user_id);
            // $currency_address = User::where('id', $user_id)->pluck('btc_address')->first();
            $currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'BTC')->pluck('currency_address')->first();
            if (empty($currency_address)) {
                $intCode   = Response::HTTP_NOT_FOUND;
                $strStatus = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, 'Please update BTC address to withdraw amount', '');
            }
        }
        if ($arrInput['Currency_type'] == "TRX") {
            // $currency_address = User::where('id', $user_id)->pluck('trn_address')->first();
            $currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'TRX')->pluck('currency_address')->first();

            if (empty($currency_address)) {
                $intCode   = Response::HTTP_NOT_FOUND;
                $strStatus = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, 'Please update Tron address to withdraw amount', '');
            }
        }
        if ($arrInput['Currency_type'] == "ETH") {
            // $currency_address = User::where('id', $user_id)->pluck('ethereum')->first();
            $currency_address = UserWithdrwalSetting::where('id', $user_id)->where('currency', 'ETH')->pluck('currency_address')->first();

            if (empty($currency_address)) {
                $intCode   = Response::HTTP_NOT_FOUND;
                $strStatus = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, 'Please update ETH address to withdraw amount', '');
            }
        }
        if ($arrInput['Currency_type'] == "BNB.ERC20") {
            $currency_address = User::where('id', $user_id)->pluck('bnb_address')->first();

            if (empty($currency_address)) {
                $intCode   = Response::HTTP_NOT_FOUND;
                $strStatus = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, 'Please update BNB address to withdraw amount', '');
            }
        }
        $user = Auth::User();
        $userData=User::where('id',$user_id)->first();
        if ($userData->google2fa_status=='disable') {
            $result = SendOtpForAll($user);
            if ($result) {
                toastr()->success('OTP sent successfully to your email Id');
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
            }
        }else{
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'OTP sent successfully to your email Id', '');
        }
    }

    public function withdrawWorkingWallet(Request $request)
    {
        try {
            $message = array('');
            $rules   = array(
                'working_wallet' => 'required|numeric|min:10',
                'Currency_type'  => 'required',
            );
            $messages = array(
                'working_wallet.required' => 'Please enter amount',
                'Currency_type.required'  => 'Please select currency',
                'working_wallet.numeric'  => 'Please enter valid amount',
                'working_wallet.min'      => 'Amount must be minimum 10',
                'working_wallet.digit'    => 'You can enter maximum 8 digit'
            );
            $validator = checkvalidation($request->all(), $rules, $messages);
            if (!empty($validator)) {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = $validator;
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }

            $check_record = whiteListIpAddress($type = 2, Auth::user()->id);

            $ip_Address = getIpAddrss();
            $check_user_hits = WhiteListIpAddress::select('id', 'withdraw_status', 'withdraw_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
            if (!empty($check_user_hits)) {
                if ($check_user_hits->withdraw_status == 1) {
                    $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    if ($check_user_hits->withdraw_expire >= $today) {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block!', $this->emptyArray);
                    }
                }
            }

            if(Auth::User()->topup_status == 0)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Inactive User", ''); 
            }

            $statusw = User::where('id', Auth::User()->id)->pluck("withdraw_block_by_admin")->first();
            $withdraw_status = $statusw;
            if($withdraw_status == 1)
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Your withdraw requests stopped please contact with support team", ''); 
            }

            //check cross browser (check_user_authentication_browser)
            // $req_temp_info = $request->header('User-Agent');
            // $result        = check_user_authentication_browser($req_temp_info, Auth::user()->temp_info);
            // if ($result == false) {
            //     return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
            // }

            $projectSettings = ProjectSettings::where('status', 1)
                ->select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->first();

            $day             = \Carbon\Carbon::now()->format('D');
            $date_day        = \Carbon\Carbon::now()->format('d');
            $hrs             = \Carbon\Carbon::now()->format('H');
            $hrs             = (int) $hrs;
            $days            = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");
            $withdrawSetting = Withdrawbydate::select('first_day', 'second_day', 'third_day', 'status')->first();
            if (!empty($withdrawSetting) && $withdrawSetting->status == "Active") {
                if ($date_day == $withdrawSetting->first_day) {
                    // dd('first');
                } else if ($date_day == $withdrawSetting->second_day) {
                    // dd('second');

                } else if ($date_day == $withdrawSetting->third_day) {
                    // dd('third');

                } else {
                    //return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of month', '');
                }
            }
            
            $user_id = Auth::user()->id;
            $arrInput = $request->all();
            $currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
            if (empty($currency_address)) {
                $intCode      = Response::HTTP_NOT_FOUND;
                $strStatus    = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
            } else {
                if (!empty($currency_address->block_user_date_time)) {
                    $today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
                    if ($currency_address->block_user_date_time >= $today) {
                        $intCode      = Response::HTTP_NOT_FOUND;
                        $strStatus    = Response::$statusTexts[$intCode];
                        /*return sendResponse($intCode, $strStatus, 'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)', '');*/
                    }
                }
            }

          
            $id       = Auth::user()->id;
            $user_id  = $id;
            $arrInput = $request->all();
            
            $withdraw_status = verifyOtpStatus::select('withdraw_update_status')
                ->where('statusID', '=', 1)->get();
                $arrInput            = $request->all();
            $userData=User::where('id',$user_id)->first();
            if ($withdraw_status[0]->withdraw_update_status == 1) {
                if ($userData->google2fa_status=='disable') {
                    $arrInput['user_id'] = Auth::user()->id;
                    $arrRules            = ['otp' => 'required|min:6|max:6'];
                    $validator           = Validator::make($arrInput, $arrRules);

                    if ($validator->fails()) {
                        return setValidationErrorMessage($validator);
                    }
                    // dd(123);
                    $otpdata         = verify_Otp($arrInput);
                    //dd($otpdata);

                } else {
                    $otpdata['status'] = 200;
                }
                
            } else {
                $otpdata['status'] = 200;
            }
            $users_id=Auth::user()->id;

            if($userData->google2fa_status=='enable') {
                $arrIn  = array();

                $arrIn['id']=$users_id;
                $arrIn['otp']=$arrInput['otp_2fa'];
                $arrIn['google2fa_secret'] = $userData->google2fa_secret;
                if (empty($arrIn['otp'])) {
                    $arrOutputData = [];
                    $strMessage = "Google 2FA code Required";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
                $res=$this->google2facontroller->validateGoogle2FA($arrIn);
                if ($res == false) {
                    $arrOutputData = [];
                    $strMessage = "Invalid Google 2FA code";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
            }
            // dd($otpdata['status']);
            if ($otpdata['status'] == 200) {
                $dash = Dashboard::where('id', $id)->select('working_wallet', 'working_wallet_withdraw')
                    ->first();
                $working_wallet_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

                if ($request->working_wallet <= $working_wallet_balance) {
                    $Deduction = WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])
                        ->pluck('deduction')->first();

                    $Deductionamount = (($request->input('working_wallet') * $Deduction) / 100);

                    $amount                                = $request->input('working_wallet') - $Deductionamount;
                    $updateData                            = array();
                    $updateData['working_wallet_withdraw'] = DB::raw("working_wallet_withdraw + " . $request->working_wallet);
                    $updtdash                              = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

                    $Toaddress                       = $currency_address->currency_address;
                    $NetworkType                     = $request->Currency_type;
                    $withDrawdata                    = array();
                    $withDrawdata['id']              = $id;
                    $withDrawdata['amount']          = $amount;
                    $withDrawdata['transaction_fee'] = 0;
                    $withDrawdata['deduction']       = $Deductionamount;
                    $withDrawdata['from_address']    = '';
                    $withDrawdata['to_address']      = trim($Toaddress);
                    $withDrawdata['network_type']    = $NetworkType;
                    $withDrawdata['ip_address']    = getIPAddress();
                    $withDrawdata['entry_time']      = $this->today;
                    $withDrawdata['withdraw_type']   = 2;
                    $WithDrawinsert                  = WithdrawPending::create($withDrawdata);

                    $getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

                    $balance   = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
                    $Trandata1 = array(
                        'id'           => $id,
                        'network_type' => $getCoin,
                        'refference'   => $id,
                        'debit'        => $request->working_wallet,
                        'balance'      => $balance - $request->working_wallet,
                        'type'         => "working_wallet",
                        'status'       => 1,
                        'remarks'      => '$' . $request->working_wallet . ' has withdrawn from working wallet',
                        'entry_time'   => $this->today
                    );

                    $Trandata3 = array(
                        'id'         => $id,
                        'message'    => '$' . $request->working_wallet . ' has withdrawn from working wallet',
                        'status'     => 1,
                        'entry_time' => $this->today
                    );

                    $TransactionDta1 = AllTransaction::insert($Trandata1);
                    //----into acitviy notification
                    $actDta = Activitynotification::insert($Trandata3);

                    $subject = "Withdraw request has been submitted successfully";
                    $pagename = "emails.Withdrawal_fund";
                    $withdraw_from="Working Wallet";
                    $entry_time= date('d M Y',strtotime($this->today));
                    $data = array('pagename' => $pagename, 'username' => Auth::User()->user_id, 'name' => Auth::User()->fullname,'date'=>$entry_time,'withdraw_amount'=>$request->working_wallet,'fees'=>$Deductionamount,'wallet_address'=>trim($Toaddress),'withdraw_from'=>$withdraw_from);
                    $mail = sendMail($data, Auth::User()->email, $subject);

                     toastr()->success('Amount withdraw successfully');
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $otpdata['msg'], '');
            }
        } catch (Exception $e) {
            dd($e);

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
        }
    }

    public function withdrawROIWallet(Request $request)
    {
        try {

            /*$intCode   = Response::HTTP_NOT_FOUND;
            $strStatus = Response::$statusTexts[$intCode];
            return sendResponse($intCode, $strStatus, 'Your gateway to a strong financial future is going to open soon!', '');

            dd("stop");*/

            $message = array('');
            $rules   = array(
                'working_wallet' => 'required|numeric|min:10',
                'Currency_type'  => 'required',
            );
            $messages = array(
                'working_wallet.required' => 'Please enter amount',
                'Currency_type.required'  => 'Please select currency',
                'working_wallet.numeric'  => 'Please enter valid amount',
                'working_wallet.min'      => 'Amount must be minimum 10',
                'working_wallet.digit'    => 'You can enter maximum 8 digit'
            );
            $validator = checkvalidation($request->all(), $rules, $messages);
            if (!empty($validator)) {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = $validator;
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }

            $check_record = whiteListIpAddress($type = 2, Auth::user()->id);

            $ip_Address = getIpAddrss();
            $check_user_hits = WhiteListIpAddress::select('id', 'withdraw_status', 'withdraw_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
            if (!empty($check_user_hits)) {
                if ($check_user_hits->withdraw_status == 1) {
                    $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    if ($check_user_hits->withdraw_expire >= $today) {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block!', $this->emptyArray);
                    }
                }
            }

            //check cross browser (check_user_authentication_browser)
            // $req_temp_info = $request->header('User-Agent');
            // $result        = check_user_authentication_browser($req_temp_info, Auth::user()->temp_info);
            // if ($result == false) {
            //     return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
            // }

            $projectSettings = ProjectSettings::where('status', 1)
                ->select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->first();

            $day             = \Carbon\Carbon::now()->format('D');
            $date_day        = \Carbon\Carbon::now()->format('d');
            $hrs             = \Carbon\Carbon::now()->format('H');
            $hrs             = (int) $hrs;
            $days            = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");
            $withdrawSetting = Withdrawbydate::select('first_day', 'second_day', 'third_day', 'status')->first();
            if (!empty($withdrawSetting) && $withdrawSetting->status == "Active") {
                if ($date_day == $withdrawSetting->first_day) {
                    // dd('first');
                } else if ($date_day == $withdrawSetting->second_day) {
                    // dd('second');

                } else if ($date_day == $withdrawSetting->third_day) {
                    // dd('third');

                } else {
                    //return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of month', '');
                }
            }
           
            $user_id = Auth::user()->id;
            $arrInput = $request->all();
            $target_business = Topup::where([['id', Auth::User()->id],['type',7]])->selectRaw('round(target_business,2) as target_business')->pluck('target_business')->first();
            $total_business= Auth::User()->l_guardian + Auth::User()->r_guardian;
            if (!empty($target_business)) {
                if ($total_business < $target_business) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],"Please achieve your target first", ''); 
                }
            }
            $currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
            if (empty($currency_address)) {
                $intCode      = Response::HTTP_NOT_FOUND;
                $strStatus    = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
            } else {
                if (!empty($currency_address->block_user_date_time)) {
                    $today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
                    if ($currency_address->block_user_date_time >= $today) {
                        $intCode      = Response::HTTP_NOT_FOUND;
                        $strStatus    = Response::$statusTexts[$intCode];
                        /*return sendResponse($intCode, $strStatus, 'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)', '');*/
                    }
                }
            }


            $id       = Auth::user()->id;
            $user_id  = $id;
            $arrInput = $request->all();
           

            $withdraw_status = verifyOtpStatus::select('withdraw_update_status')
                ->where('statusID', '=', 1)->get();
            $userData=User::where('id',$user_id)->first();
            if ($withdraw_status[0]->withdraw_update_status == 1) {
                if ($userData->google2fa_status=='disable') {

                    $arrInput            = $request->all();

                    $arrInput['user_id'] = Auth::user()->id;
                    $arrRules            = ['otp' => 'required|min:6|max:6'];
                    $validator           = Validator::make($arrInput, $arrRules);

                    if ($validator->fails()) {
                        return setValidationErrorMessage($validator);
                    }
                    // dd(123);
                    $otpdata         = verify_Otp($arrInput);
                    //dd($otpdata);
                } else {

                    $otpdata['status'] = 200;
                }
                
            } else {
                $otpdata['status'] = 200;
            }
            if($userData->google2fa_status=='enable') {
                $arrIn  = array();

                $arrIn['id']=$user_id;
                $arrIn['otp']=$arrInput['otp_2fa'];
                $arrIn['google2fa_secret'] = $userData->google2fa_secret;
                if (empty($arrIn['otp'])) {
                    $arrOutputData = [];
                    $strMessage = "Google 2FA code Required";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
                $res=$this->google2facontroller->validateGoogle2FA($arrIn);
                if ($res == false) {
                    $arrOutputData = [];
                    $strMessage = "Invalid Google 2FA code";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
            }
            // dd($otpdata['status']);
            if ($otpdata['status'] == 200) {
                $dash = Dashboard::where('id', $id)->select('roi_income', 'roi_income_withdraw', 'working_wallet', 'working_wallet_withdraw')
                    ->first();
                 $roi_income_balance = $dash->roi_income - $dash->roi_income_withdraw;
                //$roi_income_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

                if ($request->working_wallet <= $roi_income_balance) {
                    $Deduction = WithdrawSettings::where([['income', '=', 'roi_balance'], ['status', '=', 'Active']])
                        ->pluck('deduction')->first();

                    $Deductionamount = (($request->input('working_wallet') * $Deduction) / 100);

                    $amount                                = $request->input('working_wallet') - $Deductionamount;
                    $updateData                            = array();
                    // $updateData['working_wallet_withdraw'] = DB::raw("roi_income_withdraw + " . $request->working_wallet);
                    $updateData['roi_income_withdraw'] = DB::raw("roi_income_withdraw + " . $request->working_wallet);
                    $updtdash                              = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

                    $Toaddress                       = $currency_address->currency_address;

                    $NetworkType                     = $request->Currency_type;
                    $withDrawdata                    = array();
                    $withDrawdata['id']              = $id;
                    $withDrawdata['amount']          = $amount;
                    $withDrawdata['transaction_fee'] = 0;
                    $withDrawdata['deduction']       = $Deductionamount;
                    $withDrawdata['from_address']    = '';
                    $withDrawdata['to_address']      = trim($Toaddress);
                    $withDrawdata['network_type']    = $NetworkType;
                    $withDrawdata['ip_address']    = getIPAddress();
                    $withDrawdata['entry_time']      = $this->today;
                    $withDrawdata['withdraw_type']   = 3;
                    $WithDrawinsert                  = WithdrawPending::create($withDrawdata);

                    $getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

                    $balance   = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
                    $Trandata1 = array(
                        'id'           => $id,
                        'network_type' => $getCoin,
                        'refference'   => $id,
                        'debit'        => $request->working_wallet,
                        'balance'      => $balance - $request->working_wallet,
                        'type'         => "roi_wallet",
                        'status'       => 1,
                        'remarks'      => '$' . $request->working_wallet . ' has withdrawan from roi wallet',
                        'entry_time'   => $this->today
                    );

                    $Trandata3 = array(
                        'id'         => $id,
                        'message'    => '$' . $request->working_wallet . ' has withdrawan from roi wallet',
                        'status'     => 1,
                        'entry_time' => $this->today
                    );

                    $TransactionDta1 = AllTransaction::insert($Trandata1);
                    $actDta = Activitynotification::insert($Trandata3);
                    $subject = "Withdraw request has been submitted successfully";
                    $pagename = "emails.Withdrawal_fund";
                    $withdraw_from="ROI Wallet";
                    $entry_time= date('d M Y',strtotime($this->today));
                    $data = array('pagename' => $pagename, 'username' => Auth::User()->user_id, 'name' => Auth::User()->fullname,'date'=>$entry_time,'withdraw_amount'=>$request->working_wallet,'fees'=>$Deductionamount,'wallet_address'=>trim($Toaddress),'withdraw_from'=>$withdraw_from);
                    $mail = sendMail($data, Auth::User()->email, $subject);

                     toastr()->success('Amount withdraw successfully');
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $otpdata['msg'], '');
            }
        } catch (Exception $e) {
            dd($e);

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
        }
    }

    // withdraw HSCC bonus Wallet
    public function withdrawHBonusWallet(Request $request)
    {
        try {

            /*$intCode   = Response::HTTP_NOT_FOUND;
            $strStatus = Response::$statusTexts[$intCode];
            return sendResponse($intCode, $strStatus, 'Your gateway to a strong financial future is going to open soon!', '');

            dd("stop");*/

            $message = array('');
            $rules   = array(
                'working_wallet' => 'required|numeric|min:10',
                'Currency_type'  => 'required',
            );
            $messages = array(
                'working_wallet.required' => 'Please enter amount',
                'Currency_type.required'  => 'Please select currency',
                'working_wallet.numeric'  => 'Please enter valid amount',
                'working_wallet.min'      => 'Amount must be minimum 10',
                'working_wallet.digit'    => 'You can enter maximum 8 digit'
            );
            $validator = checkvalidation($request->all(), $rules, $messages);
            if (!empty($validator)) {
                $arrStatus  = Response::HTTP_NOT_FOUND;
                $arrCode    = Response::$statusTexts[$arrStatus];
                $arrMessage = $validator;
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }

            $check_record = whiteListIpAddress($type = 2, Auth::user()->id);

            $ip_Address = getIpAddrss();
            $check_user_hits = WhiteListIpAddress::select('id', 'withdraw_status', 'withdraw_expire')->where([['uid', Auth::user()->id], ['ip_add', $ip_Address]])->first();
            if (!empty($check_user_hits)) {
                if ($check_user_hits->withdraw_status == 1) {
                    $today = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    if ($check_user_hits->withdraw_expire >= $today) {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Due to too many request hits, temporary you are block!', $this->emptyArray);
                    }
                }
            }

            //check cross browser (check_user_authentication_browser)
            // $req_temp_info = $request->header('User-Agent');
            // $result        = check_user_authentication_browser($req_temp_info, Auth::user()->temp_info);
            // if ($result == false) {
            //     return sendresponse($this->statuscode[401]['code'], $this->statuscode[401]['status'], 'Invalid User Token!!!', '');
            // }

            $projectSettings = ProjectSettings::where('status', 1)
                ->select('withdraw_day', 'withdraw_start_time', 'withdraw_status', 'withdraw_off_msg')->first();

            $day             = \Carbon\Carbon::now()->format('D');
            $date_day        = \Carbon\Carbon::now()->format('d');
            $hrs             = \Carbon\Carbon::now()->format('H');
            $hrs             = (int) $hrs;
            $days            = array('Mon' => "Monday", 'Tue' => "Tuesday", 'Wed' => "Wednesday", 'Thu' => "Thursday", 'Fri' => "Friday", 'Sat' => "Saturday", 'Sun' => "Sunday");
            $withdrawSetting = Withdrawbydate::select('first_day', 'second_day', 'third_day', 'status')->first();
            if (!empty($withdrawSetting) && $withdrawSetting->status == "Active") {
                if ($date_day == $withdrawSetting->first_day) {
                    // dd('first');
                } else if ($date_day == $withdrawSetting->second_day) {
                    // dd('second');

                } else if ($date_day == $withdrawSetting->third_day) {
                    // dd('third');

                } else {
                    //return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You can withdraw only on '.$withdrawSetting->first_day.','.$withdrawSetting->second_day.','.$withdrawSetting->third_day.' of month', '');
                }
            }
          
            $user_id = Auth::user()->id;
            $arrInput = $request->all();
            $currency_address = UserWithdrwalSetting::select('currency_address', 'block_user_date_time')->where([['id', $user_id], ['currency', str_replace(".", "-", $request->Currency_type)], ['status', 1]])->first();
            if (empty($currency_address)) {
                $intCode      = Response::HTTP_NOT_FOUND;
                $strStatus    = Response::$statusTexts[$intCode];
                return sendResponse($intCode, $strStatus, 'Please update ' . $request->Currency_type . ' address to withdraw amount', '');
            } else {
                if (!empty($currency_address->block_user_date_time)) {
                    $today = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
                    if ($currency_address->block_user_date_time >= $today) {
                        $intCode      = Response::HTTP_NOT_FOUND;
                        $strStatus    = Response::$statusTexts[$intCode];
                        /*return sendResponse($intCode, $strStatus, 'You can place a withdrawal request after 24 hours of your wallet address updated. (Security Reasons)', '');*/
                    }
                }
            }

         
            $id       = Auth::user()->id;
            $user_id  = $id;
            $arrInput = $request->all();
           
            $withdraw_status = verifyOtpStatus::select('withdraw_update_status')
                ->where('statusID', '=', 1)->get();
            $userData=User::where('id',$user_id)->first();
            if ($withdraw_status[0]->withdraw_update_status == 1) {
                if ($userData->google2fa_status=='disable') {

                    $arrInput            = $request->all();

                    $arrInput['user_id'] = Auth::user()->id;
                    $arrRules            = ['otp' => 'required|min:6|max:6'];
                    $validator           = Validator::make($arrInput, $arrRules);

                    if ($validator->fails()) {
                        return setValidationErrorMessage($validator);
                    }
                    // dd(123);
                    $otpdata         = verify_Otp($arrInput);
                    //dd($otpdata);
                } else {

                    $otpdata['status'] = 200;
                }
                
            } else {
                $otpdata['status'] = 200;
            }
            $users_id=Auth::user()->id;

            if($userData->google2fa_status=='enable') {
                $arrIn  = array();

                $arrIn['id']=$users_id;
                $arrIn['otp']=$arrInput['otp_2fa'];
                $arrIn['google2fa_secret'] = $userData->google2fa_secret;
                if (empty($arrIn['otp'])) {
                    $arrOutputData = [];
                    $strMessage = "Google 2FA code Required";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
                $res=$this->google2facontroller->validateGoogle2FA($arrIn);
                if ($res == false) {
                    $arrOutputData = [];
                    $strMessage = "Invalid Google 2FA code";
                    $intCode = Response::HTTP_NOT_FOUND;
                    $strStatus = Response::$statusTexts[$intCode];
                    return sendResponse($intCode, $strStatus, $strMessage, $arrOutputData);
                }
            }
            // dd($otpdata['status']);
            if ($otpdata['status'] == 200) {
                $dash = Dashboard::where('id', $id)->select('hscc_bonus_wallet', 'hscc_bonus_wallet_withdraw', 'working_wallet', 'working_wallet_withdraw')
                    ->first();
                 $hscc_bonus_balance = $dash->hscc_bonus_wallet - $dash->hscc_bonus_wallet_withdraw;
                //$roi_income_balance = $dash->working_wallet - $dash->working_wallet_withdraw;

                if ($request->working_wallet <= $hscc_bonus_balance) {
                    $Deduction = WithdrawSettings::where([['income', '=', 'working_wallet'], ['status', '=', 'Active']])
                        ->pluck('deduction')->first();

                    $Deductionamount = (($request->input('working_wallet') * $Deduction) / 100);

                    $amount                                = $request->input('working_wallet') - $Deductionamount;
                    $updateData                            = array();
                    // $updateData['working_wallet_withdraw'] = DB::raw("roi_income_withdraw + " . $request->working_wallet);
                    $updateData['hscc_bonus_wallet_withdraw'] = DB::raw("hscc_bonus_wallet_withdraw + " . $request->working_wallet);
                    $updtdash                              = DB::table('tbl_dashboard')->where('id', $id)->update($updateData);

                    $Toaddress                       = $currency_address->currency_address;

                    $NetworkType                     = $request->Currency_type;
                    $withDrawdata                    = array();
                    $withDrawdata['id']              = $id;
                    $withDrawdata['amount']          = $amount;
                    $withDrawdata['transaction_fee'] = 0;
                    $withDrawdata['deduction']       = $Deductionamount;
                    $withDrawdata['from_address']    = '';
                    $withDrawdata['to_address']      = trim($Toaddress);
                    $withDrawdata['network_type']    = $NetworkType;
                    $withDrawdata['ip_address']    = getIPAddress();
                    $withDrawdata['entry_time']      = $this->today;
                    $withDrawdata['withdraw_type']   = 3;
                    $WithDrawinsert                  = WithdrawPending::create($withDrawdata);

                    $getCoin = ProjectSettings::where([['status', 1]])->pluck('coin_name')->first();

                    $balance   = AllTransaction::where('id', '=', $id)->orderBy('srno', 'desc')->pluck('balance')->first();
                    $Trandata1 = array(
                        'id'           => $id,
                        'network_type' => $getCoin,
                        'refference'   => $id,
                        'debit'        => $request->working_wallet,
                        'balance'      => $balance - $request->working_wallet,
                        'type'         => "roi_wallet",
                        'status'       => 1,
                        'remarks'      => '$' . $request->working_wallet . ' has withdrawan from HSCC Bonus wallet',
                        'entry_time'   => $this->today
                    );

                    $Trandata3 = array(
                        'id'         => $id,
                        'message'    => '$' . $request->working_wallet . ' has withdrawan from HSCC Bonus wallet',
                        'status'     => 1,
                        'entry_time' => $this->today
                    );

                    $TransactionDta1 = AllTransaction::insert($Trandata1);
                    $actDta = Activitynotification::insert($Trandata3);
                    $subject = "Withdraw request has been submitted successfully";
                    $pagename = "emails.Withdrawal_fund";
                    $withdraw_from="HSCC Bonus Wallet";
                    $entry_time= date('d M Y',strtotime($this->today));
                    $data = array('pagename' => $pagename, 'username' => Auth::User()->user_id, 'name' => Auth::User()->fullname,'date'=>$entry_time,'withdraw_amount'=>$request->working_wallet,'fees'=>$Deductionamount,'wallet_address'=>trim($Toaddress),'withdraw_from'=>$withdraw_from);
                    $mail = sendMail($data, Auth::User()->email, $subject);

                     toastr()->success('Amount withdraw successfully');
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Amount withdraw successfully', '');
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient wallet balance', '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $otpdata['msg'], '');
            }
        } catch (Exception $e) {
            dd($e);

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', '');
        }
    }
   
}
