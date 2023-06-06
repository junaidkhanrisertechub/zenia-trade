<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelIncome extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_level_income';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];
    public function gettimestampAttribute()
    {  
        $date= \Carbon\Carbon::now();
        $today= $date->toDateTimeString();
        $formatted_dt1=\Carbon\Carbon::parse($this->entry_time);
        $formatted_dt2=\Carbon\Carbon::parse($today);
       
        $timestamp=$formatted_dt1->diffInDays($date);
        if($timestamp=='0'){
         
           $timestamp=$formatted_dt1->diffForHumans($formatted_dt2);
        
        }else{
            $timestamp= \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->entry_time)->format('Y-m-d');
        }
        return $timestamp;
    }
    public $timestamps = false; 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];
     protected $appends = ['timestamp'];
}
