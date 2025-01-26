<?php

namespace App\Services\Sql;

use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;

/**
 * Extended SQL Grammar to ease vendor-agnostic raw SQL building.
 */
class Sql
{
    public static function as(string $query, string $alias): string
    {
        $grammar = DB::getQueryGrammar();
        $escapedAlias = $grammar->wrap($alias);
        return "$query as $escapedAlias";
    }

    public static function date(string $column, string $alias): string
    {
        $grammar = DB::getQueryGrammar();
        if ($grammar instanceof PostgresGrammar) {
            $sql = "date_trunc('day', $column)";
        } else {
            $sql = "DATE($column)";
        }
        return Sql::as($sql, $alias);
    }

    public static function month(string $column, string $alias): string
    {
        $grammar = DB::getQueryGrammar();
        if ($grammar instanceof PostgresGrammar) {
            $sql = "EXTRACT(MONTH FROM $column)";
        } else {
            $sql = "MONTH($column)";
        }
        return SQL::as($sql, $alias);
    }
}
