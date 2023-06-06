<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelincomeIco extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_level_income_ico';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','amount','tax_amount','amt_pin','level','toUserId','fromUserId','status'
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
