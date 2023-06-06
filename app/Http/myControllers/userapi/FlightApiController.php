<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;

use App\Models\AddFaq;

use App\Models\Dashboard;

use App\Models\FaqSubCategory;
use App\Models\FlightBooking;
use App\Models\FlightBookingTempInfo;
use App\Models\FlightCityKeyword;
use App\Models\FlightName;

use App\Models\ProjectSetting;

use App\Models\TravelTransaction;

use App\User;

use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

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
		$this->statuscode       = Config::get('constants.statuscode');
		$this->OTP_interval     = Config::get('constants.settings.OTP_interval');
		$this->sms_username     = Config::get('constants.settings.sms_username');
		$this->sms_pwd          = Config::get('constants.settings.sms_pwd');
		$this->sms_route        = Config::get('constants.settings.sms_route');
		$this->senderId         = Config::get('constants.settings.senderId');
		$this->commonController = $commonController;
		$this->levelController  = $levelController;
	}

	public function getFlightCodeName() {

		$data = DB::table('tbl_flight_code')->select('*')->get();

		$intCode    = Response::HTTP_OK;
		$strMessage = 'Success';
		$strStatus  = Response::$statusTexts[$intCode];
		return sendResponse($intCode, $strStatus, $strMessage, $data);

	}
	public function getFlightLocSelf(Request $request) {

		try {
			$arrInput = $request->all();

			$data = DB::table('airports')->select('*')->orWhere('cityName', 'like', '%'.$arrInput['fromLoc'].'%')->orWhere('cityCode', 'like', '%'.$arrInput['fromLoc'].'%')->limit(10)->get();

			$intCode    = Response::HTTP_OK;
			$strMessage = 'Success';
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $data);

		} catch (Exception $e) {

		}

	}
	public function getFlightLoc(Request $request) {

		try {

			//dd(1);
			$arrInput = $request->all();
			if ($arrInput) {
				$check_data = FlightCityKeyword::select('keyword', 'description')->where('keyword', $arrInput['fromLoc'])->first();
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
							CURLOPT_URL            => $apiUrl."/v1/reference-data/locations?subType=AIRPORT&keyword=".$keyword."&page%5Blimit%5D=10&page%5Boffset%5D=0&sort=analytics.travelers.score&view=FULL",
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
								$storedata = FlightCityKeyword::insert(array('keyword' => $request->fromLoc, 'description' => $toJson));
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

	public function searchFlight(Request $request) {

		try {

			$arrInput = $request->all();

			$validator = Validator::make($arrInput, [
					'sdata' => 'required',
				]);
			// check for validation
			if ($validator->fails()) {
				return setValidationErrorMessage($validator);
			}

			callOneMore:

			$proSetting = ProjectSetting::select('flight_api_token')->first();
			$bearer     = $proSetting->flight_api_token;

			$apiUrl = Config::get('constants.settings.flight_api');
			//dd(2,$apiUrl);

			$curl = curl_init();

			curl_setopt_array($curl, array(
					CURLOPT_URL            => $apiUrl."shopping/availability/flight-availabilities",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING       => "",
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 30,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => "POST",
					CURLOPT_POSTFIELDS     => '{
                                "originDestinations": [
                                    {
                                        "id": "1",
                                        "originLocationCode": "'.$arrInput['sdata']['originDestinations'][0]['originLocationCode'].'",
                                        "destinationLocationCode": "'.$arrInput['sdata']['originDestinations'][0]['destinationLocationCode'].'",
                                        "departureDateTime": {
                                            "date": "'.$arrInput['sdata']['originDestinations'][0]['departureDateTime']['date'].'",
                                            "time": "21:15:00"
                                        }
                                    }
                                ],
                                "travelers": [
                                    {
                                        "id": "'.$arrInput['sdata']['travelers'][0]['id'].'",
                                        "travelerType": "ADULT"
                                    },
                                    {
                                        "id": "'.$arrInput['sdata']['travelers'][1]['id'].'",
                                        "travelerType": "CHILD"
                                    }
                                ],
                                "sources": [
                                    "GDS"
                                ]
                            }',
					CURLOPT_HTTPHEADER => array(
						"authorization: Bearer ".$bearer,
						"cache-control: no-cache",
						"content-type: application/json",
						"postman-token: e5a80e3a-579c-e0b4-bd18-a3cb67df76d5",
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
				if (isset($res->errors)) {
					if ((int) $res->errors[0]->code == 38192) {
						setFlightAipToken();
						gotocallOneMore;

					}
				}

				// dd($res);

				//	dd($res);
				$intCode    = Response::HTTP_OK;
				$strMessage = 'Success';
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, $res);
			}

		} catch (Exception $e) {
			dd($e);

		}

	}

	public function searchFlightOffers(Request $request) {

		try {

			$arrInput = $request->all();

			$validator = Validator::make($arrInput, [
					'fromLoc' => 'required',
					'toLoc'   => 'required',
					'fdate'   => 'required',
					'tClass'  => 'required',
					'child'   => 'required',
					'adult'   => 'required',
				]);

			// check for validation
			if ($validator->fails()) {
				return setValidationErrorMessage($validator);
			}

			callOneMore:

			$proSetting = ProjectSetting::select('flight_api_token')->first();
			$bearer     = $proSetting->flight_api_token;

			$apiUrl = Config::get('constants.settings.flight_api');
			//dd(2,$apiUrl);

			$curl = curl_init();

			/*$apiUrl."shopping/flight-offers?originLocationCode=".$arrInput['fromLoc']."&destinationLocationCode=".$arrInput['toLoc']."&departureDate=".$arrInput['fdate']."&adults=".$arrInput['adult']."&children=".$arrInput['child']."&nonStop=false&max=250&currencyCode=USD&travelClass=".$arrInput['tClass'],*/

			$apiUrl = $apiUrl."/v2/shopping/flight-offers?originLocationCode=".$arrInput['fromLoc']."&destinationLocationCode=".$arrInput['toLoc']."&departureDate=".$arrInput['fdate']."&adults=".$arrInput['adult']."&children=".$arrInput['child']."&nonStop=false&max=250&currencyCode=USD&travelClass=".$arrInput['tClass'];
			// dd($apiUrl);

			curl_setopt_array($curl, array(
					CURLOPT_URL            => $apiUrl,
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
				$intCode    = Response::HTTP_OK;
				$strMessage = 'Success';
				$strStatus  = Response::$statusTexts[$intCode];
				return sendResponse($intCode, $strStatus, $strMessage, $res);
			}

		} catch (Exception $e) {
			//	dd($e);

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

	public function flightCheckout(Request $request) {

		try {

			$arrInput = $request->all();

			//$arrRules = ['payment_mode' => 'required'];
			$arrRules = [
				'payment_mode' => 'required',
				'flightInfo'   => 'required',
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

			$orderData = FlightBookingTempInfo::select('*')->where('user_id', Auth::user()->id)->where('order_id', $arrInput['order_id'])->first();

			if (empty($orderData)) {

				$arrStatus  = Response::HTTP_NOT_FOUND;
				$arrCode    = Response::$statusTexts[$arrStatus];
				$arrMessage = 'Please Book Again ';
				return sendResponse($arrStatus, $arrCode, $arrMessage, '');

			}
			/*for ($i=0; $i < $orderData->adult ; $i++) {
			# code...
			}*/
			foreach ($arrInput['adultArr'] as $adult) {
				//  dd($adult['name']);

				if ($adult['name'] == null || $adult['name'] == '') {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'Please Enter All Adult Travelers Name ';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');

				}
				# code...
			}

			/*
			$temp_info= md5($request->header('User-Agent'));
			if($temp_info==Auth::User()->temp_info){
			//dd(true);

			}else{

			$strMessage = 'BAD REQUEST';
			$intCode = Response::HTTP_BAD_REQUEST;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, '');
			}
			 */
			if ($arrInput['child'] > 0) {

				foreach ($arrInput['childArr'] as $adult) {
					if ($adult['name'] == null || $adult['name'] == '') {

						$arrStatus  = Response::HTTP_NOT_FOUND;
						$arrCode    = Response::$statusTexts[$arrStatus];
						$arrMessage = 'Please Enter All Child Travelers Name ';
						return sendResponse($arrStatus, $arrCode, $arrMessage, '');

					}
				}

			}

			// dd($orderData->booking_data);

			// $flightInfo=json_decode($orderData->booking_data);
			$flightInfo = $orderData['booking_data'];

			$total_usd_price = (float) $flightInfo['price']['total'];
			//$total_usd_price =(float)$flightInfo->price->total;

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

			$Order    = new FlightBooking;
			$order_id = $this->getUniqueTxnId();

			$Order->user_id      = $id;
			$Order->order_id     = $order_id;
			$Order->adult        = $orderData->adult;
			$Order->payment_mode = $payment_method;
			$Order->total_usd    = $total_usd_price;
			$Order->total_coin   = $reqCoinBal;
			$Order->child        = $orderData->child;
			//$Order->updated_at=\Carbon\Carbon::now();
			$Order->travel_class = $orderData->travel_class;
			$Order->booking_data = $flightInfo;
			$Order->childArr     = $arrInput['childArr'];
			$Order->adultArr     = $arrInput['adultArr'];
			$Order->carriers     = $arrInput['carriers'];
			$Order->fimg         = $arrInput['fimg'];
			//$Order->travelers=$arrInput['travelers'];
			$Order->save();

			//dd(33);

			$trans     = new TravelTransaction;
			$trans->id = Auth::user()->id;
			if ($arrInput['payment_mode'] == 'purchase_wallet') {

				$trans->credit  = 0;
				$trans->debit   = $total_usd_price;
				$trans->coin    = 0;
				$trans->type    = 'Flight Booking';
				$trans->remarks = 'Flight Booking '.$order_id;

			} else {

				$trans->credit     = 0;
				$trans->debit_coin = $reqCoinBal;
				$trans->coin       = 1;
				$trans->type       = 'Flight Booking';
				$trans->remarks    = 'Flight Booking '.$order_id;
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

	public function saveFlightTempInfo(Request $request) {

		try {
			$arrInput = $request->all();//ghgf

			$id = Auth::user()->id;

			$arrRules  = ['flightInfo' => 'required', 'adult' => 'required', 'tClass' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err     = '';
				foreach ($message->all() as $error) {
					$err = $err." ".$error;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}

			$Order    = new FlightBookingTempInfo;
			$order_id = $this->getUniqueTxnId();

			$Order->user_id      = $id;
			$Order->order_id     = $order_id;
			$Order->travel_class = $arrInput['tClass'];
			$Order->adult        = $arrInput['adult'];
			$Order->child        = $arrInput['child'];
			$Order->fimg         = $arrInput['fimg'];
			$Order->booking_data = $arrInput['flightInfo'];
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

	public function getTempFlightUdata(Request $request) {

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

		$orderData = FlightBookingTempInfo::where('order_id', $arrInput['order_id'])->where('user_id', Auth::user()->id)->first();

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

	public function getFaqCategory() {
		//dd(getaddfaq);

		try
		{

			$users_list = FaqSubCategory::get();
			// $users_list = AddFaq::select('tbl_faq_questions.category_id', 'tcn.faq_category', 'tbl_faq_questions.faq_question','tbl_faq_questions.faq_answer','tbl_faq_questions.status')
			//    		->join('tbl_faq_category as tcn', 'tcn.category_id', '=', 'tbl_faq_questions.category_id')->where('tbl_faq_questions.status','=','Active')->groupBy('tbl_faq_questions.category_id')->limit(1);

			// $users_list = DB::table('tbl_faq_category')->select('*');
			// $users_list = DB::table('tbl_faq_questions')->select('*')->where('status','=','Active');
			// dd($users_list);
			// $users_list = $users_list->skip(0)->take(20)->get();
			$strMessage = "Success";

			$intCode   = Response::HTTP_OK;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $users_list);
		} catch (Exception $e) {
			// dd($e);
			$strMessage = "Something went wrong";
			$intCode    = Response::HTTP_UNAUTHORIZED;
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, '');
		}
	}

	public function getFaqCategoryQuestions(Request $request) {
		//dd(getaddfaq);

		try
		{
			$users_list = AddFaq::select('tbl_faq_questions.category_id', 'tcn.faq_category', 'tbl_faq_questions.faq_question', 'tbl_faq_questions.faq_answer', 'tbl_faq_questions.status')
				->join('tbl_faq_category as tcn', 'tcn.category_id', '=', 'tbl_faq_questions.category_id')	->where([['tbl_faq_questions.status', '=', 'Active'], ['tbl_faq_questions.category_id', '=', $request->faq_category]])->get();

			// $users_list = DB::table('tbl_faq_category')->select('*');
			// $users_list = DB::table('tbl_faq_questions')->select('*')->where('status','=','Active');
			// dd($users_list);
			// $users_list = $users_list->skip(0)->take(20)->get();
			$strMessage = "Success";

			$intCode   = Response::HTTP_OK;
			$strStatus = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $users_list);
		} catch (Exception $e) {
			// dd($e);
			$strMessage = "Something went wrong";
			$intCode    = Response::HTTP_UNAUTHORIZED;
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, '');
		}
	}

	/**
	 * Function to get the ordre history
	 *
	 * @param $request : HTTP Request Object
	 *
	 */
	public function orderFlightHistory(Request $request) {
		$arrData = [];
		try {
			$id         = Auth::user()->id;
			$arrWhere   = [['tbl_flight_booking.user_id', $id]];
			$cartOrders = FlightBooking::
			join('tbl_users as u', 'u.id', '=', 'tbl_flight_booking.id')
				->select('tbl_flight_booking.fimg', 'tbl_flight_booking.order_id', 'tbl_flight_booking.booking_data', 'u.email', 'u.mobile', 'tbl_flight_booking.adult', 'tbl_flight_booking.child', 'tbl_flight_booking.total_usd', 'tbl_flight_booking.total_coin', 'tbl_flight_booking.payment_mode', 'tbl_flight_booking.status', 'tbl_flight_booking.entry_time', 'tbl_flight_booking.adultArr', 'tbl_flight_booking.childArr', 'tbl_flight_booking.travel_class', 'tbl_flight_booking.remark', 'tbl_flight_booking.carriers')
				->where($arrWhere)
				->orderBy('tbl_flight_booking.id', 'desc')
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

	public function getFlightLogo() {

		try {

			$url = Config::get('constants.settings.aws_url');

			$query = FlightName::select('id', 'airline_name', 'IATA', DB::RAW('CONCAT("'.$url.'","",tbl_flight_name.logo) as logo'))
				->where('status', '=', 'Active')	->get();

			$intCode    = Response::HTTP_OK;
			$strMessage = 'Success';
			$strStatus  = Response::$statusTexts[$intCode];
			return sendResponse($intCode, $strStatus, $strMessage, $query);

		} catch (Exception $e) {

			$arrStatus  = Response::HTTP_INTERNAL_SERVER_ERROR;
			$arrCode    = Response::$statusTexts[$arrStatus];
			$arrMessage = 'Something went wrong,Please try again';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');

		}

	}

}
