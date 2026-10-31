<?php

namespace App\Contracts\DivisionApi;

use App\Contracts\DivisionApiContract;
use App\Models\Rating;
use App\Models\User;

/**
 * Division API operations concerning theory exams and rating upgrades.
 *
 * Part of the focused contract set composed by {@see DivisionApiContract}.
 */
interface ExamApi
{
    public function uploadExamResults(int $studentId, int $examinerId, bool $pass, string $positionName, string $filePath);

    public function requestRatingUpgrade(User $user, Rating $rating, int $requesterId);

    public function assignTheoryExam(User $user, Rating $rating, int $requesterId);

    public function getUserExams(User $user);

    public function getUserExamRating(int $type);

    public function userHasPassedTheoryExam(User $user, Rating $rating);

    public function getExamLink();
}
