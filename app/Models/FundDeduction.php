<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundDeduction extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_deduction_stat';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 
}

