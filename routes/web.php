<?php

use App\Http\Controllers\admin\AuthenticationController;
use App\Http\Controllers\admin\FundRequestController;
use App\Http\Controllers\admin\TransactionController;
use App\Http\Controllers\admin\NavigationsController;
use App\Http\Controllers\admin\EWalletController;
use App\Http\Controllers\admin\LendingController;
use App\Http\Controllers\user\CommonController;
use App\Http\Controllers\user\DashboardController;
use App\Http\Controllers\user\ForgotPasswordController;
use App\Http\Controllers\user\ReportsController;
use App\Http\Controllers\user\SendotpController;
use App\Http\Controllers\user\UserController;
use App\Http\Controllers\user\WalletController;
use App\Http\Controllers\user\TransferController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\user\ProductController;
use App\Http\Controllers\user\UserTopUpController;
use App\Http\Controllers\user\TopUpController;
use App\Http\Controllers\user\UserLoginController;
use App\Http\Controllers\user\PackageController;
use App\Http\Controllers\user\CurrencyConvertorController;
use App\Http\Controllers\user\MakeDepositController;
use App\Http\Controllers\user\WithdrawTransactionController;

use Illuminate\Http\Request;
use App\Http\Controllers\user\LevelController;
use App\Http\Controllers\user\DashboardController as UserDashboardController;
use App\Http\Controllers\userapi\DashboardController as ApiUserDashboardController;
use App\Http\Controllers\admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\admin\UserController as AdminUserController;
use App\Http\Controllers\admin\ProductController as AdminProductController;
use App\Http\Controllers\user\Google2FAController;
use App\Http\Controllers\user\ProfileController;

use App\Http\Controllers\admin\LevelController as AdminLevelController;
use App\Http\Controllers\admin\MarketingController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::any('pass',
function () {
        $arrData = [];
        $str = "Imran@12334";
        $arrData['md5'] = md5($str);
        $arrData['bcrypt'] = bcrypt($str);
        $arrData['encryptpass'] = Crypt::encrypt($str);
        dd($arrData);
    });




Route::get('/', function () {
    return redirect('/login');
});

// Route::get('/thanku', function () {
//     return view('user.auth.thank-you');
// });



//User Route Start
Route::get('/login', [UserLoginController::class, 'showLoginForm'])->name('login');
Route::post('/store-login', [UserLoginController::class, 'login']);
Route::get('/logout', [UserLoginController::class, 'logout'])->name('sign-out');
Route::get('/sign-up', [UserLoginController::class, 'showRegisterForm'])->name('sign-up');
Route::any('/sign-up-user', [UserLoginController::class, 'register'])->name('sign-up-user');
Route::any('/checkuserexist', [UserLoginController::class, 'checkUserExist']);
Route::any('get-user-id',[UserLoginController::class, 'getUserId']);
Route::any('country', [CommonController::class,'getCountry']);

Route::any('/verify-recaptcha', [UserLoginController::class,'verifyRecaptcha']);




/*--------------------------*FORGET PASSWORD CONTROLLER*------------------------------ */
Route::get('/forget-password', [ForgotPasswordController::class, 'index'])->name('forget-password');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetPasswordLink'])->name('forgot-password');
Route::get('/resetPassword/{token}/{user_id}', [ForgotPasswordController::class, 'getLink'])->name('resetPassword');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetUserPassword'])->name('reset-password');
/*--------------------------*FORGET PASSWORD CONTROLLER*------------------------------ */

Route::group(['middleware' => ['auth']], function () {

Route::get('/dashboard', [UserDashboardController::class, 'getUserDashboardDetails'])->name('dashboard');
Route::any('/get-dashboard-data', [ApiUserDashboardController::class, 'getUserDashboardDetails']);
Route::get('/get-wallet-balance', [UserDashboardController::class, 'getWalletBalance']);


Route::get('/products-list', [ProductController::class, 'index']);
Route::get('get-products', [ProductController::class,'ecommerceProductList']);


/*-------------------------User Topup--------------------------------------*/


Route::get('/package', [PackageController::class, 'index']);
// Route::get('/self-topup/{id}', [TopUpController::class, 'selfTopupindex']);
// Route::any('/get-packages', [PackageController::class, 'getpackage']);
/*-------------------------Page URL--------------------------------------*/

//WithdrawTransactionController@withdrawWorkingWallet

Route::get('/topup', [UserTopUpController::class, 'getTopup']);


Route::get('/withdrawal', [UserTopUpController::class, 'withdrawal']);
Route::any('/withdrawal-working', [WithdrawTransactionController::class, 'withdrawWorkingWallet']);


Route::get('/withdrawal-report', [UserTopUpController::class, 'withdrawalReport']);
Route::get('/self-topup-report', [UserTopUpController::class, 'topupReport']);
Route::get('/downline-topup-report', [UserTopUpController::class, 'downlineTopupReport']);
Route::get('/downline-purchase-report', [UserTopUpController::class, 'downlinePurchseReport']);
Route::get('/downline-deposit-report', [UserTopUpController::class, 'downlineDepositReport']);

/*-------------------------Functions--------------------------------------*/
Route::any('/sendOtp-For-SelfTopup', [UserTopUpController::class, 'SendOtpForSelfTopup']);
Route::any('/sendOtp-For-Withdraw', [UserTopUpController::class, 'SendOtpForWithdraw']);
Route::get('/get-product', [UserTopUpController::class, 'getProduct']);
Route::any('/downline', [UserTopUpController::class, 'checkUserExistDownlineNew']);
Route::any('/withdraw-income', [UserTopUpController::class, 'withdrawWorkingWallet']);
Route::any('/withdrwal-income', [ReportsController::class, 'WithdrawalIncomeReport']);
Route::any('/withdraw-income-roi', [UserTopUpController::class, 'withdrawROIWallet']);
Route::any('/withdraw-income-bonus', [UserTopUpController::class, 'withdrawHBonusWallet']);
Route::any('/downline', [UserTopUpController::class, 'checkUserExistDownlineNew']);
Route::any('/store-self-topup', [UserTopUpController::class, 'userSelfTopup']);
Route::any('/self-topup-multiple-wallet', [UserTopUpController::class, 'selfTopupMultipleWallet']);
Route::any('/get-topup-report', [ReportsController::class, 'getTopupReport']);
Route::any('/get-downline-topup-report', [ReportsController::class, 'getDownlineTopupReport']);
Route::any('/get-downline-purchase-report', [ReportsController::class, 'getTeamPurchaseReport']);
Route::any('/get-downline-deposit-report', [ReportsController::class, 'getDownlineDepositReport']);

Route::any('/get-team-purchase-report', [ReportsController::class, 'teamPurchaseReportView']);
Route::any('/get-team-purchase-report-data', [ReportsController::class, 'displayLevelView']);

Route::any('/getlevelviewtree', [LevelController::class, 'getLevelsViewTreeManualProductBase'])->name('getlevelviewtree');
Route::any('/checkuserexist/crossleg', [LevelController::class, 'checkUserExistCrossLeg'])->name('checkuserexist');




Route::any('/teamview/{type?}', [LevelController::class, 'teamViewReport'])->name('teamview');
Route::any('/teamview-data', [LevelController::class, 'getTeamView']);

Route::any('/directsreport', [LevelController::class, 'drectsReport'])->name('directsreport');
Route::any('/directsreport-data', [LevelController::class, 'direct_list']);


/*--------------------------*Profile CONTROLLER*------------------------------ */
Route::get('/profile', [UserController::class,'index']);
Route::post('/send-3x-mail-notification', [SendotpController::class,'send3XmailNotification']);
Route::post('/update-profile/{id}', [UserController::class,'updateUserData'])->name('update-profile');
Route::post('/update-password/{id}', [UserController::class,'changePassword'])->name('update-password');
Route::any('/update-profile-pic/{id}', [UserController::class,'updateUserData'])->name('update-profile-pic');
Route::any('sendOtp-update-user-profile', [SendotpController::class,'sendotpEditUserProfile']);
Route::any('sendOtp-update-address', [SendotpController::class,'sendotpEditUserProfile']);
Route::any('sendOtp-update-user-password', [SendotpController::class,'sendotpEditUserProfile'])->name('sendOtp-update-user-password');
Route::any('/change-address', [UserController::class,'changeAddress']);
/*--------------------------*Profile CONTROLLER*------------------------------ */

/*--------------------------*INCOME CONTROLLER*------------------------------ */
//Route::get('/binary-income', [ReportsController::class,'binaryIncomeReport']);
//Route::get('/daily-binary-report', [ReportsController::class,'DailyBinaryReport']);
//Route::get('/direct-income', [ReportsController::class,'DirectIncomeReport']);
/*--------------------------*INCOME CONTROLLER*------------------------------ */

/*--------------------------*Repprts*------------------------------ */
Route::get('/reports/roi-reports-list', [ReportsController::class, 'RoiBonusReportBlade'])->name('roi-reports');
Route::any('/reports/roi-reports', [ReportsController::class, 'ROIIncomeReport']);

Route::get('/reports/hscc-bonus-reports-list', [ReportsController::class, 'HsccBonusReportBlade'])->name('hscc-bonus-reports-list');
Route::any('/reports/hscc-bonus-reports', [ReportsController::class, 'HsccBonusReport'])->name('HsccBonusReport');


Route::get('/reports/direct-income', [ReportsController::class, 'DirectIncomeReportBlade'])->name('direct-incpme-reports-list');
Route::any('/reports/direct-reports-data', [ReportsController::class, 'DirectIncomeReport'])->name('direct-income-reports');


Route::get('/reports/binary-income', [ReportsController::class, 'BinaryIncomeReportBlade'])->name('binary-income-list');
Route::any('/reports/binary-reports-data', [ReportsController::class, 'binaryIncomeReport'])->name('binary-income-report');

Route::get('/reports/daily-bonus-income', [ReportsController::class, 'DailyBinaryBlade'])->name('daily-bonus-income');
Route::any('/reports/daily-bonus-data', [ReportsController::class, 'DailyBinaryReport'])->name('daily-bonus-data');

Route::get('/reports/transfer-report', [ReportsController::class, 'transferReport']);
Route::any('/reports/transfer-reports-data', [ReportsController::class, 'PurchaseBalanceTransferReceiveReport']);



/*--------------------------*Repprts*------------------------------ */

/*--------------------------*Funds*------------------------------ */
Route::get('/addfund', [WalletController::class, 'create'])->name('addfund');
Route::get('/fundreport', [WalletController::class, 'fundreport'])->name('fundreport');
Route::post('/storefund', [WalletController::class, 'fundRequest']);
Route::post('/submitreport', [WalletController::class, 'submitreport']);
Route::any('/reportfund', [ReportsController::class, 'addfundReportNew']);
Route::get('/completedreport', [WalletController::class, 'completedreport'])->name('completedreport');

Route::post('/purchase-package', [MakeDepositController::class, 'create_transaction']);


/*--------------------------*End Funds*------------------------------ */

/*--------------------------*Marketing*------------------------------ */
Route::any('/marketing-tools', [UserController::class,'getToolData'])->name('marketing-tools');
/*--------------------------*End Marketing*------------------------------ */


/*--------------------------*Transfer Funds*------------------------------ */
Route::get('/transferfromfundwallet', [TransferController::class, 'transferFromFundWallet'])->name('transferfromfundwallet');
Route::post('/store-transferfromfundwallet', [TransferController::class,'PurchaseToPurchaseTransfer']);

Route::get('/transferfromhsccwallet', [TransferController::class, 'transferFromHSCCwallet'])->name('transferfromhsccwallet');
Route::post('/store-transferfromhsccwallet', [TransferController::class,'hsccWalletTohsccWalletTransfer']);

Route::get('/transferfromroiwallet', [TransferController::class, 'transferFromROIWallet'])->name('transferfromroiwallet');
Route::post('/store-transferfromroiwallet', [TransferController::class,'RoiToRoiTransfer']);

Route::get('/transferfromworkingwallet', [TransferController::class, 'transferFromWorkingWallet'])->name('transferfromworkingwallet');
Route::post('/store-transferfromworkingwallet', [TransferController::class,'WorkingToWorkingTransfer']);

Route::any('/google2fa', [Google2FAController::class, 'index']);

Route::any('/get-profile-info', [ProfileController::class, 'getprofileinfo']);
Route::any('/2fa/validate', [Google2FAController::class, 'postValidateToken']);
Route::any('/reset-g2fa-user', [Google2FAController::class, 'resetG2faUser']);
Route::any('/send-g2fa-reset-link', [Google2FAController::class, 'send2faResetLink']);
Route::any('/reset-g2fa-mail-link', [Google2FAController::class, 'onMailLinkClick']);

//Route::get('reset-g2fa-mail-link/{token}', ['as' => 'g2fa.reset.link', 'use'=>'Google2FAController@onMailLinkClick']);




Route::post('/checkuserexistAuth', [UserController::class,'checkUserExistAuth']);

Route::get('/returnLogin/{id}',         [CommonController::class, 'getReturnLogin']);

/*--------------------------*End Transfer Funds*------------------------------ */
});
//User Route End


//Admin Route Start
Route::get('/admin/login', [AuthenticationController::class, 'showLoginFormAdmin']);
Route::any('/admin/login-store', [AuthenticationController::class, 'login']);
Route::post('/admin/send-otp', [AdminUserController::class, 'sendOtp']);

Route::group(['middleware' => ['auth'],'prefix' => 'admin'], function () {




    Route::get('/logout', [AuthenticationController::class, 'logout']);
    Route::get('/navigations', [NavigationsController::class, 'getNavigations']);

    /*--------------------------*Dashboard*------------------------------ */
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboardIndex']);
    Route::any('/dashboard-data', [AdminDashboardController::class, 'getDashboardSummary']);
    /*--------------------------*Dashboard END*------------------------------ */

    /*--------------------------*Income Reports Start*------------------------------ */
    Route::get('/e-wallet/direct-income',[ReportsController::class,'directIncomeReportAdminView']);
    Route::get('/coinpayment/confirm-address-transaction',[ReportsController::class,'DepositFundReport']);
    Route::get('/coinpayment/confirm-address-transaction-sa',[ReportsController::class,'ConfirmAddressTransactionSA']);
    Route::get('/coinpayment/confirm-withdrawal-report-sa',[ReportsController::class,'ConfirmWithdrawalReportSA']);
   Route::any('/getconfirmaddrtrans',[TransactionController::class,'getConfirmAddrTrans']);
    Route::any('/getwithdrwalconfirmedSA',[EWalletController::class,'getWithdrwalConfirmedSA']);
   Route::any('/getconfirmaddrtransSA',[TransactionController::class,'getConfirmAddrTransSA']);
   Route::any('/gettransactionstatuscount',[TransactionController::class,'getTransactionStatusCount']);
    Route::any('/getDirectIncome',[EWalletController::class,'getDirectIncome']);
    Route::any('/e-wallet/direct-income-data',[ReportsController::class,'DirectIncomeReportAdmin']);


    Route::get('/e-wallet/roi-income',[LendingController::class,'RoiBonusReportBladeAdmin']);
    Route::any('/e-wallet/roi-income-data',[LendingController::class,'getDailyBonus']);


    Route::get('/e-wallet/binary-income',[ReportsController::class,'BinaryIncomeReportBladeAdmin']);
    Route::any('/getbinaryincome',[EWalletController::class,'getbinaryincome']);
    Route::any('/e-wallet/binary-income-data',[ReportsController::class,'binaryIncomeReport']);

    /*--------------------------*Ewallet END*------------------------------ */

    /*--------------------------*SUB ADMIN*------------------------------ */
    Route::get('sub-admin/create-sub-admin', [AuthenticationController::class, 'subAdmin']);
    Route::any('create/subadmin', [AuthenticationController::class, 'createSubadmin']);
    Route::any('getsubadminsdetails', [NavigationsController::class, 'getAllSubadminDetails']);
    Route::any('getsubadmins', [NavigationsController::class, 'getSubadmins']);
    Route::any('sub-admin/assign-right', [NavigationsController::class, 'assignRight']);
    Route::any('getsubadminnavigation', [NavigationsController::class, 'getSubadminNavigations']);
    Route::any('getadminnavigation', [NavigationsController::class, 'getAdminNavigations']);
    Route::any('assignrights', [NavigationsController::class, 'assignSubadminRights']);
    Route::any('/sub-admin/assign-rights-report', [NavigationsController::class, 'assignReightsReportView']);

    /*--------------------------*SUB ADMIN END*------------------------------ */


    /*--------------------------*Reports END*------------------------------ */
    Route::get('/manage-power/add-power', [AdminUserController::class, 'addPowerBlade']);
    Route::any('/send-otp-withdraw-mail', [AdminUserController::class, 'sendOtpWithdrawMail'])->name('send-otp-withdraw-mail');
    Route::any('/checkuserexist', [AdminUserController::class, 'checkUserExist'])->name('checkuserexist');
    Route::post('/manage-power/add-power', [AdminUserController::class, 'addPower'])->name('add-power-post');
    /*--------------------------*Power END*------------------------------ */

    /*-------------------------------------Add Upline--------------------------*/
    Route::get('/manage-power/add-power-upline', [AdminUserController::class, 'addBussinessUplineBlade'])->name('addBussinessUpline');
    Route::post('/checkuplineuserexist', [AdminUserController::class, 'checkUplineUserExist'])->name('checkuplineuserexist');
    Route::post('/manage-power/add-bussiness-upline', [AdminUserController::class, 'addBussinessUpline'])->name('addBussinessUpline');
    /*-------------------------------------Add Upline End--------------------------*/

    /*--------------------------------------Start:Fund------------------------------------- */
    //AddFund/AddFund
    Route::get('/admin-add-fund', [FundRequestController::class, 'fundRequestBlade'])->name('admin-add-fund');
    Route::post('/fund_request', [FundRequestController::class, 'fundRequest'])->name('add-fund');


    //Fund remove
    Route::get('/admin-remove-fund', [FundRequestController::class, 'fundremoveBlade'])->name('admin-remove-fund');
    Route::post('/remove_fund_request', [FundRequestController::class, 'removefundRequest'])->name('remove_fund_request');
    /*--------------------------------------End:Fund------------------------------------- */

    /*--------------------------------------Influencer Start------------------------------------- */
    Route::get('/top-up/add-influencer-topup', [AdminProductController::class, 'AddInfluencerTopupBlade']);
    Route::any('/store/topup-store', [AdminProductController::class, 'storeTopup']);
    /*--------------------------------------Influencer End------------------------------------- */

    /*--------------------------------------Power Start------------------------------------- */
    Route::get('/top-up/bulk-power-topup', [AdminProductController::class, 'BulkPowerTopupBlade']);
    Route::post('/store/bulktopup', [AdminProductController::class, 'storeBulkTopup']);
    Route::any('/checkbulkuserexist', [AdminUserController::class, 'checkBulkUserExist']);
    /*--------------------------------------Power End------------------------------------- */

    /*--------------------------------------Password Start------------------------------------- */
    //Change Password
    Route::any('/user/change-password', [AdminUserController::class, 'changePasswordBlade']);
    Route::any('/updateuserpassword', [AdminUserController::class, 'updateUserPassword']);
    /*--------------------------------------Password End------------------------------------- */

    /*--------------------------------------Topup Start------------------------------------- */
    Route::any('/top-up/add-top-up', [AdminProductController::class, 'addTopBlade']);
    Route::any('/admin-topup', [AdminProductController::class, 'storeTopup']);
    Route::any('/send-admin-otp', [AdminUserController::class, 'sendOtp']);
    Route::any('/checkuserexist', [AdminUserController::class, 'checkuserexist']);
    Route::any('/get-products', [AdminProductController::class, 'getProducts']);
    Route::any('/get-otp-status', [AdminUserController::class, 'GetAdminOtpStatus']);

    Route::any('/get-products', [AdminProductController::class, 'getProducts']);
    Route::any('/get-otp-status', [AdminUserController::class, 'GetAdminOtpStatus']);

    Route::any('/get-products', [AdminProductController::class, 'getProducts']);
    Route::any('/get-otp-status', [AdminUserController::class, 'GetAdminOtpStatus']);
    /*--------------------------------------Topup End------------------------------------- */

    /*--------------------------*Reports Start*------------------------------ */
    Route::any('/top-up/top-up-report', [AdminProductController::class, 'getTopupReport']);
    Route::any('/gettopup', [AdminProductController::class, 'getTopups']);
    Route::any('/topupchangroistop', [AdminProductController::class, 'topupChangeRoiStop'])->name('admin/topupchangroistop');

    Route::any('/top-up/influencer-track-report', [AdminProductController::class, 'getInfluencerTrackReport']);
    Route::any('/get-influencer-track-report', [AdminProductController::class, 'getInfluencerTrackingReport']);
    Route::any('/topuproistop', [AdminProductController::class, 'topupRoiStop']);

    Route::any('/top-up/influencer-topup-report', [AdminProductController::class, 'InfluencerTopupsBlade']);
    Route::any('/get_influencer_topup', [AdminProductController::class, 'getInfluencerTopups']);

    Route::any('/top-up/bulk-power-topup-report', [AdminProductController::class, 'BulkPowerTopupReportBlade']);
    Route::any('/getbulktopup', [AdminProductController::class, 'getbulktopups']);

    /*--------------------------*Reports END*------------------------------ */



    /*---------------------------*Tree View*--------------------------------*/
    Route::any('/user/tree-view', [AdminLevelController::class, 'getLevelsViewTreeManualProductBase'])->name('tree-view');

    /*---------------------------*Tree View*--------------------------------*/
    Route::any('marketing-tools/marketing-tools-report', [MarketingController::class, 'marketingToolsReportPage'])->name('MarketingToolReport');
    Route::any('marketing-tools/add-banners', [MarketingController::class, 'addMarketingToolsPage'])->name('add-banners');
    Route::any('marketing-tools/add-creatives', [MarketingController::class, 'creativesMarketingToolsPage'])->name('add-creatives');
    Route::any('marketing-tools/add-presentation', [MarketingController::class, 'creativesMarketingToolspresentationPage'])->name('add-presentation');
    Route::any('marketing-tools/add-videos', [MarketingController::class, 'addVideos'])->name('add-videos');
    Route::any('add-marketing-tool', [MarketingController::class, 'addMarketingTools'])->name('add-banners');
    Route::any('store-videos', [MarketingController::class, 'addMarketingTools']);
    Route::any('marketing-tool-report', [MarketingController::class, 'marketingToolsReport']);
    Route::any('remove-marketing-tool', [MarketingController::class, 'removeMarketingTools']);
    Route::any('get-tool-details', [MarketingController::class, 'getToolDetails']);
    Route::any('update-marketing-tool', [MarketingController::class, 'updateMarketingTools']);
    Route::any('edit-marketing-tool/{id}', [MarketingController::class, 'EditMarketingTool']);
    Route::any('marketing-tools/add-images', [MarketingController::class, 'addMultipleImages'])->name('add-Images');

    Route::any('/manage-power/power-report', [AdminUserController::class, 'powerReportBlade']);
    Route::any('/power-report', [AdminUserController::class, 'powerReport']);

    Route::any('/manage-power/upline-power-report', [AdminUserController::class, 'UplineReportBlade']);
    Route::any('/manage-power/business-upline-report', [AdminUserController::class, 'businessUplineReport']);

    Route::any('/user/block-users-report', [AdminUserController::class, 'blockUserBlade']);
    Route::any('/show-block-users', [AdminUserController::class, 'getBlockUsers']);

    Route::any('/user/qualified-user-list', [EWalletController::class, 'getQualifiedUsersBlade']);
    Route::any('/getqualifieduser', [EWalletController::class, 'getBinaryQualifiedUsers']);

    Route::any('/withdrawal/confirm-withdrawal-report', [EWalletController::class, 'confirmedWithdrawalBlade']);
    Route::any('/getconfirmedwithdrwal', [EWalletController::class, 'getWithdrwalConfirmed'])->name('getconfirmedwithdrwal');

    Route::any('/withdrawal/verified-withdrawal', [EWalletController::class, 'verifiedwithdrawalBlade']);
    Route::any('/GetAdminOtpStatus', [AdminUserController::class, 'GetAdminOtpStatus']);
    Route::any('/getWithdrawalSummary', [EWalletController::class, 'getWithdrawalSummary']);
    Route::any('/approve/withdrawalrequest', [EWalletController::class, 'withdrawalRequestApprove']);

    Route::any('/approveWithdraw', [EWalletController::class, 'approveWithdraw']);

    
    Route::any('/send/withdrwalrequest', [EWalletController::class, 'WithdrwalRequest']);
    Route::any('/reject/withdrwalrequest', [EWalletController::class, 'WithdrwalRequestReject']);
    Route::any('/confirmWithdrawl', [EWalletController::class, 'confirmWithdrawl']);
    Route::any('/getwithdrwalverified', [EWalletController::class, 'getWithdrwalVerified']);

    Route::any('/withdrawal/rejected-withdrawal-report', [EWalletController::class, 'rejectedWithdrawalReportBlade']);
    Route::any('/rejected_withdrawals', [EWalletController::class, 'rejectedWithdrawalReport']);

    Route::any('/withdrawal/withdrawal-request', [EWalletController::class, 'PendingWithdrawalBlade']);
    Route::any('/getwithdrwalverify', [EWalletController::class, 'getWithdrwalVerify'])->name('getwithdrwalverify');
    Route::any('/verify/withdrwalrequest', [EWalletController::class, 'WithdrwalRequestVerify'])->name('WithdrwalRequestVerify');

    Route::any('/user/influencer-direct-signup-report', [AdminUserController::class, 'influencerdirectsignupblade']);
    Route::any('/get-influencer-direct-signup-report', [AdminUserController::class, 'getInfluencerDirectSignupReport']);

    Route::any('/user/direct-signup-report', [AdminUserController::class, 'directSignUpReportBlade']);
    Route::any('/get-direct-signup-report', [AdminUserController::class, 'getDirectSignupReport']);

    Route::any('/transfer-report', [FundRequestController::class, 'transferReportBlade']);
    Route::any('/fund_transfer_report', [FundRequestController::class, 'fundTransferReport']);

    Route::any('/user/edit-profile-report', [AdminUserController::class, 'editprofilereportBlade']);
    Route::any('/getuserlogs', [AdminUserController::class, 'getUserUpdatedLog']);

    Route::any('/daily-bonus-report', [LendingController::class, 'DailyBonusReportBlade']);
    Route::any('/getdailybinary', [LendingController::class, 'getDailyBinary']);

    Route::any('/hscc-bonus-report', [LendingController::class, 'HsccBonusReportBlade']);
    Route::any('/gethsccbouns', [LendingController::class, 'getHsccBonus']);


    Route::any('/account-wallet', [AdminDashboardController::class, 'AccountWalletBlade']);
    Route::any('/getaccountwallet', [AdminDashboardController::class, 'getAccountWallet']);

    Route::any('/admin-add-fundreport', [FundRequestController::class, 'AdminAddFundReportBlade']);
    Route::any('/fund_report', [FundRequestController::class, 'fundReport']);

    Route::any('/admin-remove-fundreport', [FundRequestController::class, 'AdminRemoveFundReportBlade']);
    Route::any('/remove_fund_report', [FundRequestController::class, 'removefundReport']);

    Route::any('/user/total-team-view', [AdminLevelController::class, 'TotalTeamViewBlade']);
    Route::any('/getteamviews', [AdminLevelController::class, 'getTeamViews']);
    /*--------------------------*Reports End*------------------------------ */

    /*---------------------------*Tree View*--------------------------------*/
    Route::any('/user/tree-view', [AdminLevelController::class, 'getLevelsViewTreeManualProductBase'])->name('getlevelviewtree');
    /*---------------------------*Tree View*--------------------------------*/

    /*---------------------------*Marketing Tools*--------------------------------*/
    Route::any('marketing-tools/add-banners', [MarketingController::class, 'addMarketingToolsPage'])->name('add-banners');
    Route::any('add-marketing-tool', [MarketingController::class, 'addMarketingTools'])->name('add-banners');

    Route::get('/user/manage-user-account', [AdminUserController::class, 'ManageUserAccountBlade']);
    Route::any('/blockuser', [AdminUserController::class, 'blockUser']);
    Route::any('/user_login/{id}', [AuthenticationController::class, 'loginUser']);
    Route::any('/changeUserWithdrawStatus', [AdminUserController::class, 'changeUserWithdrawStatus']);

    ///user profile update
    Route::get('/user/edit-user-profile/{id}', [AdminUserController::class, 'EditUserProfileBlade']);
    Route::get('/user/user-profile/{id}', [AdminUserController::class, 'userProfile']);
    Route::post('/user/update-profile', [AdminUserController::class, 'updateUser']);
    Route::get('/getuserprofile', [AdminUserController::class, 'getUserProfileDetails']);
    Route::any('/updateuser',  [AdminUserController::class, 'updateUser']);
    Route::any('/getusers', [AdminUserController::class, 'getUsers']);


    Route::any('/invalid-login-users', [AdminProductController::class, 'invalidLoginPage']);
    Route::any('/getlogincountreport', [AdminProductController::class, 'getLoginCountUsersDetails']);
    Route::post('/changeuserblockstatus', [AdminUserController::class, 'changeUserBlockStatus']);



});

