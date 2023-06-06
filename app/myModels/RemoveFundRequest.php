<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class RemoveFundRequest extends Model
{
    //
    //

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_remove_fund';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 
}
