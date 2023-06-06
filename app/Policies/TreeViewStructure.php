<?php
namespace App\Policies;

use App\Policies\Contracts\TreeViewInterface;
use App\User;
use DB;



Class TreeViewStructure implements TreeViewInterface {

	 /**
     * [findFirstLevelChild description]
     * @param  [type] $user_id    [description]
     * @param  [type] $isExistObj [description]
     * @return [type]             [description]
     */
    public function findFirstLevelChild($user_id,$isExistObj) {
      $array = [];
      $sql = DB::table('tbl_today_details as todayDet')
                            ->select('todayDet.to_user_id','todayDet.from_user_id',
                                'todayDet.position','todayDet.level')
                            ->where('todayDet.to_user_id',$user_id)
                            ->where('todayDet.level','1');
            
            if($sql->count() > 0) {
                $query = $sql->get();
                //dd($query);
                $childArr = [];
                foreach ($query as $key => $value) {
                    # code...
                    $child_data = User::select("id","user_id As name")->where("id",$value->from_user_id)->first();
                    $childArr[$key]['name'] = $child_data['name'];
                    $childArr[$key]['id'] = $child_data['id'];    
                    $childArr[$key]['position'] = $value->position;   
                    $isExistUser = User::select("id","user_id As name")->where("id",$value->from_user_id)->first();
                    //echo $value->from_user_id."<br>";
                    if(!is_null($isExistUser)) {
                        $nodeArr = $this->findFirstLevelSubChild($value->from_user_id,$isExistUser);
                        if(count($nodeArr) > 0 ){
                            //$childArr[$key]['children'] = $nodeArr;
                            $childArr[$key]['children'] = [];
                        }
                    }
                    
                }
                $array['name'] = $isExistObj->name;   
                $array['children'] = $childArr;
            }  
        return $array;    
    }

    /**
     * [findFirstLevelSubChild description]
     * @param  [type] $user_id    [description]
     * @param  [type] $isExistObj [description]
     * @return [type]             [description]
     */
    public function findFirstLevelSubChild($user_id,$isExistObj) {
      $array = [];
      $sql = DB::table('tbl_today_details as todayDet')
                            ->select('todayDet.to_user_id','todayDet.from_user_id',
                                'todayDet.position','todayDet.level')
                            ->where('todayDet.to_user_id',$user_id)
                            ->where('todayDet.level','1');
            
            if($sql->count() > 0) {
                $query = $sql->get();
                $childArr = [];
                foreach ($query as $key => $value) {
                   
                    # code...
                    $child_data = User::select("id","user_id As name")->where("id",$value->from_user_id)->first();
                   if(!is_null($child_data)) {
                        $childArr[$key]['name'] = $child_data['name'];
                        $childArr[$key]['id'] = $child_data['id'];    
                        $childArr[$key]['position'] = $value->position;    
                   }
                    
                }
                $array['name'] = $isExistObj->name;   
                $array['children'] = $childArr;
            }  
        return $array;    
    }
	
}