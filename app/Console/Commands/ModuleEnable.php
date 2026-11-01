<?php

namespace App\Console\Commands;

use App\Modules\ModuleRegistry;
use Illuminate\Console\Command;

class ModuleEnable extends Command
{
    protected $signature = 'module:enable {slug : The module slug to enable}';

    protected $description = 'Enable an installed Control Center module';

    public function handle(ModuleRegistry $registry): int
    {
        $slug = (string) $this->argument('slug');
        $module = $registry->discovered()[$slug] ?? null;

        if ($module === null) {
            $this->error("Module [{$slug}] is not installed.");

            return self::FAILURE;
        }

        if (! $module->compatible) {
            $this->error($module->incompatibilityReason());

            return self::FAILURE;
        }

        // Compare against the stored flag, not the effective one, so safe mode
        // (which forces the effective state off) doesn't make this report a
        // module as not-yet-enabled when the database row is already on.
        if ($module->storedEnabled) {
            $this->info("Module [{$slug}] is already enabled.");

            return self::SUCCESS;
        }

        $registry->enable($slug);
        $this->info("Module [{$slug}] enabled.");

        return self::SUCCESS;
    }
}
