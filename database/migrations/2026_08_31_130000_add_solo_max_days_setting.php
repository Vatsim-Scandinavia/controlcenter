<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The number of solo days a student may be granted across one training.
     * 60 matches the two 30-day endorsements a solo is normally issued as, and
     * stays adjustable per installation from the global settings page.
     */
    private const DEFAULT_MAX_DAYS = '60';

    public function up(): void
    {
        $table = Config::get('settings.table');

        if (DB::table($table)->where('key', 'trainingSoloMaxDays')->exists()) {
            return;
        }

        DB::table($table)->insert([
            ['key' => 'trainingSoloMaxDays', 'value' => self::DEFAULT_MAX_DAYS],
        ]);
    }

    public function down(): void
    {
        DB::table(Config::get('settings.table'))->where('key', 'trainingSoloMaxDays')->delete();
    }
};
