<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response as Response;
use App\Http\Controllers\adminapi\CommonController;
use App\Models\User;
use App\Models\Product_Ecomm;
use App\Models\EcommerceProduct;
use App\Models\UserCartProduct;
use App\Models\UserCartOrder;
use App\Models\Payment;
use App\Models\Template;
use App\Models\CurrencyValidation;
use App\Models\ProjectSetting;
use Validator;
use Config;
use DB;
use Exception;
use PDOException;
use Auth;
use URL;

class TemplateSettingController extends Controller
{

    public $commonController;

    public function __construct(CommonController $CommonController)
    {
        $this->statuscode = Config::get('constants.statuscode');
        $this->commonController     = $CommonController;
        $date = \Carbon\Carbon::now();
        $this->today = $date->toDateTimeString();
    }


    public function selectedTepmplateData(Request $request)
    {

        $quesry = DB::table('tbl_templates')
            ->select('*')
            ->where('id', $request->id)
            ->get();

        if (!empty($quesry)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Get data succesfully', $quesry);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function updateTepmplate(Request $request)
    {
        //   return  $request->all();

        $data = array('type' => $request->type, 'title' => $request->title, 'subject' => $request->subject, 'content' => $request->content);
        $query = DB::table('tbl_templates')
            ->where('id', $request->id)
            ->update($data);

        if (!empty($query)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Updatedsuccesfully', '');
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data is Not updated If you want to update Please Fill the Above fields', '');
        }
    }
    public function getTemplateList(Request $request)
    {
        $arrInput = $request->all();
        // ini_set('memory_limit', '-1');
        $query = Template::select('tbl_templates.id', 'tbl_templates.type', 'tbl_templates.title', 'tbl_templates.subject', 'tbl_templates.content', 'tbl_templates.entry_time');

        $totalRecord = $query->count('tbl_templates.id');
        $query = $query->orderBy('tbl_templates.id', 'desc');
        // $totalRecord = $query->count();
        $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
        // dd($arrDirectInc);
        $arrData['recordsTotal'] = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records'] = $arrDirectInc;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function addTemplate(Request $request)
    {

        $arrInput = $request->all();
        // dd($arrInput);
        $rules = array('type' => 'required', 'title' => 'required', 'subject' => 'required', 'content' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            //Insert inn power Bv Table
            $power = new Template;
            $power->type = $arrInput['type'];
            $power->title = $arrInput['title'];
            $power->subject = $arrInput['subject'];
            $power->content = $arrInput['content'];
            $power->entry_time = \Carbon\Carbon::now();
            $insertAdd = $power->save();
            if (!empty($insertAdd)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Template added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[400]['status'], 'Error while adding Template', '');
            }
        }
    }

    public function addCurrencyValidation(Request $request)
    {

        $arrInput = $request->all();
        // dd($arrInput);
        $rules = array('btc_symbol' => 'required', 'rules' => 'required','min_length' => 'required','max_length' => 'required');
        $validator = Validator::make($arrInput, $rules);

        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
        } else {

            //Insert inn power Bv Table
            $power = new CurrencyValidation;
            $power->btc_symbol = $arrInput['btc_symbol'];
            $power->rules = $arrInput['rules'];
            $power->min_length = $arrInput['min_length'];
            $power->max_length = $arrInput['max_length'];
            $power->entry_time = \Carbon\Carbon::now();
            $insertAdd = $power->save();
            if (!empty($insertAdd)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Currency Validation added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[400]['status'], 'Error while adding Currency Validation', '');
            }
        }
    }

    public function getCurrencyValidation(Request $request)
    {
        $arrInput = $request->all();
        // ini_set('memory_limit', '-1');
        $query = CurrencyValidation::select('tbl_crypto_currency_validation.id', 'tbl_crypto_currency_validation.btc_symbol', 'tbl_crypto_currency_validation.rules', 'tbl_crypto_currency_validation.min_length','tbl_crypto_currency_validation.max_length', 'tbl_crypto_currency_validation.entry_time');

        $totalRecord = $query->count('tbl_crypto_currency_validation.id');
        $query = $query->orderBy('tbl_crypto_currency_validation.id', 'desc');
        // $totalRecord = $query->count();
        $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
        // dd($arrDirectInc);
        $arrData['recordsTotal'] = $totalRecord;
        $arrData['recordsFiltered'] = $totalRecord;
        $arrData['records'] = $arrDirectInc;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function selectedCurrencyData(Request $request)
    {

        $quesry = DB::table('tbl_crypto_currency_validation')
            ->select('*')
            ->where('id', $request->id)
            ->get();

        if (!empty($quesry)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Get data succesfully', $quesry);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    public function updateCurrencyValidation(Request $request)
    {
        //   return  $request->all();

        $data = array('btc_symbol' => $request->btc_symbol, 'rules' => $request->rules,'min_length' =>$request->min_length,'max_length' =>$request->max_length);
        $query = DB::table('tbl_crypto_currency_validation')
            ->where('id', $request->id)
            ->update($data);

        if (!empty($query)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data Updatedsuccesfully', '');
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
}
