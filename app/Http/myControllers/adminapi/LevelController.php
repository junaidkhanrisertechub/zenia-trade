<?php

namespace App\Http\Controllers\adminapi;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\adminapi\CommonController;
use Illuminate\Support\Facades\Auth;
use App\Models\Activitynotification;
use App\Models\LevelView;
use App\Models\LevelView1;
use App\Models\TodayDetails;
use App\Models\ProjectSettings;
use App\User;
use DB;
use Config;
use Validator;

class LevelController extends Controller
{
    /**
     * define property variable
     *
     * @return
     */
    public $statuscode, $commonController, $settings;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CommonController $commonController)
    {
        $this->statuscode =    Config::get('constants.statuscode');
        $this->settings   = Config::get('constants.settings');
        $this->commonController = $commonController;

        $this->settings   = Config::get('constants.settings');
        $this->proSettings = ProjectSettings::where('status', 1)->first();
    }

    /**
     * insert levels view of users
     *
     * @return void
     */
    public function levelView($ref_user_id, $user_id, $level_id)
    {
        $finduser = User::where([['id', '=', $ref_user_id]])->first();
        $next_ref_user_id = $finduser->ref_user_id;
        if (($ref_user_id > 0)) {
            $regUser = User::where([['id', '=', $user_id]])->first();
            $entryTime = $regUser->entry_time;
            // insert data in level view table
            $insertdata = new LevelView;
            $insertdata->id = $ref_user_id;
            $insertdata->level = $level_id;
            $insertdata->down_id = $user_id;
            $insertdata->entry_time = $entryTime;

            $insertdata->save();
            //=================insert acitvity notification=============================

            $up_user = User::where([['id', '=', $ref_user_id]])->pluck('user_id')->first();
            $down_user = User::where([['id', '=', $user_id]])->pluck('user_id')->first();

            $actdata = array();
            $actdata['id'] = $ref_user_id;
            $actdata['message'] = 'User  ' . $up_user . '  has been added to your team as level  ' . $level_id;
            $actdata['status'] = 1;
            $actDta = Activitynotification::create($actdata);
        }

        if (($next_ref_user_id > 0)) {
            $level_id = $level_id + 1;
            $this->levelView($next_ref_user_id, $user_id, $level_id);
        } else {
            return 1;
        }
    }

    /**
     * update level view of the user
     *
     * @return void
     */
    public function updateLevelView($ref_user_id, $user_id, $level_id)
    {

        LevelView::where('down_id', $user_id)->delete();
        $this->levelView($ref_user_id, $user_id, $level_id);
    }

    /**
     * get level views
     *
     * @return
     */
    /**
     * get level views
     *
     * @return
     */
    public function getLevelsView(Request $request)
    {
        $arrInput = $request->all();
        // dd('hiii');
        $callfromlevelview = 1;

        if (isset($arrInput['user_id'])) {
            $id = User::where('user_id', $arrInput['user_id'])->pluck('id')->first();
            //$arrInput['level_id'] = 1;
        } else {
            $id = $arrInput['id'];
            // $level = $arrInput['level_id'];
        }

        if (isset($arrInput['id']) && isset($arrInput['level_id'])) {
            $arrWhere = [
                'id' =>     $id,
                'level' =>  $arrInput['level_id']
            ];

            /*$arrWhere1 = [
                'id' =>     $id,
                'level' =>  1
            ];*/

            $arrLevelview = LevelView::where($arrWhere)->get();
            //dd($arrLevelview);
            if (count($arrLevelview) > 0) {
                $arrIds = [];
                foreach ($arrLevelview as $view) {
                    array_push($arrIds, $view->id);
                }
                /* $query = DB::table('tbl_level_view as tlv')
                        ->join('tbl_users as tu1','tu1.id','=','tlv.id')
                        ->join('tbl_users as tu2','tu2.id','=','tlv.down_id')
                        ->leftjoin('tbl_topup as tp','tp.id','=','tlv.down_id')
                        ->leftjoin('tbl_country_new as cn','cn.iso_code','=','tu2.country')
                        ->whereIn('tlv.id',$arrIds)
                        ->where('tlv.level',$arrInput['level_id'])
                        ->select('tu1.id','tu2.id as down_id','tu1.user_id','tu2.user_id as down_user_id','cn.country','tu1.fullname as user_id_fullname','tu2.fullname as down_user_id_fullname','tlv.entry_time','tlv.level');*/

                $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                    //  ->leftjoin('tbl_topup as tp','tp.id','=','tbl_level_view.down_id')
                    ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                    //->join('tbl_users as tu3','tu3.id','=','tu1.ref_user_id')
                    ->join('tbl_users as tu3', 'tu3.id', '=', 'tu2.ref_user_id')
                    ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                    ->whereIn('tbl_level_view.id', $arrIds)
                    ->where('tbl_level_view.level', $arrInput['level_id'])
                    ->select('tbl_level_view.id', 'tu1.id', 'tu2.ref_user_id', 'tu2.id as down_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'cn.country', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level', 'tu3.user_id as sponser_userid', 'tu3.fullname as sponser_fullname');

                /*if(isset($arrInput['id'])){
                  $query = $query->where('tbl_level_view.id',$arrInput['id']);
                }
*/
                /* if(isset($arrInput['user_id'])){
                    $id = User::where('user_id',$arrInput['user_id'])->pluck('id')->first();
                  $query = $query->where('tbl_level_view.id',$id);
                }*/

                //dd($query->get());
                /*if(isset($arrInput['user_id'])) {
                    $query = $query->where('tu1.user_id',$arrInput['user_id']);
                }*/
                if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {

                    Session::put('from', $arrInput['frm_date']);
                    Session::put('to', $arrInput['to_date']);
                    /* $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
                    $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
                    $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);*/
                }
                if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
                    //searching loops on fields
                    $fields = getTableColumns('tbl_level_view');
                    $search = $arrInput['search']['value'];
                    $query  = $query->where(function ($query) use ($fields, $search) {
                        $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                            ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                        /*foreach($fields as $field){
                            $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                        }*/
                    });
                }
                $totalRecord  = $query->get()->count('tbl_level_view.srno');
                $query        = $query->orderBy('tbl_level_view.srno', 'desc');
                // $totalRecord  = $query->get()->count();
                $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrLevels;
                /*$arrFinalData = $arrData = [];
                if(!empty($arrLevels)) {
                    foreach ($arrLevels as $value) {
                        $arrData[$value->level][] = $value;
                    }

                    foreach ($arrData as $key => $value) {
                        $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                    }
                }*/

                if ($arrData['recordsTotal'] > 0) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else if (!isset($arrInput['id']) && !isset($arrInput['level_id']) && isset($arrInput['remember_token'])) {

            if (!empty($arrInput['remember_token'])) {
                $id = User::where([
                    ['remember_token', $arrInput['remember_token']],
                    ['type', '<>', '']
                ])->pluck('id')->first();

                $arrLevelview = LevelView::where('id', $id)->get();
                if (count($arrLevelview) > 0) {
                    $arrIds = [];
                    foreach ($arrLevelview as $view) {
                        array_push($arrIds, $view->id);
                    }
                    $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                        // ->leftjoin('tbl_topup as tp','tp.id','=','tbl_level_view.down_id')
                        ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                        ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                        ->join('tbl_users as tu3', 'tu3.id', '=', 'tu2.ref_user_id')

                        ->whereIn('tbl_level_view.id', $arrIds)
                        ->select('tu1.id', 'tu2.id as down_id', 'tu2.ref_user_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'cn.country', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level', 'tu3.user_id as sponser_userid', 'tu3.fullname as sponser_fullname');
                    if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                        Session::put('from', $arrInput['frm_date']);
                        Session::put('to', $arrInput['to_date']);
                        /*$arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
                        $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
                        $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);*/
                    }
                    if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                        //searching loops on fields
                        $fields = getTableColumns('tbl_level_view');
                        $search = $arrInput['search']['value'];
                        $query  = $query->where(function ($query) use ($fields, $search) {
                            $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                                ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                                ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                                ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                                ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                            /*foreach($fields as $field){
                                $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                            }*/
                        });
                    }
                    $totalRecord  = $query->get()->count('tbl_level_view.srno');
                    $query        = $query->orderBy('tbl_level_view.srno', 'desc');
                    // $totalRecord  = $query->get()->count();
                    $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

                    $arrData['recordsTotal']    = $totalRecord;
                    $arrData['recordsFiltered'] = $totalRecord;
                    $arrData['records']         = $arrLevels;

                    /*$arrFinalData = $arrData = [];
                    if(!empty($arrLevels)) {
                        foreach ($arrLevels as $value) {
                            $arrData[$value->level][] = $value;
                        }

                        foreach ($arrData as $key => $value) {
                            $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                        }
                    }*/

                    if ($arrData['recordsTotal'] > 0) {
                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                }
            }
        } else if (isset($arrInput['id']) && !isset($arrInput['level_id'])) {

            if (isset($arrInput['user_id'])) {
                $id = User::where('user_id', $arrInput['user_id'])->pluck('id')->first();
            } else {
                $id = $arrInput['id'];
            }
            $arrLevelview = LevelView::where('id', $id)->get();

            if (count($arrLevelview) > 0) {
                $arrIds = [];
                foreach ($arrLevelview as $view) {
                    array_push($arrIds, $view->id);
                }
                // dd($arrIds);
                $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                    ////->leftjoin('tbl_topup as tp','tp.id','=','tbl_level_view.down_id')
                    ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                    ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                    ->join('tbl_users as tu3', 'tu3.id', '=', 'tu2.ref_user_id')
                    ->whereIn('tbl_level_view.id', $arrIds)
                    ->select('tu1.id', 'tu2.id as down_id', 'tu2.ref_user_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level', 'tu3.user_id as sponser_userid', 'tu3.fullname as sponser_fullname');
                //dd($query->get());
                /*if(isset($arrInput['id'])){
                  $query = $query->where('tbl_level_view.id',$arrInput['id']);
                }*/
                if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {

                    Session::put('from', $arrInput['frm_date']);
                    Session::put('to', $arrInput['to_date']);
                    /* $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
                    $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
                    $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);*/
                }
                if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                    //searching loops on fields
                    $fields = getTableColumns('tbl_level_view');
                    $search = $arrInput['search']['value'];
                    $query  = $query->where(function ($query) use ($fields, $search) {
                        $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                            ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                        /*foreach($fields as $field){
                            $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                        }*/
                    });
                }
                $totalRecord  = $query->get()->count('tbl_level_view.srno');
                $query        = $query->orderBy('tbl_level_view.srno', 'desc');
                // $totalRecord  = $query->get()->count();
                $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrLevels;
                /*$arrFinalData = $arrData = [];
                if(!empty($arrLevels)) {
                    foreach ($arrLevels as $value) {
                        $arrData[$value->level][] = $value;
                    }

                    foreach ($arrData as $key => $value) {
                        $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                    }
                }*/

                if ($arrData['recordsTotal'] > 0) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                //->leftjoin('tbl_topup as tp','tp.id','=','tbl_level_view.down_id')
                ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                ->join('tbl_users as tu3', 'tu3.id', '=', 'tu2.ref_user_id')
                ->where('tu1.type', '')
                ->select('tu1.id', 'tu2.id as down_id', 'tu2.ref_user_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'cn.country', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level', 'tu3.user_id as sponser_userid', 'tu3.fullname as sponser_fullname');
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {

                Session::put('from', $arrInput['frm_date']);
                Session::put('to', $arrInput['to_date']);
                /* $arrInput['frm_date'] = date('Y-m-d',strtotime($arrInput['frm_date']));
                  $arrInput['to_date']  = date('Y-m-d',strtotime($arrInput['to_date']));
                  $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"),[$arrInput['frm_date'], $arrInput['to_date']]);*/
            }
            if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_level_view');
                $search = $arrInput['search']['value'];
                $query  = $query->where(function ($query) use ($fields, $search) {
                    $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                        ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                        ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                    /*foreach($fields as $field){
                        $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                    }*/
                });
            }
            $totalRecord  = $query->get()->count('tbl_level_view.srno');
            $query        = $query->orderBy('tbl_level_view.srno', 'desc');
            // $totalRecord  = $query->get()->count();
            $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $arrLevels;

            /*$arrFinalData = $arrData = [];
              if(!empty($arrLevels)) {
                foreach ($arrLevels as $value) {
                  $arrData[$value->level][] = $value;
                }

                foreach ($arrData as $key => $value) {
                  $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                }
              }*/

            if ($arrData['recordsTotal'] > 0) {
                Session::flush();
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                Session::flush();
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        }
    }





    public function getLevelsViewold(Request $request)
    {
        //dd($request);
        $arrInput = $request->all();

        if (isset($arrInput['id']) && isset($arrInput['level_id'])) {
            $arrWhere = [
                'id' => $arrInput['id'],
                'level' => $arrInput['level_id']
            ];
            $arrLevelview = LevelView::where($arrWhere)->get();
            if (count($arrLevelview) > 0) {
                $arrIds = [];
                foreach ($arrLevelview as $view) {
                    array_push($arrIds, $view->id);
                }
                $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                    ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                    ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                    ->whereIn('tbl_level_view.id', $arrIds)
                    ->where('tbl_level_view.level', $arrInput['level_id'])
                    ->select('tu1.id', 'tu2.id as down_id', 'tu2.ref_user_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'cn.country', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level');
                if (isset($arrInput['id'])) {
                    $query = $query->where('tbl_level_view.id', $arrInput['id']);
                }
                if (isset($arrInput['user_id'])) {
                    $query = $query->where('tu1.user_id', $arrInput['user_id']);
                }
                if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                    $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                    $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
                    $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
                }
                if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
                    //searching loops on fields
                    $fields = getTableColumns('tbl_level_view');
                    $search = $arrInput['search']['value'];
                    $query  = $query->where(function ($query) use ($fields, $search) {
                        $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                            ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                        /*foreach($fields as $field){
                            $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                        }*/
                    });
                }
                $totalRecord  = $query->get()->count('tbl_level_view.srno');
                $query        = $query->orderBy('tbl_level_view.srno', 'desc');
                // $totalRecord  = $query->get()->count();
                $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrLevels;
                /*$arrFinalData = $arrData = [];
                if(!empty($arrLevels)) {
                    foreach ($arrLevels as $value) {
                        $arrData[$value->level][] = $value;
                    }

                    foreach ($arrData as $key => $value) {
                        $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                    }
                }*/

                if ($arrData['recordsTotal'] > 0) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else if (!isset($arrInput['id']) && !isset($arrInput['level_id']) && isset($arrInput['remember_token'])) {

            if (!empty($arrInput['remember_token'])) {
                $id = User::where([
                    ['remember_token', $arrInput['remember_token']],
                    ['type', '<>', '']
                ])->pluck('id')->first();

                $arrLevelview = LevelView::where('id', $id)->get();
                if (count($arrLevelview) > 0) {
                    $arrIds = [];
                    foreach ($arrLevelview as $view) {
                        array_push($arrIds, $view->id);
                    }
                    $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                        ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                        ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                        ->whereIn('tbl_level_view.id', $arrIds)
                        ->select('tu1.id', 'tu2.id as down_id', 'tu2.ref_user_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'cn.country', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level');
                    if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                        $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                        $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
                        $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
                    }
                    if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                        //searching loops on fields
                        $fields = getTableColumns('tbl_level_view');
                        $search = $arrInput['search']['value'];
                        $query  = $query->where(function ($query) use ($fields, $search) {
                            $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                                ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                                ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                                ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                                ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                            /*foreach($fields as $field){
                                $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                            }*/
                        });
                    }
                    $totalRecord  = $query->get()->count('tbl_level_view.srno');
                    $query        = $query->orderBy('tbl_level_view.srno', 'desc');
                    // $totalRecord  = $query->get()->count();
                    $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

                    $arrData['recordsTotal']    = $totalRecord;
                    $arrData['recordsFiltered'] = $totalRecord;
                    $arrData['records']         = $arrLevels;

                    /*$arrFinalData = $arrData = [];
                    if(!empty($arrLevels)) {
                        foreach ($arrLevels as $value) {
                            $arrData[$value->level][] = $value;
                        }

                        foreach ($arrData as $key => $value) {
                            $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                        }
                    }*/

                    if ($arrData['recordsTotal'] > 0) {
                        return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
                    } else {
                        return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                    }
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                }
            }
        } else if (isset($arrInput['id']) && !isset($arrInput['level_id'])) {
            $arrLevelview = LevelView::where('id', $arrInput['id'])->get();
            if (count($arrLevelview) > 0) {
                $arrIds = [];
                foreach ($arrLevelview as $view) {
                    array_push($arrIds, $view->id);
                }
                $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                    ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                    ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                    ->whereIn('tbl_level_view.id', $arrIds)
                    ->select('tu1.id', 'tu2.id as down_id', 'tu2.ref_user_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'cn.country', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level');
                if (isset($arrInput['id'])) {
                    $query = $query->where('tbl_level_view.id', $arrInput['id']);
                }
                if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                    $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                    $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
                    $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
                }
                if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                    //searching loops on fields
                    $fields = getTableColumns('tbl_level_view');
                    $search = $arrInput['search']['value'];
                    $query  = $query->where(function ($query) use ($fields, $search) {
                        $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                            ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                            ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                            ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                        /*foreach($fields as $field){
                            $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                        }*/
                    });
                }
                $totalRecord  = $query->get()->count('tbl_level_view.srno');
                $query        = $query->orderBy('tbl_level_view.srno', 'desc');
                // $totalRecord  = $query->get()->count();
                $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

                $arrData['recordsTotal']    = $totalRecord;
                $arrData['recordsFiltered'] = $totalRecord;
                $arrData['records']         = $arrLevels;
                /*$arrFinalData = $arrData = [];
                if(!empty($arrLevels)) {
                    foreach ($arrLevels as $value) {
                        $arrData[$value->level][] = $value;
                    }

                    foreach ($arrData as $key => $value) {
                        $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                    }
                }*/

                if ($arrData['recordsTotal'] > 0) {
                    return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
                } else {
                    return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
                }
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            $query = LevelView::join('tbl_users as tu1', 'tu1.id', '=', 'tbl_level_view.id')
                ->join('tbl_users as tu2', 'tu2.id', '=', 'tbl_level_view.down_id')
                ->leftjoin('tbl_country_new as cn', 'cn.iso_code', '=', 'tu2.country')
                ->where('tu1.type', '')
                ->select('tu1.id', 'tu2.id as down_id', 'tu2.ref_user_id', 'tu1.user_id', 'tu2.user_id as down_user_id', 'cn.country', 'tu1.fullname as user_id_fullname', 'tu2.fullname as down_user_id_fullname', 'tbl_level_view.entry_time', 'tbl_level_view.level');
            if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
                $arrInput['frm_date'] = date('Y-m-d', strtotime($arrInput['frm_date']));
                $arrInput['to_date']  = date('Y-m-d', strtotime($arrInput['to_date']));
                $query = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_level_view.entry_time,'%Y-%m-%d')"), [$arrInput['frm_date'], $arrInput['to_date']]);
            }
            if (!empty($arrInput['search']['value']) && isset($arrInput['search']['value'])) {
                //searching loops on fields
                $fields = getTableColumns('tbl_level_view');
                $search = $arrInput['search']['value'];
                $query  = $query->where(function ($query) use ($fields, $search) {
                    $query->orWhere('tu1.user_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('tu2.user_id', 'LIKE', '%' . $search . '%')
                        ->orWhere('tbl_level_view.level', 'LIKE', '%' . $search . '%')
                        ->orWhere('cn.country', 'LIKE', '%' . $search . '%')
                        ->orWhere('tu2.fullname', 'LIKE', '%' . $search . '%');
                    /*foreach($fields as $field){
                        $query->orWhere('tu1.'.$field,'LIKE','%'.$search.'%');
                    }*/
                });
            }
            $totalRecord  = $query->get()->count('tbl_level_view.srno');
            $query        = $query->orderBy('tbl_level_view.srno', 'desc');
            // $totalRecord  = $query->get()->count();
            $arrLevels    = $query->skip($arrInput['start'])->take($arrInput['length'])->get();

            $arrData['recordsTotal']    = $totalRecord;
            $arrData['recordsFiltered'] = $totalRecord;
            $arrData['records']         = $arrLevels;

            /*$arrFinalData = $arrData = [];
              if(!empty($arrLevels)) {
                foreach ($arrLevels as $value) {
                  $arrData[$value->level][] = $value;
                }

                foreach ($arrData as $key => $value) {
                  $arrFinalData[] = (object)['level_id'=>$key,'levels'=>$value];
                }
              }*/

            if ($arrData['recordsTotal'] > 0) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        }
    }

    /**
     * get user levels
     *
     * @return
     */
    /*public function getUserLevels(Request $request) {
    	$arrInput 	= 	$request->all();
    	//validate the info, create rules for the inputs
		$rules = array('id' => 'required');
		//run the validation rules on the inputs
		$validator = Validator::make($arrInput, $rules);
		if($validator->fails()) {
		    $message = $validator->errors();
		    return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
		} else {
	    	$arrLevels 	= LevelView::distinct()->where('id',$arrInput['id'])->selectRaw('level as level_id,CONCAT("level"," ",level) as level_name')->get(['level']);

	    	if(!empty($arrLevels)){
				return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'],'Record found', $arrLevels);
			}else{
				return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'],'Record not found','');
			}
		}
    }*/

    public function getUserLevels(Request $request)
    {
        $arrInput   =   $request->all();
        //validate the info, create rules for the inputs
        $rules = array('id' => 'required');
        //run the validation rules on the inputs
        $validator = Validator::make($arrInput, $rules);
        if ($validator->fails()) {
            $message = $validator->errors();
            return sendresponse($this->statuscode[403]['code'], $this->statuscode[403]['status'], 'Input credentials is invalid or required', $message);
        } else {
            if (isset($arrInput['user_id'])) {
                $id = User::where('user_id', $arrInput['user_id'])->pluck('id')->first();
            } else {
                $id = $arrInput['id'];
            }
            /*$arrLevels    = LevelView::distinct()->where('id',$id)->selectRaw('level as level_id,CONCAT("level"," ",level) as level_name')->orderBy('level','asc')->get(['level']);*/
            $arrLevels  = LevelView1::distinct()->where('id', $id)->selectRaw('level as level_id,level as level_name')->orderBy('level', 'asc')->get(['level']);

            if (!empty($arrLevels)) {
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrLevels);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        }
    }

    /**
     * get levels views tree
     *
     * @return
     */
    public function getLevelsViewTree(Request $request)
    {
        $arrInput = $request->all();

        $objUser = User::leftjoin('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            ->leftjoin('tbl_curr_amt_details as tcad', 'tcad.user_id', '=', 'tbl_users.id')
            ->selectRaw('tbl_users.id,tbl_users.rank,tbl_users.user_id,tbl_users.fullname,tbl_users.position,tbl_users.virtual_parent_id,tbl_users.l_c_count,tbl_users.r_c_count,tbl_users.l_bv,tbl_users.r_bv,tcad.left_bv,tcad.right_bv,tu.user_id as sponser_id,tu.fullname as sponser_fullname');
        if (isset($arrInput['id']) && !empty($arrInput['id'])) {
            $objUser = $objUser->where('tbl_users.id', $arrInput['id']);
        } else {
            $objUser = $objUser->where('tbl_users.remember_token', $arrInput['remember_token']);
        }
        $objUser = $objUser->first();
        //dd($objUser);
        if (!empty($objUser)) {
            $levelArray = [];
            $levlQr = DB::table('tbl_users')
                ->leftjoin('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
                ->leftjoin('tbl_curr_amt_details as tcad', 'tcad.user_id', '=', 'tbl_users.id')
                ->selectRaw('tbl_users.id,tbl_users.rank,tbl_users.user_id,tbl_users.fullname,(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" ELSE "" END) as position,"' . $this->settings['present_img'] . '" as image,tbl_users.virtual_parent_id,tbl_users.l_c_count,tbl_users.r_c_count,tbl_users.l_bv,tbl_users.r_bv,tcad.left_bv,tcad.right_bv,tu.user_id as sponser_id,tu.fullname as sponser_fullname')
                ->where('tbl_users.virtual_parent_id', $objUser->id)
                ->orderBy('tbl_users.position', 'asc')
                ->get();
            foreach ($levlQr as $value1) {
                $levelArray['level_1'][$objUser->id][$value1->position] = $value1;
                $levlQrT = DB::table('tbl_users')
                    ->leftjoin('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
                    ->leftjoin('tbl_curr_amt_details as tcad', 'tcad.user_id', '=', 'tbl_users.id')
                    ->selectRaw('tbl_users.id,tbl_users.rank,tbl_users.user_id,tbl_users.fullname,(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" ELSE "" END) as position,"' . $this->settings['present_img'] . '" as image,tbl_users.virtual_parent_id,tbl_users.l_c_count,tbl_users.r_c_count,tbl_users.l_bv,tbl_users.r_bv,tcad.left_bv,tcad.right_bv,tu.user_id as sponser_id,tu.fullname as sponser_fullname')
                    ->where('tbl_users.virtual_parent_id', $value1->id)
                    ->orderBy('tbl_users.position', 'asc')
                    ->get();
                foreach ($levlQrT as $value2) {
                    $levelArray['level_2'][$value1->id][$value2->position] = $value2;
                    $levlQrT11 = DB::table('tbl_users')
                        ->leftjoin('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
                        ->leftjoin('tbl_curr_amt_details as tcad', 'tcad.user_id', '=', 'tbl_users.id')
                        ->selectRaw('tbl_users.id,tbl_users.rank,tbl_users.user_id,tbl_users.fullname,(CASE tbl_users.position WHEN 1 THEN "Left" WHEN 2 THEN "Right" ELSE "" END) as position,"' . $this->settings['present_img'] . '" as image,tbl_users.virtual_parent_id,tbl_users.l_c_count,tbl_users.r_c_count,tbl_users.l_bv,tbl_users.r_bv,tcad.left_bv,tcad.right_bv,tu.user_id as sponser_id,tu.fullname as sponser_fullname')
                        ->where('tbl_users.virtual_parent_id', $value2->id)
                        ->orderBy('tbl_users.position', 'asc')
                        ->get();
                    /*foreach ($levlQrT11 as $value3) {
                        $levelArray['level_3'][$value2->id][$value3->position] = $value3;   
                    }*/
                }
            }
            //printData($levelArray);
            //object created for absent data left
            $objLeft = (object) array(
                'id' => $objUser->id,
                'user_id' => 'Not Present',
                'fullname' => 'Not Present',
                'position' => 'Left',
                'image' => $this->settings['absent_img'],
                'virtual_parent_id' => 'Not Present',
                'l_c_count' => 'Not Present',
                'r_c_count' => 'Not Present',
                'l_bv' => 'Not Present',
                'r_bv' => 'Not Present',
                'left_bv' => 'Not Present',
                'right_bv' => 'Not Present',
                'sponser_id' => 'Not Present',
                'sponser_fullname' => 'Not Present',
                'rank' => 'Not Present'
            );
            //object created for absent data right
            $objRight = (object) array(
                'id' => $objUser->id,
                'user_id' => 'Not Present',
                'fullname' => 'Not Present',
                'position' => 'Right',
                'image' => $this->settings['absent_img'],
                'virtual_parent_id' => 'Not Present',
                'l_c_count' => 'Not Present',
                'r_c_count' => 'Not Present',
                'l_bv' => 'Not Present',
                'r_bv' => 'Not Present',
                'left_bv' => 'Not Present',
                'right_bv' => 'Not Present',
                'sponser_id' => 'Not Present',
                'sponser_fullname' => 'Not Present',
                 'rank' => 'Not Present'
            );
            $arrTemp = [
                'level_1' =>  ['position_1' => []],
                'level_2' =>  ['position_1' => [], 'position_2' => []]
            ];
            //---level 1---//
            if (isset($levelArray['level_1']) && !empty($levelArray['level_1'])) {
                foreach ($levelArray['level_1'] as $key => $value) {
                    /*if(count($levelArray['level_1']) < 1){*/
                    if (count($value) < 2) {
                        if (isset($levelArray['level_1'][$key]['Left'])) {
                            $arrTemp['level_1']['position_1']['Left'] = $value['Left'];
                            $arrTemp['level_1']['position_1']['Right'] = $objRight;
                        } else {
                            $arrTemp['level_1']['position_1']['Left'] = $objLeft;
                            $arrTemp['level_1']['position_1']['Right'] = $value['Right'];
                        }
                    } else if (count($value) == 2) {
                        $arrTemp['level_1']['position_1'] = $value;
                    }
                    /* }else if(count($levelArray['level_1']) == 1){
                        $arrTemp['level_1'] = $levelArray['level_1'];
                    }*/
                }
            } else if (!isset($levelArray['level_1']) && empty($levelArray['level_1'])) {
                $arrTemp['level_1']['position_1']['Left']  =  $objLeft;
                $arrTemp['level_1']['position_1']['Right'] =  $objRight;
            } else {
                $arrTemp['level_1'] = $levelArray['level_1'];
            }
            //---level 1 end---//

            //---level 2---//
            if (isset($levelArray['level_2']) && !empty($levelArray['level_2'])) {
                foreach ($levelArray['level_2'] as $key => $value) {
                    //if(count($levelArray['level_2']) < 2){
                    $vpId = User::where('id', $key)->pluck('position')->first();
                    if ($vpId == 2) {
                        if (count($value) < 2) {
                            if (isset($levelArray['level_2'][$key]['Left'])) {
                                $arrTemp['level_2']['position_2']['Left']  = $value['Left'];
                                $arrTemp['level_2']['position_2']['Right'] = $objRight;
                            } else {
                                $arrTemp['level_2']['position_2']['Left']  = $objLeft;
                                $arrTemp['level_2']['position_2']['Right'] = $value['Right'];
                            }
                            if (count($arrTemp['level_2']['position_1']) == 0) {
                                $arrTemp['level_2']['position_1']['Left']  = $objLeft;
                                $arrTemp['level_2']['position_1']['Right'] = $objRight;
                            }
                        } else if (count($value) == 2) {
                            $arrTemp['level_2']['position_2'] = $value;
                            if (count($arrTemp['level_2']['position_1']) == 0) {
                                $arrTemp['level_2']['position_1']['Left']  = $objLeft;
                                $arrTemp['level_2']['position_1']['Right'] = $objRight;
                            }
                        }
                    } else if ($vpId == 1) {
                        if (count($value) < 2) {
                            if (isset($levelArray['level_2'][$key]['Left'])) {
                                $arrTemp['level_2']['position_1']['Left']  = $value['Left'];
                                $arrTemp['level_2']['position_1']['Right'] = $objRight;
                            } else {
                                $arrTemp['level_2']['position_1']['Left']  = $objLeft;
                                $arrTemp['level_2']['position_1']['Right'] = $value['Right'];
                            }
                            if (count($arrTemp['level_2']['position_2']) == 0) {
                                $arrTemp['level_2']['position_2']['Left'] = $objLeft;
                                $arrTemp['level_2']['position_2']['Right'] = $objRight;
                            }
                        } else if (count($value) == 2) {
                            $arrTemp['level_2']['position_1'] = $value;
                            if (count($arrTemp['level_2']['position_2']) == 0) {
                                $arrTemp['level_2']['position_2']['Left'] = $objLeft;
                                $arrTemp['level_2']['position_2']['Right'] = $objRight;
                            }
                        }
                    }
                    /*}else if(count($levelArray['level_1']) == 2){
                        $arrTemp['level_2'] = $levelArray['level_2'];  
                    }*/
                }
            } else if (!isset($levelArray['level_2']) && empty($levelArray['level_2'])) {
                $arrTemp['level_2']['position_1']['Left'] = $objLeft;
                $arrTemp['level_2']['position_1']['Right'] = $objRight;
                $arrTemp['level_2']['position_2']['Left'] =  $objLeft;
                $arrTemp['level_2']['position_2']['Right'] = $objRight;
            } else {
                $arrTemp['level_2'] = $levelArray['level_2'];
            }
            //---level 2 end---//

            //printData($arrTemp);
            if (!empty($objUser)) {
                $arrFinalData['userdata'] = $objUser;
                $arrFinalData['usertreeview'] = $arrTemp;
                return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrFinalData);
            } else {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
            }
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not found', '');
        }
    }


    /**
     * get levels views tree manual filling
     * id, remembered token
     * @return
     */
    public function getLevelsViewTreeManualProductBase(Request $request)
    {

        $arrInput = $request->all();
        $levelArray = [];
        $getTreeImages = DB::table('tbl_tree_imges')->where([['type','=',"1"]])->get();
        //cross leg condition
        if (isset($arrInput['id']) && !empty($arrInput['id']) && Auth::user()->user_id != $arrInput['id']) {
            $checkUser = User::select('tbl_users.id')->join('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_users.id')->where(['tbl_users.user_id' => $arrInput['id'], 'ttd.to_user_id' => Auth::user()->id])->first();

            if (empty($checkUser)) {
                return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'User not available', '');
            }
        }

        $objUser = User::leftjoin('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            /*->leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbl_users.virtual_parent_id')*/
            // ->leftjoin('tbl_curr_amt_details as tcad', 'tcad.user_id', '=', 'tbl_users.id')
            /*->leftjoin('tbl_topup as tt', 'tt.id', '=', 'tbl_users.id')
            ->leftjoin('tbl_product as tp', 'tp.id', '=', 'tt.type')*/
            ->selectRaw('tbl_users.id,tbl_users.rank,tbl_users.user_id,tbl_users.fullname,tu.user_id as sponsor_id,tu.fullname as sponsor_fullname,tbl_users.l_c_count,tbl_users.r_c_count,COALESCE(tbl_users.l_bv,0) as l_bv,COALESCE(tbl_users.r_bv,0) as r_bv,COALESCE(tbl_users.curr_l_bv,0) as left_bv,COALESCE(tbl_users.curr_r_bv,0) as right_bv,tbl_users.position,"0" as level,(CASE WHEN  tbl_users.status = "Inactive" THEN "' . $this->settings['block_img'] . '" WHEN tbl_users.topup_status = "0" THEN "' . $this->settings['no_topup'] . '" WHEN tbl_users.topup_status = "1" THEN "' . $this->settings['present_img'] . '"  ELSE "" END) as image,tbl_users.entry_time,tbl_users.amount as selftopup');

        if (isset($arrInput['id']) && !empty($arrInput['id'])) {
            $objUser = $objUser->where('tbl_users.user_id', $arrInput['id']);
        } else {
            $objUser = $objUser->where('tbl_users.user_id', Auth::user()->user_id);
        }
        $objUser = $objUser->first();
       // dd($objUser,Auth::user()->user_id);   

        if (isset($objUser) && !empty($objUser)) {

            $levlQr = $this->getLevelsViewTreeDataByIdForProductBase($objUser->id, $objUser->id,$getTreeImages);
            //dd($levlQr);
            if (!empty($levlQr) && count($levlQr) > 0) {

                foreach ($levlQr as $value1) {
                    //dd("hii".$objUser->id,$value1->id);
                    $levelArray[$value1->level][$objUser->id][$value1->position] = $value1;
                    $levlQrT = $this->getLevelsViewTreeDataByIdForProductBase($objUser->id, $value1->id,$getTreeImages);
                    //  dd($levlQrT);

                    if (!empty($levlQrT) && count($levlQrT) > 0) {

                        foreach ($levlQrT as $value2) {

                            $levelArray[$value2->level][$value1->id][$value2->position] = $value2;
                            $levlQrT11 = $this->getLevelsViewTreeDataByIdForProductBase($objUser->id, $value2->id,$getTreeImages);

                            if (!empty($levlQrT11) && count($levlQrT11) > 0) {

                                foreach ($levlQrT11 as $value3) {

                                    $levelArray[$value3->level][$value2->id][$value3->position] = $value3;
                                }
                            }
                        }
                    }
                }
            }
        }

        // dd($levelArray);

        //matrix value from project settings
        $matrixValue    = explode(":", $this->proSettings->matrix_value)[0];
        //upto level show from project settings
        $levelValue     = $this->proSettings->level_show;
        $counter        = 1;
        $arrPos         = [];
        for ($x = 1; $x <= $matrixValue; $x++) {
            for ($y = 1; $y <= $matrixValue; $y++) {
                $arrPos[$x][$y] = $counter;
                $counter++;
            }
        }
        $arrLevelsTemp = [];
        $counter1      = 1;
        if (!empty($levelArray) && count($levelArray) > 0) {
            foreach ($levelArray as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    if ($key >= 3) {
                        $objPos = User::select('tus.position as vp_position', 'tbl_users.position as position')->join('tbl_users as tus', 'tus.id', '=', 'tbl_users.virtual_parent_id')
                            ->where('tbl_users.id', $key1)->first();

                        $arrLevelsTemp[$key][$arrPos[$objPos->vp_position][$objPos->position]] = $value1;
                    } else {
                        $position = ($counter1 == 1) ? 1 : User::where('id', $key1)->pluck('position')->first();
                        $arrLevelsTemp[$key][$position] = $value1;
                    }
                    $counter1++;
                }
            }
        }
        //object created for absent data left
        $arrTemp = [];
        for ($i = 1; $i <= $levelValue; $i++) {
            $x = pow($matrixValue, $i - 1);
            for ($j = 1; $j <= $x; $j++) {
                for ($k = 1; $k <= $matrixValue; $k++) {
                    if (isset($arrLevelsTemp[$i][$j][$k]) && !empty($arrLevelsTemp[$i][$j][$k])) {
                        $arrTemp[$i][$j][$k] = $arrLevelsTemp[$i][$j][$k];
                    } else {
                        $arrTemp[$i][$j][$k] = (object) array(
                            "id"                => "Not Available",
                            "user_id"           => "Not Available",
                            "fullname"          => "Not Available",
                            "sponsor_id"        => "Not Available",
                            "sponsor_fullname"  => "Not Available",
                            "virtual_id"        => "Not Available",
                            "virtual_fullname"  => "Not Available",
                            "l_c_count"         => "Not Available",
                            "r_c_count"         => "Not Available",
                            "l_bv"              => "Not Available",
                            "r_bv"              => "Not Available",
                            "left_bv"           => "Not Available",
                            "right_bv"          => "Not Available",
                            "left_bv_rep"       => "Not Available",
                            "right_bv_rep"      => "Not Available",
                            "rank"              => "Not Available",
                            "position"          => $k,
                            "virtual_parent_id" => "Not Available",
                            "level"             => $i,
                            "image"             => $getTreeImages[1]->img_name,
                            "entry_time"        => "Not Available",
                         

                        );
                    }
                }
            }
        }

        $arrFinalData = [];
        $count = 0;
        foreach ($arrTemp as $key => $value) {
            $arrFinalData[$count]['level'] = [];
            foreach ($value as $key1 => $value1) {
                foreach ($value1 as $key2 => $value2) {
                    array_push($arrFinalData[$count]['level'], $value2);
                }
            }
            $count++;
        }
        $arrDataTemp = [];
        foreach ($arrFinalData as $key => $value) {
            $arrDataTemp[] = $value;
        }
        if (!empty($objUser)) {
            $arrData['user']        = $objUser;
            $arrData['tree_data']   = $arrDataTemp;
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }

    private function getLevelsViewTreeDataByIdForProductBase($to_user_id, $virtual_parent_id,$getTreeImages)
    {
        // dd($to_user_id,$virtual_parent_id);
        $arrData = DB::table('tbl_users')
            ->leftjoin('tbl_users as tu', 'tu.id', '=', 'tbl_users.ref_user_id')
            /*->leftjoin('tbl_users as tu1', 'tu1.id', '=', 'tbl_users.virtual_parent_id')*/
            ->leftjoin('tbl_today_details as ttd', 'ttd.from_user_id', '=', 'tbl_users.id')
            // ->leftjoin('tbl_curr_amt_details as tcad', 'tcad.user_id', '=', 'tbl_users.id')
            /*->leftjoin('tbl_topup as tt', 'tt.id', '=', 'tbl_users.id')
            ->leftjoin('tbl_product as tp', 'tp.id', '=', 'tt.type')*/
            ->selectRaw('tbl_users.id,tbl_users.rank,tbl_users.user_id,tbl_users.fullname,tu.user_id as sponsor_id,tu.fullname as sponsor_fullname,tbl_users.l_c_count,tbl_users.r_c_count,COALESCE(tbl_users.l_bv,0) as l_bv,COALESCE(tbl_users.r_bv,0) as r_bv,COALESCE(tbl_users.curr_l_bv,0) as left_bv,COALESCE(tbl_users.curr_r_bv,0) as right_bv,tbl_users.position,ttd.level,(CASE WHEN  tbl_users.status = "Inactive" THEN "' . $getTreeImages[2]->img_name . '" WHEN tbl_users.topup_status = "0" THEN "' . $getTreeImages[3]->img_name . '" WHEN tbl_users.topup_status = "1" THEN "' . $getTreeImages[0]->img_name . '"  ELSE "" END) as image,tbl_users.entry_time,tbl_users.amount as selftopup')
            ->where('tbl_users.virtual_parent_id', $virtual_parent_id)
            ->where('ttd.to_user_id', $to_user_id)
            ->orderBy('tbl_users.position', 'asc')
            ->get();
        // dd($arrData,$virtual_parent_id,$to_user_id);
        if (isset($arrData) && !empty($arrData)) {
            return $arrData;
        } else {
            return [];
        }
    }

    /**
     * get team view report
     *
     * @return void
     */
    public function getTeamViews(Request $request)
    {
        $arrInput = $request->all();

        $myarray = [];

        if (isset($arrInput['upline_id']) && !empty($arrInput['upline_id'])) {
            $user_id = User::where('user_id', $arrInput['upline_id'])->first();
        } else {
            // $user_id = Auth::user();
            $user_id=User::where('type', 'Admin')->where('id', 1)->first();
        }

        $array = [
            'left_id'   => $user_id->l_c_count,
            'right_id'  => $user_id->r_c_count,
            'left_bv'   => $user_id->l_bv,
            'right_bv'  => $user_id->r_bv
        ];

        $query = TodayDetails::select('tu.id', 'tu.user_id', 'tu.fullname', 'tu1.user_id as sponser_id', 'tu2.user_id as upline_id', DB::raw('(CASE tbl_today_details.position WHEN "1" THEN "Left" WHEN "2" THEN "Right" ELSE "" END) as position'), DB::raw('DATE_FORMAT(tu.entry_time,"%Y/%m/%d %H:%i:%s") as joining_date'), 'tu.l_c_count as left_id', 'tu.r_c_count as right_id', 'tu.l_bv as left_bv', 'tu.r_bv as right_bv', 'tu.pin_number')
            ->join('tbl_users as tu', 'tu.id', '=', 'tbl_today_details.from_user_id')
            ->join('tbl_users as tu1', 'tu1.id', '=', 'tu.ref_user_id')
            ->join('tbl_users as tu2', 'tu2.id', '=', 'tu.virtual_parent_id')
            ->where('tbl_today_details.to_user_id', $user_id->id);

        if (isset($arrInput['status'])) {
            $query  = $query->where('tbl_withdraw_link.status', $arrInput['status']);
        }
        if (isset($arrInput['position'])) {
            $query  = $query->where('tbl_today_details.position', $arrInput['position']);
        }
        if (isset($arrInput['user_id'])) {
            $query  = $query->where('tu.user_id', $arrInput['user_id']);
        }
        if (isset($arrInput['sponsor_id'])) {
            $query  = $query->where('tu1.user_id', $arrInput['sponsor_id']);
        }
        if (isset($arrInput['upline_id'])) {
            $query  = $query->where('tu2.user_id', $arrInput['upline_id']);
        }
        // $query->when(request('position') == 'Left', function ($q) {
        //     return $q->where('tbl_today_details.position', '=', '1');
        // });
        // $query->when(request('position') == 'Right', function ($q) {
        //     return $q->where('tbl_today_details.position', '=', '2');
        // });
        if (isset($arrInput['frm_date']) && isset($arrInput['to_date'])) {
            $query  = $query->whereBetween(DB::raw("DATE_FORMAT(tbl_today_details.entry_time,'%Y-%m-%d')"), [date('Y-m-d', strtotime($arrInput['frm_date'])), date('Y-m-d', strtotime($arrInput['to_date']))]);
        }
        // if (isset($arrInput['search']['value']) && !empty($arrInput['search']['value'])) {
        //     //searching loops on fields
        //     $fields = ['tu.user_id', 'tu.fullname', 'tu1.user_id', 'tu2.user_id', 'tu.l_c_count', 'tu.r_c_count', 'tu.l_bv', 'tu.r_bv', 'tu.pin_number'];
        //     $search = $arrInput['search']['value'];
        //     $query  = $query->where(function ($query) use ($fields, $search) {
        //         foreach ($fields as $field) {
        //             $query->orWhere($field, 'LIKE', '%' . $search . '%');
        //         }
        //         $query->orWhereRaw('(CASE tbl_today_details.position WHEN "1" THEN "Left" WHEN "2" THEN "Right" ELSE "" END) LIKE "%' . $search . '%"');
        //     });
        // }
      
        $query                      = $query->orderBy('tbl_today_details.today_id', 'desc');
        $totalRecord                = $query->count('tbl_today_details.today_id');
        // $totalRecord                = $query->count();
        $arrData                    = setPaginate1($query, $arrInput['start'], $arrInput['length'], '');
        $arrData['user_binary']     = $array;

        if ($arrData['recordsTotal'] > 0) {
            return sendresponse($this->statuscode[200]['code'], $this->statuscode[200]['status'], 'Record found', $arrData);
        } else {
            return sendresponse($this->statuscode[404]['code'], $this->statuscode[404]['status'], 'Record not found', '');
        }
    }
}
