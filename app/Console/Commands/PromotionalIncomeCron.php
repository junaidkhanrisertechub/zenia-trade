<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Models\Promotionals;
use App\Models\PromotionalSocialIncome;
use App\Models\PromotionalType;
use App\Models\Dashboard;
use DB;

class PromotionalIncomeCron extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotional_income:cron';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Give promotional income to user who has approved promotional by admin';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //$totalCountMatch = 30; // total approved promotional setting per user id and promotional type id wise 
        //$dateDiffer = 29; // in days(3 days 1 day back)
        // $from_date = date('Y-m-d', strtotime('-'.$dateDiffer.' days'));
        // $to_date   = date('Y-m-d');

        try{
            //get all promotional types
            $arrPromotionalsTypes = PromotionalType::get();

            foreach ($arrPromotionalsTypes as $value) {
                $from_date = date('Y-m-d', strtotime('-'.$value->duration.' days'));
                $to_date   = date('Y-m-d');
                //fetch data who has status = approved and paid status  = unpaid and entry time between from date and to date and user id, promotional type wise
                $arrPromotionals = Promotionals::selectRaw('COUNT(tbl_promotionals.srno) as count,tbl_promotionals.id,tbl_promotionals.promotional_type_id,tpt.promotional_cost,tpt.require_count,tpt.duration')
                                    ->join('tbl_promotional_type as tpt','tpt.srno','=','tbl_promotionals.promotional_type_id')
                                    ->where('tbl_promotionals.status','approved')
                                    ->where('tbl_promotionals.paid_status','unpaid')
                                    ->where('tbl_promotionals.promotional_type_id',$value->srno)
                                    ->whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$from_date, $to_date])
                                    ->groupBy('tbl_promotionals.id','tbl_promotionals.promotional_type_id')
                                    ->get();

                foreach ($arrPromotionals as $value) {
                    if($value->count >= $value->require_count){
                        //give promotional income user id and promotional type wise
                        $store  = PromotionalSocialIncome::insertGetId([
                            'id'                    => $value->id,
                            'promotional_type_id'   => $value->promotional_type_id,
                            'amount'                => $value->promotional_cost,
                            'from_date'             => $from_date,
                            'to_date'               => $to_date,
                            'entry_time'            => now(),
                        ]);

                        //update paid status who's given income above from unpaid to paid
                        $update = Promotionals::where('id',$value->id)->where('promotional_type_id',$value->promotional_type_id)->where('status','approved')->whereBetween(DB::raw("DATE_FORMAT(tbl_promotionals.entry_time,'%Y-%m-%d')"),[$from_date, $to_date])->update(['paid_status' => 'paid']);

                        $updateDash = Dashboard::where('id',$value->id)->limit(1)->update([
                            'promotional_income' => DB::raw('promotional_income + '.$value->promotional_cost),
                            'working_wallet' => DB::raw('working_wallet + '.$value->promotional_cost),
                        ]);
                    }
                }
            }
            $this->info('Promotional cron run successfully');
        } catch(Exception $e) {
            $this->info($e);
        }
    }
}