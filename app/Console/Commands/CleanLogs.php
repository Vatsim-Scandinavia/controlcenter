<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class CleanLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrub IP and user agent details from old activity log entries';

    /**
     * Execute the console command.
     *
     * Deletion of old entries is handled separately by the activitylog:clean
     * command (see config/activitylog.php).
     */
    public function handle(): void
    {
        $scrubbed = ActivityLog::where('created_at', '<', now()->subWeeks(2))
            ->update(['ip_address' => null, 'user_agent' => null]);

        $this->info("Scrubbed IP and user agent details from {$scrubbed} activity log entries older than two weeks.");
    }
}
