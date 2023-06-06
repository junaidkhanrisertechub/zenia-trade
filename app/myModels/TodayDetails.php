<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TodayDetails extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_today_details';

    /**
     * [$primaryKey description]
     * 
     * @var string
     */
    protected $primaryKey = 'today_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];
    public $timestamps = false; 

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}