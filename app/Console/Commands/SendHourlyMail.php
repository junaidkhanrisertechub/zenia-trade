<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\userapi\TotalrecievedController;
use App\User;
use Mail;

class SendHourlyMail extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testmail:update';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Mail';

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


        $user = array('abcd@abcd.com');

        foreach ($user as $a) {
            Mail::raw("This is automatically generated min Update", function($message) use ($a) {

                $message->from('abcd@abcd.com');

                $message->to('abcd@abcd.com')->subject('Test Min send mail');
            });
        }
        $this->info('Mail send successfully');
    }

}
