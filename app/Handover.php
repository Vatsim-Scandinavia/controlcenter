<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Handover extends Model
{
    /**
     * This model is special, as it's using the table non-prefixed mysql connection and garthers data provided by Handover
     */
    protected $connection = 'mysql-noprefix'; 
    public $table = 'core_members';
}
