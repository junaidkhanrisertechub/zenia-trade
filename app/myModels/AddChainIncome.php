<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddChainIncome extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_chain_business_bonus_income';

    
    public $timestamps = false; 

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}