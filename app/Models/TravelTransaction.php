<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelTransaction extends Model
{
	/**
	 * [$table description]
	 * @var string
	 */
    protected $table = "tbl_travel_transaction";

    /**
     * [$guarded description]
     * @var array
     */
    protected $guarded = [];

    /**
     * [$hidden description]
     * @var array
     */
    protected $hidden = [];

    public $timestamps = false;


}
