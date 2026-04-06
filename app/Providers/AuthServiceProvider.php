<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'anlutro\LaravelSettings\Facade' => 'App\Policies\SettingPolicy',
        'Illuminate\Notifications\Notification' => 'App\Policies\NotificationPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $matrix = config('roles.matrix', []);

        foreach ($matrix as $permission => $roles) {
            Gate::define($permission, function ($user, $area = null) use ($permission) {
                return $user->hasPermission($permission, $area);
            });
        }
    }
}
