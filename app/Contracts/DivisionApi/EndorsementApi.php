<?php

namespace App\Contracts\DivisionApi;

use App\Contracts\DivisionApiContract;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Rating;
use App\Models\User;
use Carbon\Carbon;

/**
 * Division API operations concerning tier and solo endorsements.
 *
 * Part of the focused contract set composed by {@see DivisionApiContract}.
 */
interface EndorsementApi
{
    public function getTierEndorsements(int $tier);

    public function assignTierEndorsement(User $user, Rating $rating, int $requesterId);

    public function revokeTierEndorsement(string $tier, int $userId, string $endorsementName);

    public function assignSoloEndorsement(User $user, Position $position, int $requesterId, ?Carbon $expireAt = null);

    public function revokeSoloEndorsement(Endorsement $endorsement);
}
