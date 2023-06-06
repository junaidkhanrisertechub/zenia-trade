<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class SuperAdminNavigation extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_super_admin_navigation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'navigation_id','menu','parent_id','path', 'icon_class','status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];
}
