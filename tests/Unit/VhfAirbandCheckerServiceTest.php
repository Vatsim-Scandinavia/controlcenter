<?php

namespace Tests\Unit;

use App\Services\VhfAirbandCheckerService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VhfAirbandCheckerServiceTest extends TestCase
{
    private VhfAirbandCheckerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VhfAirbandCheckerService();
    }

    #[Test]
    public function it_validates_edge_frequencies()
    {
        $this->assertTrue($this->service->check('118.000'));
        $this->assertTrue($this->service->check('136.990'));
    }

    #[Test]
    public function it_validates_mid_frequencies()
    {
        $this->assertTrue($this->service->check('122.800'));
        $this->assertTrue($this->service->check('131.705'));
        $this->assertTrue($this->service->check('121.500'));
        $this->assertTrue($this->service->check('118.005')); // 8.33kHz
        $this->assertTrue($this->service->check('118.010')); // 8.33kHz
        $this->assertTrue($this->service->check('118.015')); // 8.33kHz
        $this->assertTrue($this->service->check('136.975')); // 25kHz
        $this->assertTrue($this->service->check('136.980')); // 8.33kHz
        $this->assertTrue($this->service->check('136.985')); // 8.33kHz
    }

    #[Test]
    public function it_invalidates_frequencies_outside_range()
    {
        $this->assertFalse($this->service->check('117.995'));
        $this->assertFalse($this->service->check('137.000'));
    }

    #[Test]
    public function it_invalidates_frequencies_with_bad_spacing()
    {
        $this->assertFalse($this->service->check('118.020'));
        $this->assertFalse($this->service->check('118.021'));
        $this->assertFalse($this->service->check('122.845'));
        $this->assertFalse($this->service->check('131.770'));
        $this->assertFalse($this->service->check('135.995'));
    }

    #[Test]
    public function it_invalidates_incorrectly_formatted_frequencies()
    {
        $this->assertFalse($this->service->check('118'));
        $this->assertFalse($this->service->check('118.0'));
        $this->assertFalse($this->service->check('118.00'));
        $this->assertFalse($this->service->check('118.0000'));
        $this->assertFalse($this->service->check('abc.def'));
        $this->assertFalse($this->service->check('118.abc'));
    }
}
