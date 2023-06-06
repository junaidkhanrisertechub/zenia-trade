<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopupIco extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_topup_ico';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','user_id', 'pin','amount','percentage','type','top_up_by','roi_status','top_up_type','binary_pass_status','usd_rate'
    ];
    public $timestamps = false; 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
