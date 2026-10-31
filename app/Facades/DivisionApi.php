<?php

namespace App\Facades;

use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getName()
 */
class DivisionApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DivisionApiContract::class;
    }

    /**
     * Whether a real division API driver is active (as opposed to the no-op
     * default). This replaces the former `divisionApiEnabled` setting as the
     * single core signal that division integration is live: it is true exactly
     * when an enabled module provides and is selected as the active driver.
     */
    public static function isActive(): bool
    {
        return ! app(DivisionApiContract::class) instanceof NoOpAdapter;
    }
}
