<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChainBusinessIncome extends Model
{
    protected $table = 'tbl_chain_business_bonus_income';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','income', 'user_id','chain_business_id','l_bv','r_bv','entry_time'
    ];
    public $timestamps = false; 
}
