<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlightBookingTempInfo extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_flight_temp_book_info';
    protected $casts = [
        'childArr' => 'array','adultArr' => 'array','booking_data' => 'array',
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    public $timestamps = false; 

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}