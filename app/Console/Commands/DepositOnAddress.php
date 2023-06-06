<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\TransactionConfiController;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Product;

class DepositOnAddress extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fiveMin:update';
   // protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deposit usd to address';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TransactionConfiController $confirmTxn) {
        parent::__construct();
        $this->confirmTxn = $confirmTxn;
    }

    /*public function slaap($seconds) 
    { 
        $seconds = abs($seconds); 
        if ($seconds < 1): 
           usleep($seconds*1000000); 
        else: 
           sleep($seconds); 
        endif;    
    } */

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        //echo "hellooo";
        $UserInvoice = Invoice::where('in_status', '=', 0)->orderBy('entry_time','desc')
        //->where('invoice_id','6278949108')
        ->get();
        //dd($UserInvoice);
        if (!empty($UserInvoice)) {
            foreach ($UserInvoice as $k => $v) {
              
                time_nanosleep(0, 500000000);//dd('hii');

                echo \Carbon\Carbon::now();


                echo "\n";

                $address = $UserInvoice[$k]->address;
                $id = $UserInvoice[$k]->id;
                $invoice_id = $UserInvoice[$k]->invoice_id;
                $price_in_usd = $UserInvoice[$k]->price_in_usd;
                $payment_mode = $UserInvoice[$k]->payment_mode;
                $req1=new request();
                if (!empty($address) && $payment_mode == 'BTC') {
                    $req1['address'] = $address;
                    $this->confirmTxn->transactionconfirmation($req1);
                }
                if (!empty($address) && $payment_mode == 'ETH') {
                    $req1['address'] = $address;
                    $this->confirmTxn->ETHtransaction($req1);
                }

                /*try{
                    $touser = User::where('id',$id)->first();
                    $objProduct = Product::where('id',$UserInvoice[$k]->plan)->first();
                    $subject = "Package Activated!";
                    $pagename = "emails.deposit";
                    $data = array('pagename' => $pagename,'email' =>$touser->email, 'username' =>$touser->user_id ,'Package'=>$objProduct->name);
                    $email =$touser->email;
                    $mail = sendMail($data, $email, $subject);
                }catch(\Exception $e){
                    
                }*/
            }

             $this->info('Usd Deposit to address is done');
        } else {

            $this->info('Something went wrong');
        }
    }

}
