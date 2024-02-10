<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class Vatsim
{
    private static $divisions = [
        'CAN' => ['ZQM', 'ZUL', 'ZVR', 'ZWG', 'ZYZ'],
        'CAR' => ['KIN', 'NAS', 'PAP', 'PIA', 'SDO', 'SJU'],
        'EUD' => ['ADRIA', 'AUST', 'BLUX', 'BULG', 'CYPR', 'CZCH', 'ESTN', 'FINL', 'FRA', 'GEO', 'GER', 'GRE', 'HUN', 'ICL', 'IRL', 'ITA', 'LATVIA', 'LITH', 'MALT', 'MLDV', 'NETH', 'POL', 'POR', 'ROM', 'SCA', 'SLO', 'SPN', 'SUI', 'TURK', 'UKRA'],
        'MENA' => ['ARB', 'DZA', 'EGYPT', 'IRN', 'JOR', 'KWIQ', 'KWT', 'LBY', 'LEB', 'MAG', 'MAR', 'NEA', 'SAU', 'SYR', 'YEM'],
        'PAC' => ['PYF'],
        'SAM' => ['ARG', 'BOL', 'CHI', 'COL', 'ECU', 'GUY', 'PAR', 'PER', 'URU', 'VEN'],
        'SEA' => ['HK', 'IDN', 'MYR', 'MYS', 'PHL', 'SING', 'THA', 'VCL'],
        'USA' => ['PCF', 'ZAB', 'ZAU', 'ZBW', 'ZDC', 'ZDV', 'ZFW', 'ZHU', 'ZID', 'ZJX', 'ZKC', 'ZLA', 'ZLC', 'ZMA', 'ZME', 'ZMP', 'ZNY', 'ZOA', 'ZOB', 'ZSE', 'ZTL'],
        'WA' => ['AFG', 'BGD', 'BHU', 'IND', 'MDV', 'NPL', 'PAK', 'SLA', 'SRM'],
    ];

    /**
     * Checks if a position belongs to the division.
     *
     * @param string callsign
     * @param Collection<string> divisionCallsignPrefixes
     */
    public static function isDivisionCallsign(string $callsign, Collection $divisionCallsignPrefixes)
    {
        $validAtcSuffixes = ['DEL' => true, 'GND' => true, 'TWR' => true, 'APP' => true, 'DEP' => true, 'CTR' => true, 'FSS' => true];
        // Filter away invalid ATC suffixes
        $suffix = substr($callsign, -3);
        if (! array_key_exists($suffix, $validAtcSuffixes)) {
            return false;
        }

        // PREFIX
        if ($divisionCallsignPrefixes->contains(substr($callsign, 0, 4))) {
            return true;
        }

        return false;
    }

    public static function getDivision()
    {
        $currentSubdivision = config('app.owner_short');

        foreach (Vatsim::$divisions as $division => $subdivisions) {
            foreach ($subdivisions as $subdivision) {
                if ($subdivision == $currentSubdivision) {
                    return $division;
                }
            }
        }
    }

    public static function divisionIs(string $divisionCode)
    {

        $currentSubdivision = config('app.owner_short');

        foreach (Vatsim::$divisions as $division => $subdivisions) {
            foreach ($subdivisions as $subdivision) {
                if ($subdivision == $currentSubdivision && $divisionCode == $division) {
                    return true;
                }
            }
        }

        return false;

    }
}
