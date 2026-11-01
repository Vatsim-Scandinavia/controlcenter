<?php

namespace Tests\Fixtures\Modules;

use Illuminate\Console\Command;

class ExampleCommand extends Command
{
    protected $signature = 'modules:example';

    protected $description = 'Example module command (test fixture)';

    public function handle(): int
    {
        $this->info('Example module command ran.');

        return self::SUCCESS;
    }
}
