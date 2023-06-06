<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Session;

class WithdrawalConfirmed extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_withdrwal_confirmed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];


     

    public function getinoutAttribute()
    {  
        //dd($this->search_value); 
        if(Session::has('search_value')){
            $search_value=Session::get('search_value');
        }else{
            $search_value='0';
        }
        
        if($this->to_address==$search_value)
              {
                $in_out='input';
              }elseif($this->from_address==$search_value){
                $in_out='output';
              }
              else{
                $in_out='';
              }

       return $in_out;
    }
  
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
    protected $appends = ['in_out'];
   // protected $appends = ['total_received'];
  //  protected $appends = ['no_of_transaction'];
}