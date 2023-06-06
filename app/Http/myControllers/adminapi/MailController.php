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
 
class MailController extends Controller
{
      public function get_mail_settings(Request $request)
      {
         try
         { 
                  $res=DB::table('tbl_project_settings')
                  ->select('id','project_name','email','icon_image','background_image','domain_name')
                  ->where('id','1')
                  ->get();
         
                  if($res) 
                  {
                     $arrStatus   = Response::HTTP_OK;
                     $arrCode     = Response::$statusTexts[$arrStatus];
                     $arrMessage  = 'Data get succesfully'; 
                     return sendResponse($arrStatus,$arrCode,$arrMessage,$res);
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

    public function update_mail(Request $request)
     { 
      //   return $request->all();
        try
        {
                 $configpath=Config::get('constants.settings.domainpath');
                  //  $configpath=Config::get('constants.settings.domain');

                  if($request->hasFile('logoImage'))
                  {
                     $Limage = $request->file('logoImage')->store('mailLogo');
                     $icon_image=$configpath."/public/".$Limage;
                  }
                 
                  else
                  {
                     $icon_image=$request->logoimage;
                  }

                  if($request->hasFile('backgroundImage'))
                  {
                     $Bimage = $request->file('backgroundImage')->store('mailLogo');
                     $background_image=$configpath."/public/".$Bimage;
                  }
                  else
                  {
                     $background_image=$request->bimage;
                  }

                 $data=array('icon_image'=>$icon_image,'background_image'=>$background_image,'project_name'=>$request->project_name,'domain_name'=>$request->link,'email'=>$request->email);

                 $data1=DB::table('tbl_project_settings')
                 ->where('id',$request->id)
                 ->update($data);

                 if($data1) 
                 {
                  // $updated_data=DB::table('tbl_project_settings')
                  // ->select('id','project_name','email','icon_image','background_image','domain_name')
                  // ->where('id',$request->id)
                  // ->get();

                    $arrStatus   = Response::HTTP_OK;
                    $arrCode     = Response::$statusTexts[$arrStatus];
                    $arrMessage  = 'Data Updated succesfully'; 
                    return sendResponse($arrStatus,$arrCode,$arrMessage,'');
                 }
                 else
                 {
                    return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Problem with Updating data', '');
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
    }

 ?>