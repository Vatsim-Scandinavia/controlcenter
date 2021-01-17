<?php

namespace App\Policies;

use App\User;
use App\Vote;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

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

        if ($vote->closed) {
            return Response::deny("The vote is closed and concluded.");
        }

        if ($vote->require_active) {
            if(!$user->active) return Response::deny("Sorry, you do not qualify to participate in this vote. You must hold an active ATC rank in our subdivision.");
        }

        if ($vote->require_vatsca_member) {
            if($user->sub_division != 'SCA') return Response::deny("Sorry, you do not qualify to participate in this vote. You must be a VATSCA Member to vote.");
        }        

    }
}
