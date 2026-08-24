<?php

namespace App\Providers;

use App\Services\PermissionMatrix;
use App\Support\OpenApi\SortsDocumentAlphabetically;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\ServerVariable;
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
        Scramble::configure()->withDocumentTransformers(SortsDocumentAlphabetically::class);
        Scramble::configure('internal')->withDocumentTransformers(SortsDocumentAlphabetically::class);
    }
}
