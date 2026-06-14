<?php

namespace App\Policies;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view global settings.
     *
     * @return bool
     */
    public function index(User $user, Setting $setting)
    {
        return $user->hasPermission('system.settings.manage');
    }

    /**
     * Determine whether the user can update global settings.
     *
     * @return bool
     */
    public function edit(User $user, Setting $setting)
    {
        return $user->hasPermission('system.settings.manage');
    }
}
