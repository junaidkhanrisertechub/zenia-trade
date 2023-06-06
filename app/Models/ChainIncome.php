<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChainIncome extends Model
{
    protected $table = 'tbl_chain_income';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','chain_income', 'user_id','chain_setting_id','entry_time'
    ];
    public $timestamps = false; 
}
