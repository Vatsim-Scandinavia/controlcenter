<?php

namespace Tests\Feature;

use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use ControlCenter\Modules\Vateud\VateudAdapter;
use ControlCenter\Modules\Vateud\VateudModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\TestCase;

/**
 * The division API driver is now resolved from a registry: core ships only the
 * no-op default, and modules register concrete drivers. Selection consults core
 * config and the enabled modules' registered drivers.
 */
class DivisionApiServiceProviderTest extends TestCase
{
    use InteractsWithModules, RefreshDatabase;

    #[Test]
    public function it_resolves_the_noop_adapter_when_no_module_provides_a_driver(): void
    {
        Log::spy();

        $this->assertInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));
        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function it_resolves_a_module_driver_when_the_module_is_enabled(): void
    {
        $this->bootModules([VateudModule::class], ['vateud']);

        $this->assertInstanceOf(VateudAdapter::class, app(DivisionApiContract::class));
    }

    #[Test]
    public function it_honours_an_explicit_driver_selection_by_name(): void
    {
        $this->bootModules([VateudModule::class], ['vateud']);
        config(['vatsim.division_api_driver' => 'vateud']);

        $this->assertInstanceOf(VateudAdapter::class, app(DivisionApiContract::class));
    }

    #[Test]
    public function it_warns_when_a_configured_driver_has_no_enabled_provider(): void
    {
        // The easy misconfiguration: a driver named, but no module enabled.
        config(['vatsim.division_api_driver' => 'ghost']);
        Log::spy();

        $this->assertInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_stays_quiet_when_nothing_is_configured_and_no_module_is_enabled(): void
    {
        config(['vatsim.division_api_driver' => null]);
        Log::spy();

        $this->assertInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));
        Log::shouldNotHaveReceived('warning');
    }
}
