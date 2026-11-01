<?php

namespace App\Console\Commands;

use App\Modules\ModuleRegistry;
use Illuminate\Console\Command;

class ModuleList extends Command
{
    protected $signature = 'module:list';

    protected $description = 'List installed Control Center modules and their state';

    public function handle(ModuleRegistry $registry): int
    {
        $discovered = $registry->discovered();

        if ($discovered === []) {
            $this->info('No modules are installed.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($discovered as $module) {
            $rows[] = [
                $module->slug(),
                $module->manifest->version,
                $module->enabled ? 'enabled' : 'disabled',
                $module->compatible ? 'yes' : 'no (' . $module->manifest->ccVersion . ')',
                $module->source,
            ];
        }

        $this->table(['Slug', 'Version', 'State', 'Compatible', 'Source'], $rows);

        if ($registry->safeMode()) {
            $this->warn('Safe mode is on: no modules are booted regardless of state.');
        }

        return self::SUCCESS;
    }
}
