<?php
namespace App\Http\Controllers\adminapi;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Config;
use DB;
use App\User;
use Validator;
use Illuminate\Http\Response;
class CronController extends Controller
{
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(SettingsController $settings)
    {
        $this->statuscode = Config::get('constants.statuscode');
        $date = \Carbon\Carbon::now();
        $this->settings = $settings;
        $this->today = $date->toDateTimeString();
    }
    public function show($command, $param,$run_count='')
    {
        try {
            if (!empty($run_count)) {
                for ($i=0; $i < $run_count; $i++) { 
                    // code...
                    $artisan = \Artisan::call($command.":".$param);
                    // echo "\n".$command.":".$param."\nrun_count: ".($i+1)."\n\n";
                    $output = \Artisan::output();
                }
            }else{

                $artisan = \Artisan::call($command.":".$param);
                $output = \Artisan::output();
            }

            /*return response()->json([
                'code'=> $this->statuscode[200]['code'],
                'status' => $this->statuscode[200]['status'],
                'message'=>"Cron run successfully",
                'data' => $output
            ],200);*/

            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Cron run successfully', $output);
            
        } catch (Exception $e) {
            dd($e);
            return sendresponse($this->statuscode[500]['code'], $this->statuscode[500]['status'],'Something went wrong', '');
        }
    }
}