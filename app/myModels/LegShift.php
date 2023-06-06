<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class LegShift extends Model
{
   /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_leg_shift';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];
    public $timestamps = false; 

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];
}
