<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddPowerToParticularId extends Model
{
    protected $table = 'tbl_addPower_to_levels';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','from_power_id','up_to_id','position','amount','cron_status','entry_time'
    ];
}
