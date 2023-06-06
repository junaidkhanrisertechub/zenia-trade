<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;

use App\Models\Dashboard;

use App\Models\HotelBooking;
use App\Models\HotelBookingTempInfo;
use App\Models\HotelCityKeyword;

use App\Models\ProjectSetting;

use App\Models\TravelTransaction;

use App\User;

use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Facades\Validator;

class HotelApiController extends Controller {
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
		$this->statuscode       = Config::get('constants.statuscode');
		$this->OTP_interval     = Config::get('constants.settings.OTP_interval');
		$this->sms_username     = Config::get('constants.settings.sms_username');
		$this->sms_pwd          = Config::get('constants.settings.sms_pwd');
		$this->sms_route        = Config::get('constants.settings.sms_route');
		$this->senderId         = Config::get('constants.settings.senderId');
		$this->commonController = $commonController;
		$this->levelController  = $levelController;
	}

	public function getHotelLoc(Request $request) {

		try {

			//dd(1);
			$arrInput = $request->all();
			if ($arrInput) {
				$check_data = HotelCityKeyword::select('keyword', 'description')->where('keyword', $arrInput['fromLoc'])->first();
				$res        = array();
				if (!empty($check_data)) {
					//$res=json_decode($check_data->description);
					$res = $check_data['description'];
				} else {
					callOneMore:
					//setFlightAipToken();
					$proSetting = ProjectSetting::select('flight_api_token')->first();
					$bearer     = $proSetting->flight_api_token;
					//  dd(11,$bearer);
					$curl = curl_init();

					$keyword = $arrInput['fromLoc'];
					$apiUrl  = Config::get('constants.settings.flight_api');

					curl_setopt_array($curl, array(
							CURLOPT_URL            => $apiUrl."/v1/reference-data/locations?subType=CITY&keyword=".$keyword."&page%5Blimit%5D=10&page%5Boffset%5D=0&sort=analytics.travelers.score&view=FULL",
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING       => "",
							CURLOPT_MAXREDIRS      => 10,
							CURLOPT_TIMEOUT        => 30,
							CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST  => "GET",
							CURLOPT_HTTPHEADER     => array(
								"authorization: Bearer ".$bearer,
								"cache-control: no-cache",
								"postman-token: 953f06ed-d822-7c6d-6dd4-f861e4aa8230",
							),
						));

					$response = curl_exec($curl);
					$err      = curl_error($curl);

					curl_close($curl);
					//dd($response);
					if ($err) {echo "cURL Error #:".$err;} else {
						//echo $response;
						$res = json_decode($response);

						if (isset($res->errors)) {
							if ((int) $res->errors[0]->code == 38192) {

								setFlightAipToken();

								gotocallOneMore;

							}
						} else {
							$toJson = json_encode($res->data);
							if (!empty($toJson)) {
								$storedata = HotelCityKeyword::insert(array('keyword' => $request->fromLoc, 'description' => $toJson));
							}
						}
					}
					//dd($res);
				}

				$intCode    = Response::HTTP_OK;
				$strMessage = 'Success';
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, $res);

			}

			// dd($arrInput);

		} catch (Exception $e) {
			$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = trans('admin.defaultexceptionmessage');
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $e);
		}
	}

	public function curl_get_fun($url) {

		try {

			callOneMore:
			$proSetting = ProjectSetting::select('flight_api_token')->first();
			$bearer     = $proSetting->flight_api_token;

			//dd(2,$apiUrl);

			$curl = curl_init();

			curl_setopt_array($curl, array(
					CURLOPT_URL            => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING       => "",
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 30,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => "GET",
					CURLOPT_POSTFIELDS     => '',
					CURLOPT_HTTPHEADER     => array(
						"authorization: Bearer ".$bearer,
						"cache-control: no-cache",
						"content-type: application/json",

					),
				));

			$response = curl_exec($curl);
			$err      = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:".$err;
			} else {
				//echo $response;
				$res = json_decode($response);
				// dd($res);
				if (isset($res->errors)) {
					///if( (int)$res->errors[0]->code==38192){
					setFlightAipToken();
					gotocallOneMore;

					//	}
				}

				// dd($res);

				//	dd($res);
				//$intCode = Response::HTTP_OK;
				//$strMessage = 'Success';
				//$strStatus = Response::$statusTexts[$intCode];
				//return sendResponse($intCode, $strStatus, $strMessage, $res);
				return $res;
			}

		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_NOT_FOUND;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Please Enter All Adult Travelers Name ';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');

		}

	}

	public function searchHotelOffers(Request $request) {

		try {

			$arrInput = $request->all();

			$validator = Validator::make($arrInput, [
					'city'     => 'required',
					'rooms'    => 'required',
					'adult'    => 'required',
					'child'    => 'required',
					'checkIn'  => 'required',
					'checkOut' => 'required',

				]);

			$arrInput['ratings'] = 3;

			// check for validation
			if ($validator->fails()) {
				return setValidationErrorMessage($validator);
			}

			$curtimestamp1 = strtotime($arrInput['checkIn']);
			$curtimestamp2 = strtotime($arrInput['checkOut']);
			//   dd($curtimestamp1,$curtimestamp2);
			if ($curtimestamp1 < $curtimestamp2) {

				//dd('1 is grater ');

			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrMessage = 'Plz check checkOut Date. Alway select next to checkIn Date';
				$arrCode    = Response::$statusTexts[$arrStatus];
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}

			$apiUrl = Config::get('constants.settings.flight_api');

			// $apiUrl=$apiUrl."/v2/shopping/hotel-offers?cityCode=".$arrInput['city']."&roomQuantity=".$arrInput['rooms']."&adults=".$arrInput['adult']."&radius=25&radiusUnit=KM&ratings=".$arrInput['ratings']."&paymentPolicy=NONE&includeClosed=false&bestRateOnly=true&view=FULL&sort=NONE&currency=USD";

			// $apiUrl=$apiUrl."/v2/shopping/hotel-offers?cityCode=PAR&roomQuantity=".$arrInput['rooms']."&adults=2&radius=5&radiusUnit=KM&ratings=3&paymentPolicy=NONE&includeClosed=false&bestRateOnly=true&view=FULL&sort=NONE&currency=USD&view=FULL_ALL_IMAGES";

			$checkInDate  = $arrInput['checkIn'];
			$checkOutDate = $arrInput['checkOut'];
			//dd($checkInDate,$checkOutDate);

			$apiUrl = $apiUrl."/v2/shopping/hotel-offers?cityCode=".$arrInput['city']."&roomQuantity=".$arrInput['rooms']."&adults=".$arrInput['adult']."&radius=10&radiusUnit=KM&paymentPolicy=NONE&includeClosed=false&bestRateOnly=true&view=FULL&sort=NONE&currency=USD&view=FULL_ALL_IMAGES&checkInDate=".$checkInDate."&checkOutDate=".$checkOutDate;

			$res = $this->curl_get_fun($apiUrl);

			if (!empty($res)) {
				$arrStatus  = Response::HTTP_OK;
				$arrMessage = 'Date found';
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrMessage = 'Date Not found';
			}

			$arrCode = Response::$statusTexts[$arrStatus];
			return sendResponse($arrStatus, $arrCode, $arrMessage, $res);

		} catch (Exception $e) {

			//	dd($e);
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrMessage = 'Server Error';
			$arrCode    = Response::$statusTexts[$arrStatus];
			return sendResponse($arrStatus, $arrCode, $arrMessage, $res);

		}

	}

	public function searchHotelByID(Request $request) {

		try {

			$arrInput = $request->all();

			$validator = Validator::make($arrInput, [
					'hotelId'      => 'required',
					'roomQuantity' => 'required',
					'adults'       => 'required',

				]);

			// check for validation
			if ($validator->fails()) {
				//return setValidationErrorMessage($validator);
			}

			$
			$apiUrl = Config::get('constants.settings.flight_api');

			$apiUrl = $apiUrl."/v2/shopping/hotel-offers/by-hotel?hotelId=HIPARC12&adults=2&roomQuantity=1&paymentPolicy=NONE&view=FULL_ALL_IMAGES&currency=USD";

			$res = $this->curl_get_fun($apiUrl);
			if (!empty($res)) {
				$arrStatus  = Response::HTTP_OK;
				$arrMessage = 'Date found';
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrMessage = 'Date Not found';
			}

			$arrCode = Response::$statusTexts[$arrStatus];
			return sendResponse($arrStatus, $arrCode, $arrMessage, $res);

		} catch (Exception $e) {

			//	dd($e);
			$arrStatus  = Response::HTTP_OK;
			$arrMessage = 'Date found';
			$arrCode    = Response::$statusTexts[$arrStatus];
			return sendResponse($arrStatus, $arrCode, $arrMessage, $res);

		}

	}

	public function getUniqueTxnId() {
		return $randomUniqueTxnId = rand(1000000000, 9999999999);

		/*if(empty($randomUniqueTxnId)) {
	return $randomUniqueTxnId;
	}
	else {
	$this->getUniqueTxnId();
	}*/
	}

	public function hotelCheckout(Request $request) {

		try {

			$arrInput = $request->all();

			//$arrRules = ['payment_mode' => 'required'];
			$arrRules = [
				'payment_mode' => 'required',
				'hotelInof'    => 'required',
				//'adult' => 'required',
				//'adultList' => 'required',
				'order_id' => 'required',
				//'email' => 'required|email',
				//'mobile' => 'required',
			];
			$validator = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err     = '';
				foreach ($message->all() as $error) {
					$err = $err." ".$error;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}

			$orderData = HotelBookingTempInfo::select('*')->where('user_id', Auth::user()->id)->where('order_id', $arrInput['order_id'])->first();

			if (empty($orderData)) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Please Book Again ';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}

			/* $temp_info= md5($request->header('User-Agent'));
			if($temp_info==Auth::User()->temp_info){
			//dd(true);

			}else{

			$strMessage = 'BAD REQUEST';
			$intCode = Response::HTTP_BAD_REQUEST;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, '');
			}*/

			// $hoteInof=json_decode($orderData->booking_data);
			$hoteInof = $orderData['booking_data'];

			$total_usd_price = (float) $hoteInof['offers'][0]['price']['total'];

			// dd($total_usd_price);
			//$total_usd_price =(float)$hoteInof->price->total;

			$id = Auth::user()->id;
			if ($arrInput['payment_mode'] == 'purchase_wallet') {

				$payment_method = 'purchase_wallet';
				$payment_status = 'Success';
			} else {

				$payment_method = 'coin_wallet';
				$payment_status = 'Success';

			}

			$auth_user = User::select('tbl_dashboard.top_up_wallet', 'tbl_dashboard.top_up_wallet_withdraw', 'tbl_dashboard.coin', 'tbl_dashboard.coin_withdrawal')
				->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')
				->where([['tbl_users.id', '=', $id], ['tbl_users.status', '=', 'Active']])
				->first();

			$pro       = ProjectSetting::select('coin_rate')->where('id', '=', 1)->first();
			$coin_rate = $pro->coin_rate;

			$UserToupWallet = ($auth_user->top_up_wallet-$auth_user->top_up_wallet_withdraw);

			$userCoinWallet  = $auth_user->coin-$auth_user->coin_withdrawal;
			$reqCoinBal      = $total_usd_price/$coin_rate;
			$minusCoinWallet = $reqCoinBal;

			// /  dd(12 > 1  );

			if ($request->payment_mode == 'purchase_wallet') {

				if ($total_usd_price > $UserToupWallet) {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'You have insufficient balance in purchases Wallet';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

				$updateCoinData = array();

				$updateCoinData['top_up_wallet_withdraw'] = custom_round(($auth_user->top_up_wallet_withdraw+$total_usd_price), 7);
				//$updateCoinData['total_withdraw'] = custom_round(($auth_user->total_withdraw + $total_usd_price), 7);

				$updateCoinData = Dashboard::where('id', $id)->update($updateCoinData);

			} else {

				//dd($total_usd_price,$minusCoinWallet);
				if ($reqCoinBal >= $userCoinWallet) {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'You have insufficient balance in Coin Wallet';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

				//dd($total_usd_price,$UserToupWallet);
				$updateCoinData = array();

				$updateCoinData['coin_withdrawal'] = custom_round(($auth_user->coin_withdrawal+$minusCoinWallet), 7);

				$updateCoinData = Dashboard::where('id', $id)->update($updateCoinData);

			}

			//dd($orderData->adult);
			$orderStatus = 'Pending';

			$Order    = new HotelBooking;
			$order_id = $this->getUniqueTxnId();

			$Order->user_id      = $id;
			$Order->order_id     = $order_id;
			$Order->adult        = $orderData->adult;
			$Order->child        = $orderData->child;
			$Order->payment_mode = $payment_method;
			$Order->total_usd    = $total_usd_price;
			$Order->total_coin   = $reqCoinBal;
			$Order->checkIn      = $orderData->checkIn;
			$Order->checkOut     = $orderData->checkOut;
			//$Order->updated_at=\Carbon\Carbon::now();
			//$Order->travel_class=$orderData->travel_class;
			$Order->booking_data = $hoteInof;
			//$Order->childArr=$arrInput['childArr'];
			//$Order->adultArr=$arrInput['adultArr'];
			//$Order->travelers=$arrInput['travelers'];
			$Order->save();

			//dd(33);

			$trans     = new TravelTransaction;
			$trans->id = $id;
			if ($arrInput['payment_mode'] == 'purchase_wallet') {

				$trans->credit  = 0;
				$trans->debit   = $total_usd_price;
				$trans->coin    = 0;
				$trans->type    = 'Hotel Booking';
				$trans->remarks = 'Hotel Booking '.$order_id;

			} else {

				$trans->credit     = 0;
				$trans->debit_coin = $reqCoinBal;
				$trans->coin       = 1;
				$trans->type       = 'Hotel Booking';
				$trans->remarks    = 'Hotel Booking '.$order_id;
			}
			// $network_type->updated_at=\Carbon\Carbon::now();
			$trans->network_type     = $arrInput['payment_mode'];
			$trans->refference       = $order_id;
			$trans->transaction_date = \Carbon\Carbon::now();
			$trans->status           = 1;

			$trans->save();

			$intCode    = Response::HTTP_OK;
			$strMessage = 'Successfully Submitted Your Booking ';
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, '');

			//dd($arrInput);

		} catch (Exception $e) {

			$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = 'Success';
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, '');

		}

	}

	public function saveHotelTempInfo(Request $request) {

		try {
			$arrInput = $request->all();//ghgf

			$id = Auth::user()->id;

			$arrRules = [
				//'hotelInfo' => 'required',
				'adult'    => 'required',
				'child'    => 'required',
				'checkIn'  => 'required',
				'checkOut' => 'required',
				'rooms'    => 'required',
			];
			$validator = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err     = '';
				foreach ($message->all() as $error) {
					$err = $err." ".$error;
				}
				// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}

			$hotelId = $arrInput['hotelInfo']['hotel']['hotelId'];

			$checkIn  = $arrInput['hotelInfo']['offers'][0]['checkInDate'];
			$checkOut = $arrInput['hotelInfo']['offers'][0]['checkOutDate'];

			// dd($checkIn,$checkOut);

			$apiUrl = Config::get('constants.settings.flight_api');

			$apiUrl = $apiUrl."/v2/shopping/hotel-offers/by-hotel?hotelId=".$hotelId."&view=FULL_ALL_IMAGES";

			$res = $this->curl_get_fun($apiUrl);

			// dd($res,$arrInput['hotelInfo']['hotel']['hotelId']);
			if (isset($res->data->hotel->media)) {

				$arrInput['hotelInfo']['hotel']['more_imgs'] = $res->data->hotel->media;

			} else {
				$arrInput['hotelInfo']['hotel']['more_imgs'] = [];

			}

			$Order    = new HotelBookingTempInfo;
			$order_id = $this->getUniqueTxnId();

			$Order->user_id      = $id;
			$Order->order_id     = $order_id;
			$Order->rooms        = $arrInput['rooms'];
			$Order->checkIn      = $checkIn;
			$Order->checkOut     = $checkOut;
			$Order->adult        = $arrInput['adult'];
			$Order->child        = $arrInput['child'];
			$Order->booking_data = $arrInput['hotelInfo'];
			//$Order->booking_data=json_encode($arrInput['flightInfo']);
			//$Order->travelers=$arrInput['travelers'];
			$Order->save();

			$intCode    = Response::HTTP_OK;
			$strMessage = 'Success';
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $order_id);

			//FlightBookingTempInfo

		} catch (Exception $e) {
			$intCode    = Response::HTTP_INTERNAL_SERVER_ERROR;
			$strMessage = 'Success';
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, '');

		}

	}

	public function getTempHoteldata(Request $request) {

		$arrInput = $request->all();

		$arrRules  = ['order_id' => 'required'];
		$validator = Validator::make($arrInput, $arrRules);
		if ($validator->fails()) {
			$message = $validator->errors();
			$err     = '';
			foreach ($message->all() as $error) {
				$err = $err." ".$error;
			}
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		}

		$orderData = HotelBookingTempInfo::where('order_id', $arrInput['order_id'])->where('user_id', Auth::user()->id)->first();

		//dd($orderData);

		if ($orderData != null) {
			//dd(1,json_decode($orderData->booking_data),$orderData->booking_data);

			$pro                  = ProjectSetting::select('coin_rate')->where('id', '=', 1)->first();
			$orderData->coin_rate = $pro->coin_rate;
			//$orderData->booking_data=json_decode($orderData->booking_data);
			$strMessage = 'Success';
		} else {
			$strMessage = 'Data Not Found';

		}
		$intCode   = Response::HTTP_OK;
		$strStatus = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, $strMessage, $orderData);

	}

	/**
	 * Function to get the ordre history
	 *
	 * @param $request : HTTP Request Object
	 *
	 */
	public function orderHotelHistory(Request $request) {
		$arrData = [];
		try {
			$id         = Auth::user()->id;
			$arrWhere   = [['tbl_hotel_booking.user_id', $id]];
			$cartOrders = HotelBooking::
			join('tbl_users as u', 'u.id', '=', 'tbl_hotel_booking.id')
				->select('tbl_hotel_booking.order_id', 'tbl_hotel_booking.booking_data', 'u.email', 'u.mobile', 'tbl_hotel_booking.adult', 'tbl_hotel_booking.child', 'tbl_hotel_booking.total_usd', 'tbl_hotel_booking.total_coin', 'tbl_hotel_booking.payment_mode', 'tbl_hotel_booking.status', 'tbl_hotel_booking.entry_time', 'tbl_hotel_booking.checkOut', 'tbl_hotel_booking.checkIn', 'tbl_hotel_booking.travel_class', 'tbl_hotel_booking.remark', 'tbl_hotel_booking.img')
				->where($arrWhere)
				->orderBy('tbl_hotel_booking.id', 'desc')
				->get();

			//dd($cartOrders[0]);
			/*
			foreach ($cartOrders as $order) {

			$order->childArr=json_decode($order->childArr);
			$order->adultArr=json_decode($order->adultArr);
			$order->booking_data=json_decode($order->booking_data);
			# code...
			//dd($order->booking_data);
			}*/
			if (count($cartOrders) > 0) {
				$arrData['records'] = $cartOrders;
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arrData);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', $arrData);
			}
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', $arrData);
		}
	}

	public function travelTransactionInof(Request $request) {
		try {
			$arrInput    = $request->all();
			$UserExistid = Auth::User()->id;

			if (!empty($UserExistid)) {

				$query = TravelTransaction::select('tbl_travel_transaction.*', 'tu.user_id')
					->join('tbl_users as tu', 'tu.id', '=', 'tbl_travel_transaction.id')
					->where([['tbl_travel_transaction.id', '=', $UserExistid]])
					->orderBy('tbl_travel_transaction.entry_time', 'desc');

				if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
					//searching loops on fields
					$fields = getTableColumns('tbl_travel_transaction');
					$search = $arrInput['search']['value'];
					$query  = $query->where(function ($query) use ($fields, $search) {
							foreach ($fields as $field) {
								$query->orWhere('tbl_travel_transaction.'.$field, 'LIKE', '%'.$search.'%');
							}
							$query->orWhere('tu.user_id', 'LIKE', '%'.$search.'%');
						});
				}

				$query        = $query->orderBy('tbl_travel_transaction.entry_time', 'desc');
				$totalRecord  = $query->get()->count();
				$arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

				$arrData['recordsTotal']    = $totalRecord;
				$arrData['recordsFiltered'] = $totalRecord;
				$arrData['records']         = $arrDirectInc;

				if (!empty($arrDirectInc) && count($arrDirectInc) > 0) {
					$arrStatus  = Response::HTTP_OK;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Withdraw pending data found successfully';
					return sendResponse($arrStatus, $arrCode, $arrMessage, $arrData);
				} else {
					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Withdraw pending data not found';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}
			} else {
				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Invalid user';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}
		} catch (Exception $e) {
			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
		}
	}

	public function tempData() {

		$res = '{
    "data": [
        {
            "type": "hotel-offers",
            "hotel": {
                "type": "hotel",
                "hotelId": "HIPARC12",
                "chainCode": "HI",
                "dupeId": "700132326",
                "name": "HOLIDAY INN PARIS-NOTRE DAME123",
                "rating": "3",
                "cityCode": "PAR",
                "latitude": 48.85254,
                "longitude": 2.34198,
                "hotelDistance": {
                    "distance": 0.3,
                    "distanceUnit": "KM"
                },
                "address": {
                    "lines": [
                        "4 RUE DANTON"
                    ],
                    "postalCode": "75006",
                    "cityName": "PARIS",
                    "countryCode": "FR"
                },
                "contact": {
                    "phone": "+33 1 81690060",
                    "fax": "+33 1 81690061"
                },
                "description": {
                    "lang": "en",
                    "text": "This boutique style eco hotel is in the heart of Paris 5 minutes walk from Notre Dame Cathedral and a short stroll from the Louvre museum. Regular RER trains run to Paris Charles de Gaulle Airport and Paris Orly Airport in 30 minutes. It is a 5 minute walk to Saint Michel Notre Dame Metro station for links to company offices on Avenue de France and events at Paris Nord Villepinte Exhibition and Convention Centre. There are 3 air conditioned meeting rooms with wireless Internet which can accommodate up to 55 of your colleagues. You can round off your meeting with cocktails on the roof terrace with panoramic views of Paris. Internet is available 24 hours a day on the 2 computers in the Lobby and there is high speed Internet in all Guest rooms."
                },
                "amenities": [
                    "24_HOUR_FRONT_DESK",
                    "24_HOUR_ROOM_SERVICE",
                    "ATM/CASH_MACHINE",
                    "CONFERENCE_FACILITIES",
                    "EXCHANGE_FACILITIES",
                    "EXPRESS_CHECK_IN",
                    "EXPRESS_CHECK_OUT",
                    "ACCESSIBLE_FACILITIES",
                    "WHEELCHAIR_ACCESSIBLE_PUBLIC_AREA",
                    "WHEELCHAIR_ACCESSIBLE_ELEVATORS",
                    "WHEELCHAIR_ACCESSIBLE_ROOM",
                    "GARAGE_PARKING",
                    "LAUNDRY_SERVICE",
                    "RESTAURANT",
                    "SAFE_DEPOSIT_BOX",
                    "DRY_CLEANING",
                    "FRONT_DESK",
                    "WIRELESS_CONNECTIVITY",
                    "HIGH_SPEED_WIRELESS",
                    "FEMA_FIRE_SAFETY_COMPLIANT",
                    "PHOTOCOPIER",
                    "PRINTER",
                    "AUDIO-VISUAL_EQUIPMENT",
                    "BUSINESS_CENTER",
                    "COMPUTER_RENTAL",
                    "LCD/PROJECTOR",
                    "CONFERENCE_SUITE",
                    "CONVENTION_CENTRE",
                    "MEETING_FACILITIES",
                    "FIRE_SAFETY",
                    "EMERGENCY_LIGHTING",
                    "FIRE_DETECTORS",
                    "SPRINKLERS",
                    "FIRST_AID_STAF",
                    "SECURITY_GUARD",
                    "VIDEO_SURVEILANCE",
                    "EXTINGUISHERS",
                    "FEMA_FIRE_SAFETY_COMPLIANT"
                ],
                "media": [
                    {
                        "uri": "http://uat.multimediarepository.testing.amadeus.com/cmr/retrieve/hotel/39A9137DCEC149B59898A0598BE2C76A",
                        "category": "EXTERIOR"
                    }
                ]
            },
            "available": true,
            "offers": [
                {
                    "id": "YAMKPFKZ2O",
                    "checkInDate": "2021-06-01",
                    "checkOutDate": "2021-06-02",
                    "rateCode": "22A",
                    "rateFamilyEstimated": {
                        "code": "BAR",
                        "type": "P"
                    },
                    "boardType": "ROOM_ONLY",
                    "room": {
                        "type": "*RH",
                        "typeEstimated": {
                            "category": "STANDARD_ROOM"
                        },
                        "description": {
                            "text": "BEST FLEXIBLE RATE\nSTANDARD ROOM NONSMOKING RELAX IN A\nCONTEMPORARY DESIGNED ROOM. WE WILL DO OUR BEST"
                        }
                    },
                    "guests": {
                        "adults": 2
                    },
                    "price": {
                        "currency": "EUR",
                        "base": "399.00",
                        "total": "404.76",
                        "variations": {
                            "average": {
                                "base": "399.00"
                            },
                            "changes": [
                                {
                                    "startDate": "2021-06-01",
                                    "endDate": "2021-06-02",
                                    "base": "399.00"
                                }
                            ]
                        }
                    },
                    "policies": {
                        "holdTime": {
                            "deadline": "2021-06-01T16:00:00"
                        },
                        "guarantee": {
                            "acceptedPayments": {
                                "creditCards": [
                                    "AX",
                                    "VI",
                                    "CA"
                                ],
                                "methods": [
                                    "CREDIT_CARD"
                                ]
                            }
                        },
                        "paymentType": "guarantee",
                        "cancellation": {
                            "numberOfNights": 1,
                            "deadline": "2021-06-01T16:00:00+02:00"
                        }
                    }
                }
            ],
            "self": "https://test.api.amadeus.com/v2/shopping/hotel-offers/by-hotel?hotelId=HIPARC12&adults=2&currency=USD&paymentPolicy=NONE&roomQuantity=1&view=FULL"
        },

    ],
    "meta": {
        "links": {
            "next": "https://test.api.amadeus.com/v2/shopping/hotel-offers?adults=2&bestRateOnly=true&cityCode=PAR&currency=USD&includeClosed=false&paymentPolicy=NONE&radius=5&radiusUnit=KM&ratings=3&roomQuantity=1&sort=NONE&view=FULL&page[offset]=1FB2HLA9H6XZ_100"
        }
    },
    "dictionaries": {
        "currencyConversionLookupRates": {
            "EUR": {
                "rate": "1.2193609999999999",
                "target": "USD",
                "targetDecimalPlaces": 2
            }
        }
    }
}';
		//dd(json_encode($res));

		$arrStatus  = Response::HTTP_OK;
		$arrCode    = Response::$statusTexts[$arrStatus];
		$arrMessage = 'Withdraw pending data found successfully';
		return sendResponse($arrStatus, $arrCode, $arrMessage, $res);

	}

}
