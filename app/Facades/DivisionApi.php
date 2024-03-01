<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DivisionApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Contracts\DivisionApiContract::class;
    }
}
