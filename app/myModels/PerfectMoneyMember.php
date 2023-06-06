<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfectMoneyMember extends Model
{
    protected $table = 'tbl_perfectmoney_member';

    protected $fillable = ['member','receiver','passkey','entry_time'];

    public $timestamps = false;
}
