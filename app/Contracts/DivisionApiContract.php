<?php

namespace App\Contracts;

use App\Models\Area;
use App\Models\User;

interface DivisionApiContract
{
    public function assignMentor(User $user, int $requesterId);
    public function removeMentor(User $user, int $requesterId);

    public function assignTheoryExam($parameters);
}
