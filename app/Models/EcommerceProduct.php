<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pins;
use App\Models\ProductImgEcommerce;
use DB;
use Auth;

class EcommerceProduct extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_product_ecommerce';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];
    /**
     * timestamps false
     *
     * @var array
     */
    public $timestamps = false; 

    public function getUserCartCountAttribute() {  
        $sum_total_price = DB::table('tbl_user_cart_product')
                           ->selectRaw('COUNT(*) as user_cart_count')
                           ->where([['product_id', '=', $this->id],['status','Pending']])
                           ->first();

        return $sum_total_price->user_cart_count;
    }

    public function getImagesAttribute() 
    {
        $imageArr = DB::table('product_img_ecommerce')
                           ->select('img_url')
                           ->where([['pid', '=', $this->id],['status','Active']])
                           ->get();
        $finalImageArr = array();
        foreach($imageArr as $img)
        {
            array_push($finalImageArr,$img->img_url);
        }
        return $finalImageArr;
    }

    /**
     * append no of avalible pins by product from tbl_pins
     *
     * @var array
     */
    /*public function getNoOfPinsAvailableAttribute() 
    {
        
        $no_of_pins_available = Pins::where('product_id',$this->id);
        if(Auth::check()){
            $no_of_pins_available =$no_of_pins_available->where('user_id',Auth::user()->id);
        }
        $no_of_pins_available =$no_of_pins_available->where('status','Active')
                                ->count();

        return $no_of_pins_available;
    }*/
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    protected $appends = ['user_cart_count','images'];
}
