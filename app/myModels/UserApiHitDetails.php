<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApiHitDetails extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_user_api_hit_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 
}

