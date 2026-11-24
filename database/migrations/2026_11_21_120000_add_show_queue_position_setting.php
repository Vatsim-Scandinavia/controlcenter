<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Whether students see their place in the training queue. Off by default,
     * so an installation opts in from the global settings page.
     */
    public function up(): void
    {
        $table = Config::get('settings.table');

        if (DB::table($table)->where('key', 'trainingShowQueuePosition')->exists()) {
            return;
        }

        DB::table($table)->insert([
            ['key' => 'trainingShowQueuePosition', 'value' => false],
        ]);
    }

    public function down(): void
    {
        DB::table(Config::get('settings.table'))->where('key', 'trainingShowQueuePosition')->delete();
    }
};
