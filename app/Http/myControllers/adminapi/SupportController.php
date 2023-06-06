<?php
namespace App\Http\Controllers\adminapi;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Models\Chat;
use App\Models\FrontChat;
use DB;
use Config;
use Validator;
use App\Events\MessageSent;
use App\Events\MessageFrontChat;
class SupportController extends Controller {

  	public function __construct(){
       
        $this->statuscode =Config::get('constants.statuscode');
        $this->emptyArray=(object)array();
        $date= \Carbon\Carbon::now();
        $this->today= $date->toDateTimeString();
    }
//===========================================================================
   public function sendMessage(Request $request)
   {
      try{
        $rules = array(
                'message' => 'required_without:files|',
                'to_user' => 'required|',
                'files' => 'required_without:message|',
                'type' => 'required'
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
        if(!empty($users))
        {
          $checkAccess=User::where('id',$users->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
          if(!empty($checkAccess))
          {
            $from_id=1;
            $toUser_id= User::where([['user_id','=',$request->input('to_user')],['status','=','Active']])->pluck('id')->first();
            
            // Check if sender id is present or not in users table 
            if(!empty($toUser_id))
            {
              $fileNames=[];
              $files = null;
              $type=$request->type;
              // If message contains file or attachment then store file at public/uploads/support path
              if ($request->hasFile('files')) {
                  foreach($request->file('files.*') as $key=>$file)
                  {
                      // $fileNames[$key] = time().$file->getClientOriginalName();
                      // $file->move(public_path('uploads/support'), $fileNames[$key]);
                  }
                  $files=implode(',',$fileNames);
              }
              $data=array();
              $message=trim($request->input('message'));
              // $translated_message=$request->input('translated_message');
              //$translated_message=$this->translate($message);            
              $data['from_user_id']=$from_id;
              $data['to_user_id']=$toUser_id;
              $data['message']=$message;
             // $data['translated_message']=$translated_message;
              if($request->hasFile('files')){
                $data['attachment']=$files;
              }
              $data['type']=$type;
  	          $data['entry_time']=$this->today;
  	          $messageInsert=Chat::create($data);
              if(!empty($messageInsert))
              {
                // Broadcast message to sender through pusher
                broadcast(new MessageSent($users, $messageInsert));
              	 return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Message sent !', $this->emptyArray);
              }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong,Please try again', $this->emptyArray);
              }
            }else{
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not exist', $this->emptyArray);
            }
	       
          }else{
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
          }
        }else{
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not exist', $this->emptyArray);
          }
      }
      catch(Exception $e){
          return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'],'Internal server error', $this->emptyArray);
      }   
    
    }
//=====================
 	public function fetchMessages(Request $request)
  {
    $user  = Auth::user();
    $checkAccess=User::where('id',$user->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
    if(!empty($checkAccess))
    {
      $from_id  = 1;
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
         ->select('tbl_chat.*',DB::RAW("TIME(tbl_chat.entry_time) as time"),'tu1.user_id as fromuser_id','tu2.user_id as touser_id');
      $getMessage = $query->where([['tbl_chat.from_user_id','=',$from_id],['tbl_chat.to_user_id','=',$toUser_id]])->orwhere([['tbl_chat.to_user_id','=',$from_id],['tbl_chat.from_user_id','=',$toUser_id]])->orderBy('tbl_chat.id','asc')->get();
      $files=array();
      foreach($getMessage as $msg){
        if($msg->type=='file')
        {
          array_push($files,'public/uploads/support/'.$msg->attachment);
        }
      }
      $count  =  Chat::where([['tbl_chat.status','=','unread'],['tbl_chat.to_user_id','=',$from_id],['tbl_chat.from_user_id','=',$toUser_id]])->count();
      
      $finalMessage=[];
      if(!empty($getMessage) && count($getMessage)>0)
      {
             $showMessage=[];
             $timmingArr=array();
          foreach($getMessage as $k =>$value)
          {
              $end= $getMessage[$k]->entry_time;
              $now = \Carbon\Carbon::now();

              $created = \Carbon\Carbon::parse($end);

              $created->diffInDays($now);

              if($created->diffInDays($now) <= 0){
                $difference='Today';

              }else if($created->diffInDays($now) > 0 && $created->diffInDays($now) <= 1){
                $difference='Yesterday';

              } else if($created->diffInDays($now) > 1 &&  $created->diffInDays($now) <= 6){
                $difference=$created->format('l');

              }else if($created->diffInDays($now) > 6 )
              {
                $difference = $created->format('j F y');

              }
              $value->timing=$difference;
              $showMessage[$value->timing]['showtime']=$value->timing;
              $value->files=collect(explode(',',$getMessage[$k]->attachment));

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
              $updateData=Chat::where('from_user_id','=', $toUser_id)->update($updateData);

              $arrFinaldata['total_unread'] = $count;
              $arrFinaldata['messages'] = $finalMessage;
              $arrFinaldata['files']=$files;
              return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Message found', $arrFinaldata);
          }else{
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Message not found', $this->emptyArray);
          }
      }else{    
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Message not found', $this->emptyArray);
      }
    }
    else{    
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
    }
  }
//=======================get admin chatting user list============
  public function getAdminChattingUser(Request $request)
  { 
    $user  = Auth::user();
    $checkAccess=User::where('id',$user->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
    if(!empty($checkAccess))
    {    
        $user_id  = 1;
        $search='';
      
        if(empty($user_id)){
             return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not valid', $this->emptyArray);
        }  
        
       $query=Chat::join('tbl_users as tu','tu.id','=','tbl_chat.from_user_id')
        ->select(DB::RAW("tbl_chat.from_user_id as from_user_id"),'tu.user_id as fromuser',DB::RAW('UPPER(tu.fullname) as fromuser_name'),'tu.remember_token as remember_token',DB::RAW("tbl_chat.from_user_id as from_user_id"),'tu1.user_id as touser',DB::RAW('UPPER(tu1.fullname) as touser_name'),'tu1.remember_token as to_remember_token','tbl_chat.to_user_id')->join('tbl_users as tu1','tu1.id','=','tbl_chat.to_user_id')
        ->where('tbl_chat.to_user_id','=',$user_id);//->orWhere('tbl_chat.from_user_id',$user_id);
          if($request->has('search') && $request->input('search') != ""){
          $search=$request->input('search');
          $query = $query->where('tu.user_id','LIKE',"%$search%");
        }
        $query = $query->groupBy('tbl_chat.from_user_id')->orderBy('tbl_chat.entry_time','DESC');
        //dd($query->toSql());
        $query = $query->get();
         
       if(!empty($query) && count($query)>0)
       {

          foreach($query as $data)
          {
            $data->count=0;
            if($data->from_user_id!=1)
            {
              $count=Chat::where([['from_user_id','=',$data->from_user_id],['tbl_chat.status','=','unread']])->count();
              $data->count= $count; 
            }    
                 
           }
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Users found', $query);
       }else{

          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'No users found', $this->emptyArray);
       }
     }
     else{    
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
      }
  }
  public function getAllUserList(Request $request){
    $user  = Auth::user();
    $checkAccess=User::where('id',$user->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
    if(!empty($checkAccess))
    {
        $user_id  = 1;
        $search='';
        if($request->has('search')){
          $search=$request->input('search');
        }
        if(empty($user_id)){
             return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not valid', $this->emptyArray);
        }  
        
       $query=User::select(DB::RAW("UPPER(fullname) as fullname"),'user_id')->where('id','!=',1);
       if($request->has('search')){
          $query=$query->where('user_id','LIKE',"%$search%")->orWhere('fullname','LIKE',"%$search%");
       }
       $query=$query->orderBy('user_id')->get();
       if(!empty($query) && count($query)>0){
        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Users found', $query);
       }else{
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'No users found', $this->emptyArray);
       }
     }
     else{    
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
      }
  }
  public function translate($text){
      $apiKey = 'AIzaSyD-MP-rPzjrBclTBx-SUCHbKBJdL1BxA0I';
      $url = 'https://translation.googleapis.com/language/translate/v2?target=ru&key='.$apiKey .'&q='.rawurlencode($text).'&source=en';
      
      $handle = curl_init($url);
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($handle); 
      $responseDecoded = json_decode($response, true);
      curl_close($handle);
      
      $trans_word=$responseDecoded['data']['translations'][0]['translatedText'];
      return $trans_word;
  }
  public function supportCount(Request $request){
      $user=Auth::user();
      if(!empty($user)){
        $checkAccess=User::where('id',$user->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
        if(!empty($checkAccess))
        {
          $user_id=1;
          $count=Chat::where('to_user_id',$user_id)->where('status','=','unread')->count();
          // $count=0;
          $arrOutput['count']=$count;
          return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'', $arrOutput); 
        }
        else{    
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
        }
      }
      else{
        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is unauthenticated', ''); 
      }
  }
//===========================================Front Chat Functionality================================
  public function sendFrontMessage(Request $request)
  {
    try{
      $rules = array(
              'message' => 'required_without:files|',
              'to_user' => 'required|',
              'files' => 'required_without:message|',
              'type' => 'required'
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
      $users  = Auth::user();
    
      if(!empty($users))
      {
        $checkAccess=User::where('id',$users->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
        if(!empty($checkAccess))
        {
          $from_id=1;
          $user=User::find($from_id);
          $toUser_id= $request->to_user;
        
          // Check if sender id is present or not in users table 
          if(!empty($toUser_id))
          {
            $fileNames=[];
            $files = null;
            $type=$request->type;
            // If message contains file or attachment then store file at public/uploads/support path
            if ($request->hasFile('files')) {
                foreach($request->file('files.*') as $key=>$file)
                {
                    // $fileNames[$key] = time().$file->getClientOriginalName();
                    // $file->move(public_path('uploads/support'), $fileNames[$key]);
                }
                $files=implode(',',$fileNames);
            }
            $data=array();
            $message=trim($request->input('message'));
            // $translated_message=$request->input('translated_message');	
            // Send Email message if user send message as "leave a message" (online:0)
            $lastMessageStatus=FrontChat::where('from_email','=',$toUser_id)->orderBy('entry_time','desc')->limit(1)->first();
            if($lastMessageStatus->online==0){
                $arrEmailData  = [];
                $arrEmailData['email']    = $toUser_id;
                $arrEmailData['template'] = 'email.send-support-message';
                $arrEmailData['subject']  = "Support";
                $arrEmailData['msg']      = $message;
                $mail =sendEmail($arrEmailData); 
            }
            // $translated_message=$request->input('translated_message');
            $translated_message=$this->translate($message);                      
            $data['from_user_id']=$from_id;
            $data['to_user_id']=0;
            $data['to_email']=$toUser_id;
            $data['from_email']=$user->email;
            $data['from_name']=$user->fullname;
            $data['message']=$message;
            $data['translated_message']=$translated_message;
            if($request->hasFile('files')){
              $data['attachment']=$files;
            }
            $data['type']=$type;
            $data['entry_time']=$this->today;
            $messageInsert=FrontChat::create($data);
            if(!empty($messageInsert))
            {
              // Broadcast message to sender through pusher
              broadcast(new MessageFrontChat($users, $messageInsert));
               return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Message sent !', $this->emptyArray);
            }else{
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Something went wrong,Please try again', $this->emptyArray);
            }
          }else{
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not exist', $this->emptyArray);
          }
        }
        else{    
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
        }
       
      }else{
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not exist', $this->emptyArray);
      }
    }
    catch(Exception $e){
        return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'],'Internal server error', $this->emptyArray);
    }     
  
  }
  //=====================
  public function fetchFrontMessages(Request $request)
  {
    $users  = Auth::user();
    $checkAccess=User::where('id',$users->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
    if(!empty($checkAccess))
    {
        $from_id=1;
        //$toUser_id= User::where([['user_id','=',$request->input('to_user')],['status','=','Active']])->pluck('id')->first();
        $toUser_id= $request->to_user;
        if(empty($from_id)){
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Token is not valid', $this->emptyArray);
        } 

        if(empty($toUser_id)){
              return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'To user is not exist', $this->emptyArray);
        }   
        $query=FrontChat::select('tbl_front_chat.*',DB::RAW("TIME(tbl_front_chat.entry_time) as time"),'from_email as fromuser_id','to_user_id as touser_id');
        $getMessage = $query->where([['tbl_front_chat.from_user_id','=',$from_id],['tbl_front_chat.to_email','=',$toUser_id]])->orwhere([['tbl_front_chat.to_user_id','=',$from_id],['tbl_front_chat.from_email','=',$toUser_id]])->orderBy('tbl_front_chat.id','asc')->get();
        $files=array();
        foreach($getMessage as $msg){
          if($msg->type=='file')
          {
            array_push($files,'public/uploads/support/'.$msg->attachment);
          }
        }
        $count  =  FrontChat::where([['tbl_front_chat.status','=','unread'],['tbl_front_chat.to_user_id','=',$from_id],['tbl_front_chat.from_email','=',$toUser_id]])->count();
        
        $finalMessage=[];
        if(!empty($getMessage) && count($getMessage)>0)
        {
                $showMessage=[];
                $timmingArr=array();
            foreach($getMessage as $k =>$value)
            {
                $end= $getMessage[$k]->entry_time;
                $now = \Carbon\Carbon::now();

                $created = \Carbon\Carbon::parse($end);

                $created->diffInDays($now);

                if($created->diffInDays($now) <= 0){
                  $difference='Today';

                }else if($created->diffInDays($now) > 0 && $created->diffInDays($now) <= 1){
                  $difference='Yesterday';

                } else if($created->diffInDays($now) > 1 &&  $created->diffInDays($now) <= 6){
                  $difference=$created->format('l');

                }else if($created->diffInDays($now) > 6 )
                {
                  $difference = $created->format('j F y');

                }
                $value->timing=$difference;
                $showMessage[$value->timing]['showtime']=$value->timing;
                $value->files=collect(explode(',',$getMessage[$k]->attachment));

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
                $updateData=FrontChat::where('from_email','=', $toUser_id)->update($updateData);

                $arrFinaldata['total_unread'] = $count;
                $arrFinaldata['messages'] = $finalMessage;
                $arrFinaldata['files']=$files;
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Message found', $arrFinaldata);
            }else{
                  return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Message not found', $this->emptyArray);
            }
        }else{
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Message not found', $this->emptyArray);
        }
      }
      else{    
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
      }
  }
  //=======================get admin chatting user list============
  public function getFrontAdminChattingUser(Request $request)
  {  
    $users  = Auth::user(); 
    $checkAccess=User::where('id',$users->id)->where('type','=','sub-admin')->orWhere('type','=','Admin')->first();
    if(!empty($checkAccess))
    {  
      $user_id  = 1;
      $search='';
      if($request->has('search')){
        $search=$request->input('search');
      }
      if(empty($user_id)){
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User is not valid', $this->emptyArray);
      }  
      
      $query=FrontChat::select(DB::RAW("distinct(from_email) as from_email"),DB::RAW('UPPER(from_name) as fromuser_name'),'entry_time','from_user_id')
      ->where('from_user_id','=',1)->orWhere('to_user_id','=',1)->groupBy('from_email')
      ->orderBy('tbl_front_chat.entry_time','desc')
      ->get();
        
      if(!empty($query) && count($query)>0)
      {

        foreach($query as $data)
        {
          $data->count=0;
          if($data->from_user_id!=1)
          {
            $count=FrontChat::where([['from_email','=',$data->from_email],['status','=','unread']])->count();
            $data->count= $count; 
          }    
                
          }
          return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Users found', $query);
      }else{

        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'No users found', $this->emptyArray);
      }
    }
    else{    
          return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not have access', $this->emptyArray);
      }
  }  
} 	