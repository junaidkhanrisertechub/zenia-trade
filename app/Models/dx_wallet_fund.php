<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class dx_wallet_fund extends Model
{
   //
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_dxwallet_remove_fund';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 
}
