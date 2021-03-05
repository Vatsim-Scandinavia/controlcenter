<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use anlutro\LaravelSettings\Facade as Setting;

class SettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user view global settings.
     *
     * @param  \App\Models\User $user
     * @param Setting $setting
     * @return bool
     */
    public function index(User $user, Setting $setting) {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update global settings.
     *
     * @param  \App\Models\User $user
     * @param Setting $setting
     * @return bool
     */
    public function edit(User $user, Setting $setting) {
        return $user->isAdmin();
    }
}
