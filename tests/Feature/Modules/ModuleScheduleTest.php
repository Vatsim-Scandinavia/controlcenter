<?php

namespace Tests\Feature\Modules;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleScheduler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ModuleScheduleTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    private function scheduledCommands(): array
    {
        $schedule = new Schedule;

        ModuleScheduler::schedule($schedule, app(ModuleRegistry::class));

        return array_map(fn ($event) => $event->command, $schedule->events());
    }

    #[Test]
    public function an_enabled_module_registers_its_scheduled_command(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $commands = $this->scheduledCommands();

        $this->assertTrue(
            collect($commands)->contains(fn ($command) => str_contains((string) $command, 'modules:example')),
            'Expected the enabled module scheduled command to be registered.',
        );
    }

    #[Test]
    public function a_disabled_module_registers_no_scheduled_command(): void
    {
        $this->bootModules([ExampleModule::class]); // discovered but not enabled

        $commands = $this->scheduledCommands();

        $this->assertFalse(
            collect($commands)->contains(fn ($command) => str_contains((string) $command, 'modules:example')),
            'A disabled module must not schedule any command.',
        );
    }
}
