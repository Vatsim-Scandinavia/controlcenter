<?php

namespace App\Providers;

use App\Services\PermissionMatrix;
use App\Support\OpenApi\RemovesUnreferencedSchemas;
use App\Support\OpenApi\SortsDocumentAlphabetically;
use Database\Faker\VatsimProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\ServerVariable;
use Faker\Generator;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionMatrix::class);

        $this->registerFakerProviders();
    }

    /**
     * Add custom providers to every Faker generator, used by both factories and `fake()`.
     *
     * Faker is a dev dependency, so skip this when it is not installed.
     * Laravel caches generators per locale, so guard against adding a provider twice.
     */
    private function registerFakerProviders(): void
    {
        if (! class_exists(Generator::class)) {
            return;
        }

        $this->app->afterResolving(Generator::class, function (Generator $faker): void {
            foreach ($faker->getProviders() as $provider) {
                if ($provider instanceof VatsimProvider) {
                    return;
                }
            }

            $faker->addProvider(new VatsimProvider($faker));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The API v1 docs server URL (config/scramble.php) uses a `{host}` variable so readers
        // supply their own Control Center host in Swagger UI. Define its default and description here.
        Scramble::defineServerVariables([
            'host' => ServerVariable::make(
                'your-controlcenter-host.example',
                null,
                'The host serving your Control Center instance, without scheme or path (e.g. cc.example.org).',
            ),
        ]);

        // The `api.v1.user` endpoint is assigned to a separate `internal` API spec via the
        // #[Api('internal')] attribute on UserController::authenticated, which keeps it out of the
        // default (public) `api/v1` docs. Register that spec here, scoped to just that route.
        Scramble::registerApi('internal', ['api_path' => 'api/v1'])
            ->routes(fn (Route $route): bool => $route->getName() === 'api.v1.user');

        // Sort the generated document alphabetically so the exported `docs/api-v1.json` is
        // byte-identical regardless of the database engine it is generated against. See
        // SortsDocumentAlphabetically for the full rationale.
        // Prune first so the sort only ever runs over schemas that survive, then sort, keeping the
        // exported document byte-identical across engines. See RemovesUnreferencedSchemas for why
        // orphaned schemas appear at all.
        Scramble::configure()->withDocumentTransformers([RemovesUnreferencedSchemas::class, SortsDocumentAlphabetically::class]);
        Scramble::configure('internal')->withDocumentTransformers([RemovesUnreferencedSchemas::class, SortsDocumentAlphabetically::class]);
    }
}
