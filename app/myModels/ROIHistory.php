<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ROIHistory extends Model
{
	/**
	 * [$table description]
	 * @var string
	 */
    protected $table = "tbl_change_roi_history";

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

}
