<?php

namespace Tests\Feature\Modules;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleTables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ModuleMigrationTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private string $migrationDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrationDir = base_path('modules/example/database/migrations');
        File::ensureDirectoryExists($this->migrationDir);
        File::put(
            $this->migrationDir . '/0000_00_00_000000_create_modules_example_items_table.php',
            <<<'PHP'
            <?php

            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('modules_example_items', function (Blueprint $table) {
                        $table->id();
                        $table->string('label');
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('modules_example_items');
                }
            };
            PHP
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('modules/example'));

        parent::tearDown();
    }

    #[Test]
    public function a_module_migration_directory_is_discovered(): void
    {
        $this->bootModules([ExampleModule::class]);

        $this->assertContains(
            $this->migrationDir,
            app(ModuleRegistry::class)->migrationPaths(),
        );
    }

    #[Test]
    public function a_discovered_module_migration_runs_and_follows_the_table_prefix(): void
    {
        $this->bootModules([ExampleModule::class]);

        $this->artisan('migrate', [
            '--path' => 'modules/example/database/migrations',
            '--realpath' => false,
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('modules_example_items'));
        $this->assertTrue(ModuleTables::belongsToModule('modules_example_items', 'example'));
        $this->assertFalse(ModuleTables::belongsToModule('users', 'example'));
        $this->assertSame('modules_example_', ModuleTables::prefix('example'));
    }
}
