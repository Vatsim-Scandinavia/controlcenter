<?php

namespace Tests\Feature\Modules;

use App\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ThrowingModule;
use Tests\TestCase;
use Throwable;

class ModuleSentryTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    #[Test]
    public function a_module_failure_is_reported_to_sentry_tagged_with_the_module(): void
    {
        $sentry = new class
        {
            /** @var array<int, Throwable> */
            public array $captured = [];

            /** @var array<string, string> */
            public array $tags = [];

            public function configureScope(callable $callback): void
            {
                $scope = new class($this)
                {
                    public function __construct(private object $sentry) {}

                    public function setTag(string $key, string $value): void
                    {
                        $this->sentry->tags[$key] = $value;
                    }
                };

                $callback($scope);
            }

            public function captureException(Throwable $e): void
            {
                $this->captured[] = $e;
            }
        };

        $this->app->instance('sentry', $sentry);

        $this->bootModules([ThrowingModule::class], ['throwing']);

        // Booting the throwing module triggers its registration failure.
        app(ModuleRegistry::class)->booted();

        $this->assertNotEmpty($sentry->captured, 'The module failure should be captured by Sentry.');
        $this->assertSame('throwing', $sentry->tags['module'] ?? null);
    }
}
