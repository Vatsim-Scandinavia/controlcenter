<?php

namespace Tests\Feature\Modules;

use App\Modules\ModuleRegistry;
use App\Tasks\TaskTypeRegistry;
use App\Tasks\Types\MissingType;
use App\Tasks\Types\RatingUpgrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\Fixtures\Modules\ExampleTaskType;
use Tests\TestCase;

class TaskTypeRegistryTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private function registry(): TaskTypeRegistry
    {
        return app(TaskTypeRegistry::class);
    }

    #[Test]
    public function core_keys_resolve_to_their_type(): void
    {
        $this->assertInstanceOf(RatingUpgrade::class, $this->registry()->make('rating-upgrade'));
        $this->assertSame('rating-upgrade', $this->registry()->keyFor(RatingUpgrade::class));
        $this->assertTrue($this->registry()->has('solo-endorsement'));
    }

    #[Test]
    public function an_unknown_key_falls_back_to_the_missing_type(): void
    {
        $this->assertInstanceOf(MissingType::class, $this->registry()->make('gone.forever'));
        $this->assertFalse($this->registry()->has('gone.forever'));
    }

    #[Test]
    public function a_legacy_class_name_still_resolves_for_backwards_compatibility(): void
    {
        $this->assertInstanceOf(RatingUpgrade::class, $this->registry()->make(RatingUpgrade::class));
    }

    #[Test]
    public function module_task_types_resolve_only_while_enabled(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        app()->forgetInstance(TaskTypeRegistry::class);

        $this->assertTrue($this->registry()->has('example.review'));
        $this->assertInstanceOf(ExampleTaskType::class, $this->registry()->make('example.review'));

        // Once disabled, the key no longer resolves and degrades gracefully.
        app(ModuleRegistry::class)->disable('example');
        app()->forgetInstance(TaskTypeRegistry::class);

        $this->assertFalse($this->registry()->has('example.review'));
        $this->assertInstanceOf(MissingType::class, $this->registry()->make('example.review'));
    }
}
