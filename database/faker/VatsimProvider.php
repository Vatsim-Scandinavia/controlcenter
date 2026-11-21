<?php

namespace Database\Faker;

use Faker\Provider\Base;

/**
 * Faker provider for VATSIM data.
 *
 * Registered on Laravel's Faker generator in AppServiceProvider.
 */
class VatsimProvider extends Base
{
    /**
     * @var list<string>
     */
    protected static array $facilities = ['DEL', 'GND', 'TWR', 'APP', 'DEP', 'CTR'];

    /**
     * Generate an ATC callsign such as `ABCD_TWR` or `ABCD_F_APP`.
     *
     * The prefix is random letters rather than a real ICAO code, so there is no list to maintain.
     * The middle segment is a single character, so no callsign can be a substring of another.
     */
    public function atcCallsign(): string
    {
        $middleSegment = $this->generator->boolean(25) ? static::bothify('*_') : '';

        return strtoupper(static::lexify('????') . '_' . $middleSegment) . static::randomElement(static::$facilities);
    }
}
