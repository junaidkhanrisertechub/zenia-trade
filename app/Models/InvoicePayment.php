<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    protected $table = 'invoice_payments';

    protected $primaryKey = 'transaction_hash';

    protected $guarded = [];

    public $timestamps = false;
}
