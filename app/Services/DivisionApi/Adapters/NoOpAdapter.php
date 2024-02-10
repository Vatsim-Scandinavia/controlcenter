<?php

namespace App\Services\DivisionApi\Adapters;

use App\Contracts\DivisionApiContract;

// This is a no-op adapter, which means it does nothing by design
class NoOpAdapter implements DivisionApiContract
{
    public function assignMentor($area, $user, $requesterId)
    {
    }

    public function assignTheoryExam($parameters)
    {
    }
}
