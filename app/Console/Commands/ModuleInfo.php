<?php

namespace App\Console\Commands;

use App\Modules\ModuleRegistry;
use Illuminate\Console\Command;

class ModuleInfo extends Command
{
    protected $signature = 'module:info {slug : The module slug to describe}';

    protected $description = 'Show the manifest and declared extension points of a module';

    public function handle(ModuleRegistry $registry): int
    {
        $slug = (string) $this->argument('slug');
        $module = $registry->discovered()[$slug] ?? null;

        if ($module === null) {
            $this->error("Module [{$slug}] is not installed.");

            return self::FAILURE;
        }

        $manifest = $module->manifest;

        $this->table(['Field', 'Value'], [
            ['Name', $manifest->name],
            ['Slug', $manifest->slug],
            ['Version', $manifest->version],
            ['Author', $manifest->author ?? '—'],
            ['Requires', 'Control Center ' . $manifest->ccVersion],
            ['Compatible', $module->compatible ? 'yes' : 'no'],
            ['State', $module->enabled ? 'enabled' : 'disabled'],
            ['Source', $module->source],
            ['Description', $manifest->description],
        ]);

        if ($manifest->permissions !== []) {
            $this->line('');
            $this->info('Declared permissions:');
            foreach ($manifest->permissions as $name => $description) {
                $this->line("  - {$name}: {$description}");
            }
        }

        if ($manifest->dataHandling !== []) {
            $this->line('');
            $this->info('User data handled:');
            foreach ($manifest->dataHandling as $note) {
                $this->line("  - {$note}");
            }
        }

        return self::SUCCESS;
    }
}
