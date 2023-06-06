<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseBalanceTransfer extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_purchase_balance_transfer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 
}

