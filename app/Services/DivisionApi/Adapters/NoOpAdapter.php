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

    public function assignExaminer($user, $rating, $requesterId)
    {
    }

    public function removeExaminer($user, $rating, $requesterId)
    {
    }

    public function assignTierEndorsement($user, $rating, $requesterId)
    {
    }

    public function revokeTierEndorsement($endorsement)
    {
    }

    public function assignSoloEndorsement($user, $position, $requesterId, $expireAt = null)
    {
    }

    public function revokeSoloEndorsement($endorsement)
    {
    }

    public function requestRatingUpgrade($user, $rating, $requesterId)
    {
    }

    public function assignTheoryExam($user, $rating, $requesterId)
    {
    }

    public function getUserExams($user)
    {
    }

    public function getExamLink()
    {
        return false;
    }

    public function getRoster()
    {
    }

    public function assignRosterUser($user)
    {
    }

    public function removeRosterUser($user)
    {

    }
}
