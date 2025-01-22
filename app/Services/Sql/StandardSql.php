<?php

namespace App\Services\Sql;

use App\Contracts\SqlContract;

// SQL Building blocks that follow Standard SQL
class StandardSql implements SqlContract
{
    public function as(string $query, string $alias)
    {
        return "$query as `$alias`";
    }

    public function date(string $column, string $alias)
    {
        return $this->as("DATE($column)", $alias);
    }

    public function month(string $column, string $alias)
    {
        return $this->as("MONTH($column)", $alias);
    }
}
