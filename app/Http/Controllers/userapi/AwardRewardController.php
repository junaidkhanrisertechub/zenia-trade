<?php

namespace App\Http\Controllers\userapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AwardWinner;
use App\Models\Awards;
use App\Models\LevelView;
use App\Models\Topup;
use App\Models\Dashboard;
use App\User;
use Validator;
use Config;
use DB;
use Auth;

class AwardRewardController extends Controller {

    /**
     * [__construct description]
     */
    public function __construct() {
        $this->statuscode = Config::get('constants.statuscode');
    }

    public function awardWinner($token) {

        $userExist = Auth::user();
        if (!empty($userExist)) {
            /** @var [type] [Self + child] */
            $total_lbv = DB::table('tbl_users AS user')
                    ->select('user.l_bv')
                    ->where('user.id', '=', $userExist->id)
                    ->sum('user.l_bv');
            $total_ref_lbv = DB::table('tbl_users AS user')
                    ->select('user.l_bv')
                    ->where('user.ref_user_id', '=', $userExist->id)
                    ->sum('user.l_bv');
            // $total_left_bv =  $total_lbv + $total_ref_lbv;
            $total_left_bv = $total_lbv;

            /** @var [type] [Self + child] */
            $total_rbv = DB::table('tbl_users AS user')
                    ->select('user.r_bv')
                    ->where('user.id', '=', $userExist->id)
                    ->sum('user.r_bv');
            $total_ref_rbv = DB::table('tbl_users AS user')
                    ->select('user.r_bv')
                    ->where('user.ref_user_id', '=', $userExist->id)
                    ->sum('user.r_bv');
            //$total_right_bv =  $total_rbv + $total_ref_rbv;
            $total_right_bv = $total_rbv;
            //echo "<br>".$total_right_bv;
            /** Award conditions */
            $current_awards = Awards::select('id', 'l_bv', 'r_bv', 'rank', 'award', 'entry_date', 'status')
                    ->where('status', 'Active')
                    ->get();
            $awardDetails = [];
            $awardDetails['user_id'] = $userExist->id;
            $awardDetails['total_left_bv'] = $total_left_bv;
            $awardDetails['total_right_bv'] = $total_right_bv;
            foreach ($current_awards as $key => $value) {
                if ($total_left_bv >= $value->l_bv && $total_right_bv >= $value->r_bv) {
                    $awardDetails = $value;
                }
            }

            if (!empty($awardDetails['id'])) {

                $winnerIsExist = AwardWinner::select('id')->where([
                            ['user_id', $userExist->id],
                            ['award_id', $awardDetails['id']]
                        ])->first();
                if (count($winnerIsExist) > 0) {
                    /* return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Award already alloted to same user',''); */
                } else {
                    $insId = AwardWinner::firstOrCreate([
                                'user_id' => $userExist->id,
                                'award_id' => $awardDetails['id'],
                                'total_l_bv_match' => $total_left_bv,
                                'total_r_bv_match' => $total_right_bv,
                                /* 'entry_time' => date('Y-m-d',strtotime(now())) */
                                'entry_time' => now()
                    ]);
                }
                /* return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Successful!',$awardDetails);	 */
            }
        } else {
            /* return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'User does not exist',''); */
        }
    }
    /**
     * Winner list]
     * @param  Request $request [token alpha-num]
     * @return [Array]         
     */
    public function winnerList() {

        $url = url('uploads/photo');
        $awardurl = url('uploads/awards');
        $query = AwardWinner::join('tbl_users as tu', 'tu.id', '=', 'tbl_award_winner.user_id')
                ->join('tbl_awards_list as tawl', 'tbl_award_winner.award_id', '=', 'tawl.award_id')
                ->leftjoin('tbl_kyc as kyc', 'tu.id', '=', 'kyc.user_id')
                ->select('tu.fullname', DB::raw('IF(tawl.image IS NOT NULL,CONCAT("' . $awardurl . '","/",tawl.image),NULL)image'), DB::raw('IF(kyc.photo IS NOT NULL,CONCAT("' . $url . '","/",kyc.photo),NULL) photo'))
                ->orderBy('tbl_award_winner.created_at', 'DESC')
                ->get();
        if (!empty($query)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found Successfully!', $query);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
        }
    }

    /**
     * User Winner list]
     * @param  Request $request [token alpha-num]
     * @return [Array]         
     */
    public function userWinnerList() {
        $getuser = Auth::user();
        $url = url('uploads/photo');
        $awardurl = url('uploads/awards');
        $query = AwardWinner::join('tbl_users as tu', 'tu.id', '=', 'tbl_award_winner.user_id')
                ->join('tbl_awards_list as tawl', 'tbl_award_winner.award_id', '=', 'tawl.award_id')
                ->leftjoin('tbl_kyc as kyc', 'tu.id', '=', 'kyc.user_id')
                ->select('tawl.award_id as awards_list_id','tu.fullname', 'tawl.designation', 'tbl_award_winner.entry_time', DB::raw('IF(tawl.image IS NOT NULL,CONCAT("' . $awardurl . '","/",tawl.image),NULL)image'), DB::raw('IF(kyc.photo IS NOT NULL,CONCAT("' . $url . '","/",kyc.photo),NULL) photo'))
                ->where('tbl_award_winner.user_id', $getuser->id)
                ->orderBy('tbl_award_winner.award_id', 'DESC')
                ->get();
        if (count($query)>0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found Successfully!', $query);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
        }
    }
    
    /**
     * [Award list]
     * @param  Request $request [token alpha-num]
     * @return [Array]         
     */
    public function awardList(Request $request) {

        $getuser = Auth::user();
        $awardurl = url('uploads/awards');
        $query = Awards::select(DB::raw('IF(tbl_awards_list.image IS NOT NULL,CONCAT("' . $awardurl . '","/",tbl_awards_list.image),NULL)image'),'tbl_awards_list.designation','tbl_awards_list.qualified_bv','tbl_awards_list.award_id')->orderBy('award_id','ASC')->get();
        if (!empty($query)) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found Successfully!', $query);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
        }
    }


    /*
    * Cron for award winner
    * get match  from l_bv and r_bv 
    * Compare with award list bv and assign that user thar award 
    */
    public function cronAwardAssign(){

        $users = User::select('id')->where('status','Active')->get();
      //  $award_list = Awards::all();
        foreach($users as $user){
            $userDownlineTotal=LevelView::join('tbl_topup as ttp','ttp.id','tbl_level_view.down_id')
                                ->where('tbl_level_view.id',$user->id)
                                ->selectRaw('sum(ttp.amount) as total_investment,count(tbl_level_view.down_id) as count')
                                ->groupBy('tbl_level_view.id')
                                ->first();
            if(!empty($userDownlineTotal)){
                $down_id=$userDownlineTotal->count;
                $userInvestment=$userDownlineTotal->total_investment;
               // $userInvestment=Topup::where('id',$user->id)->sum('amount');
                $direct_id=User::where('ref_user_id',$user->id)->count('id');
                $assign_award = Awards::select('award_id','award','time_period')->where([['business_required','<=',$userInvestment],['downline_needed',"<=",$down_id],['direct',"<=",$direct_id]])->get();
                if(count($assign_award)>0){
                    $flag=false;
                    $nextEntrydate='';
                    foreach ($assign_award as $data) {

                        $count = AwardWinner::where('user_id',$user->id)->where('award_id',$data->award_id)->count('winner_id');
                        if($count<=0){   // if no award exist
                           $flag=true;
                           $nextEntrydate=\Carbon\Carbon::now();

                        }else if($count<$data->time_period){  // if count no of award is equal to nof months to give award
                           $lastEntryTime = AwardWinner::select('entry_time','winner_id')
                                        ->where([['user_id',$user->id],['award_id',$data->award_id]])
                                        ->groupBy('entry_time')
                                        ->orderBy('entry_time','desc')->limit(1)->first();
                           
                            $to = \Carbon\Carbon::createFromFormat('Y-m-d H:s:i', $lastEntryTime->entry_time); 
                            $from = \Carbon\Carbon::now();
                            $diff_in_months = $to->diffInMonths($from);
                            if($diff_in_months>=1){
                                $flag=true; 
                                $nextEntrydate = date('Y-m-d', strtotime($lastEntryTime->entry_time . ' + ' .'30 days'));
                            }
                            
                           
                        }
                        if($flag==true){
                            $insertdata = new AwardWinner;
                            $insertdata->user_id = $user->id;
                            $insertdata->award_id = $data->award_id;
                            $insertdata->amount = $data->award;
                            $insertdata->total_downId = $down_id;
                            $insertdata->total_direct = $direct_id;
                            $insertdata->entry_time = $nextEntrydate;
                            $insertdata->save();
                            
                            $dashData = Dashboard::select('working_wallet','usd','total_profit','award_income','award_income_withdraw')->where('id',$user->id)->first(); 
                            $updatedata=array();
                            $updatedata['working_wallet']=($dashData->working_wallet)+($data->award); 
                            $updatedata['usd']=($dashData->usd)+($data->award); 
                            $updatedata['total_profit']=($dashData->total_profit)+($data->award); 
                            $updatedata['award_income']=($dashData->award_income)+($data->award); 
                            $updatedata['award_income_withdraw']=($dashData->award_income_withdraw)+($data->award); 
                            Dashboard::where('id',$user->id)->update($updatedata);
                         }

                    }
                }
            }     
        }
    }

    /**
     * [Award list]
     * @param  Request $request [token alpha-num]
     * @return [Array]         
     */
    public function useraward(Request $request) {

            $id = Auth::user()->id;
            $query = AwardWinner::join('tbl_awards_list as tal','tal.award_id','tbl_award_winner.award_id')
                    ->select('tal.*','tbl_award_winner.entry_time','tbl_award_winner.amount') 
                    ->where('tbl_award_winner.user_id',$id)
                    ->orderBy('tbl_award_winner.entry_time', 'desc');
                       

            if (!empty($request->input('search')['value']) && isset($request->input('search')['value'])) {
                    //searching loops on tbl_award_winner
                    $fields = getTableColumns('tbl_award_winner');
                    $search = $request->input('search')['value'];
                    $query = $query->where(function ($query) use ($fields, $search) {
                        foreach ($fields as $field) {
                            $query->orWhere('tbl_award_winner.' . $field, 'LIKE', '%' . $search . '%');
                        }
                       });
                }
                $totalRecord = $query->count();
                $arrPendings = $query->skip($request->input('start'))->take($request->input('length'))->get();

                $arrData['recordsTotal'] = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records'] = $arrPendings;
                if (!empty($arrPendings) && count($arrPendings) != '0') {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Data found Successfully!', $arrData);
                  
                } else {
                   return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Data not found', '');
                    
                }                
        
    }
   
}
