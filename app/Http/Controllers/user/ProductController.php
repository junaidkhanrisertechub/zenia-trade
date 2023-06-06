<?php

namespace App\Http\Controllers\user;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response as Response;
use App\Models\Product;
use App\Models\EcommerceProduct;
use App\Models\ProductVariation;
use App\Models\Category;
use App\Models\SubCategories;
use App\Models\SubmenuVeriation;
use App\Models\Enquiry;
use Validator;
use Config;
use DB;
use Exception;
use PDOException;
use Auth;
use URL;
use DataTables;

class ProductController extends Controller {

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
    public function faqaddquestion (Request $request){
        dd($request);
        $arrData = [];
        try {
            $url    = Config::get('constants.settings.aws_url');
            $arrInput = $request->all();
            $arrWhere = [['status_product', 'Active']];
            // $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tpv.variation_value','tpv.variation_price','tpv.variation_price as cost','tpv.variation_mrp_cost','tpv.id as variation_id','tu.user_id as seller_id','tu.fullname as seller_name')
            // ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
            // ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
            // ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
            // ->where($arrWhere);
            $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tu.user_id as seller_id','tu.fullname as seller_name')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->where($arrWhere);

            if(isset($request->country) && !empty($request->country)){
                // / dd($request->country);
                $query = $query->where('tbl_product_ecommerce.country_id',$request->country);

            }

            if(isset($request->cat_id) && !empty($request->cat_id)){
                // / dd($request->country);
                $query = $query->where('tbl_product_ecommerce.category_id',$request->cat_id);

            }
            if(isset($request->category_id) && !empty($request->category_id) && is_numeric($request->category_id))
            {
                $query = $query->where('tbl_product_ecommerce.category_id',$request->category_id);
            }
            if(isset($request->seller_user_id) && !empty($request->seller_user_id))
            {
                $query = $query->where('tbl_product_ecommerce.seller_user_id',$request->seller_user_id);
            }

            if(isset($request->submenu_veriation) && !empty($request->submenu_veriation))
            {
                $arr=json_decode($request->submenu_veriation);
                if(count($arr)>0){
                    $query = $query->whereIn('tbl_product_ecommerce.submenu_variation',$arr);

                }

            }
            if(isset($request->variation) && !empty($request->variation))
            {
                $query = $query->where('tbl_product_ecommerce.variation',$request->variation);
            }
            if(isset($request->brand_name) && !empty($request->brand_name))
            {
                $query = $query->where('tbl_product_ecommerce.brand_name',$request->brand_name);
            }
            if(isset($request->manufacturer) && !empty($request->manufacturer))
            {
                $query = $query->where('tbl_product_ecommerce.manufacturer',$request->manufacturer);
            }
            if(isset($request->mpn) && !empty($request->mpn))
            {
                $query = $query->where('tbl_product_ecommerce.mpn',$request->mpn);
            }
            if(isset($request->searchtext) && !empty($request->searchtext) && $request->searchtext != ""){
                //dd($request->searchtext);
                //searching loops on fields
                $fields = ['tbl_product_ecommerce.tag','tbl_product_ecommerce.name','tbl_product_ecommerce.brand_name'];
                $search = $request->searchtext;
                $query  = $query->where(function ($query) use ($fields, $search){
                    foreach($fields as $field){
                        $query->orWhere($field,'LIKE','%'.$search.'%');
                    }
                });
            }
            if(isset($request->sort_by) && !empty($request->sort_by))
            {
                if(isset($request->sort_by_order) && !empty($request->sort_by_order))
                {
                    $sort_by_order = $request->sort_by_order;
                }
                else
                {
                    $sort_by_order = 'asc';
                }

                $sort_by = $request->sort_by;
                if($sort_by == 'date')
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
                else if($sort_by == 'price')
                {
                    $sort_by = 'tpv.variation_price';
                }
                else if($sort_by == 'name')
                {
                    $sort_by = 'tbl_product_ecommerce.name';
                }
                else
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
            }
            else
            {
                $sort_by = 'tbl_product_ecommerce.entry_time';
                $sort_by_order = 'desc';
            }
            $query = $query->orderBy($sort_by,$sort_by_order);
            //dd($query->toSql());

            $totalRecord  = $query->count();

            $start = ((isset($arrInput['start'])) && (!empty($arrInput['start']))) ? $arrInput['start'] : 0;
            $length = ((isset($arrInput['length'])) && (!empty($arrInput['length']))) ? $arrInput['length'] : $totalRecord;

            //dd($query->toSql());
            if($totalRecord > 0 )
            {


                $arrProductData  = $query->skip($start)->take($length)->get();
                $ProductData  = $query->first();
                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrProductData;
                // dd($ProductData->sub_category_id);

                $arrData['submenuVeriation'] = SubmenuVeriation::where('s_cat_id','=',$ProductData->sub_category_id)->get();


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
//4


    public function index(Request $request)
    {
        $data['title'] = 'Product List | HSCC';
        return view('user.product.index', compact('data'));
    }

    public function ecommerceProductList (Request $request){
        // dd($request);
        $arrData = [];
        try {
//            $url    = Config::get('constants.settings.aws_url');
            $arrInput = $request->all();
            $arrWhere = [['status_product', 'Active']];
            // $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tpv.variation_value','tpv.variation_price','tpv.variation_price as cost','tpv.variation_mrp_cost','tpv.id as variation_id','tu.user_id as seller_id','tu.fullname as seller_name')
            // ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
            // ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
            // ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
            // ->where($arrWhere);
            $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tu.user_id as seller_id','tu.fullname as seller_name')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->where($arrWhere);
            $data = DB::table('airports')->select('*')->get();


            return Datatables::of($data)
                ->addIndexColumn()
                ->make(true);

        } catch (Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
        }
    }
    public function getpopularProduct (Request $request){

        $arrData = [];
        try {
            $url    = Config::get('constants.settings.aws_url');
            $arrInput = $request->all();
            $arrWhere = [['status_product', 'Active']];
            $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tpv.variation_value','tpv.variation_price','tpv.variation_price as cost','tpv.id as variation_id','tpv.variation_mrp_cost','tu.user_id as seller_id','tu.fullname as seller_name','tu.store_name as seller_store_name')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
                ->where($arrWhere);


            $query = $query->where('tbl_product_ecommerce.category_id','=','4');



            if(isset($request->sort_by) && !empty($request->sort_by))
            {
                if(isset($request->sort_by_order) && !empty($request->sort_by_order))
                {
                    $sort_by_order = $request->sort_by_order;
                }
                else
                {
                    $sort_by_order = 'asc';
                }

                $sort_by = $request->sort_by;
                if($sort_by == 'date')
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
                else if($sort_by == 'price')
                {
                    $sort_by = 'tpv.variation_price';
                }
                else if($sort_by == 'name')
                {
                    $sort_by = 'tbl_product_ecommerce.name';
                }
                else
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
            }
            else
            {
                $sort_by = 'tbl_product_ecommerce.entry_time';
                $sort_by_order = 'desc';
            }
            $query = $query->orderBy($sort_by,$sort_by_order);

            $totalRecord  = $query->count();

            $start = ((isset($arrInput['start'])) && (!empty($arrInput['start']))) ? $arrInput['start'] : 0;
            $length = ((isset($arrInput['length'])) && (!empty($arrInput['length']))) ? $arrInput['length'] : $totalRecord;

            if($totalRecord > 0 )
            {
                //$arrProductData  = $query->skip($start)->take($length)->get();
                $arrProductData  = $query->skip($start)->take(6)->get();
                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrProductData;

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
// 12
    public function getDealsOfday (Request $request){

        $arrData = [];
        try {
            $url    = Config::get('constants.settings.aws_url');
            $arrInput = $request->all();
            $arrWhere = [['status_product', 'Active']];
            $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tpv.variation_value','tpv.variation_price','tpv.variation_price as cost','tpv.id as variation_id','tpv.variation_mrp_cost','tu.user_id as seller_id','tu.fullname as seller_name','tu.store_name as seller_store_name')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
                ->where($arrWhere);


            $query = $query->where('tbl_product_ecommerce.category_id','=','12');



            if(isset($request->sort_by) && !empty($request->sort_by))
            {
                if(isset($request->sort_by_order) && !empty($request->sort_by_order))
                {
                    $sort_by_order = $request->sort_by_order;
                }
                else
                {
                    $sort_by_order = 'asc';
                }

                $sort_by = $request->sort_by;
                if($sort_by == 'date')
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
                else if($sort_by == 'price')
                {
                    $sort_by = 'tpv.variation_price';
                }
                else if($sort_by == 'name')
                {
                    $sort_by = 'tbl_product_ecommerce.name';
                }
                else
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
            }
            else
            {
                $sort_by = 'tbl_product_ecommerce.entry_time';
                $sort_by_order = 'desc';
            }
            $query = $query->orderBy($sort_by,$sort_by_order);

            $totalRecord  = $query->count();

            $start = ((isset($arrInput['start'])) && (!empty($arrInput['start']))) ? $arrInput['start'] : 0;
            $length = ((isset($arrInput['length'])) && (!empty($arrInput['length']))) ? $arrInput['length'] : $totalRecord;

            if($totalRecord > 0 )
            {
                //$arrProductData  = $query->skip($start)->take($length)->get();
                $arrProductData  = $query->skip($start)->take(6)->get();
                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrProductData;

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

//3
    public function getnewCollection (Request $request){

        $arrData = [];
        try {
            $url    = Config::get('constants.settings.aws_url');
            $arrInput = $request->all();
            $arrWhere = [['status_product', 'Active']];
            $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tpv.variation_value','tpv.variation_price','tpv.variation_mrp_cost','tpv.variation_price as cost','tpv.id as variation_id','tu.user_id as seller_id','tu.fullname as seller_name','tu.store_name as seller_store_name')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
                ->where($arrWhere);


            $query = $query->where('tbl_product_ecommerce.category_id','=','3');



            if(isset($request->sort_by) && !empty($request->sort_by))
            {
                if(isset($request->sort_by_order) && !empty($request->sort_by_order))
                {
                    $sort_by_order = $request->sort_by_order;
                }
                else
                {
                    $sort_by_order = 'asc';
                }

                $sort_by = $request->sort_by;
                if($sort_by == 'date')
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
                else if($sort_by == 'price')
                {
                    $sort_by = 'tpv.variation_price';
                }
                else if($sort_by == 'name')
                {
                    $sort_by = 'tbl_product_ecommerce.name';
                }
                else
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
            }
            else
            {
                $sort_by = 'tbl_product_ecommerce.entry_time';
                $sort_by_order = 'desc';
            }
            $query = $query->orderBy($sort_by,$sort_by_order);

            $totalRecord  = $query->count();

            $start = ((isset($arrInput['start'])) && (!empty($arrInput['start']))) ? $arrInput['start'] : 0;
            $length = ((isset($arrInput['length'])) && (!empty($arrInput['length']))) ? $arrInput['length'] : $totalRecord;

            if($totalRecord > 0 )
            {
                //$arrProductData  = $query->skip($start)->take($length)->get();
                $arrProductData  = $query->skip($start)->take(6)->get();
                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrProductData;

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

    // 11
    public function getbestSeller (Request $request){

        $arrData = [];
        try {
            $url    = Config::get('constants.settings.aws_url');
            $arrInput = $request->all();
            $arrWhere = [['status_product', 'Active']];
            $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tpv.variation_value','tpv.variation_price','tpv.variation_price as cost','tpv.id as variation_id','tpv.variation_mrp_cost','tu.user_id as seller_id','tu.fullname as seller_name','tu.store_name as seller_store_name')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
                ->where($arrWhere);


            $query = $query->where('tbl_product_ecommerce.category_id','=','11');



            if(isset($request->sort_by) && !empty($request->sort_by))
            {
                if(isset($request->sort_by_order) && !empty($request->sort_by_order))
                {
                    $sort_by_order = $request->sort_by_order;
                }
                else
                {
                    $sort_by_order = 'asc';
                }

                $sort_by = $request->sort_by;
                if($sort_by == 'date')
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
                else if($sort_by == 'price')
                {
                    $sort_by = 'tpv.variation_price';
                }
                else if($sort_by == 'name')
                {
                    $sort_by = 'tbl_product_ecommerce.name';
                }
                else
                {
                    $sort_by = 'tbl_product_ecommerce.entry_time';
                }
            }
            else
            {
                $sort_by = 'tbl_product_ecommerce.entry_time';
                $sort_by_order = 'desc';
            }
            $query = $query->orderBy($sort_by,$sort_by_order);

            $totalRecord  = $query->count();

            $start = ((isset($arrInput['start'])) && (!empty($arrInput['start']))) ? $arrInput['start'] : 0;
            $length = ((isset($arrInput['length'])) && (!empty($arrInput['length']))) ? $arrInput['length'] : $totalRecord;

            if($totalRecord > 0 )
            {
                //$arrProductData  = $query->skip($start)->take($length)->get();
                $arrProductData  = $query->skip($start)->take(6)->get();
                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrProductData;

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
// 11
    public function getRelatedProducts (Request $request){

        $arrData = [];
        try {
            $url    = Config::get('constants.settings.aws_url');
            $arrInput = $request->all();
            $arrWhere = [['status_product', 'Active']];
            $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tpv.variation_value','tpv.variation_price','tpv.variation_price as cost','tpv.variation_mrp_cost','tpv.id as variation_id','tu.user_id as seller_id','tu.fullname as seller_name')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
                ->where($arrWhere);





            if(isset($request->product_id) && !empty($request->product_id))
            {
                $product_id = $request->product_id;


                $first= EcommerceProduct::select('id','category_id')->where('tbl_product_ecommerce.id','=',$product_id)->first();
                //dd($first,$product_id);
                if(!empty($first)){
                    $query = $query->where('tbl_product_ecommerce.category_id','=',$first->category_id);


                }
            }

            $sort_by = 'tbl_product_ecommerce.entry_time';
            $sort_by_order = 'desc';

            $query = $query->orderBy($sort_by,$sort_by_order);

            $totalRecord  = $query->count();

            $start = ((isset($arrInput['start'])) && (!empty($arrInput['start']))) ? $arrInput['start'] : 0;
            $length = ((isset($arrInput['length'])) && (!empty($arrInput['length']))) ? $arrInput['length'] : $totalRecord;

            if($totalRecord > 0 )
            {
                //$arrProductData  = $query->skip($start)->take($length)->get();
                $arrProductData  = $query->skip($start)->take(6)->get();
                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrProductData;

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
     * Function to get the product detail
     *
     * @param $request : HTTP Request Object
     *
     */
    public function ecommerceProductDetail(Request $request){
        $arrData = [];
        try {
            $arrInput = $request->all();
            $arrRules = ['product_id' => 'required'];
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                }
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
            }

            // $arrWhere = [['tbl_product_ecommerce.id', $arrInput['product_id']], ['tbl_product_ecommerce.status_product', 'Active'],['tpv.id',$request->variation_id]];
            $arrWhere = [['tbl_product_ecommerce.id', $arrInput['product_id']], ['tbl_product_ecommerce.status_product', 'Active']];
            $product  = EcommerceProduct::select('currency.currency_symbol','currency.name as country','currency.currency_code','currency.usd_to_other_curr','tbl_product_ecommerce.*','tc.name as category_name','tpv.id as variation_id','tpv.variation_value','tpv.variation_mrp_cost','tpv.variation_price','tpv.variation_price as cost','cat.is_mob_filed')
                ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id')
                ->join('tbl_categories as cat', 'cat.id', '=', 'tbl_product_ecommerce.category_id')
                //->join('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                ->join('country_wize_currency as currency','currency.code','=','tbl_product_ecommerce.country_id')
                ->where($arrWhere)->first();


            if(!empty($product))
            {
                //$usd=DB::table('country_wize_currency')->select('usd_to_other_curr')->where('',$product->country_id)->first();

                $variationData = ProductVariation::where([['product_id',$product->id],['status','Active']])->get();
                $product->variation_data = $variationData;
                //$product->usd_to_other_curr = $usd->usd_to_other_curr;
                if(count($variationData) > 0)
                {
                    $variationData = ProductVariation::where([['id',$request->variation_id],['status','Active']])->first();
                    if(!empty($variationData))
                    {
                        $product->no_of_pins_available = $variationData->no_of_pins_available;
                    }
                    else
                    {
                        $product->no_of_pins_available = 0;
                    }
                }

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $product);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Data not found', $arrData);
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
        }
    }

    public function ecommerceProductCategories(Request $request) {
        $url = url('uploads/categories');
        $query = Category::select('tbl_categories.*',DB::RAW('CONCAT("'.$url.'","/",image) as image'));
        if(isset($request->id)){
            $query  = $query->where('id', $request->id);
        }
        if(isset($request->name)){
            $query  = $query->where('name', $request->name);
        }
        if(isset($request->status)){
            $query  = $query->where('status', $request->status);
        }
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['name','status'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query = $query->orderBy('name','asc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }

        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Categories found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Product Categories not found', '');
        }
    }

    public function getCategoriesWithQuantity(Request $request) {
        $url = url('uploads/categories');
        $query = Category::select('tbl_categories.*',DB::RAW("COUNT(tbl_product_ecommerce.id) as product_qty"))
            ->join('tbl_product_ecommerce','tbl_product_ecommerce.category_id','=','tbl_categories.id')
            ->groupBy('tbl_categories.id');


        $query = $query->orderBy('tbl_categories.name','asc');
        if(!isset($request->start) || !isset($request->length)){
            //dd(22,$request->start ,$request->length);
            // $data = $query->get();
            $data = setPaginate($query,1,10);
        }else{
            //dd(3322);
            $data = setPaginate($query,$request->start,$request->length);
        }

        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Categories found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Product Categories not found', '');
        }
    }


    public function getCatWithSub(Request $request) {

        $cat = Category::select('*')
            ->where('status','=','Active')
            ->get();

        $allCat= $cat->toArray();



        for ($i=0; $i < count($allCat) ; $i++) {

            // echo $allCat[$i]['id'];

            $sub=SubCategories::
            where('tbl_sub_categories.cat_id','=',$allCat[$i]['id'])
                ->where('status','=','Active')
                ->get();
            if(count($sub->toArray())>0){

                $allCat[$i]['subcat']=$sub->toArray();
                $allCat[$i]['sub']=true;

            }else{
                //  dd(2);
                //$cat['subcat']=$sub->toArray();
                $allCat[$i]['subcat']=$sub->toArray();
                $allCat[$i]['sub']=false;
            }
            # code...
        }

        //dd($allCat);

        // $query = $query->orderBy('tbl_categories.name','asc');


        if(count($cat)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Categories found', $allCat);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Product Categories not found', '');
        }
    }


    /**
     * get state by country
     *
     * @return \Illuminate\Http\Response
     */
    public function enquiry(Request $request) {
        try {
            $rules = array('email' => 'required','subject' => 'required','mobile' => 'required','message' => 'required','fullname'=>'required');
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                }
            }

            $arrInput=$request->all();
            $countries = Enquiry::insert($arrInput);

            if ($countries > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Enquiry message successfully send!', $countries);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Not found', '');
            }
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Something went wrong,Please try again', '');
        }
    }
}
