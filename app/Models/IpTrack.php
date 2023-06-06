<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpTrack extends Model
{
    protected $table = "tbl_ip_tack";

    protected $primaryKey = 'id';

    protected $fillable = ['hostname','ipaddress','rec_date','forward','status','user_id'];

    public $timestamps = false;
}
