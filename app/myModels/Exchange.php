<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_exchange_order';
     public function getBtcVolumeAttribute()
    {
        return $this->btc_rate*$this->total_quantity;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    protected $appends = ['btc_volume'];
   // public $timestamps = false; 
}

