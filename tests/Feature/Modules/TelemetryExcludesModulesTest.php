<?php

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class TelemetryExcludesModulesTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    #[Test]
    public function telemetry_payload_contains_no_module_information(): void
    {
        Http::fake(['telemetry.vatsca.org/*' => Http::response([], 200)]);

        // Even with a module enabled, telemetry must not leak module data.
        $this->bootModules([ExampleModule::class], ['example']);

        $this->artisan('send:telemetry')->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            $keys = array_keys($request->data());

            foreach ($keys as $key) {
                if (str_contains(strtolower((string) $key), 'module')) {
                    return false;
                }
            }

            $encoded = strtolower((string) json_encode($request->data()));

            return ! str_contains($encoded, 'module') && ! str_contains($encoded, 'example');
        });
    }
}
