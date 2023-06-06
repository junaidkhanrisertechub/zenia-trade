<?php

namespace App\Http\Controllers\userapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response as Response;
use App\User;
use App\Models\Product;
use App\Models\EcommerceProduct;
use App\Models\UserCartProduct;
use App\Models\BillingInfo;
use App\Models\ShippingAddress;
use App\Models\UserCartOrder;
use App\Models\Pins;
//use App\Models\Wishlist;
use App\Models\ProductVariation;
use App\Models\ProjectSetting;
use App\Models\Category;
use Validator;
use Config;
use DB;
use Exception;
use PDOException;
use Auth;
use URL;

class CartController extends Controller {

	public function __construct() {
        $this->statuscode = Config::get('constants.statuscode');
        $date = \Carbon\Carbon::now();
        $this->today = $date->toDateTimeString();
    }
    
	/**
	 * Function to register the front site user
	 * 
	 * @param $request : HTTP Request Object
	 * 
	 */
	public function addProductToCart(Request $request){
		$arrData = [];
		try {
			$arrInput = $request->all();
			$arrRules = ['product_id' => 'required', 'quantity' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
			// check for current product is in cart or not
			$id = Auth::User()->id;
			$arrProductWhere  = [['id', $arrInput['product_id']], ['status_product', 'Active']];
			$product = Product::where($arrProductWhere)->first();
			if(empty($product)){
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product not found', []);
			}
			$arrWhere = [['product_id', $arrInput['product_id']], ['status', 'Pending'], ['user_id', $id],['order_id',0]]; 
			$productCart  = UserCartProduct::where($arrWhere)->first();
			if(empty($productCart)) {
				$productCart = new UserCartProduct;
				$productCart->product_id = $arrInput['product_id'];
				$productCart->user_id = $id;
			} 
			if($productCart->quantity == $arrInput['quantity']){
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product with same quantity already in cart.', $arrData);
			}
			$productCart->discount = $product->coupon;
			$productCart->quantity = $arrInput['quantity'];
			$productCart->price = $product->cost;
			$productCart->total_price = ($product->cost * $arrInput['quantity']);
			$productCart->entry_time  = $this->today;
			$productCart->save();
			// calculate total sum of the user cart
			$arrCartTotalWhere = [['user_id', $id], ['status', 'Pending'],['order_id',0]];
			$totalPrice = UserCartProduct::where($arrCartTotalWhere)->sum('total_price');
			$arrData['total_price'] = $totalPrice;
			if((isset($arrInput['cart'])) && ($arrInput['cart'] == 1)){
				$strMessage = 'Product quantity updated to cart';
			} else {
				$strMessage = 'Product added to cart';
			}
			return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],$strMessage, $arrData);
		} catch (Exception $e) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}
	/**
	 * Function to register the front site user
	 * 
	 * @param $request : HTTP Request Object
	 * 
	 */
	public function addEcommerceProductToCart(Request $request)
	{
		$arrData = [];
		try {
			$arrInput = $request->all();
			$arrRules = ['product_id' => 'required','variation_id'=>'required', 'quantity' => 'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
	       // dd($arrInput['mobile']);
			// check for current product is in cart or not
			$id = Auth::User()->id;

			$user_details = User::select('id', 'user_id', 'fullname', 'pin_number', 'mobile', 'email', 'gender', 'state','country','pincode', 'address as user_address','city')
                ->where('id', $id)
                ->first();
            

			$arrProductWhere  = [['tbl_product_ecommerce.id', $arrInput['product_id']], ['tbl_product_ecommerce.status_product', 'Active'],['tpv.id',$arrInput['variation_id']]];
			$product = EcommerceProduct::select('tbl_product_ecommerce.*','tpv.variation_price as cost')->join('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')->where($arrProductWhere)->first();
			if(empty($product)){
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product not found', []);
			}
              $category=Category::where('id','=',$product->category_id)->first();
			if($category->is_mob_filed==1){

				if($arrInput['mobile']==''){
					return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Mobile Number Required', []);
				}



			}

			$product_pins = $product->qty - $product->qty_minus;/*Pins::where([['product_id',$arrInput['product_id']],['variation_id',$arrInput['variation_id']],['status','Active']])->count();*/
			$arrOutput = array();
			$arrOutput['product_id'] = $arrInput['product_id'];
			$arrOutput['variation_id'] = $arrInput['variation_id'];
			$arrOutput['product_name'] = $product->name;
			$arrOutput['stock'] = $product_pins;

			//if(!empty($product_pins) || $product_pins > 0){

			if($product_pins >= $arrInput['quantity']){
					//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Insufficient stock', $arrOutput);
				}
			//}
			else
			{
				//return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product out of stock', $arrOutput);
			}

			$arrWhere = [['product_id', $arrInput['product_id']],['variation_id',$arrInput['variation_id']], ['status', 'Pending'], ['user_id', $id],['order_id',0]]; 
			$productCart  = UserCartProduct::where($arrWhere)->first();
			if(empty($productCart)) {
				$productCart = new UserCartProduct;
				$productCart->product_id = $arrInput['product_id'];
				$productCart->variation_id = $arrInput['variation_id'];
				$productCart->user_id = $id;
				$strMessage = 'Product added to cart';

			//dd($arrInput['product_id']);
			} 
			else
			{
				$strMessage = 'Product quantity updated to cart';
			}

			$productCart->discount = $product->coupon;
			$productCart->quantity =  $arrInput['quantity'];
			if($productCart->quantity > 0)
			{
           //dd($productCart);

				$productCart->price = $product->cost;
				$productCart->total_price = ($product->cost * ($productCart->quantity));
				$productCart->entry_time  = $this->today;
				$productCart->save();
				$cartID = $productCart->id;


			    //dd(11,$cartID);
			    $checkD = [['user_id', $id], ['status', 'Pending'],['order_id',0]];
				$cartData = UserCartProduct::select('id','billing_id','shipping_id','quantity')
                ->where($checkD);
            $totalRecord  = $cartData->count();
			if($totalRecord > 1 ){
				$data  = $cartData->first();

				$arrCartAddress = [['user_id', $id], ['status', 'Pending'],['order_id',0]];
				$updateBillingAddress= UserCartProduct::where($arrCartAddress)->update(array('billing_id'=>$data->billing_id, 'shipping_id' => $data->shipping_id,'mobile'=>$arrInput['mobile']));
			}else{
				$data  = $cartData->first();
				if($data->quantity == 1){
				$name_arr = explode(" ", $user_details->fullname);

				$first_name='';
				$last_name='';
				if(count($name_arr)==1){
					$first_name=$name_arr[0];
				}
				else if(count($name_arr)==2){
					$first_name=$name_arr[0];
					$last_name=$name_arr[1];
				}
				else if(count($name_arr)==3){
					$first_name=$name_arr[0];
					$last_name=$name_arr[2];
				}
	            $billinginfodata = new BillingInfo;
				$billinginfodata->order_id = $cartID;
				$billinginfodata->user_id = $id;
				$billinginfodata->fname = $first_name;
				$billinginfodata->lname = $last_name;
				$billinginfodata->email = $user_details->email;
				$billinginfodata->phone = $user_details->mobile;
				$billinginfodata->address = $user_details->user_address;
				$billinginfodata->country = $user_details->country;
				$billinginfodata->city = $user_details->city;
				$billinginfodata->state = $user_details->state;
				$billinginfodata->zipcode = $user_details->pincode;
				$billinginfodata->save();

				$insIdBilling = $billinginfodata->id;


	            $shippingdata = new ShippingAddress;
				$shippingdata->order_id = $cartID;
				$shippingdata->user_id = $id;
				$shippingdata->fname = $first_name;
				$shippingdata->lname = $last_name;
				$shippingdata->email = $user_details->email;
				$shippingdata->phone = $user_details->mobile;
				$shippingdata->address = $user_details->user_address;
				$shippingdata->country = $user_details->country;
				$shippingdata->city = $user_details->city;
				$shippingdata->state = $user_details->state;
				$shippingdata->zipcode = $user_details->pincode;
				$shippingdata->save();

				$insIdShipping = $shippingdata->id;


				$arrCartAddress = [['user_id', $id], ['status', 'Pending'],['order_id',0]];
				$updateBillingAddress= UserCartProduct::where($arrCartAddress)->update(array('billing_id'=>$insIdBilling, 'shipping_id' => $insIdShipping, 'is_same_address' => 1,'mobile'=>$arrInput['mobile']));
			}
			}

				
				
           // dd($arrInput['product_id'],$arrInput['quantity'],$ss);
				// calculate total sum of the user cart
				$arrCartTotalWhere = [['user_id', $id], ['status', 'Pending'],['order_id',0]];
				$totalPrice = UserCartProduct::where($arrCartTotalWhere)->sum('total_price');			
				$arrData['total_price'] = $totalPrice;
		
             
               //$ss= EcommerceProduct::where('tbl_product_ecommerce.id','=', $arrInput['product_id'])->update(array('qty_minus'=>$arrInput['quantity'] + $product->qty_minus));
           // dd($arrInput['product_id'],$arrInput['quantity'],$ss);
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],$strMessage, $arrData);
			}
			else
			{
				

				$arrCartTotalWhere = [['user_id', $id], ['status', 'Pending'],['order_id',0],['product_id', $arrInput['product_id']]];
				UserCartProduct::where($arrCartTotalWhere)->delete();
				
				//EcommerceProduct::where('tbl_product_ecommerce.id','=', $arrInput['product_id'])->update(array('qty'=>$product->qty_minus + $arrInput['quantity']));
			
                /*BillingInfo::where([['order_id',$cartID]])->delete();
                ShippingAddress::where([['order_id'=>$cartID]])->delete();*/
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product removed successfully!', $arrData);
			}
		} catch (Exception $e) 
		{
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}

	/**
	 * Function to fetch the cart details
	 * 
	 * @param $request  : HTTP Request Object
	 * 
	 */ 
	public function getCartDetails(Request $request){
		

		$arrData = [];
		try {
			$id = Auth::User()->id;
			$url  = URL::to('/').'/uploads/products';
			$arrWhere = [['tbl_user_cart_product.status', 'Pending'], ['tp.status_product', 'Active'], ['user_id', $id], ['order_id', 0]];
			$userCart = UserCartProduct::selectRaw('tp.name, tp.cost, tp.description,tbl_user_cart_product.quantity, tbl_user_cart_product.price, tbl_user_cart_product.total_price,tp.attachment,product_id,tbl_user_cart_product.id as cart_id,(CASE WHEN tp.attachment IS NOT NULL THEN CONCAT("'.$url.'","/",tp.attachment) ELSE "" END) as attachment,tbl_user_cart_product.discount')
						->join('tbl_product as tp', 'tp.id', '=', 'tbl_user_cart_product.product_id')
						->where($arrWhere);
			$totalRecord  = $userCart->count();
			if($totalRecord > 0 ){
				$data  = $userCart->get();
				$arrCartTotalWhere = [['user_id', $id], ['status', 'Pending'], ['order_id', 0]];
				$query = UserCartProduct::where($arrCartTotalWhere);
				$query ->sum('discount');
				$arrData['records'] = $data;
				$arrData['total_price'] = $query ->sum('total_price');
				$arrData['total_discount'] = $query ->sum('discount');;
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $arrData);
		    } else {
		    	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Data not found', $arrData);
		    }
		} catch (Exception $e) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}

	/**
	 * Function to fetch the cart details
	 * 
	 * @param $request  : HTTP Request Object
	 * 
	 */ 
	public function getEcommerceCartDetails(Request $request){
		$arrData = [];
		try {
			$id = Auth::User()->id;
			$url  = URL::to('/').'/uploads/products';
			$arrWhere = [['tbl_user_cart_product.status', 'Pending'], ['tp.status_product', 'Active'], ['user_id', $id], ['order_id', 0]];
			$pro=ProjectSetting::select('coin_rate')->where('id','=',1)->first();
           $coin_rate=$pro->coin_rate;
			$userCart = UserCartProduct::selectRaw('country.currency_code,country.usd_to_other_curr,country.currency_symbol,tp.name,tp.country_id, tp.cost, tp.description,tbl_user_cart_product.quantity, tbl_user_cart_product.price, tbl_user_cart_product.total_price,tbl_user_cart_product.product_id,tbl_user_cart_product.id as cart_id,tbl_user_cart_product.discount,tbl_user_cart_product.coupon_amount as coupon_amount,tbl_user_cart_product.is_same_address,tpv.variation_price as cost, tpv.variation_price,tpv.variation_value,tpv.id as variation_id,'.$coin_rate.' as coin_rate')

						->join('tbl_product_ecommerce as tp', 'tp.id', '=', 'tbl_user_cart_product.product_id')
						->join('tbl_product_variation_details as tpv', 'tpv.id', '=', 'tbl_user_cart_product.variation_id')
						->join('country_wize_currency as country', 'country.code', '=', 'tp.country_id')
						//->join('product_img_ecommerce as pimg', 'pimg.pid', '=', 'tp.id')
						->where($arrWhere);
			$totalRecord  = $userCart->count();
			if($totalRecord > 0 ){
				$data  = $userCart->get();
				$arrCartTotalWhere = [['user_id', $id], ['status', 'Pending'], ['order_id', 0]];
				$query = UserCartProduct::where($arrCartTotalWhere);
				$query ->sum('discount');
				$arrData['records'] = $data;
				$arrData['total_records'] = count($data);
				$arrData['total_price'] = $query ->sum('total_price');
				$arrData['total_discount'] = $query ->sum('discount');
				$fquery =  $query->first();
				$arrData['coupon_amount'] = $fquery->coupon_amount;
				$arrData['coupon_code'] = $fquery->coupon_code;
				$arrData['is_same_address'] = $fquery->is_same_address;

				foreach ($arrData['records'] as $key => $value) 
	            {
	                $variationData = ProductVariation::where([['id',$value->variation_id],['status','Active']])->first();
	                if(!empty($variationData))
	                {
	                	$value->no_of_pins_available = $variationData->no_of_pins_available;
	                }
	                else
	                {
	                	$value->no_of_pins_available = 0;
	                }
	            }

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
	 * Function to remove product from cart
	 * 
	 * @param $request : HTTP Request HTTP
	 * 
	 */
	public function removeProductFromCart(Request $request){
		$arrData = [];
		try {
			$arrInput = $request->all();
			$arrRules = ['product_id' => 'required','variation_id'=>'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
			$arrWhere = [['product_id', $arrInput['product_id']], ['variation_id', $arrInput['variation_id']], ['status', 'Pending'],['order_id',0]];
			$cartProduct = UserCartProduct::where($arrWhere)->first();

			//dd($cartProduct);
			if(empty($cartProduct)){
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product is not in cart', $arrData);
			} else{
				$cartProduct->status = 'Deleted';
				$cartProduct->delivery_status = 'Cancel';
				$cartProduct->save();

				   $old=EcommerceProduct::select('qty_minus','qty')->where('id','=', $arrInput['product_id'])->first();
				   $ss =EcommerceProduct::where('id', $cartProduct->product_id)
	        		->update(array('qty'=>$old->qty + $cartProduct->quantity));


				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product removed from cart', $arrData);
			}
		} catch (Exception $e) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}  

	/**
	 * Function to get the ordre history
	 * 
	 * @param $request : HTTP Request Object
	 * 
	 */
	public function orderHistory(Request $request) {
		$arrData = [];
		try {
			$id = Auth::user()->id;
			$arrWhere = [['tbl_user_cart_order.user_id', $id],['tucp.user_id', $id]];
			$cartOrders = UserCartOrder::select('tbl_user_cart_order.*',DB::RAW('UPPER(tbl_user_cart_order.payment_mode) as payment_mode'),'tp.name as product_name','tp.country_id as country_id', DB::RAW('sum(tucp.total_price)as total_price'),'country.currency_code','country.currency_symbol')
							->join('tbl_user_cart_product as tucp','tucp.order_id','=','tbl_user_cart_order.id')
							->join('tbl_product_ecommerce as tp','tp.id','=','tucp.product_id')
							->join('country_wize_currency as country','country.code','=','tp.country_id')
							->groupBy('tbl_user_cart_order.order_id')
							->orderBy('tbl_user_cart_order.entry_time','desc')
							->where($arrWhere)
							->get();

				 //dd($cartOrders[0]);			
			if(count($cartOrders) > 0 ){
				$arrData['records'] = $cartOrders;
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $arrData);
		    } else {
		    	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Data not found', $arrData);
		    }
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}

	public function addToWishlist(Request $request)
	{
		$arrData = [];
		try 
		{
			$arrInput = $request->all();
			$arrRules = ['product_id' => 'required','variation_id'=>'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
			// check for current product is in cart or not
			$id = Auth::User()->id;

			$arrProductWhere  = [['tbl_product_ecommerce.id', $arrInput['product_id']], ['tpv.id', $arrInput['variation_id']], ['tbl_product_ecommerce.status_product', 'Active']];
			$product = EcommerceProduct::join('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')->where($arrProductWhere)->first();
			if(empty($product)){
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product not found', []);
			}


			$arrWhere = [['product_id', $arrInput['product_id']],['user_id',$id]]; 
			$wishlist  = Wishlist::where($arrWhere)->first();
			if(empty($wishlist)) {
				$wishlist = new Wishlist;
				$wishlist->product_id = $arrInput['product_id'];
				$wishlist->variation_id = $arrInput['variation_id'];
				$wishlist->user_id = $id;
				$wishlist->entry_time  = $this->today;
				$wishlist->save();

				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product added to wishlist', '');
			} 
			else
			{
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product is already in wishlist', '');
			}
		} catch (Exception $e) 
		{
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}

	public function removeFromWishlist(Request $request){
		$arrData = [];
		try {
			$id = Auth::user()->id;
			$arrInput = $request->all();
			$arrRules = ['product_id' => 'required','variation_id'=>'required'];
			$validator = Validator::make($arrInput, $arrRules);
	        if ($validator->fails()) {
	            $message = $validator->errors();
	            $err = '';
	            foreach ($message->all() as $error) {
	                $err = $err . " " . $error;
	            }
	            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
	        }
			$arrWhere = [['product_id', $arrInput['product_id']],['variation_id', $arrInput['variation_id']],['user_id',$id]];
			$wishlist = Wishlist::where($arrWhere)->first();
			if(empty($wishlist))
			{
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product already removed from wishlist', $arrData);
			} 
			else
			{
				$delete_wishlist = Wishlist::where($arrWhere)->delete();
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product removed from wishlist', $arrData);
			}
		} catch (Exception $e) {
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}

	public function getWishlist(Request $request){
		$arrInput = $request->all();
		$url = url('public/uploads/products');
		$arrData = [];
		try {
			$id = Auth::User()->id;
			$arrWhere = [['user_id', $id]];
			$query = Wishlist::selectRaw('tbl_wishlist.*,tp.name, tp.cost, tp.description,tpv.id as variation_id,tpv.variation_value,tpv.variation_price,tpv.variation_price as cost,tp.image')
						->join('tbl_product_ecommerce as tp', 'tp.id', '=', 'tbl_wishlist.product_id')
						->join('tbl_product_variation_details as tpv', 'tpv.id', '=', 'tbl_wishlist.variation_id')
						->where($arrWhere);
			$totalRecord  = $query->count();
			if($totalRecord > 0 )
			{
				if(isset($request->start) && isset($request->length))
				{
					$data  = $query->skip($arrInput['start'])->take($arrInput['length'])->get();
				}
				else
				{
					$data = $query->get();
				}
				$filteredRecord = count($data);
	            $arrData['recordsTotal']    = $totalRecord;
	            $arrData['recordsFiltered'] = $filteredRecord;
	            $arrData['records']         = $data;

	            foreach ($arrData['records'] as $key => $value) 
	            {
	                $variationData = ProductVariation::where([['id',$value->variation_id],['status','Active']])->first();
	                if(!empty($variationData))
	                {
	                	$value->no_of_pins_available = $variationData->no_of_pins_available;
	                }
	                else
	                {
	                	$value->no_of_pins_available = 0;
	                }
	            }
	            
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $arrData);
		    } else {
		    	return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Data not found', $arrData);
		    }
		} catch (Exception $e) {
			dd($e);
			return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
		}
	}
}
