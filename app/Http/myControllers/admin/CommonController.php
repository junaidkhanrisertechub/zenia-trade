<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Activitynotification;
use App\Models\PushNotification;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Currencyrate;
use App\Models\Navigation;
use App\Models\Packages;
use App\Models\ProjectSettings;
use App\User;
use DB;
use Config;
use Mail;
use Storage;

class CommonController extends Controller
{
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->statuscode = Config::get('constants.statuscode');
    }

    /**
     * get all user list with conditional parameter
     * @param $arrOptionalData has array of conditional parameter
     * @return \Illuminate\Http\Response
     */
    public function getAllUserList($arrOptionalData = null) {

        $arrUsers = User::where('type','');
        if(!empty($arrOptionalData)){
            $arrUsers = $arrUsers->where($arrOptionalData);
        }
        $arrUsers = $arrUsers->orderBy('entry_time','desc')->get();
        return $arrUsers;
    }
    /**
     * get all subadmin list with conditional parameter
     * @param $arrOptionalData has array of conditional parameter
     * @return \Illuminate\Http\Response
     */
    public function getSubadminList($arrOptionalData = null) {
        $arrSubadminData = User::query();
        if(!empty($arrOptionalData)){
            $arrSubadminData = $arrSubadminData->where($arrOptionalData)->where('id','!=',1);
        }
        $arrSubadminData = $arrSubadminData->orderBy('entry_time','desc')->get();
        return $arrSubadminData;
    }

    /**
     * get all users by passing conditional parameter
     *
     * @return \Illuminate\Http\Response
     */
    public function getSpecificUserData($arrData) {

        $arrSpecificUsers = User::where($arrData)->first();
        return $arrSpecificUsers;
    }
    /**
     * get logged user details by remember token or other parameter
     *
     * @return \Illuminate\Http\Response
     */
    public function getLoggedUserData($arrData) {

        $objLoggedData = User::where($arrData)->first();
        return $objLoggedData;
    }

    /**
     * get all country list
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllCountry() {

        $arrCountry = Country::get();
        return $arrCountry;
    }

    /**
     * get all currency records
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllCurrency() {

        $arrCurrency = Currency::get();
        return $arrCurrency;
    }

    /**
     * get all PushNotification records
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllPushNotification() {

        $arrPushNotification = PushNotification::get();
        return $arrPushNotification;
    }

    /**
     * get all PushNotification records
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllActivityNotification() {

        $arrActivitynotification = Activitynotification::join('tbl_users as tu','tu.id','=','activity_notification.id')->select('activity_notification.*','tu.user_id')->get();
        return $arrActivitynotification;
    }

    /**
     * get all currency rate records
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllCurrencyRate() {

        $arrCurrencyRate = Currencyrate::get();
        return $arrCurrencyRate;
    }

    /**
     * get specific country
     *
     * @return \Illuminate\Http\Response
     */
    public function getSpecificCountry($arrData) {

        $country = Country::where($arrData)->first();
        return $country;
    }

    /**
     * get navigations menu details list
     *
     * @return \Illuminate\Http\Response
     */
    public function getNavigations($arrOptionalData = null) {

        $arrNavigations = Navigation::where('status','Active');
        if(!empty($arrOptionalData)) {
            $arrNavigations = $arrNavigations->where($arrOptionalData);
        }
        $arrNavigations = $arrNavigations->get();
        return $arrNavigations;
    }

    /**
     * get products details list
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllProducts($arrOptionalData = null) {

        $arrProducts = Packages::query();
        if(!empty($arrOptionalData)) {
            $arrProducts = $arrProducts->where($arrOptionalData);
        }
        $arrProducts = $arrProducts->orderBy('entry_time','desc')->get();

        return $arrProducts;
    }
    /**
     * get settings of project
     *
     * @return void
     */
    public function getProjectSettings() {

        $ProjectSettings = ProjectSettings::where('status',1)->first();
        return $ProjectSettings;
    }

    /**
     * send mails
     *
     * @return \Illuminate\Http\Response
     */
    public function sendEmail(){
        $data = array('name'=>" ");
        Mail::send('emails.mail', $data, function($message) {
            $message->to('abcd@abcd.com', 'Mail Test')
                ->subject('Laravel Testing Mail')
                ->from('abcd@abcd.com','test');
        });
        echo "Email Sent. Check your inbox.";
    }
    public  function exportToExcel($data,$reportName){
        $filename = $reportName ."_". time() . ".xlsx";
        //dd($data);

        header("Content-type: application/x-msdownload");
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Pragma: no-cache");
        header("Expires: 0");

        $flag = false;
        $var='<table><tr><th>Sr.No.</th>';
        $i = 1;
        foreach($data as $row) {
            if(!$flag) {
                // display field/column names as first row
                $keys = array_keys($row);
                foreach ($keys as $vk) {
                    $style = '';
                    /* if ($vk == "deposite_id") {
                         $style = "style='width:30%'";
                     }*/
                    $var = $var."<th>".ucwords(str_replace("_", " ", $vk))."</th>";
                }
                $var = $var."</tr><tr>";
                $flag = true;
            }

            $var = $var."<td>".$i++."</td>";
            foreach ($row as $vr) {
                $var = $var."<td>".$vr."</td>";
            }
            $var = $var."</tr>";
        }
        $var = $var."</tr></table>";
        return $var;
    }

}
