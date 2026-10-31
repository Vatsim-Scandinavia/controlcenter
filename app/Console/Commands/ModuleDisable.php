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

        if (! $module->enabled) {
            $this->info("Module [{$slug}] is already disabled.");

            return self::SUCCESS;
        }

        $registry->disable($slug);
        $this->info("Module [{$slug}] disabled.");

        return self::SUCCESS;
    }
}
