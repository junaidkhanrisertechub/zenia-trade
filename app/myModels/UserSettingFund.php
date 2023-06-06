<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSettingFund extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_user_setting_fund';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 
}

