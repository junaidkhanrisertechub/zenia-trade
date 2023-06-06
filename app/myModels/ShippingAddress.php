<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pins;
use DB;
use Auth;

class ShippingAddress extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_shipping_address';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function getImagesAttribute() 
    {
        $imageArr = DB::table('product_img_ecommerce')
                           ->select('img_url')
                           ->where([['pid', '=', $this->product_id],['status','Active']])
                           ->get();
        $finalImageArr = array();
        foreach($imageArr as $img)
        {
            array_push($finalImageArr,$img->img_url);
        }
        return $finalImageArr;
    }

    protected $appends = ['images'];
}