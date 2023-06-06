<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\Models\supermatching;

class AssignRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:AssignRank';

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
        $users = DB::table('tbl_users')
                ->select('id')
                ->where('rank', '!=', null)
                ->get();
        //dd($users);
        foreach ($users as $value) {

            $usersleft = DB::table('tbl_users')
                    ->select('id')
                    ->where('ref_user_id', '=', $value->id)
                    ->where('position', '=', 2)
                    ->count();


            $usersright = DB::table('tbl_users')
                    ->select('id')
                    ->where('ref_user_id', '=', $value->id)
                    ->where('position', '=', 1)
                    ->count();
            
            if($usersleft >0 && $usersright >0)
            {
                $invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');


                $Insertdata = array();
                $Insertdata['pin'] = $invoice_id;
                $Insertdata['rank'] = 'ACE';
                $Insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($Insertdata);
                $data = array();
                $data['rank'] = 'ACE';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }        
        }
        echo "ACE Cron Run Successfully";

         $users = DB::table('tbl_users')
                ->select('id')
                ->where('rank', '=', 'ACE')
                ->get();

        foreach ($users as $value) {
            $getusersleft = DB::table('tbl_users')
                    ->select('*')
                    ->where('ref_user_id', '=', $value->id)
                    ->where('position', '=', 2)
                    ->get();
            $usersleft = $getusersleft->where('rank', '=', 'ACE')->count();

            $getusersright = DB::table('tbl_users')
                    ->select('*')
                    ->where('ref_user_id', '=', $value->id)
                    ->where('position', '=', 1)
                    ->get(); 
            $usersright = $getusersright->where('rank', '=', 'ACE')->count(); 

            $invoice_id = substr(number_format(time() * rand(), 0, '', ''), 0, '15');
            if(($usersleft >= 3 && $usersleft <7) && ($usersright >=3 && $usersright <7))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'Herald';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'Herald';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);

                  echo "Herald Cron Run Successfully";
            } 
            if(($usersleft >= 7 && $usersleft <15) && ($usersright >=7 && $usersright <15))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'GUARDIAN';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'GUARDIAN';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 15 && $usersleft <31) && ($usersright >=15 && $usersright <31))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'CRUSADER';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'CRUSADER';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 31 && $usersleft <100) && ($usersright >=31 && $usersright <100))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'Commander';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'Commander';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 100 && $usersleft <250) && ($usersright >=100 && $usersright <250))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'VALORANT';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'VALORANT';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 250 && $usersleft <500) && ($usersright >=250 && $usersright <500))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'LEGEND';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'LEGEND';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 500 && $usersleft <1000) && ($usersright >=500 && $usersright <1000))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'RELIC';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'RELIC';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 1000 && $usersleft <2500) && ($usersright >=1000 && $usersright <2500))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'ALMIGHTY';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'ALMIGHTY';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 2500 && $usersleft <5000) && ($usersright >=2500 && $usersright <5000))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'CONQUEROR';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'CONQUEROR';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }
            if(($usersleft >= 5000 && $usersleft <10000) && ($usersright >=5000 && $usersright <10000))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'TITAN';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'TITAN';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }   
            if($usersleft >=10000 && $usersright >=10000)
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'IMMORTAL';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'IMMORTAL';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }
            /*if(($usersleft >= 15 && $usersleft <31) && ($usersright >=7 && $usersright <15))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'CRUSADER';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'CRUSADER';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
            if(($usersleft >= 31 && $usersleft <100) && ($usersright >=31 && $usersright <100))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'VALORANT';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'VALORANT';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }  
          if(($usersleft >= 100 && $usersleft <250) && ($usersright >=100 && $usersright <250))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'LEGEND';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'LEGEND';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
          if(($usersleft >= 250 && $usersleft <500) && ($usersright >=250 && $usersright <500))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'RELIC';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'RELIC';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
          if(($usersleft >= 500 && $usersleft <1000) && ($usersright >=500 && $usersright <1000))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'ALMIGHTY';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'ALMIGHTY';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            } 
          if(($usersleft >= 1000 && $usersleft <2500) && ($usersright >=1000 && $usersright <2500))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'CONQUEROR';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'CONQUEROR';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }  
          if(($usersleft >= 2500 && $usersleft <5000) && ($usersright >=2500 && $usersright <5000))
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'TITAN';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'TITAN';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }   
          if($usersleft >=5000 && $usersright >=5000)
            {
                $insertdata = array();
                $insertdata['pin'] = $invoice_id;
                $insertdata['rank'] = 'IMMORTAL';
                $insertdata['user_id'] = $value->id;
        
                $insertAdd = supermatching::create($insertdata);

                $data['rank'] = 'IMMORTAL';
                  DB::table('tbl_users')
                  ->where('id', $value->id)
                  ->update($data);
            }  */
                     
        }

    }
}
