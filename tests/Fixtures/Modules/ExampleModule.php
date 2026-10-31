<?php

namespace Tests\Fixtures\Modules;

use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use ControlCenter\ModuleApi\Registrar;
use ControlCenter\ModuleApi\Settings\SettingField;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use ControlCenter\ModuleApi\Ui\Slot;
use Illuminate\Support\Facades\Route;

/**
 * Test fixture module exercising every extension point. Registered in CI tests
 * by pushing it onto config('modules.register'). It is deliberately
 * self-contained and stores no real data.
 */
class ExampleModule implements Module
{
    public const string SLUG = 'example';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Example Module',
            version: '1.0.0',
            ccVersion: '*',
            description: 'Fixture module exercising every Control Center extension point.',
            author: 'Control Center',
            permissions: [
                'modules.example.view' => 'View the example module',
                'modules.example.manage' => 'Manage the example module',
            ],
            dataHandling: ['Stores an example note per user in memory only.'],
        );
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.example.view', 'View the example module')
            ->permission('modules.example.manage', 'Manage the example module')
            ->navigation(new NavigationItem(
                title: 'Example',
                route: 'modules.example.index',
                permission: 'modules.example.view',
                icon: 'fa-plug',
            ))
            ->slot(Slot::DashboardWidgets, 'modules::example.widget', 'modules.example.view')
            ->slot(Slot::UserPanels, 'modules::example.panel')
            ->adminSettings(
                (new SettingsSchema)
                    ->text('greeting', 'Greeting', ['nullable', 'string', 'max:255'])
                    ->secret('api_key', 'API key', ['nullable', 'string'])
                    ->boolean('enabled_flag', 'An example toggle')
            )
            ->userSettings((new SettingsSchema)->add(new SettingField('nickname', 'Nickname', 'text', ['nullable', 'string', 'max:50'])))
            ->role('reviewer', [
                'name' => 'Example Reviewer',
                'description' => 'Reviews example things',
                'scope' => 'global',
            ], ['modules.example.view'])
            ->taskType('example.review', ExampleTaskType::class)
            ->privacyHandler(ExamplePrivacyHandler::class)
            ->command(ExampleCommand::class)
            ->schedule(function ($schedule): void {
                $schedule->command('modules:example')->daily();
            })
            ->activityLogName('modules.example', 'Example')
            ->apiScope('modules.example.read', 'Read example data')
            ->driver(DivisionApiContract::class, 'example', fn ($app) => new NoOpAdapter)
            ->bind('modules.example.service', fn () => new \stdClass)
            ->web(function (): void {
                Route::get('/', fn () => 'example module index')->name('index');
            })
            ->api(function (): void {
                Route::get('/ping', fn () => response()->json(['ok' => true]))
                    ->middleware('api-token:modules.example.read')
                    ->name('ping');
            });
    }
}
