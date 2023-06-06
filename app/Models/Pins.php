<?php

namespace App\Models;
use App\Models\Pins as Pins1;

use Illuminate\Database\Eloquent\Model;

class Pins extends Model
{
    protected $table = "tbl_pins";

    protected $guarded = [];

    public $timestamps = false;

     public function getUsedByUsersAttribute()
    {  //dd($this->product_id);
    	
        $users = Pins1::select('used_by','tu11.fullname','tu11.user_id')
        ->join('tbl_users as tu11','tu11.id','=','tbl_pins.used_by')
        ->where('tbl_pins.product_id', '=', $this->product_id)->get();

        //dd($users);

        return $users;
       
       
    }

 // protected $appends = ['used_by_users'];
}
