<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use App\Models\MarketTool;
use App\Models\verifyAdminOtpStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Input;
use App\User;
use App\config\constants; 
use Hash;
use DB;
use Config;
use Validator;
use Exception;

class MarketingController extends Controller
{
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode, $commonController;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CommonController $commonController)
    {
        $date             = \Carbon\Carbon::now();
        $this->today      = $date->toDateTimeString();
        $this->statuscode =    Config::get('constants.statuscode');
        $this->commonController = $commonController;
    }

    public function addMarketingTools(Request $request){
        try {
            $arrInput = $request->all();
            if ($request->tool_type == 1 || $request->tool_type == 3) {
                $rules = array(
                    'tool_name' => 'required|',
                    'market_tool' => 'required|mimes:jpeg,jpg,png',
                );
                $messages = array(
                    'market_tool.required' => 'Please choose tool file.',
                    'tool_name.required' => 'Please enter name.',
                );
            }elseif ($request->tool_type == 2 || $request->tool_type == 22 || $request->tool_type == 23) {
                $rules = array(
                    'tool_name' => 'required|',
                    // 'market_tool' => 'required|mimes:mp4,webm,ogg,mkv|max:10240',
                    'market_tool' => 'required|',
                );
                $messages = array(
                    'market_tool.required' => 'Please choose tool file.',
                    // 'market_tool.max' => 'File size should not be more than 10MB',
                    'tool_name.required' => 'Please enter name.',
                );
            }elseif ($request->tool_type == 4 || $request->tool_type == 42) {
                $rules = array(
                    'tool_name' => 'required|',
                    'market_tool' => 'required|mimes:pdf',
                );
                $messages = array(
                    'market_tool.required' => 'Please choose tool file.',
                    'tool_name.required' => 'Please enter name.',
                );

            }
            

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                }
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
            }

            $adminOtpStatusData = verifyAdminOtpStatus::select('add_fund_otp_status')->first();
            if ($adminOtpStatusData->add_fund_otp_status == 1) {
                if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
                }
                $arrInput['user_id'] = Auth::User()->id;
                $arrInput['remark'] = 'admin fund';
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
            $userExist = Auth::user();
            if (!empty($userExist)) {
                if ($request->tool_type == 1 || $request->tool_type == 3 || $request->tool_type == 4 || $request->tool_type == 42) {
                    $file       = Input::file('market_tool');
                    $newUrl = '';
                    if($request->hasFile('market_tool')) {
                        $url    = Config::get('constants.settings.aws_url');
                                 // dd($url);
                        $fileName = Storage::disk('s3')->put("tool_url", $file, "public");
                                //dd($fileName);
                        $newUrl=$url.$fileName;
                                //dd($newUrl);
                    }
                }else{
                    $newUrl = $request->market_tool;
                }

                $marketTool = new MarketTool;
                $marketTool->tool_name = $request->tool_name;
                $marketTool->tool_type = $request->tool_type;
                $marketTool->tool_url = $newUrl;
                $marketTool->entry_time = $this->today;
                $marketTool->update_time = $this->today;
                $marketTool->save();

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Marketing Tool Added Successfully', '');

            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User does not exist', '');
            }
            
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong, Please try again', '');
        }
    }

    public function marketingToolsReport(Request $request){
        try {
            $arrInput = $request->all();
            $query = MarketTool::select('tbl_marketing_tools.srno', 'tbl_marketing_tools.tool_name', 'tbl_marketing_tools.entry_time','tbl_marketing_tools.update_time',DB::raw('(CASE  WHEN tbl_marketing_tools.tool_type = 1 THEN "Banner" WHEN tbl_marketing_tools.tool_type = 2 THEN "Business Presentation Video" WHEN 
                tbl_marketing_tools.tool_type = 22 THEN "Tutorial Video" WHEN 
                tbl_marketing_tools.tool_type = 23 THEN "Promo Video" WHEN
                tbl_marketing_tools.tool_type = 3 THEN "Creatives" WHEN 
                tbl_marketing_tools.tool_type = 4 THEN "Business Presentation" WHEN
                tbl_marketing_tools.tool_type = 42 THEN "Founder & CEO" END) as tool_type'
            ));
            if (isset($arrInput['tool_type'])) {
                $query = $query->where('tbl_marketing_tools.tool_type', $request->tool_type);
            }
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_marketing_tools.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
            }

            if (isset($arrInput['action']) && $arrInput['action'] == 'export') {
                $qry = $query;
                $qry = $qry->select('tbl_marketing_tools.entry_time','tbl_marketing_tools.tool_name',DB::raw('(CASE  WHEN tbl_marketing_tools.tool_type = 1 THEN "Banner" ELSE "Video" END ) as tool_type'))->orderBy('tbl_marketing_tools.entry_time', 'desc');
                $records = $qry->get();
                $res = $records->toArray();
                if (count($res) <= 0) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
                }
                $var = $this->commonController->exportToExcel($res, "MarketingToolsReport");
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
            }


            $totalRecord   = $query->count('tbl_marketing_tools.srno');
            $query         = $query->orderBy('tbl_marketing_tools.entry_time', 'desc');
            // $totalRecord   = $query->count();
            $arrFranchise      = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $arrFranchise;

            if (count($arrFranchise) > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
            
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong, Please try again', '');
        }
    }

    public function getToolDetails(Request $request){
        try {
            $arrInput = $request->all();

            $id = Auth::user()->id;
            if (!empty($id)) {

                $toolDetails = MarketTool::select('tbl_marketing_tools.srno', 'tbl_marketing_tools.tool_name', 'tbl_marketing_tools.entry_time','tbl_marketing_tools.update_time','tbl_marketing_tools.tool_type')->where('tbl_marketing_tools.srno', $arrInput['id'])->first();

                $toolData = array();
                $toolData['id'] = $toolDetails->srno;
                $toolData['tool_name'] = $toolDetails->tool_name;
                $toolData['tool_type'] = $toolDetails->tool_type;
                $toolData['entry_time'] = $toolDetails->entry_time;
                $toolData['update_time'] = $toolDetails->update_time;

                $arrFinalData['toolData']             = $toolData;
                if (count($arrFinalData) > 0) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrFinalData);
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                }

            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User does not exist', '');
            }

            
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong, Please try again', '');
        }
    }

    public function updateMarketingTools(Request $request){
        try {
            $arrInput = $request->all();
                $rules = array(
                    'tool_id' => 'required|numeric',
                    'tool_name' => 'required|',
                );
                $messages = array(
                    'tool_id.required' => 'Tool-ID required.',
                    'tool_name.required' => 'Please enter name.',
                );
            

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                }
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
            }

            $adminOtpStatusData = verifyAdminOtpStatus::select('add_fund_otp_status')->first();
            if ($adminOtpStatusData->add_fund_otp_status == 1) {
                if(!isset($arrInput['otp']) && empty($arrInput['otp'])) {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Otp Required', '');
                }
                $arrInput['user_id'] = Auth::User()->id;
                $arrInput['remark'] = 'admin fund';
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
            $userExist = Auth::user();
            if (!empty($userExist)) {

                $arrUpdate = [
                    'tool_name' => $arrInput['tool_name'],
                    'update_time' => $this->today,
                ];

                $updateData = MarketTool::where('srno', $arrInput['tool_id'])->limit(1)->update($arrUpdate);

                if (!empty($updateData)) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Tool data updated successfully.', '');
                } else {
                    return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'], 'Data already existed with given inputs.', '');
                 }

            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User does not exist', '');
            }
            
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong, Please try again', '');
        }
    }

    public function removeMarketingTools(Request $request){
        try {
            $arrInput = $request->all();
                $rules = array(
                    'tool_id' => 'required|numeric',
                );
                $messages = array(
                    'tool_id.required' => 'Tool-ID required.',
                );
            

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                }
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
            }

            $userExist = Auth::user();
            if (!empty($userExist)) {

                $delete = MarketTool::where('srno',$request->tool_id)->delete();

                if(!empty($delete)){
                    $arrStatus   = Response::HTTP_OK;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Deleted succesfully'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                }
                else{
                    $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
                    $arrCode = Response::$statusTexts[$arrStatus];
                    $arrMessage = 'Something went wrong,Please try again';
                    return sendResponse($arrStatus, $arrCode, $arrMessage, '');
                }

            }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User does not exist', '');
            }
            
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Something went wrong, Please try again', '');
        }
    }

    public function videoUploadCheck(Request $request){
        $rules_img=array(
            'market_tool'=>"required|mimes:mp4,webm,ogg,mkv|max:10240"
        );
        $messages = array(
            'market_tool.max' => 'File size should not be more than 10MB',
        );

        // $validator = checkvalidation($request->all(), $rules_img, $messages);
        $validator = Validator::make($request->all(), $rules_img, $messages);
        if ($validator->fails()) {
            $message = $validator->errors();
            $err = '';
            foreach ($message->all() as $error) {
                $err = $err . " " . $error;
            }
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
        /*}
        if (!empty($validator)) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $validator, '');*/
        }else{
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Valid Video Size', '');
        }
    }


    
}
