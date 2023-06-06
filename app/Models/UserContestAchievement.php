<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserContestAchievement extends Model
{
	/**
	 * [$table description]
	 * @var string
	 */
    protected $table = "tbl_user_contest_achievment";

    /**
     * [$guarded description]
     * @var array
     */
    protected $guarded = [];
    public $timestamps = false; 

    /**
     * [$hidden description]
     * @var array
     */
    protected $hidden = [];

}
