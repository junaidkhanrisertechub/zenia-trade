<?php

namespace App\Http\Controllers\adminapi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\adminapi\CommonController;
use App\Models\Category;
use App\Models\EcommerceProduct;
use App\Models\ProductImgEcommerce;
use App\Models\Product;
use App\Models\Topup;
use App\Models\BinaryIncome;
use App\Models\User;
use App\Models\ROIHistory;
// use App\Models\ChangeHistory;
use App\Models\Pins;
// use App\Models\Activitynotification;
use App\Models\ProductVariation;
use App\Models\SubCategories;
use App\Models\FaqSubCategory;
use App\Models\AddFaq;
use App\Models\SettingGallery;

// use App\Models\SubmenuVeriation;
// use App\Traits\AllIncomes;
use DB;
use Config;
use Illuminate\Support\Facades\Validator;
use Auth;
use File;
use URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Input;

class EcommerceProductController extends Controller
{
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode,$constants_settings,$commonController;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CommonController $CommonController) {
        $this->statuscode           = Config::get('constants.statuscode');
        $this->constants_settings   = Config::get('constants.settings');
        $this->commonController     = $CommonController;
        $date = \Carbon\Carbon::now();
        $this->today = $date->toDateTimeString();
    }
    /**
     * get all EcommerceProduct list
     *
     * @return void
     */
    public function getEcommerceProducts(Request $request) {
        $url = url('uploads/products');

        $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tu.user_id as seller_id','tu.fullname as seller_name','tpv.variation_value','tpv.variation_price','tpv.variation_price as cost','tpv.id as variation_id')
                 ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                 ->leftjoin('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id')
                 ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id');
        if(isset($request->type) && $request->type == 'Seller')
        {
            $query = $query->where('tbl_product_ecommerce.seller_user_id',Auth::user()->id);
        }
        if(isset($request->id))
        {
            $query  = $query->where('tbl_product_ecommerce.id', $request->id);
        }
        if(isset($request->cost)){
            $query  = $query->where('tbl_product_ecommerce.cost', $request->cost);
        }
        if(isset($request->status)){
            $query  = $query->where('tbl_product_ecommerce.status_product', $request->status);
        }
        if(isset($request->admin_status)){
            $query  = $query->where('tbl_product_ecommerce.admin_status', $request->admin_status);
        }
        if(isset($request->b_value)){
            $query  = $query->where('tbl_product_ecommerce.bvalue', $request->b_value);
        }
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_product_ecommerce.created_at,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['name','cost','bvalue','status_product'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_product_ecommerce.'.$field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query = $query->orderBy('tbl_product_ecommerce.entry_time','desc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
            foreach ($data as $key => $value) 
            {

                $variationData = ProductVariation::where([['id',$value->variation_id],['status','Active']])->first();
                if(!empty($variationData))
                {
                    $value->no_of_pins_available = $variationData->no_of_pins_available;
                    $data[$key]->no_of_pins_available = $value->no_of_pins_available;
                }
                else
                {
                    $data[$key]->no_of_pins_available = 0;
                }
            }
        }else{
            $data = setPaginate($query,$request->start,$request->length);

            foreach ($data['record'] as $key => $value) 
            {
                $variationData = ProductVariation::where([['id',$value->variation_id],['status','Active']])->first();
                if(!empty($variationData))
                {
                    $value->no_of_pins_available = $variationData->no_of_pins_available;
                    $data['record'][$key]->no_of_pins_available = $value->no_of_pins_available;
                }
                else
                {
                    $data['record'][$key]->no_of_pins_available = 0;
                }
            }
        }

        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Ecommerce Products found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Ecommerce Products not found', '');
        }
    } 

    public function getMainEcommerceProducts(Request $request) {
        // $url = url('uploads/products');
        $query = EcommerceProduct::select('tbl_product_ecommerce.*','tc.name as category_name','tu.user_id as seller_id','tu.fullname as seller_name')
                 ->join('tbl_categories as tc','tc.id','=','tbl_product_ecommerce.category_id')
                 ->leftjoin('tbl_users as tu','tu.id','=','tbl_product_ecommerce.seller_user_id');
                 // ->leftjoin('tbl_product_variation_details as tpv','tpv.product_id','=','tbl_product_ecommerce.id');
        if(isset($request->type) && $request->type == 'Seller')
        {
            $query = $query->where('tbl_product_ecommerce.seller_user_id',Auth::user()->id);
        }
        if(isset($request->id))
        {
            $query  = $query->where('tbl_product_ecommerce.id', $request->id);
        }
        if(isset($request->cost)){
            $query  = $query->where('tbl_product_ecommerce.cost', $request->cost);
        }
        if(isset($request->status)){
            $query  = $query->where('tbl_product_ecommerce.status_product', $request->status);
        }
        if(isset($request->admin_status)){
            $query  = $query->where('tbl_product_ecommerce.admin_status', $request->admin_status);
        }
        if(isset($request->b_value)){
            $query  = $query->where('tbl_product_ecommerce.bvalue', $request->b_value);
        }
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_product_ecommerce.created_at,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['name','cost','bvalue','status_product'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere('tbl_product_ecommerce.'.$field,'LIKE','%'.$search.'%');
                }
            });
        }

        $query = $query->orderBy('tbl_product_ecommerce.entry_time','desc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
            foreach ($data as $key => $value) 
            {

                $variationData = ProductVariation::where([['id',$value->variation_id],['status','Active']])->first();
                if(!empty($variationData))
                {
                    $value->no_of_pins_available = $variationData->no_of_pins_available;
                    $data[$key]->no_of_pins_available = $value->no_of_pins_available;
                }
                else
                {
                    $data[$key]->no_of_pins_available = 0;
                }
            }
        }else{
            $data = setPaginate($query,$request->start,$request->length);

            foreach ($data['record'] as $key => $value) 
            {
                $variationData = ProductVariation::where([['id',$value->variation_id],['status','Active']])->first();
                if(!empty($variationData))
                {
                    $value->no_of_pins_available = $variationData->no_of_pins_available;
                    $data['record'][$key]->no_of_pins_available = $value->no_of_pins_available;
                }
                else
                {
                    $data['record'][$key]->no_of_pins_available = 0;
                }
            }
        }
        // dd($data);

        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Ecommerce Products found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Ecommerce Products not found', '');
        }
    } 

    public function getEcommerceProductsImg(Request $request) {
        $url = url('uploads/products');

         $arrInput = $request->all();

        $rules = array(
            'pid'              => 'required',
          
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } 

        $arrInput=$request->all();
        $query = ProductImgEcommerce::select('product_img_ecommerce.*','product_img_ecommerce.status as t','tp.name')
                 ->join('tbl_product_ecommerce as tp','product_img_ecommerce.pid','=','tp.id');
     
        $query = $query->orderBy('tp.entry_time','desc');
        
         if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
            //searching loops on fields
            $fields = ['tp.name'];
            $search = $arrInput['search']['value'];
            $query = $query->where(function ($query) use ($fields, $search) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });

        }

         $query->Where('product_img_ecommerce.pid', '=', $arrInput['pid']);
     //  dd($query->get());
     //   $query = $query->orderBy('tbl_users.entry_time', 'desc');
        $data = setPaginate($query, $request->start, $request->length);
        // dd($data);
        if (!empty($data['totalRecord'])) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found Successful!', $data);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
        }
        
     // / dd(22);
        
    }
    public function productImgStatus(Request $request){
        $arrData = [];
        try {
            $arrInput = $request->all();
            $arrRules = ['id' => 'required', 'status' => 'required'];
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                }
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
            }
            $changeOrderStatus = ProductImgEcommerce::where('id', $arrInput['id'])->update(['status' => $arrInput['status']]);

           // dd(11,$changeOrderStatus,$arrInput['id']);
            if ($changeOrderStatus) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product '.$arrInput['status'].' successfully', $arrData);
            }
        } catch (Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
        }
    }

    public function change_product_status(Request $request){
        $arrData = [];
        try {
            $arrInput = $request->all();
            $arrRules = ['id' => 'required', 'status' => 'required'];
            $validator = Validator::make($arrInput, $arrRules);
            if ($validator->fails()) {
                $message = $validator->errors();
                $err = '';
                foreach ($message->all() as $error) {
                    $err = $err . " " . $error;
                }
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err, '');
            }
            $changeOrderStatus = EcommerceProduct::whereIn('id', $request['id'])->update(['admin_status' => $request['status'],'admin_status_changed_remark'=>$request->remark,'admin_status_changed_date'=>now(),'status_product'=>'Active']);
            if ($changeOrderStatus) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product '.$request->status.' successfully', $arrData);
            }
        } catch (Exception $e) {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong', $arrData);
        }
    }
    /**
     * store EcommerceProduct
     *
     * @return void
     */
    public function storeEcommerceProduct(Request $request){
        $arrInput = $request->all();

if($request->hasFile('files')) {
                /*if(!File::exists($path)){
                    File::makeDirectory($path, 0775, true);
                }*/
                foreach($request->file('files') as $key=>$file)
                {
                    list($width, $height) = getimagesize($file);

                  if($width==263 && $height==349){


                     }else{

                 /*    return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], ' image width: 263
height: 349','');*/
                     }
                }

            }


        $rules = array(
           // 'name'              => 'required|unique:tbl_product_ecommerce',
            'name'              => 'required',
            // 'cost'              => 'required_if:variation,No',
            'description'       => 'required',
            'category_id'       => 'required',
            'country_id'       => 'required',
            'files'              => 'required',
            'variation'         => 'required',
           // 'brand_name'        => 'required',
            //'manufacturer'      => 'required',
           // 'mpn'               => 'required',
            'qty'               => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            if(isset($request->variation) && $request->variation == 'No')
            {
                if($request->cost <= 0)
                {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product price must be greater than 0','');
                }
            }
            if(isset($request->variation) && $request->variation != 'No')
            {
                $variation_arr = json_decode($request->variation_values,true);
                foreach ($variation_arr as $key => $value) 
                {
                    if(empty($value['value']))
                    {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $request->variation.' '.($key+1).' is required','');
                    }
                    if(empty($value['price']))
                    {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Price for '.$value['value'].' is required','');
                        
                    }
                }
                
            }

               $path   = public_path('uploads/products');
            $fileName   = null;
            $fileNameArr = '';
   
            $user = Auth::user();
            if($user->type == 'Admin')
            {
                $admin_status = 'Approved';
                $status = 'Active';
            }
            else
            {
                $admin_status = 'Pending';
                $status = 'Inactive';
            }
            
            $arrInsert = [
                'name'              => $arrInput['name'],
               //'tag'              => $arrInput['tag'],
               'qty'              => $arrInput['qty'],
                // 'cost'              => $arrInput['cost'],
                'image'             => $fileNameArr,
                'description'       => $arrInput['description'],
                'category_id'       => $arrInput['category_id'],
                'sub_category_id'   => $arrInput['sub_category_id']==null?0:$arrInput['sub_category_id'],
                'submenu_variation'   => $arrInput['submenu_variation']==null?0:$arrInput['submenu_variation'],
                'country_id'       => $arrInput['country_id'],
               // 'manufacturer'      => $arrInput['manufacturer'],
                //'mpn'               => $arrInput['mpn'],
                //'brand_name'        => $arrInput['brand_name'],
                'variation'         => $arrInput['variation'],
                'admin_status'      => $admin_status,
                'status_product'    => $status,
                'seller_user_id'    => $user->id,
                'entry_time'        => $this->today,
            ];

            // dd($variation['price']);
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'insert');
            $storeId = EcommerceProduct::insertGetId($arrInsert);
          //$storeId=  DB::table('tbl_product_ecommerce')->get();

           // dd($storeId);


         
            if($request->hasFile('files')) {
                /*if(!File::exists($path)){
                    File::makeDirectory($path, 0775, true);
                }*/
                foreach($request->file('files') as $key=>$file)
                {
                    /*$file       = Input::file('file');
                    //$fileName   = time().'.'.$file->getClientOriginalExtension();
                   //file->move($path, $fileName);
                   // $fileNameArr .= $fileName;
                    if(($key+1) == count($request->file('files')))
                    {

                    }
                    else
                    {
                        $fileNameArr .= ",";
                    }*/
                        
                         $url    = Config::get('constants.settings.aws_url');
                      //$fileName = Storage::disk('s3')->put("saqcessshoppings3", $request->file('file'), "public");
                      $fileName = Storage::disk('s3')->put("product_images", $file, "public");
                        //getEcommerceProductsImg

                        $newUrl=$url.$fileName;
                         //dd($newUrl);
                                  
                        $arr['pid']=$storeId;
                        $arr['img_url']=$newUrl;
                        $update = ProductImgEcommerce::insert($arr);

                     /*$url    = Config::get('constants.settings.aws_url');
                      $fileName = Storage::disk('s3')->put("projectname", $request->file('file'), "public");

                      //$fileName = Storage::disk('s3')->put("product_images", $file, "public");  //i comment
                        //getEcommerceProductsImg
                        //$fileName = "abc";
                        $newUrl=$url.$fileName;
                        // dd($newUrl);
                                  
                        $arr['pid']=$storeId;
                        // $arr['img_url']=$newUrl;
                        $arr['img_url']=$fileName;
                        $update = ProductImgEcommerce::insert($arr);*/
                }
            }

            if(!empty($storeId))
            {
                if(isset($request->variation) && $request->variation != 'No')
                {
                    $variation_arr = json_decode($request->variation_values,true);
                    foreach($variation_arr as $variation)
                    {
                        $arrVariationInsert = [
                            'product_id'            => $storeId,
                            'variation'             => $arrInput['variation'],
                            'variation_value'       => $variation['value'],
                            'variation_mrp_cost'    => $variation['mrp_cost'],
                            'variation_price'       => $variation['price'],
                            'status'                => 'Active',
                        ];
                        $insertVariation = ProductVariation::insertGetId($arrVariationInsert);
                    }
                }
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Ecommerce Product added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding Ecommerce Product', '');
            }
        }
    }
           // dd(234);
    /**
     * update EcommerceProduct
     *
     * @return void
     */
    public function updateEcommerceProduct(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'                => 'required'
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            if(isset($request->variation) && $request->variation == 'No')
            {
                if($request->cost <= 0)
                {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product price must be greater than 0','');
                }
            }

            $product = EcommerceProduct::where('id',$arrInput['id'])->first();
            if(empty($product))
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product not found','');
            }

              $arrInput['sub_category_id']=($arrInput['sub_category_id']==null)?0:$arrInput['sub_category_id'];

            $name         = isset($arrInput['name']) ? $arrInput['name'] : $product->name;
            $cost         = isset($arrInput['cost']) ? $arrInput['cost'] : $product->cost;
            $description  = isset($arrInput['description']) ? $arrInput['description'] : $product->description;
            $category_id  = isset($arrInput['category_id']) ? $arrInput['category_id'] : $product->category_id;
            $sub_category_id  = isset($arrInput['sub_category_id']) ? $arrInput['sub_category_id'] : $product->sub_category_id;
            $submenu_variation  = isset($arrInput['submenu_variation']) ? $arrInput['submenu_variation'] : $product->submenu_variation;
            $country_id  = isset($arrInput['country_id']) ? $arrInput['country_id'] : $product->country_id;
            $manufacturer = isset($arrInput['manufacturer']) ? $arrInput['manufacturer'] : $product->manufacturer;
            $mpn          = isset($arrInput['mpn']) ? $arrInput['mpn'] : $product->mpn;
            $brand_name   = isset($arrInput['brand_name']) ? $arrInput['brand_name'] : $product->brand_name;
            $variationInput    = isset($arrInput['variation']) ? $arrInput['variation'] : $product->variation;
            $tag    = isset($arrInput['tag']) ? $arrInput['tag'] : $product->tag;
            $qty    = isset($arrInput['qty']) ? $arrInput['qty'] : $product->qty + $arrInput['qty'];

            // $path   = public_path('uploads/products');
            $fileName   = null;
            $arrUpdate = [
                'name'              => $name,
               // 'tag'              => $tag,
                'qty'              => $qty,
                // 'cost'              => $cost,
                'description'       => $description,
                'category_id'       => $category_id,
                'submenu_variation'       => $submenu_variation,
                'sub_category_id'       => $sub_category_id,
                'country_id'       => $country_id,
                //'manufacturer'      => $manufacturer,
               // 'mpn'               => $mpn,
               // 'brand_name'        => $brand_name,
                'variation'         => $variationInput,
            ];
            if($request->hasFile('files')) {
                $fileNameArr = '';
                // if(!File::exists($path)){
                //     File::makeDirectory($path, 0775, true);
                // }
                foreach($request->file('files') as $key=>$file)
                {
                    // $file       = Input::file('file');
                    // $fileName   = time().'.'.$file->getClientOriginalExtension();
                    // $file->move($path, $fileName);
                    // $fileNameArr .= $fileName;
                    if(($key+1) == count($request->file('files')))
                    {

                    }
                    else
                    {
                        $fileNameArr .= ",";
                    }
                }
                $arrUpdate['image'] = $fileNameArr;
            }
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'update');
           
            $update = EcommerceProduct::where('id',$arrInput['id'])->update($arrUpdate);

            if(1) 
            {

                
                $active_variation_ids = array();
                $delete_variation_ids = array();
                if(isset($product->variation) && $product->variation != 'No')
                {

                    $variation_arr = json_decode($request->variation_values,true);
                    foreach($variation_arr as $variation)
                    {
                        array_push($active_variation_ids,$variation['id']);
                        if($variation['id']!='' || !empty($variation['id']))
                        {
                            $arrVariationUpdate = array();
                            $arrVariationUpdate = [
                                'variation'       => $variationInput,
                                'variation_value' => $variation['value'],
                                'variation_price' => $variation['price'],
                                'variation_mrp_cost' => $variation['mrp_cost']
                            ];
                            $updateVariation = ProductVariation::where('id',$variation['id'])->update($arrVariationUpdate);
                        }
                        else
                        {
                            $arrVariationInsert = array();
                            $arrVariationInsert = [
                                'product_id'      => $arrInput['id'],
                                'variation'       => $variationInput,
                                'variation_value' => $variation['value'],
                                'variation_price' => $variation['price'],
                                'variation_mrp_cost' => $variation['mrp_cost'],
                                'status'          => 'Active',
                            ];
                            $insertVariation = ProductVariation::insertGetId($arrVariationInsert);
                        }
                    }
                    $delete_variation_ids = ProductVariation::where('product_id',$arrInput['id'])->whereNotIn('id',$active_variation_ids)->pluck('id');
                }
                else if($request->variation=='No')
                {
                    $delete_variation_ids = ProductVariation::where('product_id',$arrInput['id'])->pluck('id');
                }
                $delete_variation = ProductVariation::where('product_id',$arrInput['id'])->whereIn('id',$delete_variation_ids)->update(['status'=>'Inactive']);

                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Ecommerce Product updated successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating Ecommerce Product', '');
            }
        }
    }  

    public function addProductImages(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'                => 'required',
            'file'                => 'required',
            //'file'                => 'reqired'
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
           

          
           
           // $tag    = isset($arrInput['tag']) ? $arrInput['tag'] : $product->tag;

            //$path   = public_path('uploads');
            $path   = 'uploads/products/'.time().$_FILES["file"]["name"];
            $fileName   = null;
            $arrUpdate = [
                

            ]; 

            $url    = Config::get('constants.settings.aws_url');

   
         $fileName = Storage::disk('s3')->put("product_images", $request->file('file'), "public");
           // $fileName = "null"; //added by me
            //getEcommerceProductsImg

            $newUrl=$url.$fileName;
        
       // if (move_uploaded_file($_FILES["file"]["tmp_name"],$path)) {

            if($fileName){
       
            $arr['pid']=$arrInput['id'];
            $arr['img_url']=$newUrl;
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'update');
           
            $update = ProductImgEcommerce::insert($arr);

             if($update){
                 return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Ecommerce Product image updated successfully', '');
             
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating Ecommerce Product', '');
            }
      } else {
         return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating Ecommerce Product', '');
      }

           //dd($arrInput,$request,$request->hasFile('file'));
          /*  if($request->hasFile('file')) {
               //dd(11);
                $fileNameArr = '';
                if(!File::exists($path)){
                    File::makeDirectory($path, 0775, true);
                }
                foreach($request->file('file') as $key=>$file)
                {
                    // $file       = Input::file('file');
                    $fileName   = time().'.'.$file->getClientOriginalExtension();
                    $file->move($path, $fileName);
                    //file
                    $fileNameArr .= $fileName;
                    if(($key+1) == count($request->file('file')))
                    {

                    }
                    else
                    {
                        $fileNameArr .= ",";
                    }
                }
                $arrUpdate['image'] = $fileNameArr;
            }*/
            //dd($arrUpdate);


            
        }
    }
    /**
     * delete EcommerceProduct
     *
     * @return void
     */
    public function deleteEcommerceProduct(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'   => 'required',
          //  'remember_token'    => 'required'
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $product = EcommerceProduct::where([['id',$arrInput['id']],['admin_status','Approved']])->first();
            if(empty($product))
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Product is already Inactive', '');
            }

            $status = EcommerceProduct::where([['id',$arrInput['id']],['admin_status','Approved']])->pluck('status_product')->first();
            if($status == 'Active')
            {
                $update_status = 'Inactive';
                $return_msg = 'deleted';
            }
            else
            {
                $update_status = 'Active';
                $return_msg = 'restored';
            }
            $updateArr = array();
            $updateArr['status_product'] = $update_status;
            $delete = EcommerceProduct::where('id',$arrInput['id'])->update($updateArr);
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product '.$return_msg.' successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleting Product', '');
            }
        }
    }
    /**
     * edit news
     * @param
     * @return \Illuminate\Http\Response
     */
    public function editEcommerceProducts(Request $request)
    {
        // $url = url('uploads/products');
        $arrInput = $request->all();
        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $editNews = EcommerceProduct::where('id',$arrInput['id'])->first();
            if(!empty($editNews))
            {
                $variation_values = ProductVariation::select('id','variation_value as value','variation_price as price','variation_mrp_cost as mrp_cost')->where([['product_id',$arrInput['id']],['status','Active']])->get();
                if(count($variation_values)<=0)
                {
                    $emptyArr = [['id'=>'','value'=>'','price'=>'','mrp_cost'=>'']];
                    $variation_values = $emptyArr;
                }
                $editNews['variation_values'] = $variation_values;
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$editNews);
            }
            else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'No record available','');
            }
        }
    }
     public function getgallerysetting(Request $request) 
     {
       
        $query = SettingGallery::select('name','description','photo');
        if(isset($request->id)){
            $query  = $query->where('id', $request->id);
        }
        if(isset($request->name)){
            $query  = $query->where('name', $request->name);
        }
       
       
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['name'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }

        $query = $query->orderBy('name','desc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }
        // dd($data);
        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Gallery Setting found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Gallery Setting not found', '');
        }
    }


     public function getEcommercefaqCategories(Request $request) {
       
        $query = FaqSubCategory::select('tbl_faq_category.category_id','tbl_faq_category.faq_category');
        if(isset($request->category_id)){
            $query  = $query->where('category_id', $request->category_id);
        }
        if(isset($request->faq_category)){
            $query  = $query->where('faq_category', $request->faq_category);
        }
       
       
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['faq_category'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }

        $query = $query->orderBy('faq_category','desc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }
        // dd($data);
        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Categories found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Product Categories not found', '');
        }
    }

    public function getEcommerceProductCategories(Request $request) {
        // $url = url('uploads/categories');

        // $query = Category::select('tbl_categories.*',DB::RAW('CONCAT("'.$url.'","/",image) as image'));
        $query = Category::select('tbl_categories.id','tbl_categories.name','tbl_categories.status','tbl_categories.entry_time');
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
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
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

        $query = $query->orderBy('entry_time','desc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }
        // dd($data);
        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Categories found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Product Categories not found', '');
        }
    }

    public function getEcommerceProductTopup(Request $request) {
        // $url = url('uploads/categories');

        $arrInput = $request->all();

        // $query = Category::select('tbl_categories.*',DB::RAW('CONCAT("'.$url.'","/",image) as image'));
        $query = Topup::join('tbl_users as tu', 'tu.id', '=', 'tbl_topup.id')->select('tbl_topup.id','tbl_topup.pin','tbl_topup.amount','tbl_topup.entry_time','tu.user_id as user_id','tu.fullname as fullname');
        if(isset($request->id)){
            $query  = $query->where('tu.user_id', $request->id);
        }
        if(isset($request->pin)){
            $query  = $query->where('tbl_topup.pin', $request->pin);
        }






        // dd($query->toSql());
        // if(isset($request->name)){
        //     $query  = $query->where('name', $request->name);
        // }
        // if(isset($request->status)){
        //     $query  = $query->where('status', $request->status);
        // }
        // if(isset($request->frm_date) && isset($request->to_date)) {
        //     $query  = $query->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        // }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['user_id','tbl_topup.amount','tbl_topup.entry_time'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }

        $query = $query->orderBy('tbl_topup.entry_time','desc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }
        // dd($data);
        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Categories found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Product Categories not found', '');
        }
    }


    public function getSubCategories(Request $request) {

        $query = SubCategories::select('tbl_sub_categories.*','tbl_categories.name as category_name')
         ->join('tbl_categories','tbl_sub_categories.cat_id','=','tbl_categories.id');
        if(isset($request->cat_id)){
            $query  = $query->where('tbl_sub_categories.cat_id', $request->cat_id);
        }
        if(isset($request->id)){
            $query  = $query->where('tbl_sub_categories.id', $request->id);
        }
        if(isset($request->name)){
            $query  = $query->where('tbl_sub_categories.name', $request->name);
        }
        if(isset($request->status)){
            $query  = $query->where('tbl_sub_categories.status', $request->status);
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
        $query = $query->orderBy('entry_time','desc');
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

    //----getSubmenuVariation

    public function getSubmenuVariation(Request $request) {

        $query = SubmenuVeriation::select('tbl_submenu_veriation.*','tbl_sub_categories.name as sub_category_name')
         ->join('tbl_sub_categories','tbl_submenu_veriation.s_cat_id','=','tbl_sub_categories.id');
        if(isset($request->s_cat_id)){
          //  dd(22);
            $query  = $query->where('tbl_submenu_veriation.s_cat_id', $request->s_cat_id);
        }
        if(isset($request->id)){
            $query  = $query->where('tbl_submenu_veriation.id', $request->id);
        }
        if(isset($request->name)){
            $query  = $query->where('tbl_submenu_veriation.name', $request->name);
        }
        if(isset($request->status)){
            $query  = $query->where('tbl_submenu_veriation.status', $request->status);
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
        $query = $query->orderBy('tbl_submenu_veriation.entry_time','desc');
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
    /**
     * store EcommerceProduct
     *
     * @return void
     */
    public function storeEcommerceProductCategory(Request $request){
        try {

            $arrInput = $request->all();

        $rules = array(
            'name'              => 'required|unique:tbl_categories',
           
           // 'image'             => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $path   = public_path('uploads/categories');
            $fileName   = null;
          /*  if($request->hasFile('image')) {
                if(!File::exists($path)){
                    File::makeDirectory($path, 0775, true);
                }
                $file       = Input::file('image');
                $fileName   = time().'.'.$file->getClientOriginalExtension();
                $file->move($path, $fileName);
            }*/
            $arrInsert = [
                'name'              => $arrInput['name'],
                'image'             => $fileName,
                'entry_time'        => $this->today,
            ];
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_categories", $request, 'insert');
            $storeId = Category::insertGetId($arrInsert);
            if(!empty($storeId)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Category added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding Product Category', '');
            }
        }

            
        } catch (Exception $e) {

          dd($e);
;
            
        }
     
    }
     /**
     * store EcommerceProduct
     *
     * @return void
     */
    public function addSubCat(Request $request){
        try {

            $arrInput = $request->all();

        $rules = array(
           // 'name'              => 'required',
            'name'              => 'required|unique:tbl_sub_categories',
            'cat_id'              => 'required',
           // 'image'             => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
          
          
            $arrInsert = [
                'name'              => $arrInput['name'],
                'cat_id'             => $arrInput['cat_id'],
                'entry_time'        => $this->today,
            ];
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_categories", $request, 'insert');
            $storeId = SubCategories::insertGetId($arrInsert);
            if(!empty($storeId)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Category added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding Product Category', '');
            }
        }

            
        } catch (Exception $e) {


            
        }
     
    }     /**
     * store EcommerceProduct
     *
     * @return void
     */
    public function addSubmenuVariation(Request $request){
        try {

            $arrInput = $request->all();

        $rules = array(
           // 'name'              => 'required',
            'name'              => 'required|unique:tbl_submenu_veriation',
            'cat_id'              => 'required',
           // 'image'             => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
         //   dd($arrInput);
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
          
          
            $arrInsert = [
                'name'              => $arrInput['name'],
                's_cat_id'             => $arrInput['cat_id'],
                'entry_time'        => $this->today,
            ];
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_categories", $request, 'insert');
            $storeId = SubmenuVeriation::insertGetId($arrInsert);
            if(!empty($storeId)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Category added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding Product Category', '');
            }
        }

            
        } catch (Exception $e) {


            
        }
     
    } 

        /**
     * store EcommerceProduct
     *
     * @return void
     */
    public function updateSubmenuVariation(Request $request){
        try {

            $arrInput = $request->all();

        $rules = array(
            'name'              => 'required',
            'cat_id'              => 'required',
           // 'image'             => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
          
          
            $arrInsert = [
                'name'              => $arrInput['name'],
                's_cat_id'             => $arrInput['cat_id'],
                'entry_time'        => $this->today,
            ];
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_categories", $request, 'insert');
            $update = SubmenuVeriation::where('id',$arrInput['id'])->update($arrInsert);
            // /dd($update);
            if($update==1){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Category added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding Product Category', '');
            }
        }

            
        } catch (Exception $e) {

             dd($e);
            
        }
     
    }
        /**
     * store EcommerceProduct
     *
     * @return void
     */
    public function updateSubCat(Request $request){
        try {

            $arrInput = $request->all();

        $rules = array(
            'name'              => 'required',
            'cat_id'              => 'required',
           // 'image'             => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
          
          
            $arrInsert = [
                'name'              => $arrInput['name'],
                'cat_id'             => $arrInput['cat_id'],
                'entry_time'        => $this->today,
            ];
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_categories", $request, 'insert');
            $update = SubCategories::where('id',$arrInput['id'])->update($arrInsert);
            // /dd($update);
            if($update==1){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Category added successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while adding Product Category', '');
            }
        }

            
        } catch (Exception $e) {

             dd($e);
            
        }
     
    }
    /**
     * update EcommerceProduct
     *
     * @return void
     */
    public function updateEcommerceProductCategory(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'                => 'required',
            'name'              => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            // $path   = public_path('uploads/categories');
            $fileName   = null;
            $arrUpdate = [
                'name'              => $arrInput['name'],
            ];
            if($request->hasFile('image')) {
                // if(!File::exists($path)){
                //     File::makeDirectory($path, 0775, true);
                // }
                $file       = Input::file('image');
                // $fileName   = time().'.'.$file->getClientOriginalExtension();
                // $file->move($path, $fileName);
                $arrUpdate['image'] = $fileName;
            }
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_categories", $request, 'update');

            $update = Category::where('id',$arrInput['id'])->update($arrUpdate);
            if(!empty($update)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Category updated successfully', '');
            }
            else if(empty($update)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'No changes in category', '');
            }
            else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating Product Category', '');
            }
        }
    }

    public function updateEcommerceProductTopup(Request $request){
        $arrInput = $request->all();
        $actual_date = $arrInput['entry_time_actual'];
        $rules = array(
            'entry_time'                => 'required',
            
              );
            $date=$arrInput['entry_time'];
            $statMonth = trim(preg_replace('/\s*\([^)]*\)/', '', $date));
            $new_date  = date('Y-m-d H-i-s', strtotime($statMonth));
            $arrUpdate = ['entry_time' => $new_date];
            $todaydate=date('Y-m-d H-i-s');
            // dd($todaydate);

        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } 
        else{

           if($new_date<=$actual_date){

            $update = Topup::where('id',$arrInput['id'])->update($arrUpdate);
            // dd($update);
        
            if(!empty($update)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Topup date updated successfully', '');
            }
            else if(empty($update)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'No changes in topup', '');
            }
            else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating Topup Date', '');
            }

           }  else {
               return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Date is greater than actual date','');
            }
        }
    }

    public function updateCronDates(Request $request){
        try {
            $arrInput = $request->all();
            $rules = array(
                'entry_time'                => 'required',
                'pin'                       => 'required',
                'cron_type'                => 'required',
                
            );
                $date=$arrInput['entry_time'];
                $statMonth = trim(preg_replace('/\s*\([^)]*\)/', '', $date));
                $new_date  = date('Y-m-d H-i-s', strtotime($statMonth));
                $arrUpdate = ['entry_time' => $new_date];
                $todaydate=date('Y-m-d H-i-s');
                // dd($todaydate);

            $validator = Validator::make($arrInput, $rules);
            //if the validator fails, redirect back to the form
            if ($validator->fails()) {
                $message = messageCreator($validator->errors());
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $message,'');
            } 
            else{
                if ($arrInput['cron_type']=='optimized_roi_static') {
                    $checktopupExists = Topup::where([['pin', $arrInput['pin']]])->count('srno');
                    if (!empty($checktopupExists)) {
                        $get_topup_info = Topup::where('pin',$arrInput['pin'])->select('entry_time')->orderBy('srno', 'desc')->first();
                        $current_entry_date=date('Y-m-d H-i-s', strtotime($get_topup_info->entry_time));
                        if ($new_date<=$current_entry_date) {
                            $arrTopupUpdate=array();
                            $arrTopupUpdate = [
                                'entry_time' => $new_date,
                                'last_roi_entry_time'=>$new_date,
                            ];
                            $update = Topup::where('pin',$arrInput['pin'])->update($arrTopupUpdate);

                            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Date Updated successfully','');

                        } else {

                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Date is greater than actual date','');
                        }
                        
                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'No such deposit ID or pin exists for the given cron','');
                    }
                    
                }
                if ($arrInput['cron_type']=='daily_optimized_binary_income') {
                   $checkBinaryIncomeExists = BinaryIncome::where([['pin', $arrInput['pin']]])->count('id');
                    if (!empty($checkBinaryIncomeExists)) {
                        $get_binary_income_info = BinaryIncome::where('pin',$arrInput['pin'])->select('entry_time')->orderBy('id', 'desc')->first();
                        $current_income_entry_date=date('Y-m-d H-i-s', strtotime($get_binary_income_info->entry_time));
                        if ($new_date<=$current_income_entry_date) {
                            $arrBinaryUpdate=array();
                            $arrBinaryUpdate = [
                                'entry_time' => $new_date,
                            ];
                            $updateBinary = BinaryIncome::where('pin',$arrInput['pin'])->update($arrBinaryUpdate);
                            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Date Updated successfully','');
                            
                            
                        } else {

                            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Date is greater than actual date','');
                        }
                        
                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'No such deposit ID or pin exists for the given cron','');
                    } 
                }
                
            }
            
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'], 'Server Error', '');
        }
    }
    /**
     * delete EcommerceProduct
     *
     * @return void
     */
    public function deleteSubCat(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'   => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {

            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'delete');
            $status = SubCategories::where('id',$arrInput['id'])->pluck('status')->first();
            if($status == 'Active')
            {
                $update_status = 'Inactive';
                $return_msg = 'deleted';
            }
            else
            {
                $update_status = 'Active';
                $return_msg = 'restored';
            }
            $updateArr = array();
            $updateArr['status'] = $update_status;
            $delete = SubCategories::where('id',$arrInput['id'])->update($updateArr);
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Category '.$return_msg.' successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleting Product Category', '');
            }
        }
    }  /**
     * delete EcommerceProduct
     *
     * @return void
     */
    public function deleteSubmenuViriation(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'   => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {

            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'delete');
            $status = SubmenuVeriation::where('id',$arrInput['id'])->pluck('status')->first();
            if($status == 'Active')
            {
                $update_status = 'Inactive';
                $return_msg = 'deleted';
            }
            else
            {
                $update_status = 'Active';
                $return_msg = 'restored';
            }
            $updateArr = array();
            $updateArr['status'] = $update_status;
            $delete = SubmenuVeriation::where('id',$arrInput['id'])->update($updateArr);
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Category '.$return_msg.' successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleting Product Category', '');
            }
        }
    }/**
     * delete EcommerceProduct
     *
     * @return void
     */
    public function deleteEcommerceProductCategory(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'   => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {

            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'delete');
            $status = Category::where('id',$arrInput['id'])->pluck('status')->first();
            if($status == 'Active')
            {
                $update_status = 'Inactive';
                $return_msg = 'deleted';
            }
            else
            {
                $update_status = 'Active';
                $return_msg = 'restored';
            }
            $updateArr = array();
            $updateArr['status'] = $update_status;
            $delete = Category::where('id',$arrInput['id'])->update($updateArr);
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Category '.$return_msg.' successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleting Product Category', '');
            }
        }
    }
    public function deletegallery(Request $request){
        $arrInput = $request->all();
        // dd($arrInput);
        $rules = array(
            'id'   => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {

            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'delete');
            $status = SettingGallery::where('id',$arrInput['id'])->pluck('status')->first();
            if($status == 'Active')
            {
                $update_status = 'Inactive';
                $return_msg = 'deleted';
            }
            else
            {
                $update_status = 'Active';
                $return_msg = 'restored';
            }
            $updateArr = array();
            $updateArr['status'] = $update_status;
            $delete = SettingGallery::where('id',$arrInput['id'])->update($updateArr);
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Setting Gallery '.$return_msg.' successfully', '');
            } 
            else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleting Setting Gallery', '');
            }
        }
    }

    public function deleteFAQCategory(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'   => 'required',
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {

            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'delete');
            $status = AddFaq::where('id',$arrInput['id'])->pluck('status')->first();
            if($status == 'Active')
            {
                $update_status = 'Inactive';
                $return_msg = 'deleted';
            }
            else
            {
                $update_status = 'Active';
                $return_msg = 'restored';
            }
            $updateArr = array();
            $updateArr['status'] = $update_status;
            $delete = AddFaq::where('id',$arrInput['id'])->update($updateArr);
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Product Category '.$return_msg.' successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleting Product Category', '');
            }
        }
    }
    /**
     * edit news
     * @param
     * @return \Illuminate\Http\Response
     */
    public function editEcommerceProductCategory(Request $request){
        $arrInput = $request->all();

        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $url = url('uploads/categories');
            $editNews = Category::select('tbl_categories.*',DB::RAW('CONCAT("'.$url.'","/",image) as image'))->where('id',$arrInput['id'])->first();
            if(!empty($editNews)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$editNews);
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'No record available','');
            }
        }
    }
    public function editEcommerceProductTopup(Request $request){
        $arrInput = $request->all();
        
        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            
            $editNews = Topup::select('id','pin','entry_time')->where('id',$arrInput['id'])->first();
            // dd($editNews->entry_time);
            if(!empty($editNews)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$editNews);
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'No record available','');
            }
        }
    }


        /**
     * edit news
     * @param
     * @return \Illuminate\Http\Response
     */
    public function editSubCategory(Request $request){
        $arrInput = $request->all();

        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $url = url('uploads/categories');
            $editNews = SubCategories::select('*')->where('id',$arrInput['id'])->first();
            if(!empty($editNews)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$editNews);
            }else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'No record available','');
            }
        }
    }

    public function getCountries(Request $request) {

        $query = DB::table('tbl_country_new')->select('*');
        $query = DB::table('country_wize_currency')->select('*')->where('status','=',1);
        if(isset($request->id)){
            $query  = $query->where('country_id', $request->id);
        }
        if(isset($request->name)){
            $query  = $query->where('country', $request->name);
        }
        // if(isset($request->status)){
        //     $query  = $query->where('status', $request->status);
        // }
        if(isset($request->frm_date) && isset($request->to_date)) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d')"),[date('Y-m-d',strtotime($request->frm_date)), date('Y-m-d',strtotime($request->to_date))]);
        }
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['country','country_id'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query = $query->orderBy('name','desc');
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }
        // dd($data);
        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Country found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Country not found', '');
        }
    }

    public function productVariationList(Request $request)
    {
        $arrInput = $request->all();
        // dd("hello");
        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $editNews = EcommerceProduct::where('id',$arrInput['id'])->first();
            $query = DB::table('tbl_product_variation_details')->select('*');
            if(isset($request->id)){
                $query  = $query->where('product_id', $request->id);
            }
            if(isset($request->name)){
                $query  = $query->where('variation_name', $request->name);
            }
            // if(isset($request->status)){
            //     $query  = $query->where('status', $request->status);
            // }
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
            // $query = $query->orderBy('entry_time','desc');
            if(!isset($request->start) || !isset($request->length)){
                $data = $query->get();
            }else{
                $data = setPaginate($query,$request->start,$request->length);
            }
            // dd($data);
            if(count($data)>0){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Country found', $data);
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Country not found', '');
            }
        }
    }

    public function deleteProductVariation(Request $request){
        $arrInput = $request->all();

        $rules = array(
            'id'   => 'required',
          //  'remember_token'    => 'required'
        );
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            
            $status = DB::table('tbl_product_variation_details')->where([['id',$arrInput['id']],['status','Active']])->pluck('status')->first();
            if($status == 'Active')
            {
                $update_status = 'Inactive';
                $return_msg = 'deleted';
            }
            else
            {
                $update_status = 'Active';
                $return_msg = 'restored';
            }
            $updateArr = array();
            $updateArr['status'] = $update_status;
            $delete = DB::table('tbl_product_variation_details')->where('id',$arrInput['id'])->update($updateArr);
            if(!empty($delete)){
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Variation '.$return_msg.' successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while deleting Variation', '');
            }
        }
    }

    public function getProduct(Request $request) {
       
        // $query = DB::table('tbl_product')->select('*');
        $query = DB::table('tbl_product')->select('*');
        // dd($query);
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['package_type','package_name','roi','updated_at'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query = $query->orderBy('id','asc');
        
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }
        // dd($data);
        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Products found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Products not found', '');
        }
    } 

    public function getROIChange(Request $request) {
        $query = DB::table('tbl_change_roi_history')->select('*');
        if(isset($request->search['value']) && !empty($request->search['value'])){
            //searching loops on fields
            $fields = ['package_type','package_name','before_roi','after_roi','entry_time'];
            $search = $request->search['value'];
            $query  = $query->where(function ($query) use ($fields, $search){
                foreach($fields as $field){
                    $query->orWhere($field,'LIKE','%'.$search.'%');
                }
            });
        }
        $query = $query->orderBy('entry_time','desc');
        
        if(!isset($request->start) || !isset($request->length)){
            $data = $query->get();
        }else{
            $data = setPaginate($query,$request->start,$request->length);
        }
        // dd($data);
        if(count($data)>0){
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Data found', $data);
        } else {
            return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Data not found', '');
        }
    } 

    public function editManageProducts(Request $request)
    {
        
        $arrInput = $request->all();
        $rules = array('id' => 'required');
        $validator = Validator::make($arrInput, $rules);
        if($validator->fails()){
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        } else {
            $editNews = Product::where('id',$arrInput['id'])->first();
            if(!empty($editNews))
            {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found',$editNews);
            }
            else{
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'No record available','');
            }
        }
    }

    public function updateManageProduct(Request $request){
        $arrInput = $request->all();
       
        $rules = array(
            'id'                => 'required'
        );
        $productdata = Product::where('id',$arrInput['id'])->first();
        $validator = Validator::make($arrInput, $rules);
        //if the validator fails, redirect back to the form
        if ($validator->fails()) {
            $message = messageCreator($validator->errors());
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');
        }elseif($productdata->min_roi > $arrInput['roi'] || $arrInput['roi'] > $productdata->max_roi) {

            $message = 'Roi should be on range ' . $productdata->min_roi . ' to ' . $productdata->max_roi;
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], $message,'');

        } else {
            $product = Product::where('id',$arrInput['id'])->first();
            if(empty($product))
            {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Product not found','');
            }
            $roi         = isset($arrInput['roi']) ? $arrInput['roi'] : $product->roi;
            $arrUpdate = [
                'roi'              => $roi,
            ];
           
           
            /** @var [ins into Change History table] */
            // $this->commonController->storeChangeHistory($table = "tbl_product_ecommerce", $request, 'update');
           
            $update = Product::where('id',$arrInput['id'])->update($arrUpdate);

            if(1) 
            {   
                // $udtTopup = Topup::where('type',$productdata->id)->update([['amount_roi'=>DB::raw('((tbl_topup.amount*'.$roi.')/100)')],['percentage' => $roi]]);
                $udtTopup = Topup::where('type',$productdata->id)->update(array('amount_roi'=>DB::raw('((tbl_topup.amount*'.$roi.')/100)'),'percentage' => $roi));
                $arrRoiChange= [
                    'package_id'                => $productdata->id,
                    'package_name'              => $productdata->name,
                    'package_type'              => $productdata->package_type,
                    'before_roi'                => $productdata->roi,
                    'after_roi'                 => $roi,
                 ];
                 $ChangeRoi = ROIHistory::insert($arrRoiChange);
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],' Product updated successfully', '');
            } else {
                return sendresponse($this->statuscode[409]['code'], $this->statuscode[409]['status'],'Error occured while updating  Product', '');
            }
        }
    } 


}