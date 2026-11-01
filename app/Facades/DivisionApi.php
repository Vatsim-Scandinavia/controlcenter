<?php

namespace App\Facades;

use App\Contracts\DivisionApiContract;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the division API. The underlying contract is composed from focused
 * sub-interfaces ({@see DivisionApiContract}); these annotations spell out the
 * full surface so static analysis resolves it through the facade.
 *
 * @method static mixed assignMentor(\App\Models\User $user, int $requesterId)
 * @method static mixed removeMentor(\App\Models\User $user, int $requesterId)
 * @method static mixed assignExaminer(\App\Models\User $user, \App\Models\Rating $rating, int $requesterId)
 * @method static mixed removeExaminer(\App\Models\User $user, \App\Models\Endorsement $endorsement, int $requesterId)
 * @method static mixed getTierEndorsements(int $tier)
 * @method static mixed assignTierEndorsement(\App\Models\User $user, \App\Models\Rating $rating, int $requesterId)
 * @method static mixed revokeTierEndorsement(string $tier, int $userId, string $endorsementName)
 * @method static mixed assignSoloEndorsement(\App\Models\User $user, \App\Models\Position $position, int $requesterId, ?\Carbon\Carbon $expireAt = null)
 * @method static mixed revokeSoloEndorsement(\App\Models\Endorsement $endorsement)
 * @method static mixed uploadExamResults(int $studentId, int $examinerId, bool $pass, string $positionName, string $filePath)
 * @method static mixed requestRatingUpgrade(\App\Models\User $user, \App\Models\Rating $rating, int $requesterId)
 * @method static mixed assignTheoryExam(\App\Models\User $user, \App\Models\Rating $rating, int $requesterId)
 * @method static mixed getUserExams(\App\Models\User $user)
 * @method static mixed getUserExamRating(int $type)
 * @method static mixed userHasPassedTheoryExam(\App\Models\User $user, \App\Models\Rating $rating)
 * @method static mixed getExamLink()
 * @method static mixed getRoster()
 * @method static mixed assignRosterUser(int $userId)
 * @method static mixed removeRosterUser(int $userId)
 * @method static string getName()
 *
 * @see DivisionApiContract
 */
class DivisionApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DivisionApiContract::class;
    }
}
