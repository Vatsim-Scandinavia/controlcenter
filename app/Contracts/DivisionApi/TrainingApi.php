<?php

namespace App\Contracts\DivisionApi;

use App\Contracts\DivisionApiContract;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\User;

/**
 * Division API operations concerning mentor and examiner assignments.
 *
 * Part of the focused contract set composed by {@see DivisionApiContract}.
 */
interface TrainingApi
{
    public function assignMentor(User $user, int $requesterId);

    public function removeMentor(User $user, int $requesterId);

    public function assignExaminer(User $user, Rating $rating, int $requesterId);

    public function removeExaminer(User $user, Endorsement $endorsement, int $requesterId);
}
