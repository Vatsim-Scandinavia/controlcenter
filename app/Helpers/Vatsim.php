<?php

use Illuminate\Support\Collection;

/**
 * Constants for VATSIM ratings.
 */
enum VatsimRating: int {
    case S1 = 2;
    case S2 = 3;
    case S3 = 4;
    case C1 = 5;
    case C3 = 7;
    case I1 = 8;
    case I3 = 10;
    case SUPERVISOR = 11;
}

/**
* Checks if a position belongs to the division.
* @param string callsign
* @param Collection<string> divisionCallsignPrefixes
*/
function isDivisionCallsign(string $callsign, Collection $divisionCallsignPrefixes)
{
    $validAtcSuffixes = ["DEL" => true, "GND" => true, "TWR" => true, "APP" => true, "DEP" => true, "CTR" => true, "FSS" => true];
    // Filter away invalid ATC suffixes
    $suffix = substr($callsign, -3);
    if (!array_key_exists($suffix, $validAtcSuffixes)) {
        return false;
    }
    
    // PREFIX
    
    if ($divisionCallsignPrefixes->value(substr($callsign, 4))) {
        return true;
    }

    return false;
}

?>