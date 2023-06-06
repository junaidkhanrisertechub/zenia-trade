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
use App\Models\Depositaddress;
use App\Models\Otp as Otp;
use App\Models\PowerBV;
use App\Models\Representative;
use App\Models\LevelView;
use App\Models\UsersChangeData;
use App\Models\WithdrawPending;
use App\Models\FlightBooking;
use App\Models\HotelBooking;
use App\Models\FlightName;
use App\User;
use Hash;
use Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FlightApiController extends Controller {
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
	public function __construct(CommonController $commonController, LevelController $levelController) {
		$this->statuscode = Config::get('constants.statuscode');
		$this->OTP_interval = Config::get('constants.settings.OTP_interval');
		$this->sms_username = Config::get('constants.settings.sms_username');
		$this->sms_pwd = Config::get('constants.settings.sms_pwd');
		$this->sms_route = Config::get('constants.settings.sms_route');
		$this->senderId = Config::get('constants.settings.senderId');
		$this->commonController = $commonController;
		$this->levelController = $levelController;
	}

	public function getFlightLco(Request $request){


		try {

			$arrInput=$request->all();


			dd($arrInput);
			
		} catch (Exception $e) {
			$intCode = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = trans('admin.defaultexceptionmessage');
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $e);
		}
	}

	public function getFlightOrder(Request $request){
		try {
			$user = Auth::user();
			$arrInput = $request->all();
			$query = FlightBooking::select('tbl_flight_booking.id','tbl_flight_booking.order_id','tbl_flight_booking.booking_data','tbl_flight_booking.remark','tbl_flight_booking.status','tbl_flight_booking.entry_time','tbl_flight_booking.total_usd','tbl_flight_booking.total_coin','tbl_flight_booking.payment_mode','tbl_flight_booking.adult','tbl_flight_booking.child','tu.user_id','tu.mobile')->join('tbl_users as tu','tu.id','=', 'tbl_flight_booking.user_id');
			// DB::raw('JSON_UNQUOTE(JSON_EXTRACT(tbl_flight_booking.adultArr, "$[1][0]")) AS adultArr')
	        if((isset($arrInput['status'])) && (!empty($arrInput['status']))){
	        	$query = $query->where('tbl_flight_booking.status', $arrInput['status']);
	        }
			if((isset($arrInput['user_id'])) && (!empty($arrInput['user_id']))){
	            $query = $query->where('tu.user_id', $arrInput['user_id']);
	        }
	        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
	            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
	            $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
	            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_flight_booking.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
	        }
	        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
	            //searching loops on fields
	            $fields = getTableColumns('tbl_flight_booking');
	            $search = $arrInput['search']['value'];
	            $query = $query->where(function ($query) use ($fields, $search) {
	                foreach ($fields as $field) {
	                    $query->orWhere('tbl_flight_booking.' . $field, 'LIKE', '%' . $search . '%');
	                }
	                $query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
	            });
	        }
            // export
	        if (isset($request->action) && $request->action == 'export') {
                if($arrInput['status'] == 'pending'){
                    $qry = $query->select('tbl_flight_booking.order_id','tu.user_id','tu.mobile','tbl_flight_booking.total_usd','tbl_flight_booking.total_coin','tbl_flight_booking.payment_mode','tbl_flight_booking.status','tbl_flight_booking.entry_time as Date');
                }else{
                    $qry = $query->select('tbl_flight_booking.order_id','tu.user_id','tu.mobile','tbl_flight_booking.total_usd','tbl_flight_booking.total_coin','tbl_flight_booking.payment_mode','tbl_flight_booking.remark','tbl_flight_booking.status','tbl_flight_booking.entry_time as Date');
                }
	            $records = $qry->get();
	            $res = $records->toArray();
	            if (count($res) <= 0) {
	                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
	            }
	            $var = $this->commonController->exportToExcel($res,"AllUsers");
	            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
	        }

	        $query = $query->orderBy('tbl_flight_booking.id', 'desc');
	        // dd($query->toSql());
	        $totalRecord = $query->count();
	        if($totalRecord > 0){
		        $data = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		        $arrData['recordsTotal'] = $totalRecord;
		        $arrData['recordsFiltered'] = $totalRecord;
		        $arrData['records'] = $data;
			    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
	        } else {
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
	        }
		} catch (Exception $e) {
			$strMessage = "Something went wrong";
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$strMessage, []);
		}
	}

	public function approveBookingRequest(Request $request) {
        $arrInput = $request->all();
        $rules = array(
           // 'remark'            => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        }
        $getreq = FlightBooking::where('id',$arrInput['id'])->first();
        if(!empty($getreq))
        {      
        	$data=[];
            $data['status'] = 'confirm';
           // dd($arrInput['remark']);
            if(!empty($arrInput['remark'])){
            	$data['remark'] = $arrInput['remark'];
            }
            $data['updated_at'] = \Carbon\Carbon::now();
            $res=DB::table('tbl_flight_booking')->where('id',$arrInput['id'])->update($data);

        //dd($res);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record approved successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    public function rejectBookingRequest(Request $request) {
        $arrInput = $request->all();
        $rules = array(
            'remark'            => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        }
        $getreq = FlightBooking::where('id',$arrInput['id'])->first();
        if(!empty($getreq))
        {
            $getreq->status = 'cancel';
            if(!empty($request->remark)){
            	$getreq->remark = $request->remark;
            }
            $getreq->updated_at = \Carbon\Carbon::now();
            $getreq->update();

            //refund
            if($getreq['payment_mode'] == 'purchase_wallet'){
                Dashboard::where('id', $getreq['user_id'])->update(['top_up_wallet' => DB::raw('top_up_wallet + '. $getreq->total_usd)]);
             }elseif($getreq['payment_mode'] == 'coin_wallet'){
                 Dashboard::where('id', $getreq['user_id'])->update(['coin' => DB::raw('coin + '. $getreq->total_coin)]);
             }else{}

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record Rejected successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    public function viewBookingDetails(Request $request) {
        $arrInput = $request->all();
        $rules = array( 'order_id' => 'required', );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Order id required', $message);
        }

        $getreq = FlightBooking::select('tbl_flight_booking.travel_class','tbl_flight_booking.fimg','tbl_flight_booking.carriers','tbl_flight_booking.adultArr','tbl_flight_booking.childArr','tbl_flight_booking.booking_data')->where('id',$arrInput['order_id'])->first();
        if(!empty($getreq))
        {          
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found.', $getreq);
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'No Record found', '');
        }
    } 

 
    public function getFlightList(Request $request){

        $url    = Config::get('constants.settings.aws_url');

        try {
            $user = Auth::user();
            $arrInput = $request->all();
            $query = FlightName::select('*',DB::RAW('CONCAT("'.$url.'","",tbl_flight_name.logo) as logo'));
            // DB::raw('JSON_UNQUOTE(JSON_EXTRACT(tbl_flight_booking.adultArr, "$[1][0]")) AS adultArr')
           
            if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
              
                     $search = $arrInput['search']['value'];
                    $query->orWhere('airline_name', 'LIKE', '%' . $search . '%');
                    $query->orWhere('IATA', 'LIKE', '%' . $search . '%');
            
            }

            $query = $query->orderBy('tbl_flight_name.airline_name', 'asc');
            // dd($query->toSql());
            $totalRecord = $query->count();
            if($totalRecord > 0){
                $data = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

                $arrData['recordsTotal'] = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records'] = $data;
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } catch (Exception $e) {
            $strMessage = "Something went wrong";
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$strMessage, []);
        }
    }


 public function addFlightName(Request $request) {

        $arrInput = $request->all();
        $rules = array(
         'airline_name'            => 'required',
         'IATA'            => 'required',
        );
        // $validator = Validator::make($arrInput, $rules);
        // if ($validator->fails()) {
        //     $message = $validator->errors();
        //     return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        // }
        $getreq = FlightName::where('IATA',$arrInput['IATA'])->first();
        if(empty($getreq))
        {   
           
            $res=FlightName::insert($arrInput);

        //dd($res);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record approved successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record already  exist ', '');
        }
    }
 public function uploadFlightLogo(Request $request) {

        $arrInput = $request->all();
        $rules = array(
           // 'remark'            => 'required',
        );
        // $validator = Validator::make($arrInput, $rules);
        // if ($validator->fails()) {
        //     $message = $validator->errors();
        //     return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        // }
        $getreq = FlightName::where('id',$arrInput['id'])->first();
        if(!empty($getreq))
        {   

           $fileName   = null;
            if($request->hasFile('file')) {
                
                $file = $request->file('file');

                list($width, $height) = getimagesize($file);

                if($width==80 && $height==80){


                }else{

                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], ' image width: 80
                height: 80','');
                }
                

                $fileName = time() . '.' . $file->getClientOriginalExtension();

                $fileName = Storage::disk('s3')->put("flight-logo", $request->file('file'), "public");
            }

            $data=[];
            ///$data['status'] = 'confirm';
            $data['logo'] = $fileName;
           
            $res=FlightName::where('id',$arrInput['id'])->update($data);

        //dd($res);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record approved successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
    public function getHotelOrder(Request $request){

        $url    = Config::get('constants.settings.aws_url');

		try {
			$user = Auth::user();
			$arrInput = $request->all();
			$query = HotelBooking::select('tbl_hotel_booking.id','tbl_hotel_booking.order_id','tbl_hotel_booking.booking_data','tbl_hotel_booking.remark','tbl_hotel_booking.status','tbl_hotel_booking.entry_time','tbl_hotel_booking.total_usd','tbl_hotel_booking.total_coin','tbl_hotel_booking.payment_mode','tbl_hotel_booking.adult','tbl_hotel_booking.child','tbl_hotel_booking.remark','tu.user_id','tu.mobile',DB::RAW('CONCAT("'.$url.'","",tbl_hotel_booking.img) as image'))->join('tbl_users as tu','tu.id','=', 'tbl_hotel_booking.user_id');
			// DB::raw('JSON_UNQUOTE(JSON_EXTRACT(tbl_flight_booking.adultArr, "$[1][0]")) AS adultArr')
	        if((isset($arrInput['status'])) && (!empty($arrInput['status']))){
	        	$query = $query->where('tbl_hotel_booking.status', $arrInput['status']);
	        }
			if((isset($arrInput['user_id'])) && (!empty($arrInput['user_id']))){
	            $query = $query->where('tu.user_id', $arrInput['user_id']);
	        }
	        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
	            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
	            $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
	            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_hotel_booking.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
	        }
	        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
	            //searching loops on fields
	            $fields = getTableColumns('tbl_hotel_booking');
	            $search = $arrInput['search']['value'];
	            $query = $query->where(function ($query) use ($fields, $search) {
	                foreach ($fields as $field) {
	                    $query->orWhere('tbl_hotel_booking.' . $field, 'LIKE', '%' . $search . '%');
	                }
	                $query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
	            });
	        }

            // export
	        if (isset($request->action) && $request->action == 'export') {
                if($arrInput['status'] == 'pending'){
                    $qry = $query->select('tbl_hotel_booking.order_id','tu.user_id','tu.mobile','tbl_hotel_booking.total_usd','tbl_hotel_booking.total_coin','tbl_hotel_booking.payment_mode','tbl_hotel_booking.status','tbl_hotel_booking.entry_time as Date');
                }else{
                    $qry = $query->select('tbl_hotel_booking.order_id','tu.user_id','tu.mobile','tbl_hotel_booking.total_usd','tbl_hotel_booking.total_coin','tbl_hotel_booking.payment_mode','tbl_hotel_booking.remark','tbl_hotel_booking.status','tbl_hotel_booking.entry_time as Date');
                }
	            $records = $qry->get();
	            $res = $records->toArray();
	            if (count($res) <= 0) {
	                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
	            }
	            $var = $this->commonController->exportToExcel($res,"AllUsers");
	            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data'=>$var));
	        }

	        $query = $query->orderBy('tbl_hotel_booking.id', 'desc');
	        // dd($query->toSql());
	        $totalRecord = $query->count();
	        if($totalRecord > 0){
		        $data = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

		        $arrData['recordsTotal'] = $totalRecord;
		        $arrData['recordsFiltered'] = $totalRecord;
		        $arrData['records'] = $data;
			    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
	        } else {
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
	        }
		} catch (Exception $e) {
			$strMessage = "Something went wrong";
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$strMessage, []);
		}
	}

	public function viewHotelBookingDetails(Request $request) {
        $arrInput = $request->all();
        $rules = array( 'order_id' => 'required', );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Order id required', $message);
        }

        $getreq = HotelBooking::select('tbl_hotel_booking.travel_class','tbl_hotel_booking.adultArr','tbl_hotel_booking.childArr','tbl_hotel_booking.booking_data','tbl_hotel_booking.payment_mode','tbl_hotel_booking.total_usd','tbl_hotel_booking.checkIn','tbl_hotel_booking.checkOut')->where('id',$arrInput['order_id'])->first();
        if(!empty($getreq))
        {          
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found.', $getreq);
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'No Record found', '');
        }
    }

    public function approveHotelBookingRequest(Request $request) {

        $arrInput = $request->all();
        $rules = array(
           // 'remark'            => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        }
        $getreq = HotelBooking::where('id',$arrInput['id'])->first();
        if(!empty($getreq))
        {   

           $fileName   = null;
            if($request->hasFile('file')) {
                
                $file = $request->file('file');

                $fileName = time() . '.' . $file->getClientOriginalExtension();

                $fileName = Storage::disk('s3')->put("hotelbooking", $request->file('file'), "public");
            }

        	$data=[];
            $data['status'] = 'confirm';
            $data['img'] = $fileName;
           // dd($arrInput['remark']);
            if(!empty($arrInput['remark'])){
            	$data['remark'] = $arrInput['remark'];
            }
            $data['updated_at'] = \Carbon\Carbon::now();
            $res=DB::table('tbl_hotel_booking')->where('id',$arrInput['id'])->update($data);

        //dd($res);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record approved successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    public function rejectHotelBookingRequest(Request $request) {
        $arrInput = $request->all();
        $rules = array(
            'remark'            => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        }
        $getreq = HotelBooking::where('id',$arrInput['id'])->first();
        if(!empty($getreq))
        {
            $fileName   = null;
            if($request->hasFile('file')) {
                
                $file = $request->file('file');

                $fileName = time() . '.' . $file->getClientOriginalExtension();

                $fileName = Storage::disk('s3')->put("hotelbooking", $request->file('file'), "public");
            }

            $getreq->status = 'cancel';
            $getreq->img = $fileName;
            if(!empty($request->remark)){
            	$getreq->remark = $request->remark;
            }
            $getreq->updated_at = \Carbon\Carbon::now();
            $getreq->update();

            //refund
            if($getreq['payment_mode'] == 'purchase_wallet'){
                Dashboard::where('id', $getreq['user_id'])->update(['top_up_wallet' => DB::raw('top_up_wallet + '. $getreq->total_usd)]);
             }elseif($getreq['payment_mode'] == 'coin_wallet'){
                 Dashboard::where('id', $getreq['user_id'])->update(['coin' => DB::raw('coin + '. $getreq->total_coin)]);
             }else{}

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record Rejected successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }


}
