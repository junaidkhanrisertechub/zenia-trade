<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteListIpAddress extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_white_list_ip_address';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 
}

