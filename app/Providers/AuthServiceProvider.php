<?php

namespace App\Providers;

use App\Services\RoleSource;
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
        // Define a gate for every permission — core plus the permissions of
        // currently-enabled modules. Read live from RoleSource at boot (after
        // the database is available) rather than from cached config, so a
        // disabled module's permissions are never grantable and a freshly
        // enabled module's are.
        foreach (app(RoleSource::class)->permissions() as $permission) {
            Gate::define($permission, function ($user, $area = null) use ($permission) {
                return $user->hasPermission($permission, $area);
            });
        }
    }
}
