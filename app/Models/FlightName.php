<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightName extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_flight_name';
         
         /*protected $casts = [
        'childArr' => 'array','adultArr' => 'array','booking_data' => 'array','description' => 'array'];*/
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];

public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}