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

        // Module abilities are additionally resolved dynamically, so enabling or
        // disabling a module takes effect immediately — even within the request
        // that toggles it — rather than only on the next boot. The gate
        // definitions above are a snapshot of the state at boot; this resolves
        // any `modules.*` ability live against RoleSource (via hasPermission),
        // whether or not a gate was defined for it.
        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (! str_starts_with($ability, 'modules.')) {
                return null;
            }

            return $user->hasPermission($ability, $arguments[0] ?? null) ?: null;
        });
    }
}
