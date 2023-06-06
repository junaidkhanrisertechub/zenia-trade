<?php

namespace App\Http\Controllers\userapi;

use App\Http\Controllers\Controller;
use App\Models\BillingInfo;
use App\Models\Dashboard;
use App\Models\EcommerceProduct;
use App\Models\Otp;

use App\Models\ShippingAddress;
use App\Models\UserCartOrder;
use App\Models\UserCartProduct;
use App\Models\VoucherTransaction;
use App\User;
use Auth;
use Config;
use DB;
use Exception;

use Illuminate\Http\Request;
use Illuminate\Http\Response as Response;
use Illuminate\Support\Facades\Hash;

use URL;
use Validator;

class CheckoutController extends Controller {

	public function __construct() {
		$this->statuscode = Config::get('constants.statuscode');
		$date             = \Carbon\Carbon::now();
		$this->today      = $date->toDateTimeString();
	}

	/**
	 * Function for checkout
	 *
	 * @param $request : HTTP Request
	 *
	 */
	public function cartCheckout(Request $request) {
		try
		{
			$id       = Auth::user()->id;
			$arrInput = $request->all();
			//$arrRules = ['payment_mode' => 'required'];
			$arrRules  = ['payment_mode' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err     = '';
				foreach ($message->all() as $error) {
					$err = $err." ".$error;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}

			//$pro=ProjectSetting::select('coin_rate','woz_voucher_buy_coin')->where('id','=',1)->first();
			//$coin_rate=$pro->coin_rate;
			///$this.checkCouponValid($request);
			/*if($request->payment_mode=='purchase_wallet'){

			}else{
			if($pro->woz_voucher_buy_coin=='off'){

			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'coin is coming soon !!  ', '');

			}

			}*/

			$today      = date('Y-m-d', strtotime($this->today));
			$orderCount = UserCartOrder::where('user_id', $id)->where('entry_time', 'LIKE', "%$today%")->count('id');
			if ($orderCount >= 1) {
				//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You are allowed to place only one order per day', '');
			}

			$arrCartAddress = [['user_id', $id], ['status', 'Pending'], ['order_id', 0]];

			$cartCount = UserCartProduct::where($arrCartAddress)->count('product_id');

			if ($cartCount != 1) {
				// return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one voucher type allowed to use per day', '');
			}
			$usercountry = Auth::User()->country;
			$country     = DB::table('tbl_country_new')->where([['iso_code', '=', $usercountry], ['avoid_con', 1]])->first();
			if ($country != null) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '', '');
			}

			//dd(22);
			$arrCartTotalWhere = [['user_id', $id], ['status', 'Pending'], ['order_id', 0]];
			$query             = UserCartProduct::where($arrCartTotalWhere);
			$totalDiscount     = $query->sum('discount');
			$totalPrice        = $query->sum('total_price');
			$fquery            = $query->first();
			//dd($fquery);
			$coupon_amount = $fquery->coupon_amount;
			$coupon_code   = $fquery->coupon_code;
			$billing_id    = $fquery->billing_id;
			$shipping_id   = $fquery->shipping_id;

			$total_usd_price = 0;
			$allCart         = UserCartProduct::where($arrCartTotalWhere)->get();

			//dd($allCart);
			foreach ($allCart as $cart) {

				$usdRate = $this->getUsdRate($cart->product_id);
				$tempUsd = $cart->total_price/$usdRate;
				//dd($cart->total_price,$usdRate,$total_usd_price);

				if ($request->payment_mode == 'purchase_wallet') {
					$total_usd_price = $total_usd_price+$tempUsd;
					UserCartProduct::where('id', $cart->id)->update(array('sub_total_usd' => $tempUsd, 'total_usd' => $total_usd_price));
				} else {

					$total_usd_price = $total_usd_price+$tempUsd;
					UserCartProduct::where('id', $cart->id)->update(array('sub_total_usd' => $tempUsd, 'total_usd' => $total_usd_price));

					/*$total_usd_price=$total_usd_price +$tempUsd;
				$temp=$tempUsd/$coin_rate;
				$reqCoinBal=$total_usd_price/$coin_rate;
				UserCartProduct::where('id',$cart->id)->update(array('sub_total_coin'=>$temp,'total_coin'=>$reqCoinBal));*/
				}

				# code...
			}

			//   dd($total_usd_price);

			//dd($total_usd_price);
			/*$billingData = BillingInfo::select('*')
			->where('id', $billing_id)
			->first();*/
			$city = '';
			//$billingData->city;
			$fullname = '';
			//$billingData->fname . ' ' . $billingData->lname;
			$mobile = '';
			//$billingData->phone;
			$email = '';
			//$billingData->email;
			$country = '';
			//$billingData->country;
			$address = '';
			//$billingData->address;
			$state = '';
			//$billingData->state;
			$pincode = '';
			///$billingData->zipcode;

			// Check epin available or not
			$arrWhere = [['tbl_user_cart_product.status', 'Pending'], ['tp.status_product', 'Active'], ['user_id', $id], ['order_id', 0]];
			$userCart = UserCartProduct::selectRaw('tp.name, tp.cost,tbl_user_cart_product.quantity, tbl_user_cart_product.price, tbl_user_cart_product.variation_id,tbl_user_cart_product.total_price,product_id,tbl_user_cart_product.id,tbl_user_cart_product.order_id,tp.id as product_id')
				->join('tbl_product_ecommerce as tp', 'tp.id', '=', 'tbl_user_cart_product.product_id')
				->where($arrWhere)
				->get();

			$auth_user = User::select('tbl_dashboard.working_wallet_withdraw', 'tbl_dashboard.working_wallet', 'tbl_dashboard.top_up_wallet', 'tbl_dashboard.top_up_wallet_withdraw', 'tbl_dashboard.coin', 'tbl_dashboard.coin_withdrawal')
				->join('tbl_dashboard', 'tbl_dashboard.id', '=', 'tbl_users.id')
				->where([['tbl_users.id', '=', $id], ['tbl_users.status', '=', 'Active']])
				->first();

			$UserToupWallet = ($auth_user->top_up_wallet-$auth_user->top_up_wallet_withdraw);

			$minusCoinWallet = 0;

			// /  dd(12 > 1  );

			if ($request->payment_mode == 'purchase_wallet') {

				if ($total_usd_price > $UserToupWallet) {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'You have insufficient balance in purchases Wallet';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

				$updateCoinData                           = array();
				$updateCoinData['usd']                    = custom_round(($auth_user->usd-$total_usd_price), 7);
				$updateCoinData['top_up_wallet_withdraw'] = custom_round(($auth_user->top_up_wallet_withdraw+$total_usd_price), 7);
				//$updateCoinData['total_withdraw'] = custom_round(($auth_user->total_withdraw + $total_usd_price), 7);

				$updateCoinData = Dashboard::where('id', $id)->update($updateCoinData);

			} else {
				//--  Wallet

				$WalletBal = $auth_user->working_wallet-$auth_user->working_wallet_withdraw;

				//  dd($auth_user);
				// dd($total_usd_price,$WalletBal);
				if ($total_usd_price > $DEXDWalletBal) {

					$arrStatus  = Response::HTTP_NOT_FOUND;
					$arrCode    = Response::$statusTexts[$arrStatus];
					$arrMessage = 'You have insufficient balance in  Wallet';
					return sendResponse($arrStatus, $arrCode, $arrMessage, '');
				}

				$updateCoinData = array();

				$updateCoinData['working_wallet'] = custom_round(($auth_user->working_wallet+$total_usd_price), 7);
				$updateCoinData                   = Dashboard::where('id', $id)->update($updateCoinData);

			}
			/*else{

			$userCoinWallet=$auth_user->coin-$auth_user->coin_withdrawal;
			$reqCoinBal=$total_usd_price/$coin_rate;
			$minusCoinWallet=$reqCoinBal;

			//dd($total_usd_price,$minusCoinWallet);
			if ($reqCoinBal  > $userCoinWallet) {

			$arrStatus = Response::HTTP_NOT_FOUND;
			$arrCode = Response::$statusTexts[$arrStatus];
			$arrMessage = 'You have insufficient balance in Coin Wallet';
			return sendResponse($arrStatus, $arrCode, $arrMessage, '');
			}

			$updateCoinData = array();

			$updateCoinData['coin_withdrawal'] = custom_round(($auth_user->coin_withdrawal + $minusCoinWallet), 7);



			$updateCoinData = Dashboard::where('id',  Auth::user()->id)->update($updateCoinData);

			}*/

			//$total_price_in_usd=
			if (count($userCart) <= 0) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Cart is empty', '');
			}

			foreach ($userCart as $cart) {
				$arrProductWhere = [['tbl_product_ecommerce.id', $cart->product_id], ['tbl_product_ecommerce.status_product', 'Active'], ['tpv.id', $cart->variation_id]];
				$product         = EcommerceProduct::select('tbl_product_ecommerce.*', 'tpv.variation_price as cost')->join('tbl_product_variation_details as tpv', 'tpv.product_id', '=', 'tbl_product_ecommerce.id')->where($arrProductWhere)->first();

				$product_pins = $product->qty-$product->qty_minus;
				/*Pins::where([['product_id',$cart->product_id],['status','Active']])->count();
				 */

				$qty_minus                 = $product->qty_minus;
				$arrOutput                 = array();
				$arrOutput['product_id']   = $cart->product_id;
				$arrOutput['product_name'] = $cart->name;
				$arrOutput['stock']        = $product_pins;

				//if(!empty($product_pins) || $product_pins > 0){
				if ($product_pins >= $cart->quantity) {
					//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient stock', $arrOutput);
					EcommerceProduct::where('id', '=', $cart->product_id)
						->update(array('qty_minus' => $qty_minus+$cart->quantity));

				}
				//}
				 else {
					///return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product out of stock', $arrOutput);
				}

			}

			$order_id = $this->getOrderId();

			$temp_order_id = $order_id;

			if ($arrInput['payment_mode'] == 'purchase_wallet') {

				$payment_method      = 'purchase_wallet';
				$payment_method_type = 'purchase_wallet';
				$payment_status      = 'Success';
			} else {

				$payment_method      = 'wallet';
				$payment_method_type = 'wallet';
				$payment_status      = 'Success';

				/*$payment_method='coin_wallet';
			$payment_method_type='coin_wallet';
			$payment_status='Success';*/

			}

			//dd($arrInput['payment_mode'],$payment_method);
			$orderStatus = 'Pending';

			$cartOrder = new UserCartOrder;
			$txnId     = $this->getUniqueTxnId();

			$cartOrder->user_id  = $id;
			$cartOrder->order_id = $order_id;
			// $cartOrder->total_amount = $totalPrice;
			$cartOrder->total_amount = ($totalPrice);
			$cartOrder->total_usd    = $total_usd_price;
			$cartOrder->total_coin   = $minusCoinWallet;
			//$cartOrder->total_price = ($totalPrice - $totalDiscount);
			$cartOrder->total_price         = ($totalPrice);
			$cartOrder->status              = $orderStatus;
			$cartOrder->delivery_status     = 'Pending';
			$cartOrder->payment_status      = $payment_status;
			$cartOrder->payment_method_type = $payment_method_type;
			$cartOrder->payment_method      = $payment_method;
			$cartOrder->entry_time          = $this->today;
			$cartOrder->txnid               = $txnId;
			$cartOrder->payment_mode        = $arrInput['payment_mode'];
			$cartOrder->discount            = $totalDiscount;
			$cartOrder->fullname            = $fullname;
			$cartOrder->address             = $address;
			$cartOrder->country             = $country;
			$cartOrder->state               = $state;
			$cartOrder->city                = $city;
			$cartOrder->pin                 = $pincode;
			$cartOrder->mobile              = $mobile;
			$cartOrder->email               = $email;
			$cartOrder->coupon_amount       = $coupon_amount;
			$cartOrder->coupon_code         = $coupon_code;
			$cartOrder->billing_id          = $billing_id;
			$cartOrder->shipping_id         = $shipping_id;
			if (isset($order_note)) {
				$cartOrder->notes = $arrInput['order_note'];
			}
			$cartOrder->save();
			//dd($cartOrder);
			//dd(11,$arrInput);

			$orderId     = $cartOrder->id;
			$orderStatus = 'Pending';
			foreach ($userCart as $cart) {
				/*$pindata = Pins::where([['product_id',$cart->product_id],['status','Active']])->first();*/

				$query           = UserCartProduct::find($cart->id);
				$query->order_id = $orderId;
				/*$query->pin      =  $pindata->pin;*/
				$query->status = 'Confirm';
				$query->save();

				/*$update_status_pin = Pins::where('id',$pindata->id)->update(['status'=>'Inactive','used_by'=>$user->id]);*/

			}

			$trans     = new VoucherTransaction;
			$trans->id = $id;
			// if($arrInput['payment_mode']=='purchase_wallet'){

			$trans->credit = 0;
			$trans->debit  = $total_usd_price;
			$trans->coin   = 0;

			//}
			/*else{

			$trans->credit = 0;
			$trans->debit_coin =$minusCoinWallet;
			$trans->coin =1;
			}*/

			$trans->network_type     = $arrInput['payment_mode'];
			$trans->refference       = $temp_order_id;
			$trans->transaction_date = \Carbon\Carbon::now();
			$trans->status           = 1;
			$trans->type             = ' Buy Voucher';
			$trans->remarks          = 'Buy Voucher orderId'.$temp_order_id;
			$trans->save();

			// $cartOrder->payment_mode = $arrInput['payment_mode'];
			// $cartOrder->status = 'Confirm';
			if ($arrInput['payment_mode'] == 'juspay') {

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '', $order);
			} else {
				$strMessage = "Order confirmed successfully";
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $strMessage, $cartOrder);
			}

		} catch (Exception $e) {
			dd($e);
			$strMessage = "Error in processing your order";
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $strMessage, []);
		}
	}

	public function getUsdRate($product_id) {

		$pro = EcommerceProduct::select('currency.usd_to_other_curr')
			->where('tbl_product_ecommerce.id', $product_id)
			->join('country_wize_currency as currency', 'currency.code', '=', 'tbl_product_ecommerce.country_id')	->first();

		return (float) $pro->usd_to_other_curr;
	}

	public function paymentResponse(Request $request) {
		try {
			$user      = Auth::user();
			$arrInput  = $request->all();
			$arrRules  = ['status' => 'required', 'order_id' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
			if ($validator->fails()) {
				$message = $validator->errors();
				$err     = '';
				foreach ($message->all() as $error) {
					$err = $err." ".$error;
				}
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
			}

			// Get payment status

			$status   = $arrInput['status'];
			$order_id = $arrInput['order_id'];
			// $params = array();
			// $params['orderId'] = $order_id;
			// $params['merchantId']='REGTEC';
			// $payment=Order::status($params);
			// return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '', $payment);
			// $status_id=$payment->status_id;
			// $payment_method_type=$payment->payment_method_type;
			// $payment_method=$payment->payment_method;
			// $txnId = $payment->txn_id;
			$payment_status          = 'Failed';
			$orderStatus             = 'Cancel';
			$transaction_status_code = $status;
			if ($status == 'CHARGED') {
				$payment_status = 'Success';
				$orderStatus    = 'Confirm';
			}
			$order                          = UserCartOrder::where('order_id', '=', $order_id)->first();
			$order->payment_status          = $payment_status;
			$order->transaction_status_code = $status;
			$order->status                  = $orderStatus;
			$order->save();

			if ($status == 'CHARGED') {
				$strMessage = "Order confirmed successfully";
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], $strMessage, []);
			} else {
				$strMessage = "Transaction failed";
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[200]['status'], $strMessage, []);
			}

		} catch (Exception $e) {
			$strMessage = "Error in processing your order";
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $strMessage, []);
		}
	}
	/**
	 * Function to get the unique txnid
	 *
	 */
	public function getUniqueTxnId() {
		$randomUniqueTxnId = rand(1000000000, 9999999999);
		$uniqueTxnId       = UserCartOrder::where('txnid', $randomUniqueTxnId)->first();
		if (empty($uniqueTxnId)) {
			return $randomUniqueTxnId;
		} else {
			$this->getUniqueTxnId();
		}
	}
	public function getOrderId() {
		$randomOrderId = rand(1000000000, 9999999999);
		$orderId       = UserCartOrder::where('order_id', $randomOrderId)->first();
		if (empty($orderId)) {
			return $randomOrderId;
		} else {
			$this->getOrderId();
		}
	}
	/**
	 * Function to get order details
	 *
	 * @param $request : HTTP Request Object
	 *
	 */
	public function ecommerceOrderDetail(Request $request) {
		$arrData = $arrUserData = [];
		$url     = url('public/uploads/products');
		try {
			$id        = Auth::user()->id;
			$arrInput  = $request->all();
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
			$arrOrderWhere = [['id', $arrInput['order_id']], ['user_id', $id]];
			$order         = UserCartOrder::where($arrOrderWhere)->first();
			if (empty($order)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Order Id', $arrData);
			}
			$url   = URL::to('/').'/uploads/products';
			$query = UserCartProduct::selectRaw('tp.name,tp.id, tp.cost, tp.description,tbl_user_cart_product.quantity,tbl_user_cart_product.total_coin,tbl_user_cart_product.sub_total_coin,tbl_user_cart_product.coupon_code as coupon_list,tbl_user_cart_product.total_usd,tbl_user_cart_product.sub_total_usd,tbl_user_cart_product.price, tbl_user_cart_product.total_price,tp.image,tbl_user_cart_product.product_id,tbl_user_cart_product.id as cart_id,tbl_user_cart_product.price,tbl_user_cart_product.coupon_amount,tbl_user_cart_product.discount,tbl_user_cart_product.mobile,tpv.id as variation_id,tpv.variation_value,tpv.variation_price,tpv.variation_price as cost,tp.variation,country.currency_symbol,country.currency_code,country.name as country_name')
				->join('tbl_product_ecommerce as tp', 'tp.id', '=', 'tbl_user_cart_product.product_id')
			//->join('tbl_categories as cat', 'cat.id', '=', 'tp.category_id')
			//cat.is_mob_filed
				->join('tbl_product_variation_details as tpv', 'tpv.id', '=', 'tbl_user_cart_product.variation_id')

			//->Leftjoin('tbl_shipping_address as tsa','tbl_user_cart_product.shipping_id','=', 'tsa.id')
			//->Leftjoin('tbl_billing_info as tbi','tbl_user_cart_product.billing_id','=', 'tbi.id')
				->Leftjoin('country_wize_currency as country', 'country.code', '=', 'tp.country_id');

			//tbl_user_cart_order
			$arrWhere = [['tbl_user_cart_product.order_id', $arrInput['order_id']]];
			// if($order->status == 'Cancel'){
			// 	$ids = trim($order->user_cart_product_ids);
			// 	$arrUserProductCartId = explode(" ", $ids);
			// 	$query = $query->whereIn('tbl_user_cart_product.id', $arrUserProductCartId);
			// } else {
			$query = $query->where($arrWhere);
			// }
			$totalRecord = $query->count();
			if ($totalRecord > 0) {
				$data = $query->get();
				//dd($data);
				$dataCart = $query->first();
				// dd($data);
				$arrData['id']          = $order->id;
				$arrData['order_id']    = $order->order_id;
				$arrData['coupon_code'] = $order->coupon_code;
				$arrData['name']        = $order->fullname;
				$arrData['mobile']      = $order->mobile;
				$arrData['email']       = $order->email;
				$arrData['address']     = $order->address;
				$arrData['pincode']     = $order->pin;
				$arrData['country']     = $order->country;
				$arrData['city']        = $order->city;
				$arrData['state']       = $order->state;
				$arrData['remark']      = $order->remark;

				$arrData['s_fname']           = $dataCart->s_fname;
				$arrData['s_lname']           = $dataCart->s_lname;
				$arrData['s_phone']           = $dataCart->s_phone;
				$arrData['s_email']           = $dataCart->s_email;
				$arrData['s_address']         = $dataCart->s_address;
				$arrData['s_zipcode']         = $dataCart->s_zipcode;
				$arrData['s_country']         = $dataCart->s_country;
				$arrData['s_city']            = $dataCart->s_city;
				$arrData['s_state']           = $dataCart->s_state;
				$arrData['s_special_request'] = $dataCart->s_special_request;
				$arrData['s_company']         = $dataCart->s_company;

				$arrData['b_fname']   = $dataCart->b_fname;
				$arrData['b_lname']   = $dataCart->b_lname;
				$arrData['b_phone']   = $dataCart->b_phone;
				$arrData['b_email']   = $dataCart->b_email;
				$arrData['b_address'] = $dataCart->b_address;
				$arrData['b_zipcode'] = $dataCart->b_zipcode;
				$arrData['b_country'] = $dataCart->b_country;
				$arrData['b_city']    = $dataCart->b_city;
				$arrData['b_state']   = $dataCart->b_state;
				$arrData['b_company'] = $dataCart->s_company;

				// $arrData['user_info'] = $arrUserData;
				$arrData['products']           = $data;
				$totalPrice                    = $order->total_price;
				$totalDiscount                 = $order->discount;
				$arrData['totalDiscount']      = $totalDiscount;
				$totalAmt                      = $order->total_amount;
				$arrData['total_price']        = $totalPrice;
				$arrData['coupon_amount']      = $order->coupon_amount;
				$arrData['total_coupon_minus'] = ($totalPrice-$order->coupon_amount);
				$arrData['discount']           = $totalDiscount;
				$arrData['order_date']         = $order->entry_time;
				$arrData['payment_mode']       = $order->payment_mode;
				$arrData['payment_status']     = $order->payment_status;
				$arrData['delivery_status']    = $order->status;
				// $arrData['total_amt'] = $totalAmt;
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $arrData);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', $arrData);
			}
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', $arrData);
		}
	}
	public function cancelOrders(Request $request) {
		$arrData = $arrUserData = [];
		try {
			$user      = Auth::user();
			$arrInput  = $request->all();
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
			$arrOrderWhere = [['order_id', $arrInput['order_id']], ['user_id', $user->id]];
			$order         = UserCartOrder::where($arrOrderWhere)->first();
			// dd($arrInput['order_id'],$order);
			if (empty($order)) {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid Order Id', $arrData);
			}

			$query = UserCartOrder::where('tbl_user_cart_order.order_id', '=', $arrInput['order_id'])->update(array('status' => 'Cancelled'));

			if ($query == 1) {

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], ' Cancelled ', $arrData);
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', $arrData);
			}
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong', $arrData);
		}
	}

	/**
	 * get user profile
	 *
	 * @param  Request $request : HTTP Request Object
	 *
	 * @return user data
	 *
	 */
	public function userProfile(Request $request) {
		$getuser = User::/*join('tbl_users as tu1', 'tbl_users.ref_user_id', '=', 'tu1.id')->*/select('tbl_users.id', 'tbl_users.user_id', 'tbl_users.ref_user_id', 'tbl_users.fullname', 'tbl_users.pin_number', 'tbl_users.mobile', 'tbl_users.email', 'tbl_users.gender', 'tbl_users.position', 'tbl_users.entry_time', 'tbl_users.account_no', 'tbl_users.holder_name', 'tbl_users.bank_name', 'tbl_users.pan_no', 'tbl_users.ifsc_code', 'tbl_users.state', 'tbl_users.country', 'tbl_users.pincode', 'tbl_users.address as user_address', 'tbl_users.about_us', 'tbl_users.nominee_name', 'tbl_users.relation', 'tbl_users.branch_name', 'tbl_users.blood_group', 'tbl_users.medisavecard_no', 'tbl_users.age', 'tbl_users.dob', 'tbl_users.bhim_no', 'tbl_users.city')
		//->leftjoin('tbl_country_new as cn','cn.iso_code','=','tbl_users.country')
			->where('tbl_users.id', Auth::user()->id)
			->first();

		//dd(Auth::user()->id,$getuser);
		if (!empty($getuser)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $getuser);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
		}
	}

	public function changePassword(Request $request) {
		$rules = array(
			'old_password'    => 'required|min:6|max:15',
			'new_password'    => 'required|min:6|max:15',
			'retype_password' => 'required|min:6|max:15',
		);
		$messages = array(
			'old_password.required'    => 'Please enter your old password.',
			'new_password.required'    => 'Please enter new password.',
			'retype_password.required' => 'Please retype password.',
			'new_password.regex'       => 'Pasword contains first character letter, contains atleast 1 capital letter,combination of alphabets,numbers and special character i.e. ! @ # $ *',
		);
		$validator = Validator::make($request->all(), $rules, $messages);
		if ($validator->fails()) {
			$message = messageCreator($validator->errors());
			return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, '');
		}
		$usrDetails = Auth::user();
		/* Changes done by NK on 03-08-2018 */
		//if (count($usrDetails) > 0) {
		/* End Changes done by NK on 03-08-2018 */
		if (!empty($usrDetails) > 0) {
			if ($request->Input('new_password') == $request->Input('retype_password')) {
				if (Hash::check($request->Input('old_password'), $usrDetails->bcrypt_password)) {
					$usrDetails->password        = encrypt($request->Input('new_password'));
					$usrDetails->bcrypt_password = bcrypt($request->input('new_password'));
					$usrDetails->save();

					return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Password change successfully!', '');
				} else {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Wrong password! Please try again.', '');
				}
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Password and Re-type password does not match', '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Invalid user', '');
		}
	}

	public function edituserProfile(Request $request) {

		$arrRules = ['pincode' => 'required|int|digits:6',
			'mobile'              => 'required|int|digits:10',
			'fullname'            => 'required',
			'email'               => 'required|email',
			'city'                => 'required',
			'country'             => 'required',
			'address'             => 'required',

		];
		$arrInput = $request->all();

		$validator = Validator::make($arrInput, $arrRules);
		if ($validator->fails()) {
			$message = $validator->errors();
			$err     = '';
			foreach ($message->all() as $error) {
				$err = $err." ".$error;
			}
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		}

		$getuser = Auth::user();
		if (!empty($request->pan_no)) {
			if ($getuser->pan_no != $request->pan_no) {
				if ($getuser->pan_no != '' || $getuser->pan_no != NULL) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Pancard already exist', '');
				}
			}
		}

		$data = [];
		/** @var [personal Details] */
		$data['fullname']     = (!empty($request->fullname))?trim($request->fullname):$getuser->fullname;
		$data['nominee_name'] = (!empty($request->nominee_name))?trim($request->nominee_name):$getuser->nominee_name;
		$data['relation']     = (!empty($request->relation))?trim($request->relation):$getuser->relation;
		$data['mobile']       = (!empty($request->mobile))?trim($request->mobile):$getuser->mobile;
		$data['email']        = (!empty($request->email))?trim($request->email):$getuser->email;
		if (!empty($request->gender)) {
			$data['gender'] = (!empty($request->gender))?$request->gender:$getuser->gender;
		}
		$data['dob'] = (!empty($request->dob))?trim($request->dob):$getuser->dob;
		/* Code Added by NK on 03-08-2018 */
		$data['about_us'] = (!empty($request->about_us))?trim($request->about_us):$getuser->about_us;
		/* End code Added by NK on 03-08-2018 */

		/** @var [bank Details] */
		$data['account_no']  = (!empty($request->account_no))?trim($request->account_no):$getuser->account_no;
		$data['holder_name'] = (!empty($request->holder_name))?trim($request->holder_name):$getuser->holder_name;
		$data['bank_name']   = (!empty($request->bank_name))?trim($request->bank_name):$getuser->bank_name;
		$data['branch_name'] = (!empty($request->branch_name))?trim($request->branch_name):$getuser->branch_name;
		$data['pan_no']      = (!empty($request->pan_no))?trim($request->pan_no):$getuser->pan_no;
		$data['ifsc_code']   = (!empty($request->ifsc_code))?trim($request->ifsc_code):$getuser->ifsc_code;
		/** @var [address Details] */
		$data['city']     = (!empty($request->city))?trim($request->city):$getuser->city;
		$data['state']    = (!empty($request->state))?trim($request->state):$getuser->state;
		$data['address']  = (!empty($request->address))?trim($request->address):$getuser->address;
		$data['address1'] = (!empty($request->address1))?trim($request->address1):$getuser->address1;
		$data['country']  = (!empty($request->country))?trim($request->country):$getuser->country;

		$data['country1'] = (!empty($request->country1))?trim($request->country1):$getuser->country1;
		$data['pincode']  = (!empty($request->pincode))?trim($request->pincode):$getuser->pincode;
		$data['pincode1'] = (!empty($request->pincode1))?trim($request->pincode1):$getuser->pincode1;
		$data['state1']   = (!empty($request->state1))?trim($request->state1):$getuser->state1;
		$data['city1']    = (!empty($request->city1))?trim($request->city1):$getuser->city1;
		$data['dob']      = (!empty($request->dob))?trim($request->dob):$getuser->dob;
		// $data['bhim_no'] = (!empty($request->bhim_no)) ? trim($request->bhim_no) : $getuser->bhim_no;

		/** Update user profile */
		$update_user = User::where('id', $getuser->id)->update($data);
		if ($update_user == 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Nothing to update in profile!', '');
		}
		/** Insert entery for each update of users */
		$upd_data            = $data;
		$upd_data['id']      = $getuser->id;
		$upd_data['user_id'] = $getuser->user_id;
		$insId               = DB::table('tbl_users_change_data')->insert($upd_data);
		if (!empty($insId)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Profile updated successfully!', '');
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong while update profile', '');
		}
	}

	/**
	 * Function to send OTP on mobile
	 *
	 * @param $request : HTTP Request object
	 */
	public function sendOTPOnMobile(Request $request) {
		print_r("dk");
		die();
		$getUser = User::select('mobile')->where('tbl_user.mobile', $request->input('mobile'))->first();
		dd($getUser);
		if (!empty($getUser)) {

			$arrInput['mobile'] = $request->mobile;
			// return $this->sendRegisterOtp($arrInput);
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $getuser);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
		}
	}

	public function sendRegisterOtp($arrInput) {

		$user                    = new User;
		$user->mobile            = $arrInput['mobile'];
		$random                  = rand(100000, 999999);
		$insertotp               = array();
		$insertotp['id']         = $user_id;
		$insertotp['mobile_no']  = $arrInput['mobile'];
		$insertotp['ip_address'] = trim($_SERVER['REMOTE_ADDR']);
		$insertotp['otp']        = md5($random);
		$insertotp['otp_status'] = 'Active';
		$insertotp['type']       = "mobile";
		$msg                     = $random.' is your verification code';
		sendMessage($user, $msg);
		$sendotp    = Otp::insert($insertotp);
		$intCode    = Response::HTTP_OK;
		$strMessage = 'OTP sent on mobile no.';

	}

	public function addCoupon(Request $request) {
		$coupon_code = $request->coupon_code;

		$arrCartAddress = [['user_id', Auth::user()->id], ['status', 'Pending'], ['order_id', 0]];
		$cartData       = UserCartProduct::select('id', 'billing_id', 'shipping_id', 'quantity', 'coupon_code')
			->where($arrCartAddress)	->first();

		if ($cartData->coupon_code == $coupon_code) {
			$data['c_status'] = 1;
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Coupon Added successfully!', $data);
		}
		$getuser = Auth::user()->user_id;
		$mlm_api = Config::get('constants.settings.mlm_api');

		if (!empty($cartData)) {
			$url1            = $mlm_api."updateCouponStatus";
			$status          = "Active";
			$coupon_code_old = $cartData->coupon_code;
			$ch1             = curl_init();
			curl_setopt($ch1, CURLOPT_URL, $url1);
			curl_setopt($ch1, CURLOPT_POST, 1);
			curl_setopt($ch1, CURLOPT_POSTFIELDS, "user_id=".trim($getuser)."&coupon=".$coupon_code_old."&status=".$status);
			$headr = array();
			curl_setopt($ch1, CURLOPT_HTTPHEADER, $headr);
			curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
			$result1 = curl_exec($ch1);
			$result1 = json_decode($result1, true);
			curl_close($ch1);
			if ($result1) {

				$url2 = $mlm_api."checkCouponValid";
				$ch2  = curl_init();
				curl_setopt($ch2, CURLOPT_URL, $url2);
				curl_setopt($ch2, CURLOPT_POST, 1);
				curl_setopt($ch2, CURLOPT_POSTFIELDS, "user_id=".trim($getuser)."&coupon=".$coupon_code);
				$headr = array();
				// $headr[] = 'token: '.$token;
				curl_setopt($ch2, CURLOPT_HTTPHEADER, $headr);
				curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
				$result2 = curl_exec($ch2);
				$result2 = json_decode($result2, true);
				curl_close($ch2);
				// dd($url1);
				return $result2;
			}

		} else {
			$url1 = $mlm_api."checkCouponValid";
			$ch1  = curl_init();
			curl_setopt($ch1, CURLOPT_URL, $url1);
			curl_setopt($ch1, CURLOPT_POST, 1);
			curl_setopt($ch1, CURLOPT_POSTFIELDS, "user_id=".trim($getuser)."&coupon=".$coupon_code);
			$headr = array();
			// $headr[] = 'token: '.$token;
			curl_setopt($ch1, CURLOPT_HTTPHEADER, $headr);
			curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
			$result1 = curl_exec($ch1);
			$result1 = json_decode($result1, true);
			curl_close($ch1);
			// dd($url1);
			return $result1;
		}

	}

	public function updateCouponStatus(Request $request) {
		$status      = $request->status;
		$coupon_code = $request->coupon_code;
		$getuser     = Auth::user()->user_id;
		$mlm_api     = Config::get('constants.settings.mlm_api');
		$url1        = $mlm_api."updateCouponStatus";
		$ch1         = curl_init();
		curl_setopt($ch1, CURLOPT_URL, $url1);
		curl_setopt($ch1, CURLOPT_POST, 1);
		curl_setopt($ch1, CURLOPT_POSTFIELDS, "user_id=".trim($getuser)."&coupon=".$coupon_code."&status=".$status);
		$headr = array();
		// $headr[] = 'token: '.$token;
		curl_setopt($ch1, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
		$result1 = curl_exec($ch1);
		$result1 = json_decode($result1, true);
		curl_close($ch1);
		// dd($result1);
		return $result1;
	}

	public function checkCouponValid(Request $request) {

		try {
			$mlm_api     = Config::get('constants.settings.mlm_api');
			$status      = $request->status;
			$coupon_code = $request->coupon_code;
			$getuser     = Auth::user()->user_id;
			$url1        = $mlm_api."checkCouponValid";
			$ch1         = curl_init();
			curl_setopt($ch1, CURLOPT_URL, $url1);
			curl_setopt($ch1, CURLOPT_POST, 1);
			curl_setopt($ch1, CURLOPT_POSTFIELDS, "user_id=".trim($getuser)."&coupon=".$coupon_code);
			$headr = array();
			// $headr[] = 'token: '.$token;
			curl_setopt($ch1, CURLOPT_HTTPHEADER, $headr);
			curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
			$result1 = curl_exec($ch1);
			$result1 = json_decode($result1, true);
			curl_close($ch1);
			// dd($url1);
			return $result1;
		} catch (Exception $e) {
			return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Server Error', $data);

		}

	}

	public function billingAddressData(Request $request) {

		$checkD   = [['user_id', Auth::user()->id], ['status', 'Pending'], ['order_id', 0]];
		$cartData = UserCartProduct::select('id', 'billing_id', 'shipping_id', 'quantity')
			->where($checkD)	->first();

		$billing_id = $cartData->billing_id;
		$getuser    = BillingInfo::select('*')
			->where('id', $billing_id)
			->first();

		if (!empty($getuser)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $getuser);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
		}
	}

	public function shippingAddressData(Request $request) {

		$checkD   = [['user_id', Auth::user()->id], ['status', 'Pending'], ['order_id', 0]];
		$cartData = UserCartProduct::select('id', 'billing_id', 'shipping_id', 'quantity')
			->where($checkD)	->first();

		$shipping_id = $cartData->shipping_id;
		$getuser     = ShippingAddress::select('*')
			->where('id', $shipping_id)
			->first();

		if (!empty($getuser)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found', $getuser);
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
		}
	}

	public function editBillingInfo(Request $request) {

		$getuser  = Auth::user();
		$checkD   = [['user_id', Auth::user()->id], ['status', 'Pending'], ['order_id', 0]];
		$cartData = UserCartProduct::select('id', 'billing_id', 'shipping_id', 'quantity')
			->where($checkD)	->first();

		$billing_id  = $cartData->billing_id;
		$shipping_id = $cartData->shipping_id;

		$data          = [];
		$data['fname'] = (!empty($request->fname))?trim($request->fname):$getuser->fname;
		$data['lname'] = (!empty($request->lname))?trim($request->lname):$getuser->lname;
		$data['phone'] = (!empty($request->mobile))?trim($request->mobile):$getuser->mobile;
		$data['email'] = (!empty($request->email))?trim($request->email):$getuser->email;

		$data['city']         = (!empty($request->city))?trim($request->city):$getuser->city;
		$data['state']        = (!empty($request->state))?trim($request->state):$getuser->state;
		$data['address']      = (!empty($request->address))?trim($request->address):$getuser->address;
		$data['country']      = (!empty($request->country))?trim($request->country):$getuser->country;
		$data['zipcode']      = (!empty($request->zipcode))?trim($request->zipcode):$getuser->pincode;
		$data['company_name'] = (!empty($request->company_name))?trim($request->company_name):$getuser->company_name;

		/** Update user profile */
		$update_user = BillingInfo::where('id', $billing_id)->update($data);
		if ($update_user == 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Nothing to update in profile!', '');
		}

		/** Insert entery for each update of users */
		if (!empty($update_user)) {
			if ($request->isSameAddress) {
				$update_shipping_data = ShippingAddress::where('id', $shipping_id)->update($data);
			}
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Billing Info updated successfully!', '');
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong while update profile', '');
		}
	}
	public function editShippingAddress(Request $request) {

		$getuser  = Auth::user();
		$arrInput = $request->all();

		$arrRules  = ['fname' => 'required', 'lname' => 'required', 'address' => 'required', 'phone' => 'required', 'country' => 'required', 'state' => 'required', 'zipcode' => 'required|numeric|digits:6', 'email' => 'required|email', 'city' => 'required'];
		$validator = Validator::make($arrInput, $arrRules);
		if ($validator->fails()) {
			$message = $validator->errors();
			$err     = '';
			foreach ($message->all() as $error) {
				$err = $err." ".$error;
			}
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
		}

		$checkD   = [['user_id', Auth::user()->id], ['status', 'Pending'], ['order_id', 0]];
		$cartData = UserCartProduct::select('id', 'billing_id', 'shipping_id', 'quantity')
			->where($checkD)	->first();

		$shipping_id = $cartData->shipping_id;

		$data          = [];
		$data['fname'] = (!empty($request->fname))?trim($request->fname):$getuser->fname;
		$data['lname'] = (!empty($request->lname))?trim($request->lname):$getuser->lname;
		$data['phone'] = (!empty($request->mobile))?trim($request->mobile):$getuser->mobile;
		$data['email'] = (!empty($request->email))?trim($request->email):$getuser->email;

		/** @var [address Details] */
		$data['city']            = (!empty($request->city))?trim($request->city):$getuser->city;
		$data['state']           = (!empty($request->state))?trim($request->state):$getuser->state;
		$data['address']         = (!empty($request->address))?trim($request->address):$getuser->address;
		$data['country']         = (!empty($request->country))?trim($request->country):$getuser->country;
		$data['zipcode']         = (!empty($request->zipcode))?trim($request->zipcode):$getuser->pincode;
		$data['company_name']    = (!empty($request->company_name))?trim($request->company_name):$getuser->company_name;
		$data['special_request'] = (!empty($request->special_request))?trim($request->special_request):$getuser->special_request;

		/** Update user profile */
		$update_user = ShippingAddress::where('id', $shipping_id)->update($data);
		if ($update_user == 0) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Nothing to update in profile!', '');
		}

		/** Insert entery for each update of users */
		if (!empty($update_user)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Shipping Address updated successfully!', '');
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong while update profile', '');
		}
	}

	public function updateCouponStatusInCart(Request $request) {

		$coupon_code    = $request->coupon_code;
		$coupon_amount  = $request->coupon_amount;
		$arrCartAddress = [['user_id', Auth::user()->id], ['status', 'Pending'], ['order_id', 0]];

		$updateCouponCode = UserCartProduct::where($arrCartAddress)
			->update(array('coupon_code' => $coupon_code, 'coupon_amount' => $coupon_amount));

		if (!empty($updateCouponCode)) {
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Coupon Added successfully!', '');
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong while update profile', '');
		}

	}

	public function setSameAddressLikeBilling(Request $request) {

		$isSameAddress = $request->isSameAddress;

		$checkD = [['user_id', Auth::user()->id], ['status', 'Pending'], ['order_id', 0]];

		$cartData = UserCartProduct::select('id', 'billing_id', 'shipping_id', 'quantity', 'is_same_address')
			->where($checkD)	->first();

		$billing_id      = $cartData->billing_id;
		$shipping_id     = $cartData->shipping_id;
		$is_same_address = $cartData->is_same_address;

		$billingData = BillingInfo::select('*')
			->where('id', $billing_id)
			->first();

		if ($isSameAddress == true) {

			$data['city']    = $billingData->city;
			$data['fname']   = $billingData->fname;
			$data['lname']   = $billingData->lname;
			$data['phone']   = $billingData->phone;
			$data['email']   = $billingData->email;
			$data['country'] = $billingData->country;
			$data['address'] = $billingData->address;
			$data['state']   = $billingData->state;
			$data['zipcode'] = $billingData->zipcode;

			$update_shipping = ShippingAddress::where('id', $shipping_id)->update($data);

			$updateis_same_address = UserCartProduct::where($checkD)
				->update(array('is_same_address' => '1'));

			if ($updateis_same_address == 0) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Already Same address!', '');
			} else {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Shipping Address is same as Billing Address!', '');
			}

		} else {
			$updateis_same_address = UserCartProduct::where($checkD)
				->update(array('is_same_address' => '0'));
			if ($updateis_same_address == 0) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Nothing to update in profile!', '');
			} else {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Shipping Address is not same as Billing Address!', '');
			}
		}
	}
	public function cartWithSingleProduct(Request $request) {

		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '', '');
		$id = Auth::user()->id;

		$arrCartAddress = [['user_id', $id], ['status', 'Pending'], ['order_id', 0]];

		$cartCount = UserCartProduct::where($arrCartAddress)->count('product_id');

		if ($cartCount == 1) {
			$today      = date('Y-m-d', strtotime($this->today));
			$orderCount = UserCartOrder::where('user_id', $id)->where('entry_time', 'LIKE', "%$today%")->count('id');
			if ($orderCount <= 0) {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], '', '');
			} else {
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'You are allowed to place only one order per day', '');
			}
		} else {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Only one voucher type allowed to use per day', '');
		}

	}

	public function voucherTransaction(Request $request) {
		try {
			$arrInput    = $request->all();
			$UserExistid = Auth::User()->id;

			if (!empty($UserExistid)) {

				$query = VoucherTransaction::select('tbl_voucher_transaction.*', 'tu.user_id')
					->join('tbl_users as tu', 'tu.id', '=', 'tbl_voucher_transaction.id')
					->where([['tbl_voucher_transaction.id', '=', $UserExistid]])
					->orderBy('tbl_voucher_transaction.entry_time', 'desc');

				if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
					//searching loops on fields
					$fields = getTableColumns('tbl_voucher_transaction');
					$search = $arrInput['search']['value'];
					$query  = $query->where(function ($query) use ($fields, $search) {
							foreach ($fields as $field) {
								$query->orWhere('tbl_voucher_transaction.'.$field, 'LIKE', '%'.$search.'%');
							}
							$query->orWhere('tu.user_id', 'LIKE', '%'.$search.'%');
						});
				}

				$query        = $query->orderBy('tbl_voucher_transaction.entry_time', 'desc');
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
}
