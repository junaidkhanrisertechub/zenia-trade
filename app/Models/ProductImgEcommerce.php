<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pins;
use DB;
use Auth;

class  ProductImgEcommerce extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'product_img_ecommerce';

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

   // protected $appends = ['user_cart_count','images'];
}
