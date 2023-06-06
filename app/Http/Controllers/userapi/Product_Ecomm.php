<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pins;
use DB;
use Auth;

class Product_Ecomm extends Model
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

    public function getJoiningCountAttribute() {  
        $sum_total_price = DB::table('tbl_user_cart')
                           ->selectRaw('SUM(total_price) as joining_count')
                           ->where('pin_request_id', '=', $this->pin_request_id)
                           ->first();

        return $sum_total_price->joining_count;
    }
    /**
     * append no of avalible pins by product from tbl_pins
     *
     * @var array
     */
    public function getNoOfPinsAvailableAttribute() {

        $no_of_pins_available = Pins::where('product_id',$this->id)
                                ->where('user_id',Auth::user()->id)
                                ->where('status','Active')
                                ->count();

        return $no_of_pins_available;
    }
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    protected $appends = ['joining_count','no_of_pins_available'];
}
