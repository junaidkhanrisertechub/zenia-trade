<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TodaySummary extends Model
{
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_today_phase_summary';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    //public $timestamps = false; 
}

