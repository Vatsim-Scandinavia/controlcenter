<?php

namespace App\Helpers;

class FactoryHelper
{
    public static function subdivision(string $division)
    {
        $subdivisions = [
            ['code' => 'ACCVAN', 'fullname' => 'Vanilla vACC', 'parentdivision' => 'SAF'],
            ['code' => 'ADRIA', 'fullname' => 'Adria', 'parentdivision' => 'EUD'],
            ['code' => 'AFG', 'fullname' => 'Afghanistan', 'parentdivision' => 'WA'],
            ['code' => 'ARE', 'fullname' => 'United Arab Emirates', 'parentdivision' => 'MENA'],
            ['code' => 'ARG', 'fullname' => 'Argentina', 'parentdivision' => 'SAM'],
            ['code' => 'AUST', 'fullname' => 'Austria', 'parentdivision' => 'EUD'],
            ['code' => 'BGD', 'fullname' => 'Bangladesh', 'parentdivision' => 'WA'],
            ['code' => 'BHR', 'fullname' => 'Bahrain', 'parentdivision' => 'MENA'],
            ['code' => 'BHU', 'fullname' => 'Bhutan', 'parentdivision' => 'WA'],
            ['code' => 'BLUX', 'fullname' => 'Belux', 'parentdivision' => 'EUD'],
            ['code' => 'BOL', 'fullname' => 'Bolivia', 'parentdivision' => 'SAM'],
            ['code' => 'BULG', 'fullname' => 'Bulgaria', 'parentdivision' => 'EUD'],
            ['code' => 'CHI', 'fullname' => 'Chile', 'parentdivision' => 'SAM'],
            ['code' => 'COL', 'fullname' => 'Colombia', 'parentdivision' => 'SAM'],
            ['code' => 'CYPR', 'fullname' => 'Cyprus', 'parentdivision' => 'EUD'],
            ['code' => 'CZCH', 'fullname' => 'Czech Republic', 'parentdivision' => 'EUD'],
            ['code' => 'DZA', 'fullname' => 'Algeria', 'parentdivision' => 'AF'],
            ['code' => 'ECU', 'fullname' => 'Ecuador', 'parentdivision' => 'SAM'],
            ['code' => 'EGYPT', 'fullname' => 'Egypt', 'parentdivision' => 'MENA'],
            ['code' => 'ESTN', 'fullname' => 'Estonia', 'parentdivision' => 'EUD'],
            ['code' => 'FINL', 'fullname' => 'Finland', 'parentdivision' => 'EUD'],
            ['code' => 'FRA', 'fullname' => 'France', 'parentdivision' => 'EUD'],
            ['code' => 'GEO', 'fullname' => 'Georgia', 'parentdivision' => 'EUD'],
            ['code' => 'GER', 'fullname' => 'Germany', 'parentdivision' => 'EUD'],
            ['code' => 'GRE', 'fullname' => 'Hellenic', 'parentdivision' => 'EUD'],
            ['code' => 'GUY', 'fullname' => 'Guyana Francesa/Inglesa o Surinám', 'parentdivision' => 'SAM'],
            ['code' => 'HK', 'fullname' => 'Hong Kong', 'parentdivision' => 'SEA'],
            ['code' => 'HUN', 'fullname' => 'Hungary', 'parentdivision' => 'EUD'],
            ['code' => 'ICL', 'fullname' => 'Iceland', 'parentdivision' => 'EUD'],
            ['code' => 'IDN', 'fullname' => 'Indonesia', 'parentdivision' => 'SEA'],
            ['code' => 'IND', 'fullname' => 'India', 'parentdivision' => 'WA'],
            ['code' => 'IRL', 'fullname' => 'Ireland', 'parentdivision' => 'EUD'],
            ['code' => 'IRN', 'fullname' => 'Iran', 'parentdivision' => 'MENA'],
            ['code' => 'IRQ', 'fullname' => 'Iraq', 'parentdivision' => 'MENA'],
            ['code' => 'ITA', 'fullname' => 'Italy', 'parentdivision' => 'EUD'],
            ['code' => 'JOR', 'fullname' => 'Jordan', 'parentdivision' => 'MENA'],
            ['code' => 'KWT', 'fullname' => 'Kuwait', 'parentdivision' => 'MENA'],
            ['code' => 'LATVIA', 'fullname' => 'Latvia', 'parentdivision' => 'EUD'],
            ['code' => 'LBY', 'fullname' => 'Libya', 'parentdivision' => 'AF'],
            ['code' => 'LEB', 'fullname' => 'Lebanon', 'parentdivision' => 'MENA'],
            ['code' => 'LITH', 'fullname' => 'Lithuania', 'parentdivision' => 'EUD'],
            ['code' => 'MALT', 'fullname' => 'Malta', 'parentdivision' => 'EUD'],
            ['code' => 'MAR', 'fullname' => 'Morocco', 'parentdivision' => 'AF'],
            ['code' => 'MDV', 'fullname' => 'Maldives', 'parentdivision' => 'WA'],
            ['code' => 'MLDV', 'fullname' => 'Moldova', 'parentdivision' => 'RUS'],
            ['code' => 'MYR', 'fullname' => 'Burma (Myanmar)', 'parentdivision' => 'WA'],
            ['code' => 'MYS', 'fullname' => 'Malaysia', 'parentdivision' => 'SEA'],
            ['code' => 'NAF', 'fullname' => 'North Africa', 'parentdivision' => 'MENA'],
            ['code' => 'NETH', 'fullname' => 'Dutch', 'parentdivision' => 'EUD'],
            ['code' => 'NPL', 'fullname' => 'Nepal', 'parentdivision' => 'WA'],
            ['code' => 'OMN', 'fullname' => 'Oman', 'parentdivision' => 'MENA'],
            ['code' => 'PAK', 'fullname' => 'Pakistan', 'parentdivision' => 'WA'],
            ['code' => 'PAR', 'fullname' => 'Paraguay', 'parentdivision' => 'SAM'],
            ['code' => 'PER', 'fullname' => 'Perú', 'parentdivision' => 'SAM'],
            ['code' => 'PHL', 'fullname' => 'Philippines', 'parentdivision' => 'SEA'],
            ['code' => 'POL', 'fullname' => 'Poland', 'parentdivision' => 'EUD'],
            ['code' => 'POR', 'fullname' => 'Portugal', 'parentdivision' => 'EUD'],
            ['code' => 'PYF', 'fullname' => 'French Polynesia', 'parentdivision' => 'PAC'],
            ['code' => 'ROM', 'fullname' => 'Romania', 'parentdivision' => 'EUD'],
            ['code' => 'SAF', 'fullname' => 'South Africa', 'parentdivision' => 'AF'],
            ['code' => 'SAU', 'fullname' => 'Saudi Arabia', 'parentdivision' => 'MENA'],
            ['code' => 'SCA', 'fullname' => 'Scandinavia', 'parentdivision' => 'EUD'],
            ['code' => 'SING', 'fullname' => 'Singapore', 'parentdivision' => 'SEA'],
            ['code' => 'SLA', 'fullname' => 'Sri Lanka', 'parentdivision' => 'WA'],
            ['code' => 'SLO', 'fullname' => 'Slovak Republic', 'parentdivision' => 'EUD'],
            ['code' => 'SPN', 'fullname' => 'Spain', 'parentdivision' => 'EUD'],
            ['code' => 'SRM', 'fullname' => 'Sri Lanka and Maldives', 'parentdivision' => 'WA'],
            ['code' => 'SUI', 'fullname' => 'Switzerland', 'parentdivision' => 'EUD'],
            ['code' => 'SYR', 'fullname' => 'Syria', 'parentdivision' => 'MENA'],
            ['code' => 'THA', 'fullname' => 'Thailand', 'parentdivision' => 'SEA'],
            ['code' => 'TURK', 'fullname' => 'Turkey', 'parentdivision' => 'EUD'],
            ['code' => 'UKRA', 'fullname' => 'Ukraine', 'parentdivision' => 'RUS'],
            ['code' => 'URU', 'fullname' => 'Uruguay', 'parentdivision' => 'SAM'],
            ['code' => 'VCL', 'fullname' => 'Vietnam Cambodia Laos', 'parentdivision' => 'SEA'],
            ['code' => 'VEN', 'fullname' => 'Venezuela', 'parentdivision' => 'SAM'],
            ['code' => 'YEM', 'fullname' => 'Yemen', 'parentdivision' => 'MENA'],
        ];

        $subdivision = collect($subdivisions)->where('parentdivision', $division)->random();

        return $subdivision['code'];
    }

    public static function division(string $region)
    {
        $divisions = [
            ['id' => 'BRZ', 'name' => 'Brazil (VATBRZ)', 'parentregion' => 'AMAS', 'subdivisionallowed' => 0],
            ['id' => 'CAM', 'name' => 'Central America', 'parentregion' => 'AMAS', 'subdivisionallowed' => 0],
            ['id' => 'CAN', 'name' => 'Canada', 'parentregion' => 'AMAS', 'subdivisionallowed' => 0],
            ['id' => 'CAR', 'name' => 'Caribbean', 'parentregion' => 'AMAS', 'subdivisionallowed' => 0],
            ['id' => 'EUD', 'name' => 'Europe (except UK)', 'parentregion' => 'EMEA', 'subdivisionallowed' => 1],
            ['id' => 'GBR', 'name' => 'United Kingdom', 'parentregion' => 'EMEA', 'subdivisionallowed' => 0],
            ['id' => 'IL', 'name' => 'Israel (VATIL)', 'parentregion' => 'EMEA', 'subdivisionallowed' => 0],
            ['id' => 'JPN', 'name' => 'Japan', 'parentregion' => 'APAC', 'subdivisionallowed' => 0],
            ['id' => 'KOR', 'name' => 'Korea', 'parentregion' => 'APAC', 'subdivisionallowed' => 0],
            ['id' => 'MCO', 'name' => 'Mexico', 'parentregion' => 'AMAS', 'subdivisionallowed' => 0],
            ['id' => 'MENA', 'name' => 'Middle East and North Africa', 'parentregion' => 'EMEA', 'subdivisionallowed' => 1],
            ['id' => 'NZ', 'name' => 'New Zealand (VATNZ)', 'parentregion' => 'APAC', 'subdivisionallowed' => 0],
            ['id' => 'PAC', 'name' => 'Australia (VATPAC)', 'parentregion' => 'APAC', 'subdivisionallowed' => 0],
            ['id' => 'PRC', 'name' => 'People\'s Republic of China', 'parentregion' => 'APAC', 'subdivisionallowed' => 0],
            ['id' => 'ROC', 'name' => 'Republic of China Taiwan', 'parentregion' => 'APAC', 'subdivisionallowed' => 0],
            ['id' => 'RUS', 'name' => 'Russia', 'parentregion' => 'EMEA', 'subdivisionallowed' => 0],
            ['id' => 'SAF', 'name' => 'Southern Africa', 'parentregion' => 'EMEA', 'subdivisionallowed' => 1],
            ['id' => 'SAM', 'name' => 'South America', 'parentregion' => 'AMAS', 'subdivisionallowed' => 1],
            ['id' => 'SEA', 'name' => 'Southeast Asia (VATSEA)', 'parentregion' => 'APAC', 'subdivisionallowed' => 1],
            ['id' => 'USA', 'name' => 'United States', 'parentregion' => 'AMAS', 'subdivisionallowed' => 0],
            ['id' => 'WA', 'name' => 'West Asia', 'parentregion' => 'APAC', 'subdivisionallowed' => 1],
        ];

        $division = collect($divisions)->where('parentregion', $region)->random();

        return [$division['id'], $division['subdivisionallowed']];
    }

    public static function region()
    {
        $regions = [
            ['id' => 'AMAS', 'name' => 'Americas', 'director' => '1013441'],
            ['id' => 'APAC', 'name' => 'Asia Pacific', 'director' => '901134'],
            ['id' => 'EMEA', 'name' => 'Europe, Middle East and Africa', 'director' => '858680'],
        ];

        $region = collect($regions)->random();

        return $region['id'];
    }

    public static function longRating(int $rating)
    {
        switch ($rating) {
            case 0: return 'Suspended';
            case 1: return 'Pilot/Observer';
            case 2: return 'Tower Trainee';
            case 3: return 'Tower Controller';
            case 4: return 'TMA Controller';
            case 5: return 'Enroute Controller';
            case 6: return 'Senior Controller';
            case 7: return 'Senior Controller';
            case 8: return 'Instructor';
            case 9: return 'Senior Instructor';
            case 10: return 'Senior Instructor';
            case 11: return 'Supervisor';
            case 12: return 'Administrator';
            default: return 'Inactive';
        }
    }

    public static function shortRating(int $rating)
    {
        switch ($rating) {
            case 0: return 'SUS';
            case 1: return 'OBS';
            case 2: return 'S1';
            case 3: return 'S2';
            case 4: return 'S3';
            case 5: return 'C1';
            case 6: return 'C2';
            case 7: return 'C3';
            case 8: return 'I1';
            case 9: return 'I2';
            case 10: return 'I3';
            case 11: return 'SUP';
            case 12: return 'ADM';
            default: return 'INA';
        }
    }
}
