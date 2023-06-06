<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pins;
use DB;
use Auth;

class UserCartOrder extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_user_cart_order';
    public $timestamps = false;

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
}