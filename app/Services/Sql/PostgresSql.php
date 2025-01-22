<?php

namespace App\Services\Sql;

use App\Contracts\SqlContract;

// SQL Building blocks that follow PostgreSQL
class PostgresSql implements SqlContract
{
    public function as(string $query, string $alias)
    {
        return "$query as \"$alias\"";
    }

    public function date(string $column, string $alias)
    {
        return $this->as("date_trunc('day', $column)", $alias);
    }


    public function month(string $column, string $alias)
    {
        return $this->as("EXTRACT(MONTH FROM $column)", $alias);
    }
}
