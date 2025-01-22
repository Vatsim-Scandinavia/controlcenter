<?php

namespace App\Facades;

use App\Contracts\SqlContract;
use Illuminate\Support\Facades\Facade;

class Sql extends Facade
{

    protected static function getFacadeAccessor()
    {
        return SqlContract::class;
    }
}
