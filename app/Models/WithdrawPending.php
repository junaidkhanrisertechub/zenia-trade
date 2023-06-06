<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawPending extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_withdrwal_pending';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
    
    /**
     * false timestamp created_at and updated_at in table
     *
     * @var array
     */
    public $timestamps = false;
}