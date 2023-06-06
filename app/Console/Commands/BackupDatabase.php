<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Carbon;
use Storage;
use DB;
use Mail;

class BackupDatabase extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database';
    protected $hidden = true;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run backup on database and upload to S3';

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


        $filename = "backup-" . Carbon\Carbon::now()->format('Y-m-d_H-i-s') . ".sql";

        //mysqldump command with account credentials from .env file. storage_path() adds default local storage path

        $command = "mysqldump --user=" . env('DB_USERNAME') . " --password=" . env('DB_PASSWORD') . " --host=" . env('DB_HOST') . " " . env('DB_DATABASE') . "  > " . storage_path('backup') . "/" . $filename;

        $returnVar = NULL;
        $output = NULL;
        //exec command allows you to run terminal commands from php 
        exec($command, $output, $returnVar);

        //if nothing (error) is returned
        if (!$returnVar) {
            //get mysqldump output file from local storage
            $getFile = Storage::disk('local')->get($filename);
            // put file in backups directory on s3 storage
            Storage::disk('s3')->put("backups/" . $filename, $getFile);
            // delete local copy
            Storage::disk('local')->delete($filename);
        } else {
            // if there is an error send an email 
            Mail::raw('There has been an error backing up the database.', function ($message) {
                $message->to("abcd@abcd.com", "Test backup")->subject("Backup Error");
            });
        }
        // $this->info('Exchanger value updated successfully');
    }

}
