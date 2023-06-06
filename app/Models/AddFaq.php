<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddFaq extends Model
{
     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_faq_questions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false; 

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
