<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiAccessDetails extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_api_access_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false; 

      protected $guarded = [
        ''];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];
}