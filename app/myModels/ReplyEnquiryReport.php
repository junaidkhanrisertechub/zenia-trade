<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReplyEnquiryReport extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_reply_enquiry_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'srno','from_mail','to_mail','subject','message','created_by','entry_time','id'
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
