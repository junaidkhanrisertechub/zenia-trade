<?php
namespace App\Http\Controllers\adminapi;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Models\Chat;
use DB;
use Config;
use Validator;

class ChatController extends Controller {

  	public function __construct(){
       
        $this->statuscode =Config::get('constants.statuscode');
        $this->emptyArray=(object)array();
        $date= \Carbon\Carbon::now();
        $this->today= $date->toDateTimeString();
    }
//===========================================================================
 	public function sendMessage(Request $request) {
      
       $rules = array(
        // 'remember_token'    => 'required|',
        'message'    => 'required|',
        'to_user'    => 'required|',
        );

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
               $message = $validator->errors();
               $err ='';
               foreach($message->all() as $error)
               {
                  if(count($message->all())>1){
                    $err=$err.' '.$error;
                  }else{
                    $err=$error;
                  }
                }
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,$this->emptyArray);
        }    
       
    
     //$users = User::where([['remember_token','=',$request->input('remember_token')],['status','=','Active']])->first();
     $users  = Auth::user();

     if(!empty($users)){
            
           $toUser_id= User::where([['user_id','=',$request->input('to_user')],['status','=','Active']])->pluck('id')->first();
           
            if(!empty($toUser_id)){
            $data=array();
            $data['from_user_id']=$users->id;
	        $data['to_user_id']=$toUser_id;
	        $data['message']=trim($request->input('message'));
	        $data['entry_time']=$this->today;
	        $messageInsert=Chat::create($data);
            if(!empty($messageInsert)){
            	 return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Message sent !', $this->emptyArray);
            }else{
                 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong,Please try again', $this->emptyArray);
            }
        }else{
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not exist', $this->emptyArray);
        }
	       
     }else{
                 return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not exist', $this->emptyArray);
     }
    
    
 }
//=====================
 	public function fetchMessages(Request $request){
 		    // $rules = array(
       //    'remember_token'    => 'required|'
       //  );

       //  $validator = Validator::make($request->all(), $rules);
       //  if ($validator->fails()) {
       //         $message = $validator->errors();
       //         $err ='';
       //         foreach($message->all() as $error)
       //         {
       //            if(count($message->all())>1){
       //              $err=$err.' '.$error;
       //            }else{
       //              $err=$error;
       //            }
       //          }
       //        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,$this->emptyArray);
       //  }    

    //$toUser_id= User::where([['remember_token','=',$request->input('to_user')],['status','=','Active']])->pluck('id')->first();

    //$from_id= User::where([['remember_token','=',$request->input('remember_token')],['status','=','Active']])->pluck('id')->first();

    $from_id  = Auth::user()->id;

    //$toUser_id= User::where([['user_id','=',$request->input('to_user')],['status','=','Active']])->pluck('id')->first();
    $toUser_id= User::where([['user_id','=',$request->input('to_user')],['status','=','Active']])->pluck('id')->first();
    if(empty($from_id)){
         return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Token is not valid', $this->emptyArray);
    } 

    if(empty($toUser_id)){
         return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'To user is not exist', $this->emptyArray);
    }   
    $query=Chat::leftjoin('tbl_users as tu1','tu1.id','=','tbl_chat.from_user_id')
       ->leftjoin('tbl_users as tu2','tu2.id','=','tbl_chat.to_user_id')
       ->select('tbl_chat.*','tu1.user_id as fromuser_id','tu2.user_id as touser_id');
    $getMessage = $query->where([['tbl_chat.from_user_id','=',$from_id],['tbl_chat.to_user_id','=',$toUser_id]])->orwhere([['tbl_chat.to_user_id','=',$from_id],['tbl_chat.from_user_id','=',$toUser_id]])->orderBy('tbl_chat.id','asc')->get();

    $count  =  Chat::where([['tbl_chat.status','=','unread'],['tbl_chat.to_user_id','=',$from_id],['tbl_chat.from_user_id','=',$toUser_id]])->count();
    
    $finalMessage=[];
    if(!empty($getMessage) && count($getMessage)>0){
           $showMessage=[];
           $timmingArr=array();
        foreach($getMessage as $k =>$value){
            $end= $getMessage[$k]->entry_time;
            $now = \Carbon\Carbon::now();

            $created = \Carbon\Carbon::parse($end);

            $created->diffInDays($now);

            if($created->diffInDays($now) <= 0){
            $difference='today';

            }else if($created->diffInDays($now) > 0 && $created->diffInDays($now) <= 1){
            $difference='yesterday';

            } else if($created->diffInDays($now) > 1 &&  $created->diffInDays($now) <= 6){
            $difference=$created->format('l');

            }else if($created->diffInDays($now) > 6 )
            {
            $difference = $created->format('j F y');

            }

            $value->timing=$difference;
            $showMessage[$value->timing]['showtime']=$value->timing;
            if($from_id==$getMessage[$k]->from_user_id){
                $showMessage[$value->timing]['msgdata'][]= ['right' => $value];
            }else {
                $showMessage[$value->timing]['msgdata'][]= ['left' => $value];
            }
        }
        if(!empty($showMessage)){ 
               foreach($showMessage as $timedata ){
                $finalMessage[]=$timedata;
               }
        }else{
                $finalMessag='';
        }


        if(!empty($finalMessage)){
            $updateData=array();
            $updateData['status']='read';
            $updateData=Chat::where('to_user_id', $toUser_id)->update($updateData);

            $arrFinaldata['total_unread'] = $count;
            $arrFinaldata['messages'] = $finalMessage;
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Message found', $arrFinaldata);
        }else{
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Message not found', $this->emptyArray);
        }
         

    }else{
           
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Message not found', $this->emptyArray);
    }
  
  }
//=======================get admin chatting user list============
  public function getAdminChattingUser(Request $request){
       
       // $rules = array(
       //  'remember_token'    => 'required|',
       //  );

       //  $validator = Validator::make($request->all(), $rules);
       //  if ($validator->fails()) {
       //         $message = $validator->errors();
       //         $err ='';
       //         foreach($message->all() as $error)
       //         {
       //            if(count($message->all())>1){
       //              $err=$err.' '.$error;
       //            }else{
       //              $err=$error;
       //            }
       //          }
       //        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], $err,$this->emptyArray);
       //  }    
        
        //$user_id= User::where([['remember_token','=',$request->input('remember_token')],['status','=','Active']])->pluck('id')->first();
       
        $user_id  = Auth::user()->id;
        if(empty($user_id)){
             return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not valid', $this->emptyArray);
        }  
        
       $query=Chat::join('tbl_users as tu','tu.id','=','tbl_chat.from_user_id')
        ->select(DB::RAW("distinct(tbl_chat.from_user_id) as form_user_id"),'tu.user_id as fromuser','tu.remember_token as remember_token')
        ->where('tbl_chat.to_user_id','=',$user_id)
        ->groupBy('tbl_chat.from_user_id')
        ->get();
         
       if(!empty($query) && count($query)>0){

          foreach($query as $data){
                 $count=Chat::where([['from_user_id','=',$data->form_user_id],['tbl_chat.status','=','unread']])->count();
                 $data->count= $count; 
           }
       return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Users found', $query);
       }else{

          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'No users found', $this->emptyArray);
       }

  }


} 	