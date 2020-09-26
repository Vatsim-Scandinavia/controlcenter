<?php

namespace App\Policies;

use App\User;
use App\Vatbook;
use Illuminate\Auth\Access\HandlesAuthorization;

class VatbookPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the vatbook.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $vatbook
     * @return mixed
     */
    public function view(User $user, Vatbook $vatbook)
    {
        //
    }

    /**
     * Determine whether the user can create vatbooks.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->rating >= 3 || $user->getActiveTraining(2) != null;
    }

    /**
     * Determine whether the user can update the vatbook.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $vatbook
     * @return mixed
     */
    public function update(User $user, Vatbook $vatbook)
    {
        //
    }

    /**
     * Determine whether the user can delete the vatbook.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $vatbook
     * @return mixed
     */
    public function delete(User $user, Vatbook $vatbook)
    {
        //
    }

    /**
     * Determine whether the user can restore the vatbook.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $vatbook
     * @return mixed
     */
    public function restore(User $user, Vatbook $vatbook)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the vatbook.
     *
     * @param  \App\User  $user
     * @param  \App\Vatbook  $vatbook
     * @return mixed
     */
    public function forceDelete(User $user, Vatbook $vatbook)
    {
        //
    }
}
