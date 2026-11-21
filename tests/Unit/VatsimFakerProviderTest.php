<?php

namespace Tests\Unit;

use App\Models\Position;
use Database\Faker\VatsimProvider;
use Faker\Generator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VatsimFakerProviderTest extends TestCase
{
    private const CALLSIGN_PATTERN = '/^[A-Z]{4}_([A-Z0-9]_)?(DEL|GND|TWR|APP|DEP|CTR)$/';

    #[Test]
    public function atc_callsign_matches_vatsim_format(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $this->assertMatchesRegularExpression(self::CALLSIGN_PATTERN, fake()->atcCallsign());
        }
    }

    #[Test]
    public function atc_callsign_sometimes_has_a_middle_segment(): void
    {
        $callsigns = collect(range(1, 200))->map(fn () => fake()->atcCallsign());

        $this->assertTrue($callsigns->contains(fn (string $callsign) => substr_count($callsign, '_') === 2));
        $this->assertTrue($callsigns->contains(fn (string $callsign) => substr_count($callsign, '_') === 1));
    }

    #[Test]
    public function unique_atc_callsigns_never_contain_one_another(): void
    {
        $callsigns = collect(range(1, 500))->map(fn () => fake()->unique()->atcCallsign());

        foreach ($callsigns as $needle) {
            $containing = $callsigns->filter(fn (string $haystack) => str_contains($haystack, $needle));
            $this->assertCount(1, $containing, "{$needle} appears inside another callsign");
        }
    }

    #[Test]
    public function provider_is_registered_once_on_the_factory_generator(): void
    {
        $faker = app(Generator::class);
        app(Generator::class);

        $providers = collect($faker->getProviders())->filter(fn (object $provider) => $provider instanceof VatsimProvider);

        $this->assertCount(1, $providers);
    }

    #[Test]
    public function position_factory_uses_atc_callsigns(): void
    {
        $position = Position::factory()->make();

        $this->assertMatchesRegularExpression(self::CALLSIGN_PATTERN, $position->callsign);
    }
}
