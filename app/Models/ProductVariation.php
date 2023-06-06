<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pins;
use DB;

class ProductVariation extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_product_variation_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    
    public $timestamps = false; 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */

    public function getNoOfPinsAvailableAttribute() 
    {
        $no_of_pins_available = Pins::where('variation_id',$this->id);
        /*if(Auth::check()){
            $no_of_pins_available =$no_of_pins_available->where('user_id',Auth::user()->id);
        }*/
        $no_of_pins_available =$no_of_pins_available->where('status','Active')->count();
        return $no_of_pins_available;
    }

    protected $hidden = [];

    protected $appends = ['no_of_pins_available'];
}
