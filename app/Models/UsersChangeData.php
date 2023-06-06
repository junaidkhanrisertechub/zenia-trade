<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersChangeData extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_users_change_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sr_no','id','user_id','ref_user_id','fullname','nominee_name','relation','pin_number','amount','city','state','mobile','email','gender','account_no','holder_name','bank_name','branch_name','pan_no','ifsc_code','btc_address','password','tr_passwd','position','l_c_count','r_c_count','l_bv','r_bv','vl_bv','vr_bv','confirmed_users','virtual_parent_id','status','entry_time','type','roi_stop','stop_roi_time','pindate','address','litecoin','country','ethereum','pincode','payment_mode','state1','city1','dob','dispatch_date','delivery_date','confirm_date','delivery_status','delivery_note','designation','old_status','delivery_mode','pdc_id','ip','change_by','change_remark','service_date1','service_date2','service_date3','auth_type','flag','tempf','topup_status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}
