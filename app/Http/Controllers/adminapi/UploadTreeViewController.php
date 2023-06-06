<?php

namespace App\Http\Controllers\adminapi;

use App\Http\Controllers\adminapi\CommonController;
use App\Http\Controllers\adminapi\LevelController;
use App\Http\Controllers\Controller;
use App\Models\Activitynotification;
use App\Models\AddressTransaction;
use App\Models\AddressTransactionPending;
use App\Models\AllTransaction;
use App\Models\Country;
use App\Models\CurrentAmountDetails;
use App\Models\Dashboard;
use App\Models\ProjectSetting;
use App\Models\Template;
use App\Models\Depositaddress;
use App\Models\Otp as Otp;
use App\Models\PowerBV;
use App\Models\AddRemoveBusiness;
use App\Models\Representative;
use App\Models\LevelView;
use App\Models\UsersChangeData;
use App\Models\Topup;
use App\Models\UploadTreeview;

use App\Models\Rank;
use App\Models\TodayDetails;
use App\Models\UserBulkUpdate;
use App\Models\UserContestAchievement;
use App\Models\AddFaq;
use App\Models\SettingGallery;


use App\Models\WithdrawPending;
use App\User;
use Hash;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UploadTreeViewController extends Controller
{
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
        $this->statuscode = Config::get('constants.statuscode');
        $this->OTP_interval = Config::get('constants.settings.OTP_interval');
        $this->sms_username = Config::get('constants.settings.sms_username');
        $this->sms_pwd = Config::get('constants.settings.sms_pwd');
        $this->sms_route = Config::get('constants.settings.sms_route');
        $this->senderId = Config::get('constants.settings.senderId');
        $this->commonController = $commonController;
        $this->levelController = $levelController;
    }
    public function activeuploadPhotos(Request $request)
    {
         // dd($request);
        $rules = array(
            'photo' => 'required',
            'name' => 'required'
        );

        $messages = array(
            'photo.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
         $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo']['name'], PATHINFO_FILENAME);
        //dd($picture_filename);
        $imageName = null;
        if (!empty($request->photo)) {
            // $imageName = 'present.' . $request->photo->getClientOriginalExtension();
        }
        //dd($imageName);

        if ($request->name == 'photo') {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"0"],['img_type','=','1']])->limit(1)->update($updateCoinData);


            // $request->photo->move(public_path('uploads/user_files/gallery'), $imageName);
            // $path = public_path('uploads/user_files/photo/' . $imageName);

        }
        if (!empty($path)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Useruploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }
    public function uploadPhotos(Request $request)
    {
         // dd($request);
        $rules = array(
            'photo' => 'required',
            'name' => 'required'
        );

        $messages = array(
            'photo.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
         $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo']['name'], PATHINFO_FILENAME);
        //dd($picture_filename);
        $imageName = null;
        if (!empty($request->photo)) {
        // $imageName =   'present.' . $request->photo->getClientOriginalExtension();

            // $imageName = $picture_filename . '.' . $request->photo->getClientOriginalExtension();
        }
        //dd();

        if ($request->name == 'photo') {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"1"],['img_type','=','1']])->limit(1)->update($updateCoinData);
            // $request->photo->move(public_path('admin_assets/uploads/gallery/admin_assets'), $imageName);
            // $path = public_path('uploads/photo/' . $imageName);

        }
        //dd($path);
        if (!empty($path)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image AdminSideuploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }
    public function getnotuploadPhotos(Request $request)
    {
        //dd($request);
        $rules = array(
            'photo1' => 'required',
            'name' => 'required'
        );
        $messages = array(
            'photo1.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
         $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo1']['name'], PATHINFO_FILENAME);
        $imageName = null;
        if (!empty($request->photo1)) {
        // $imageName =   'absent.' . $request->photo1->getClientOriginalExtension();

            // $imageName = $picture_filename . '.' . $request->photo1->getClientOriginalExtension();
        }
        if ($request->name == 'photo1') {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"1"],['img_type','=','2']])->limit(1)->update($updateCoinData);
            // $request->photo1->move(public_path('admin_assets/uploads/gallery/admin_assets'), $imageName);
            // $path = public_path('uploads/photo1/' . $imageName);

        }
        //dd($path);
        if (!empty($path)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image AdminSideuploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }
    public function getusernotuploadPhotos(Request $request)
    {
        //dd($request);
        $rules = array(
            'photo1' => 'required',
            'name' => 'required'
        );
        $messages = array(
            'photo1.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
         $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo1']['name'], PATHINFO_FILENAME);
        $imageName = null;
        if (!empty($request->photo1)) {
            // $imageName =  'absent.' . $request->photo1->getClientOriginalExtension();
        }
        if ($request->name == 'photo1') {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"0"],['img_type','=','2']])->limit(1)->update($updateCoinData);

            // $request->photo1->move(public_path('uploads/user_files/gallery'), $imageName);
            // $path = public_path('uploads/user_files/photo1/' . $imageName);

        }
        if (!empty($path)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image Useruploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }
    public function getBlockuploadPhotos(Request $request)
    {
        //dd($request);
        $rules = array(
            'photo2' => 'required',
            'name' => 'required'
        );
        $messages = array(
            'photo2.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
         $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo2']['name'], PATHINFO_FILENAME);
        $imageName = null;
        if (!empty($request->photo2)) {
            // $imageName =   'block.' . $request->photo2->getClientOriginalExtension();

            // $imageName = $picture_filename . '.' . $request->photo2->getClientOriginalExtension();
        }
        if ($request->name == 'photo2') {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"1"],['img_type','=','3']])->limit(1)->update($updateCoinData);
            // $request->photo2->move(public_path('admin_assets/uploads/gallery/admin_assets'), $imageName);
            // $path = public_path('uploads/photo2/' . $imageName);

        }
        if (!empty($path)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image AdminSideuploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }
     public function getphotoBlockupload(Request $request)
    {
        //dd($request);
        $rules = array(
            'photo2' => 'required',
            'name' => 'required'
        );
        $messages = array(
            'photo2.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
         $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo2']['name'], PATHINFO_FILENAME);
        $imageName = null;
        if (!empty($request->photo2)) {
            // $imageName = 'block.' . $request->photo2->getClientOriginalExtension();
        }
        if ($request->name == 'photo2') {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"0"],['img_type','=','3']])->limit(1)->update($updateCoinData);
            // $request->photo2->move(public_path('uploads/user_files/gallery'), $imageName);
            // $path = public_path('uploads/user_files/photo2/' . $imageName);

        }
        if (!empty($path)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image Usereuploaded successfully!', '');
        } else {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }
    public function getpaiduploadPhotos(Request $request)
    {
        //dd($request);
        $rules = array(
            'photo3' => 'required',
            'name' => 'required'
        );
        $messages = array(
            'photo3.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails())
        {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
         $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo3']['name'], PATHINFO_FILENAME);
        $imageName = null;
        if (!empty($request->photo3))
        {
            // $imageName =  'no_topup.'  . $request->photo3->getClientOriginalExtension();
        }
        if ($request->name == 'photo3')
        {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"1"],['img_type','=','4']])->limit(1)->update($updateCoinData);
            // $request->photo3->move(public_path('admin_assets/uploads/gallery/admin_assets'), $imageName);
            // $path = public_path('uploads/photo3/' . $imageName);

        }
        if (!empty($path))
        {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image AdminSideuploaded successfully!', '');
        } else
        {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }
    public function getuseruploadPhotos(Request $request)
    {
        //dd($request);
        $rules = array(
            'photo3' => 'required',
            'name' => 'required'
        );
        $messages = array(
            'photo3.required' => 'Please select photo.',
            'name.required' => 'Please enter name.'
        );

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails())
        {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        }
        $getuser = Auth::user();
        $id = $request->symbol;
        $picture_filename = pathinfo($_FILES['photo3']['name'], PATHINFO_FILENAME);
        $imageName = null;
        if (!empty($request->photo3))
        {
            // $imageName = 'no_topup.'  . $request->photo3->getClientOriginalExtension();
        }
        if ($request->name == 'photo3') {
            $updateCoinData = array();
            $updateCoinData['img_name'] = $imageName;
            $updateCoinData = DB::table('tbl_tree_imges')->where([['type','=',"0"],['img_type','=','4']])->limit(1)->update($updateCoinData);
            // $request->photo3->move(public_path('uploads/user_files/gallery'), $imageName);
            // $path = public_path('uploads/user_files/photo3/' . $imageName);

        }
        if (!empty($path))
        {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Image Useruploaded successfully!', '');
        } else
        {

            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Photo not uploaded.', '');
        }
    }




}
