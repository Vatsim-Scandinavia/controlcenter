<?php

namespace App\Providers;

use App\Models\Area;
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

        // Module permission abilities are additionally resolved dynamically, so
        // enabling a module takes effect immediately — even within the request
        // that toggles it — rather than only on the next boot (the definitions
        // above are a snapshot of the enabled state at boot).
        //
        // This is a narrow, fail-open-to-null fallback, not an override:
        //  - only catalogued module permissions are handled; anything else
        //    (uncatalogued abilities, resource gates, policies) falls through;
        //  - only the permission argument shape (an optional Area) is handled;
        //  - an explicitly defined gate always wins (we defer to it);
        //  - it may only GRANT (return true) or defer (return null) — it never
        //    returns false, so it can never override an explicit denial.
        Gate::before(function ($user, string $ability, array $arguments = []) {
            if (! str_starts_with($ability, 'modules.')) {
                return null;
            }

            // Let an explicitly defined gate (core snapshot or a module's own
            // custom gate) decide; only step in when nothing is defined yet.
            if (Gate::has($ability)) {
                return null;
            }

            // Only catalogued permissions resolve dynamically.
            if (! in_array($ability, app(RoleSource::class)->permissions(), true)) {
                return null;
            }

            // Only the (?Area) argument shape is understood; resource/policy-style
            // arguments are not ours to answer.
            $area = $arguments[0] ?? null;

            if ($area !== null && ! $area instanceof Area) {
                return null;
            }

            return $user->hasPermission($ability, $area) ? true : null;
        });
    }
}
