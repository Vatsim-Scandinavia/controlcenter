<?php

namespace App\Facades;

use App\Contracts\DivisionApiContract;
use Illuminate\Support\Facades\Facade;

class DivisionApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DivisionApiContract::class;
    }
}
