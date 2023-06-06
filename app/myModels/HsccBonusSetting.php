<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsccBonusSetting extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_hscc_bonus_setting';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    
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
