<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddRemoveRankPower extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_add_remove_rank_business';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        '	user_id' , 'rank' , 'position' , '	type' , 'before_bv' , 'power_bv' , 'after_bv' , 'remark' , 'entry_time'
    ];
    public $timestamps = false; 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}