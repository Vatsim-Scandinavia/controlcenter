<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class VotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @return bool
     */
    public function index(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create the model.
     *
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can store the model.
     *
     * @return bool
     */
    public function store(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can vote
     *
     * @return Illuminate\Auth\Access\Response
     */
    public function vote(User $user, Vote $vote)
    {
        if ($vote->closed) {
            return Response::deny('The vote closed and concluded at ' . Carbon::create($vote->end_at)->toEuropeanDateTime());
        }

        if ($vote->require_our_member) {
            if ($user->subdivision != Config::get('app.owner_short')) {
                return Response::deny('Sorry, you do not qualify to participate in this vote. You must belong to ' . Config::get('app.owner') . ' subdivision to vote.');
            }
        }

        if ($vote->require_active) {
            if (! $user->isAtcActive()) {
                return Response::deny('Sorry, you do not qualify to participate in this vote. You must hold an active ATC rank to vote.');
            }
        }

        return Response::allow();
    }
}
