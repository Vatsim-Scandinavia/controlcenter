<?php

namespace App\Console\Commands;

use App\Modules\ModuleRegistry;
use Illuminate\Console\Command;

class ModuleDisable extends Command
{
    protected $signature = 'module:disable {slug : The module slug to disable}';

    protected $description = 'Disable an installed Control Center module';

    public function handle(ModuleRegistry $registry): int
    {
        $slug = (string) $this->argument('slug');
        $module = $registry->discovered()[$slug] ?? null;

        if ($module === null) {
            $this->error("Module [{$slug}] is not installed.");

            return self::FAILURE;
        }

        // Use the stored state, not the effective one: under safe mode a module
        // is effectively off but still enabled in the database, and the operator
        // must be able to turn the offending module off.
        if (! $module->storedEnabled) {
            $this->info("Module [{$slug}] is already disabled.");

            return self::SUCCESS;
        }

        $registry->disable($slug);
        $this->info("Module [{$slug}] disabled.");

        return self::SUCCESS;
    }
}
