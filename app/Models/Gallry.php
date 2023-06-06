<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gallerya;

class Gallry extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tbl_gallery';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        ''
    ];
    public $timestamps = false; 

    public function getAttachmentsAttribute(){
        $url    = url('uploads/gallery');
        $attachment = Gallerya::selectRaw('(CASE WHEN attachment IS NOT NULL THEN CONCAT("'.$url.'","/",attachment) ELSE "" END) as attachment')
                      ->where('gid',$this->id)
                      ->get();

        return $attachment;
    }
    /**
     * appends field on select data
     *
     * @var array
     */
    protected $appends = ['attachments'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}