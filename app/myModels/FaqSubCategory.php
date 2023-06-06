<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqSubCategory extends Model
{
   
    /**
     * [$table description]
     * @var string
     */
    protected $table = "tbl_faq_category";

    /**
     * [$guarded description]
     * @var array
     */
    protected $guarded = [''];
    public $timestamps = false; 

    /**
     * [$hidden description]
     * @var array
     */
    protected $hidden = [];
}
