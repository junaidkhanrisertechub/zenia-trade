<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Awards extends Model
{
	/**
	 * [$table description]
	 * @var string
	 */
    protected $table = "tbl_awards_list";

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
