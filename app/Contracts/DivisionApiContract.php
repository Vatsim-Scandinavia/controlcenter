<?php

namespace App\Contracts;

use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Rating;
use App\Models\User;
use Carbon\Carbon;

interface DivisionApiContract
{
    public function assignMentor(User $user, int $requesterId);

    public function removeMentor(User $user, int $requesterId);

    public function assignExaminer(User $user, Rating $rating, int $requesterId);

    public function removeExaminer(User $user, Endorsement $endorsement, int $requesterId);

    public function getTierEndorsements(int $tier);

    public function assignTierEndorsement(User $user, Rating $rating, int $requesterId);

    public function revokeTierEndorsement(string $tier, int $userId, string $endorsementName);

    public function assignSoloEndorsement(User $user, Position $position, int $requesterId, ?Carbon $expireAt = null);

    public function revokeSoloEndorsement(Endorsement $endorsement);

    public function uploadExamResults(int $studentId, int $examinerId, bool $pass, string $positionName, string $filePath);

    public function requestRatingUpgrade(User $user, Rating $rating, int $requesterId);

    public function assignTheoryExam(User $user, Rating $rating, int $requesterId);

    public function getUserExams(User $user);

    public function userHasPassedTheoryExam(User $user, Rating $rating);

    public function getRoster();

    public function assignRosterUser(int $userId);

    public function removeRosterUser(int $userId);

    public function getExamLink();
}
