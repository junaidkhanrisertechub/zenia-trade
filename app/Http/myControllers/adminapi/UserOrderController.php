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
use App\Models\ProjectSetting;
use Validator;
use Config;
use DB;
use Exception;
use PDOException;
use Auth;
use URL;

class UserOrderController extends Controller {

	public $commonController;

	public function __construct(CommonController $CommonController) {
        $this->statuscode = Config::get('constants.statuscode');
        $this->commonController     = $CommonController;
        $date = \Carbon\Carbon::now();
        $this->today = $date->toDateTimeString();
    }

	/**
	 * Function for checkout
	 * 
	 * @param $request : HTTP Request
	 * 
	 */ 

   public function getCoinRate(Request $request){

   	try {
   		$arrData=ProjectSetting::select('coin_rate')->where('id','=',1)->first();

   		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
   		
   	} catch (Exception $e) {
   		return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Server error', '');
   	}

   }

   public function updateCoin(Request $request){

   	try {

         $arrData=ProjectSetting::select('coin_rate')->where('id','=',1)->first();
        ProjectSetting::where('id','=',1)->update(array('coin_rate' => $request->coin_rate)); 

        $data=array('coin_rate'=>$arrData->coin_rate);

        DB::table('tbl_coin_rate_changes_info')->insert($data);

   		return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Updated successfully', '');
   		
   	} catch (Exception $e) {
   		return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Server error', '');
   	}
   }

	public function getUserOrder(Request $request){
		try {
			$user = Auth::user();
			$arrInput = $request->all();
			$query = UserCartOrder::select('tbl_user_cart_order.remark','tbl_user_cart_order.order_id','tbl_user_cart_order.total_usd','tbl_user_cart_order.payment_mode','tbl_user_cart_order.payment_status','tbl_user_cart_order.status','tbl_user_cart_order.entry_time','tbl_user_cart_order.id','tu.user_id','tu.fullname','tu.mobile'/*,'tsa.address as s_address','tsa.city as s_city' ,'tsa.country as s_country','tsa.state as s_state','tsa.zipcode as s_zipcode','tsa.company_name as s_company','tsa.special_request as s_special_request','tsa.fname as s_fname','tsa.lname as s_lname','tbi.address as b_address','tbi.city as b_city' ,'tbi.country as b_country','tbi.state as b_state','tbi.zipcode as b_zipcode','tbi.company_name as b_company','tbi.fname as b_fname','tbi.lname as b_lname'*/)		

			/*,'tsa.country as s_country','tsa.state as s_state','tsa.zipcode as s_zipcode','tsa.company_name as s_company','tsa.special_request as s_special_request'*/
						->join('tbl_users as tu','tu.id','=', 'tbl_user_cart_order.user_id');

						//->Leftjoin('tbl_shipping_address as tsa','tbl_user_cart_order.shipping_id','=', 'tsa.id')
						//->Leftjoin('tbl_billing_info as tbi','tbl_user_cart_order.billing_id','=', 'tbi.id');
			if((isset($arrInput['user_id'])) && (!empty($arrInput['user_id']))){
	            $query = $query->where('tu.user_id', $arrInput['user_id']);
	        }
	        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
	            $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
	            $arrInput['to_date'] = date('Y-m-d', strtotime($arrInput['to_date']));
	            $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_user_cart_order.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
	        }
	        // if((isset($arrInput['payment_mode'])) && (!empty($arrInput['payment_mode']))){
	        // 	$query = $query->where('tbl_user_cart_order.payment_mode', $arrInput['payment_mode']);
	        // }
	        if((isset($arrInput['status'])) && (!empty($arrInput['status']))){
	        	$query = $query->where('tbl_user_cart_order.status', $arrInput['status']);
	        }
	        if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
	            //searching loops on fields
	            $fields = getTableColumns('tbl_user_cart_order');
	            $search = $arrInput['search']['value'];
	            $query = $query->where(function ($query) use ($fields, $search) {
	                foreach ($fields as $field) {
	                    $query->orWhere('tbl_user_cart_order.' . $field, 'LIKE', '%' . $search . '%');
	                }
	                $query->orWhere('tu.user_id', 'LIKE', '%' . $search . '%');
	            });
	        }
	        // export
	        if (isset($request->action) && $request->action == 'export') {
				$qry     = $query;
				$qry = $query->select('tbl_user_cart_order.remark','tbl_user_cart_order.order_id','tbl_user_cart_order.total_usd','tbl_user_cart_order.payment_mode','tbl_user_cart_order.payment_status','tbl_user_cart_order.status','tbl_user_cart_order.entry_time','tbl_user_cart_order.id','tu.user_id','tu.fullname','tu.mobile'/*,'tsa.address as s_address','tsa.city as s_city' ,'tsa.country as s_country','tsa.state as s_state','tsa.zipcode as s_zipcode','tsa.company_name as s_company','tsa.special_request as s_special_request','tsa.fname as s_fname','tsa.lname as s_lname','tbi.address as b_address','tbi.city as b_city' ,'tbi.country as b_country','tbi.state as b_state','tbi.zipcode as b_zipcode','tbi.company_name as b_company','tbi.fname as b_fname','tbi.lname as b_lname'*/);		
	            //$qry = $query->select('tbl_user_cart_order.order_id as OrderId','tu.user_id as UserId','tu.fullname as FullName','tu.mobile as Mobile','tbl_user_cart_order.total_usd as TotalUsd','tbl_user_cart_order.payment_mode as PaymentMode','tbl_user_cart_order.payment_status as PaymentStaus','tbl_user_cart_order.status as Status','tbl_user_cart_order.entry_time as Date');
				$records = $qry->get();
				$res     = $records->toArray();
				if (count($res) <= 0) {
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', array());
				}
				$var = $this->commonController->exportToExcel($res, "AllUsers");
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Records found', array('data' => $var));
	        }

	        $query = $query->orderBy('tbl_user_cart_order.id', 'desc');
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
			dd($e);
			$strMessage = "Something went wrong";
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],$strMessage, []);
		}
	}

	/**
	 * Function to get order details
	 * 
	 * @param $request : HTTP Request Object
	 * 
	 */
	public function getUserOrderDetail(Request $request){
		$arrData = [];
		try {
			$arrInput = $request->all();
			$arrRules = ['order_id' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
	        $order = UserCartOrder::where('id', $arrInput['order_id'])->first();
	        if(empty($order)){
	        	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Invalid Order Id', $arrData);
	        }
			// $url  = URL::to('/').'/public/uploads/products';
	        // $query = UserCartProduct::selectRaw('tuco.*,tp.name, tp.cost, tp.description,tbl_user_cart_product.quantity,tbl_user_cart_product.mobile,tbl_user_cart_product.coupon_code as coupon_data,tbl_user_cart_product.sub_total_usd,tbl_user_cart_product.price, tbl_user_cart_product.total_price,tp.image,product_id,tbl_user_cart_product.id as cart_id,(CASE WHEN tp.image IS NOT NULL THEN CONCAT("'.$url.'","/",tp.image) ELSE "" END) as image,tbl_user_cart_product.price,tbl_user_cart_product.total_coin,tbl_user_cart_product.sub_total_coin,tp.country_id,country.currency_code,country.currency_symbol,country.name as country_name')
	        $query = UserCartProduct::selectRaw('tp.name, tp.cost,tbl_user_cart_product.quantity,tbl_user_cart_product.mobile,tbl_user_cart_product.coupon_code as coupon_data,tbl_user_cart_product.sub_total_usd,tbl_user_cart_product.total_usd,tbl_user_cart_product.price, tbl_user_cart_product.total_price, product_id, tbl_user_cart_product.id as cart_id, country.currency_code, country.currency_symbol, country.name as country_name')
				->join('tbl_product_ecommerce as tp', 'tp.id', '=', 'tbl_user_cart_product.product_id')
				->leftjoin('country_wize_currency as country', 'country.code', '=', 'tp.country_id');
				//->Leftjoin('tbl_shipping_address as tsa','tbl_user_cart_product.shipping_id','=', 'tsa.id')
				//->Leftjoin('tbl_billing_info as tbi','tbl_user_cart_product.billing_id','=', 'tbi.id');
				
						
						
			$arrWhere = [['tbl_user_cart_product.order_id', $arrInput['order_id']]];
	        // if($order->status == 'Cancel' && $order->payment_mode=='cod'){
	        // 	$ids = trim($order->user_cart_product_ids);
	        // 	$arrUserProductCartId = explode(" ", $ids);
	        // 	$query = $query->join('tbl_user_cart_order as tuco', 'tuco.user_id', '=', 'tbl_user_cart_product.user_id')->where('tbl_user_cart_product.order_id', '0')->where('tuco.status', 'Cancel')->whereIn('tbl_user_cart_product.id', $arrUserProductCartId);
	        // } else {
	        	$query = $query->leftjoin('tbl_user_cart_order as tuco', 'tuco.id', '=', 'tbl_user_cart_product.order_id')->where($arrWhere);
	        // }
			$totalRecord  = $query->count();
			if($totalRecord > 0 ){
				$data  = $query->get();
				$totalPrice = $query->sum('tbl_user_cart_product.total_price');
				// $data['fullname'] = $order->fullname;
				// $data['address'] = $order->address . ',' . $order->country . ',' .
				// $order->city . ',' .  $order->pin ;

				// $data['mobile'] = $order->mobile;
				$arrData['records'] = $data;				
				$arrData['total_price'] = $totalPrice;
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $arrData);
		    } else {
		    	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Data not found', $arrData);
		    }
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	} 

	/**
	 * Function to change the Order Status
	 * 
	 * @param $request : HTTP Request Object
	 */ 
	public function changeOrderStatus(Request $request){
		$arrData = [];
		try {
			$arrInput = $request->all();
			$arrRules = ['order_id' => 'required', 'status' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
	    $changeOrderStatus = UserCartOrder::whereIn('id', $request['order_id'])->update(array('status' => $request['status']));
	       if($arrInput['status']=='Cancelled' || $arrInput['status']=='Packed' ){
	       	 $query = UserCartProduct::selectRaw('tp.name,tp.id, tp.cost, tp.description,tbl_user_cart_product.quantity, tbl_user_cart_product.price, tbl_user_cart_product.total_price,tp.image,tbl_user_cart_product.product_id,tbl_user_cart_product.id as cart_id,tbl_user_cart_product.price,tbl_user_cart_product.discount,tpv.id as variation_id,tpv.variation_value,tpv.variation_price,tpv.variation_price as cost,tp.variation')
				->join('tbl_product_ecommerce as tp', 'tp.id', '=', 'tbl_user_cart_product.product_id')
				->join('tbl_product_variation_details as tpv', 'tpv.id', '=', 'tbl_user_cart_product.variation_id');
						
			$arrWhere = [['tbl_user_cart_product.order_id', $arrInput['order_id']]];
	     
	        	$query = $query->where($arrWhere)->get();
	        	//dd($arrInput['order_id'],$query->toArray());
	        	$OrData=$query->toArray();
	        //dd($arrInput['status']=='Cancelled' || $arrInput['status']=='Packed',$arrInput['status'],$OrData);

	        		//dd($value['product_id']);
	        	foreach ($OrData as $value) {

                    $old=EcommerceProduct::select('qty_minus','qty')->where('id','=', $value['product_id'])->first();

                    if($arrInput['status']=='Packed'){
	        		/*$ss =EcommerceProduct::where('id', $value['product_id'])
	        		->update(array('qty_minus'=>$old->qty_minus + $value['quantity']));*/
                    } 
                      if($arrInput['status']=='Cancelled'){
	        		$ss =EcommerceProduct::where('id', $value['product_id'])
	        		->update(array('qty'=>$old->qty + $value['quantity']));
                    } 

	        		//dd($ss,$arrInput['status'],$value['product_id'],$value['quantity']);
	        		# code...
	        	}

	       }
	        
	        //UserCartProduct::whereIn('id', $request['order_id'])

	      //  dd($arrInput['status']);
	        //EcommerceProduct
	        if ($changeOrderStatus) {
	        	return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Order '.$request->status.' successfully', $arrData);
	        }
	        // dd($changeOrderStatus);
	  //       $arrOrderWhere = [['id', $arrInput['order_id'],['payment_mode','cod']],['status', 'Pending']];
	  //       $userOrder = UserCartOrder::where($arrOrderWhere)->first();
	  //       if(empty($userOrder)){
	  //       	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], "Invalid Order Id", '');
	  //       }
	  //       $arrCartWhere = [['order_id', $arrInput['order_id']]];
			// $userCartProduct = UserCartProduct::where($arrCartWhere)->get();
			// $ids = "";
			// if(count($userCartProduct) > 0 ){
			// 	foreach ($userCartProduct as $cart) {
			// 		if($arrInput['status'] != 'Confirm'){
			// 			$cart->order_id = 0;
			// 		}
			// 		$cart->status   = $arrInput['status'];
			// 		$cart->save();
			// 		$ids.= $cart->id." ";
			// 	}
			// }
	        // $userOrder->change_by = Auth::user()->id;
	        // $userOrder->status = $arrInput['status'];
	        // $userOrder->user_cart_product_ids = $ids;
	        // $userOrder->save();
	        
	   	} catch (Exception $e) {
	   		dd($e);
	   		return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}

	/**
	 * Function for payment detail
	 * 
	 * @param $request : HTTP Request Object
	 * 
	 */
	public function paymentDetail(Request $request){
		$arrData = [];
		try {
			$arrInput = $request->all();
			$arrRules = ['txnid' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
			$arrWhere = [['txnid', $arrInput['txnid']]];
			$paymentDetail = Payment::where($arrWhere)->first();
			if(empty($paymentDetail)){
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
			} else {
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $paymentDetail);
			}
		} catch (Exception $e) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	} 

	public function saveCoupon(Request $request) {
        $arrInput = $request->all();
        $rules = array(
            'cart_id'            => 'required',
            'coupon_code'            => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message, $message);
        }
        $getreq = UserCartProduct::where('id',$arrInput['cart_id'])->first();
        if(!empty($getreq))
        {
            //$getreq->status = 'Delivered';
            //$getreq->payment_status = 'Success';
            $getreq->coupon_code = $request->coupon_code;
            $getreq->update();

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Coupon code Updated successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }
public function approveOrderRequest(Request $request) {
        $arrInput = $request->all();
        $rules = array(
           // 'remark'            => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        }
        $getreq = UserCartOrder::where('id',$arrInput['id'])->first();
        if(!empty($getreq))
        {      
        	$data=[];
            $data['status'] = 'Delivered';
            $data['payment_status'] = 'Success';
            $data['coupon_code'] = '';
            $data['remark'] = $arrInput['remark'];
            $res=UserCartOrder::where('id',$arrInput['id'])->update($data);

        //dd($res);
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record approved successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }

    public function rejectOrderRequest(Request $request) {
        $arrInput = $request->all();
        $rules = array(
            'remark'            => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Remark required', $message);
        }
        $getreq = UserCartOrder::where('id',$arrInput['id'])->first();
        if(!empty($getreq))
        {
            $getreq->status = 'Cancelled';
            $getreq->payment_status = 'Failed';
            $getreq->remark = $request->remark;
            $getreq->update();

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record Rejected successfully.', array());
        }else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found', '');
        }
    }



}