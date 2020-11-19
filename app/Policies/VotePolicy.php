<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VotePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function index(User $user) {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create the model.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user) {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can store the model.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function store(User $user) {
        return $user->isAdmin();
    }
}
