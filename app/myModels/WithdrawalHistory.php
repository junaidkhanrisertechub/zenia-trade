<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalHistory extends Model
{
    protected $table = "tbl_withdrwal_history";

    protected $primaryKey = "id";

    protected $guarded = [];

    public $timestamps = false;
}
