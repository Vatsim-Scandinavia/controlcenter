<?php

namespace App\Contracts\DivisionApi;

use App\Contracts\DivisionApiContract;

/**
 * Division API operations concerning the controller roster.
 *
 * Part of the focused contract set composed by {@see DivisionApiContract}.
 */
interface RosterApi
{
    public function getRoster();

    public function assignRosterUser(int $userId);

    public function removeRosterUser(int $userId);
}
