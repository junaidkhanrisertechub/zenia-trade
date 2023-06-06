<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\collectusd;
use App\Models\Topup;
/*use App\Models\collectusd;
*/
use DateTime;

class UpdateUSD extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:update_usd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*$usddata = collectusd::select('USD','total','created_at')->orderBy('created_at', 'desc')->first();

        $timestamp = strtotime($usddata->created_at);
        $lasthour = (date("H", $timestamp));

        date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
        $currenthour =  date('H');

        if($lasthour!=$currenthour)
        {
            $insertdata = new collectusd;
            $insertdata->USD = $usddata->USD;
            $insertdata->total = $usddata->total + 0.0000002;
            $insertdata->save();
        }

        date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
        $currenthour =  date('H');*/


        date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)
        $currenthour =  date('i');


        $usddata = Topup::select('id','total_usd','update_time')->get();
        foreach ($usddata as $value) {
             $timestamp = strtotime($value->update_time);
             $lasthour = (date("i", $timestamp));

            if($lasthour!=$currenthour)
            {

                $insertdata = array();
                    $insertdata['user_id'] = $value->id;
                    $insertdata['USD'] = 50;
                    $insertdata['total'] = $value->total_usd + 0.0001;


                    $insertAdd = collectusd::create($insertdata);

               $updatedata['total_usd']=$value->total_usd + 0.0001;
               $updatedata['update_time']= now();

               Topup::where('id',$value->id)->update($updatedata);
            }

        }




    }
}
