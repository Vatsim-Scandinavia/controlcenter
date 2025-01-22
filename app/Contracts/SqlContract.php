<?php

namespace App\Contracts;

interface SqlContract
{
    public function as(string $query, string $alias);

    public function date(string $column, string $alias);

    public function month(string $column, string $alias);
}
