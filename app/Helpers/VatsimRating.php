<?php

namespace App\Helpers;

use App\Traits\ComparableIntEnum;

/**
 * The VATSIM ATC ratings.
 *
 * The case name is the short code (e.g. `OBS`, `S3`) and the backing value is the numeric
 * VATSIM rating id. Note that `C2` (6) and `I2` (9) exist in VATSIM but are unused here.
 */
enum VatsimRating: int
{
    use ComparableIntEnum;

    /** Inactive. */
    case INA = -1;

    /** Suspended. */
    case SUS = 0;

    /** Pilot/Observer. */
    case OBS = 1;

    /** Tower Trainee. */
    case S1 = 2;

    /** Tower Controller. */
    case S2 = 3;

    /** TMA Controller. */
    case S3 = 4;

    /** Enroute Controller. */
    case C1 = 5;

    /** Senior Controller. */
    case C3 = 7;

    /** Instructor. */
    case I1 = 8;

    /** Senior Instructor. */
    case I3 = 10;

    /** Supervisor. */
    case SUP = 11;

    /** Administrator. */
    case ADM = 12;

    public const SPECIAL_RATINGS = self::NOT_POSITION_RATINGS;

    public const NOT_POSITION_RATINGS = [
        self::INA,
        self::SUS,
        self::OBS,
        self::SUP,
        self::ADM,
    ];

    public const CONTROLLER_RATINGS = [
        self::S1,
        self::S2,
        self::S3,
        self::C1,
        self::C3,
        self::I1,
        self::I3,
    ];

    public const TRAINABLE_RATINGS = [
        self::S1,
        self::S2,
        self::S3,
        self::C1,
        self::C3,
    ];

    public static function getControllerRatings()
    {
        return collect(self::CONTROLLER_RATINGS)->mapWithKeys(function ($rating) {
            return [$rating->value => $rating];
        })->toArray();
    }

    /**
     * Check if a numeric value is a valid VatsimRating
     */
    public static function isValidValue(int $value): bool
    {
        try {
            self::from($value);

            return true;
        } catch (\ValueError $e) {
            return false;
        }
    }

    /**
     * Get all valid position rating values (for form dropdowns)
     */
    public static function getPositionRatingValues(): array
    {
        $validValues = [];

        foreach (self::cases() as $rating) {
            // Skip non-position ratings
            if (in_array($rating, self::NOT_POSITION_RATINGS)) {
                continue;
            }

            $validValues[] = $rating->value;
        }

        return $validValues;
    }
}
