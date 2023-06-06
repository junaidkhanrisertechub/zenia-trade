<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Topup;
use Session;
use DB;

class LevelView extends Model
{
  
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_level_view';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];
    public $timestamps = false; 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        
    ];

    public function getInvestmentAttribute(){
        
        if(Session::has('from')){
            
             $from  = Session::get('from');
             $to  =   Session::get('to');     

            /*if($this->down_id == 33){
               dd($this->down_id,$from,$to);
            }*/
            $topup_inv = Topup::where('id',$this->down_id)->whereBetween(DB::raw("DATE_FORMAT(entry_time,'%d-%m-%Y')"),[$from, $to])->sum('amount');
           
           
        }else{
            $topup_inv = Topup::where('id',$this->down_id)->sum('amount');
        }
       
        return $topup_inv;
    }


     public function getDesgAttribute(){
        
       $desg =  DB::table('tbl_award_winner')->join('tbl_awards_list as tal','tal.award_id','=','tbl_award_winner.award_id')->where('tbl_award_winner.user_id',$this->down_id)->first();
       if(!empty($desg)){
        return $desg->designation;
       }else{
        return "-";
       }
    }
     public function getSponserUserIdAttribute(){
        
        $sp1 = DB::table('tbl_users')->where('id',$this->ref_user_id)->pluck('user_id')->first();
        return $sp1;
    }
     public function getSponserFullnameAttribute(){
        
       $sp2 = DB::table('tbl_users')->where('id',$this->ref_user_id)->pluck('fullname')->first();
       return $sp2;
    }


    protected $appends = ['investment','desg','sponser_userid','sponser_fullname'];

  
  
}
