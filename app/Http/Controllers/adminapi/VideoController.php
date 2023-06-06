<?php 
namespace App\Http\Controllers\adminapi;
use App\Http\Controllers\Controller;
use App\User; 
use App\config\constants; 

use Hash;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response as Response; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
public function getcategory()
{
        try
        {
         $result=DB::table('tbl_video_category')->select('*')->get();
         $arrStatus   = Response::HTTP_OK;
         $arrCode     = Response::$statusTexts[$arrStatus];
         $arrMessage  = 'data get succesfully'; 
         return sendResponse($arrStatus,$arrCode,$arrMessage,$result);
            
        }
        catch (Exception $e) {
            
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }
}
 
public function update_video(Request $request)
{
    try
    { 
        // $arrInput = $request->all();

        // $validator = Validator::make($arrInput, array(
        //     'file' => 'required',
        // ));

        // if ($validator->fails()) {
        //     $message = messageCreator($validator->errors());
        //     return array('message'=>$message);
        // }

           if($request->file==null)
           {
            $actual_path = $request->oldImage;
           }
           else
           {
                $configpath=Config::get('constants.settings.domainpath');
                // $configpath=Config::get('constants.settings.domain');

                $path = $request->file('file')->store('uploads');
                $actual_path= $configpath."/public/".$path;
           }
      
             $data=array('v_cat_id'=>$request->category_id,'v_scat_id'=>$request->subcategory_id,'v_title'=>$request->name,'entry_time'=>\Carbon\Carbon::now(),'video_link'=>$actual_path);

             $data1=DB::table('tbl_video_list')
             ->where('id',$request->id)
             ->update($data);
    
             if($data1) 
             {
                $arrStatus   = Response::HTTP_OK;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Data Updated succesfully'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
             }
             else
             {
                return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Problem with uploading image', '');
             } 
    }
    catch (Exception $e) {
        // dd($e);
        $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'Something went wrong,Please try again'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
    }

} 

public function add_video(Request $request)
{
        try
        {
            $arrInput = $request->all();

            $validator = Validator::make($arrInput, array(
                'file' => 'required',
            ));

                 $path = $request->file('file')->store('uploads');

                 $configpath=Config::get('constants.settings.domainpath');
                //  $configpath=Config::get('constants.settings.domain');

                 $actual_path= $configpath."/public/".$path;
                
                 $data=array('v_cat_id'=>$request->category_id,'v_scat_id'=>$request->subcategory_id,'v_title'=>$request->name,'entry_time'=>\Carbon\Carbon::now(),'video_link'=>$actual_path);

                 $data1=DB::table('tbl_video_list')->insert($data);
        
                 if($data1) 
                 {
                    $arrStatus   = Response::HTTP_OK;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Video added succesfully'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                 }
                 else
                 {
                    return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Problem with uploading image', '');
                 } 
        }
        catch (Exception $e) {
            dd($e);
            $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Something went wrong,Please try again'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
        }
}

public function selected_category(Request $request)
{
    //  return $request->category_id;
     try
     {
      $result=DB::table('tbl_video_sub_category')
      ->select('*')
      ->where('category_id',$request->category_id)
      ->get();

        if($result)
        {
            $arrStatus   = Response::HTTP_OK;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Sub category fetched succesfully'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,$result);  
        }
        else
        {
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Problem with fetching subcategory', '');
        }      
     }
     catch (Exception $e) {
         
         $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
         $arrCode     = Response::$statusTexts[$arrStatus];
         $arrMessage  = 'Something went wrong,Please try again'; 
         return sendResponse($arrStatus,$arrCode,$arrMessage,'');
     }
}

public function savesubCategory(Request $request)
{
    // return $request->all();
    try
    {
        $arrInput = $request->all();

        $rules = array(
            'category_id' => 'required',
            'sub_category'=> 'required'
        );
        $validator = Validator::make($arrInput, $rules);

        $res=array('category_id'=>$request->category_id,'sub_category'=>$request->sub_category,'entry_time'=>\Carbon\Carbon::now());

         $data1=DB::table('tbl_video_sub_category')->insert($res);

         if($data1)
         {
            $arrStatus   = Response::HTTP_OK;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Sub Category Added Succesfully'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
         }
         else
         {
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Problem with submitting data', '');
         }


    }
    catch (Exception $e) {
      
        $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'Something went wrong,Please try again'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
    }
}
 
public function saveVideoCategory(Request $request)
{
    try
    {
        $arrInput = $request->all();

        $rules = array(
            'category' => 'required',
        );
        $validator = Validator::make($arrInput, $rules);

        $arrInput['entry_time']= \Carbon\Carbon::now();

         $data=DB::table('tbl_video_category')->insert($arrInput);
         if($data)
         {
            $arrStatus   = Response::HTTP_OK;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'Data Submitted Succesfully'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
         }
         else
         {
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Problem with submitting data', '');
         }


    }
    catch (Exception $e) {
      
        $arrStatus   = Response::HTTP_INTERNAL_SERVER_ERROR;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'Something went wrong,Please try again'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
    }
}

public function get_video_category(Request $request)
{
    try 
    {
      $arrInput = $request->all();

      $query = DB::table('tbl_video_category')
      ->select('*');			
   
       $totalRecord = $query->count('id');
       $query = $query->orderBy('id','desc');
       // $totalRecord = $query->count();
       $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

       $arrData['recordsTotal'] = $totalRecord;
       $arrData['recordsFiltered'] = $totalRecord;
       $arrData['records'] = $arrDirectInc;

       if ($arrData['recordsTotal'] > 0) {

        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data get succesfully'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);
       } else
        {
        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data not found'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
       }
  }catch (Exception $e) {
      dd($e);
      $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
      $arrCode = Response::$statusTexts[$arrStatus];
      $arrMessage = 'Something went wrong,Please try again';
      return sendResponse($arrStatus, $arrCode, $arrMessage, '');
  }


}
public function get_video_subcategory(Request $request)
{
    try 
    {
            $arrInput = $request->all();

        $query = DB::table('tbl_video_category')
        ->join('tbl_video_sub_category as mgc', function ($join) {
        $join->on('mgc.category_id', '=', 'tbl_video_category.id');
            })
        ->select('mgc.sub_category','mgc.id','tbl_video_category.category');

        $totalRecord = $query->count('mgc.id');
        $query = $query->orderBy('mgc.id','desc');
       // $totalRecord = $query->count();

       $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

       $arrData['recordsTotal'] = $totalRecord;
       $arrData['recordsFiltered'] = $totalRecord;
       $arrData['records'] = $arrDirectInc;

       if ($arrData['recordsTotal'] > 0) {

        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data get succesfully'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);
       } else
        {
        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data not found'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
       }
  }catch (Exception $e) {
      dd($e);
      $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
      $arrCode = Response::$statusTexts[$arrStatus];
      $arrMessage = 'Something went wrong,Please try again';
      return sendResponse($arrStatus, $arrCode, $arrMessage, '');
  } 

}

public function showvideo_category(Request $request)
{
    try 
    {
        $arrInput = $request->all();

        $query = DB::table('tbl_video_category')
        ->select('*')
        ->where('id',$request->id)
        ->get();

       if($query) {

        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data get succesfully'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,$query);
       } else
        {
        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data not found'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
       }
  }catch (Exception $e) {
      dd($e);
      $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
      $arrCode = Response::$statusTexts[$arrStatus];
      $arrMessage = 'Something went wrong,Please try again';
      return sendResponse($arrStatus, $arrCode, $arrMessage, '');
  }

}

public function showsub_video_category(Request $request)
{
    try 
    {
            $arrInput = $request->all();

        $query = DB::table('tbl_video_category')
        ->join('tbl_video_sub_category as mgc', function ($join) {
        $join->on('mgc.category_id', '=', 'tbl_video_category.id');
            })  
        ->select('mgc.sub_category','mgc.id','tbl_video_category.category','tbl_video_category.id')
        ->where('mgc.id',$request->id)
        ->get();

       if($query) {

        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data get succesfully'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,$query);
       } else
        {
        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data not found'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
       }
  }catch (Exception $e) {
      dd($e);
      $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
      $arrCode = Response::$statusTexts[$arrStatus];
      $arrMessage = 'Something went wrong,Please try again';
      return sendResponse($arrStatus, $arrCode, $arrMessage, '');
  }

}

public function update_video_category(Request $request)
{
    try
    {
         $data=array('category'=>$request->category);

          $selected_data =DB::table('tbl_video_category')
          ->where('id',$request->id)
          ->update($data);
          
          $arrStatus = Response::HTTP_OK; 
          $arrCode = Response::$statusTexts[$arrStatus];
          $arrMessage = 'Data Updated Succesfully';
          return sendResponse($arrStatus, $arrCode, $arrMessage,'');

 }catch (Exception $e) { 
     dd($e);
     $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
     $arrCode = Response::$statusTexts[$arrStatus];
     $arrMessage = 'Something went wrong,Please try again';
     return sendResponse($arrStatus, $arrCode, $arrMessage, '');
 }    
}
 
public function update_subvideo_category(Request $request)
{
    try
    {
         $data=array('category_id'=>$request->category_id,'sub_category'=>$request->sub_category);

           DB::table('tbl_video_sub_category')
          ->where('id',$request->id)
          ->update($data);
          
          $arrStatus = Response::HTTP_OK; 
          $arrCode = Response::$statusTexts[$arrStatus];
          $arrMessage = 'Data Updated Succesfully';
          return sendResponse($arrStatus, $arrCode, $arrMessage,'');

 }catch (Exception $e) {
     dd($e);
     $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
     $arrCode = Response::$statusTexts[$arrStatus];
     $arrMessage = 'Something went wrong,Please try again';
     return sendResponse($arrStatus, $arrCode, $arrMessage, '');
 }    
}


public function get_videos_report(Request $request)
{
    try 
    {
        $arrInput = $request->all();

        $query = DB::table('tbl_video_category')
        ->join('tbl_video_sub_category', 'tbl_video_sub_category.category_id', '=', 'tbl_video_category.id')
        ->join('tbl_video_list', 'tbl_video_list.v_scat_id', '=', 'tbl_video_sub_category.id')
        ->select('tbl_video_category.category','tbl_video_sub_category.sub_category','tbl_video_list.v_title','tbl_video_list.id','tbl_video_list.video_link');
        // ->get();

        $totalRecord = $query->count('tbl_video_list.id');
        $query = $query->orderBy('tbl_video_list.id','desc');
       // $totalRecord = $query->count();

       $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

       $arrData['recordsTotal'] = $totalRecord;
       $arrData['recordsFiltered'] = $totalRecord;
       $arrData['records'] = $arrDirectInc;

       if ($arrData['recordsTotal'] > 0) {

        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data get succesfully'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);
       } else
        {
        $arrStatus   = Response::HTTP_OK;
        $arrCode     = Response::$statusTexts[$arrStatus];
        $arrMessage  = 'data not found'; 
        return sendResponse($arrStatus,$arrCode,$arrMessage,'');
       }
  }catch (Exception $e) {
      dd($e);
      $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
      $arrCode = Response::$statusTexts[$arrStatus];
      $arrMessage = 'Something went wrong,Please try again';
      return sendResponse($arrStatus, $arrCode, $arrMessage, '');
  } 

}
 
public function show_selected_video(Request $request)
{
    try 
    {
        $arrInput = $request->all();

        $query = DB::table('tbl_video_category')
        ->join('tbl_video_sub_category', 'tbl_video_sub_category.category_id', '=', 'tbl_video_category.id')
        ->join('tbl_video_list', 'tbl_video_list.v_scat_id', '=', 'tbl_video_sub_category.id')
        ->select('tbl_video_category.category','tbl_video_sub_category.sub_category','tbl_video_list.v_title','tbl_video_list.id','tbl_video_list.v_cat_id','tbl_video_list.v_scat_id','tbl_video_list.video_link')
        ->where('tbl_video_list.id',$request->id)
        ->get();

        if($query)
        {
            $arrStatus   = Response::HTTP_OK;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'data get succesfully'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,$query);
           }
           else
            {
            $arrStatus   = Response::HTTP_OK;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'data not found'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,'');
           }
  }
  catch (Exception $e) {
      dd($e);
      $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
      $arrCode = Response::$statusTexts[$arrStatus];
      $arrMessage = 'Something went wrong,Please try again';
      return sendResponse($arrStatus, $arrCode, $arrMessage, '');
  } 
}

public function delete_category_video(Request $request)
{
        try
        {
            $delete = DB::table('tbl_video_category')
            ->where('id',$request->id)
            ->delete();

            if(!empty($delete)){
                $arrStatus   = Response::HTTP_OK;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Video Deleted succesfully'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }
            else{
                $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Something went wrong,Please try again';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        }
        catch (Exception $e) {
            dd($e);
            $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
}

public function delete_subcategory_video(Request $request)
{
        try
        {
            $delete = DB::table('tbl_video_sub_category')
            ->where('id',$request->id)
            ->delete();

            if(!empty($delete)){
                $arrStatus   = Response::HTTP_OK;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Sub Category Deleted succesfully'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }
            else{
                $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Something went wrong,Please try again';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        }
        catch (Exception $e) {
            dd($e);
            $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
}

public function delete_video_list(Request $request)
{
        try
        {
            $delete = DB::table('tbl_video_list')
            ->where('id',$request->id)
            ->delete();

            if(!empty($delete)){
                $arrStatus   = Response::HTTP_OK;
                $arrCode     = Response::$statusTexts[$arrStatus];
                $arrMessage  = 'Video Deleted succesfully'; 
                return sendResponse($arrStatus,$arrCode,$arrMessage,'');
            }
            else{
                $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
                $arrCode = Response::$statusTexts[$arrStatus];
                $arrMessage = 'Something went wrong,Please try again';
                return sendResponse($arrStatus, $arrCode, $arrMessage, '');
            }
        }
        catch (Exception $e) {
            dd($e);
            $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            $arrCode = Response::$statusTexts[$arrStatus];
            $arrMessage = 'Something went wrong,Please try again';
            return sendResponse($arrStatus, $arrCode, $arrMessage, '');
        }
}



}

?>
