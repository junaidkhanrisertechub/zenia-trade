<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddFunds extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_add_funds';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'srno','id','network_type','network_type_value','remark','entry_time','status','created_by','fund_type'
    ];
    public $timestamps = false; 

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}