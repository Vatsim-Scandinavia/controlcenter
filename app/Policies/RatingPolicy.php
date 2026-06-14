<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RatingPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('training.ratings.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('training.ratings.manage');
    }

    public function update(User $user, Rating $rating): bool
    {
        return $user->hasPermission('training.ratings.manage');
    }

    public function delete(User $user, Rating $rating): bool
    {
        return $user->hasPermission('training.ratings.manage');
    }
}
