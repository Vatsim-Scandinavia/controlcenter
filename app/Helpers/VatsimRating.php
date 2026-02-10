<?php

namespace App\Helpers;

/**
 * Constants for VATSIM ratings.
 */
enum VatsimRating: int
{
    case INA = -1;
    case SUS = 0;
    case OBS = 1;
    case S1 = 2;
    case S2 = 3;
    case S3 = 4;
    case C1 = 5;
    case C3 = 7;
    case I1 = 8;
    case I3 = 10;
    case SUP = 11;
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
