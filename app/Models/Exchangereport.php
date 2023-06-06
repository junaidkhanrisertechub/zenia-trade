<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchangereport extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_exchange_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'srno','id', 'coin','currency_id','transction_fees','btc','usd_rate','btc_rate','status'
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
