<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressTransactionPending extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_deposit_address_transaction_pending';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'srno','transaction_hash', 'address','confirmation','value','btc','usd','entry_time','id','remark','confirm_remark','confirm_date','notification','ip_address','status'
    ];
    public $timestamps = false; 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
