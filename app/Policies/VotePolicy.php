<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class VotePolicy
{
    use HandlesAuthorization;

    public function index(User $user) {
        return $user->isAdmin();
    }

    public function create(User $user) {
        return $user->isAdmin();
    }

    public function store(User $user) {
        return $user->isAdmin();
    }
}
