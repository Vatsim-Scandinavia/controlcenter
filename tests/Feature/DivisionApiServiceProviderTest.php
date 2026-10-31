<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Contracts\DivisionApiContract;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use App\Services\DivisionApi\Adapters\VATEUD;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DivisionApiServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_the_vateud_adapter_when_the_api_is_enabled_and_configured(): void
    {
        Setting::set('divisionApiEnabled', true);
        config(['vatsim.division_api_driver' => 'VATEUD']);
        Log::spy();

        $this->assertInstanceOf(VATEUD::class, app(DivisionApiContract::class));
        Log::shouldNotHaveReceived('warning');
    }

    #[Test]
    public function it_warns_when_the_api_is_enabled_but_the_driver_is_unrecognised(): void
    {
        // The easy misconfiguration: enabled in the admin panel, env var never set.
        Setting::set('divisionApiEnabled', true);
        config(['vatsim.division_api_driver' => null]);
        Log::spy();

        $this->assertInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_stays_quiet_when_the_api_is_simply_switched_off(): void
    {
        // Disabled is a deliberate choice, not a mistake worth warning about.
        Setting::set('divisionApiEnabled', false);
        config(['vatsim.division_api_driver' => null]);
        Log::spy();

        $this->assertInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));
        Log::shouldNotHaveReceived('warning');
    }
}
