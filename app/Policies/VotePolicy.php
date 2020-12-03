<?php

namespace App\Policies;

use App\User;
use App\Vote;
use Illuminate\Auth\Access\HandlesAuthorization;

class VotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function index(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create the model.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can store the model.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function store(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can vote
     *
     * @param User $user
     * @param Vote $vote
     * @return bool
     */
    public function vote(User $user, Vote $vote)
    {
        $can = true;

        if ($vote->require_active) {
            ($can == false) ?: $can = $user->active;
        }

        if ($vote->require_vatsca_member) {
            ($can == false) ?: $can = $user->sub_division == 'SCA';
        }

        return $can;
    }
}
