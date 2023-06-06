<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategories extends Model
{
	/**
	 * [$table description]
	 * @var string
	 */
    protected $table = 'tbl_sub_categories';

    /**
     * [$guarded description]
     * @var array
     */
    protected $guarded = [];

	/**
	 * [$timestamps description]
	 * @var boolean
	 */
    public $timestamps = false;

}
