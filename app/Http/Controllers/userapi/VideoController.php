<?php 
namespace App\Http\Controllers\userapi;
use App\Http\Controllers\Controller;


use App\User;  
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
public function getVideosReport(Request $request)
{
    try 
    {
        $arrInput = $request->all();

        $query = DB::table('tbl_video_list')
        ->select('v_title','video_link','id')
        ->where('v_cat_id',$request->v_cat_id)
        ->where('v_scat_id',$request->v_scat_id)
        ->get();
  
        if(!empty($query))
        {
            $arrStatus   = Response::HTTP_OK;
            $arrCode     = Response::$statusTexts[$arrStatus];
            $arrMessage  = 'data get succesfully'; 
            return sendResponse($arrStatus,$arrCode,$arrMessage,$query);
        }
       
    //     $totalRecord = $query->count('tbl_video_list.id');
    //     $query = $query->orderBy('tbl_video_list.id','desc');
    //    // $totalRecord = $query->count();

    //    $arrDirectInc = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

    //    $arrData['recordsTotal'] = $totalRecord;
    //    $arrData['recordsFiltered'] = $totalRecord;
    //    $arrData['records'] = $arrDirectInc;

    //    if ($arrData['recordsTotal'] > 0) {

    //     $arrStatus   = Response::HTTP_OK;
    //     $arrCode     = Response::$statusTexts[$arrStatus];
    //     $arrMessage  = 'data get succesfully'; 
    //     return sendResponse($arrStatus,$arrCode,$arrMessage,$arrData);
    //    } else
    //     {
    //     $arrStatus   = Response::HTTP_OK;
    //     $arrCode     = Response::$statusTexts[$arrStatus];
    //     $arrMessage  = 'data not found'; 
    //     return sendResponse($arrStatus,$arrCode,$arrMessage,'');
    //    }
  }catch (Exception $e) {
      dd($e);
      $arrStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
      $arrCode = Response::$statusTexts[$arrStatus];
      $arrMessage = 'Something went wrong,Please try again';
      return sendResponse($arrStatus, $arrCode, $arrMessage, '');
  } 

}
 
}

?>
