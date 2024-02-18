<?php

namespace App\Services\DivisionApi\Adapters;

use App\Contracts\DivisionApiContract;

// This is a no-op adapter, which means it does nothing by design
class NoOpAdapter implements DivisionApiContract
{
    public function assignMentor($user, $requesterId)
    {
    }

    public function removeMentor($user, $requesterId)
    {
    }

    public function assignTheoryExam($parameters)
    {
    }
}
