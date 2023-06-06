<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualifiedUserList extends Model
{
    protected $table = "tbl_qualified_user_list";

    protected $primaryKey = 'id';

    protected $guarded = [];

    public $timestamps = false;

}
