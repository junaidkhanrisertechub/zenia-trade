<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyReport extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_buy_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
   // public $timestamps = false; 
}

