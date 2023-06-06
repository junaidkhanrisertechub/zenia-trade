<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChainIncomeSettings extends Model
{
    protected $table = 'chain_income_setting';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','directs', 'income'
    ];
}
