<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class freedomclubincome extends Model
{
    /**
	 * [$table description]
	 * @var string
	 */
    protected $table = 'tbl_freedom_club_income';

    /**
     * [$guarded description]
     * @var array
     */
    protected $guarded = [];

    /**
     * [$hidden description]
     * @var array
     */
    public $timestamps = false; 

    
    protected $hidden = [];
}
