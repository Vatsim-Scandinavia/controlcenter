<?php

namespace Tests\Feature\Statistics;

use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatisticsSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('user.statistics.sessions'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['vatsimId', 'from', 'to']);
    }

    #[Test]
    public function it_validates_date_formats(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('user.statistics.sessions', [
                'vatsimId' => 123456,
                'from' => 'not-a-date',
                'to' => 'not-a-date',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);
    }

    #[Test]
    public function it_validate_to_not_before_from(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('user.statistics.sessions', [
                'vatsimId' => 123456,
                'from' => '2024-01-02',
                'to' => '2024-01-01',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    #[Test]
    public function it_calls_service_with_normalized_dates(): void
    {
        $vatsimId = '123456';
        $from = '2024-01-01T00:00:00';
        $to = '2024-01-02T00:00:00';

        // Mock the service to ensure it receives the correct parameters
        $this->mock(StatisticsService::class, function (MockInterface $mock) use ($vatsimId) {
            $mock->shouldReceive('getAtcSessions')
                ->once()
                ->withArgs(function ($argId, $argFrom, $argTo) use ($vatsimId) {
                    // Verify ID matches
                    if ($argId !== $vatsimId) {
                        return false;
                    }

                    // Verify dates are the right type (e.g. Carbon)
                    if (! ($argFrom instanceof \DateTimeInterface) || ! ($argTo instanceof \DateTimeInterface)) {
                        return false;
                    }

                    return true;
                })
                // Return empty array for simplicity
                ->andReturn([]);

            $mock->shouldReceive('transformSessions')
                ->once()
                ->andReturn([]);
        });

        $this->actingAs($this->user)
            ->getJson(route('user.statistics.sessions', [
                'vatsimId' => $vatsimId,
                'from' => $from,
                'to' => $to,
            ]))
            ->assertStatus(200);
    }
}
