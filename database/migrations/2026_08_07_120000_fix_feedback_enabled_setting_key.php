<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Converge existing installs onto the correct `feedbackEnabled` settings key.
     * Idempotent: renames the legacy key, or drops it if the correct key already exists.
     */
    public function up(): void
    {
        $table = Config::get('settings.table');

        $hasCorrect = DB::table($table)->where('key', 'feedbackEnabled')->exists();
        $hasLegacy = DB::table($table)->where('key', 'feedbackEnable')->exists();

        if ($hasLegacy && $hasCorrect) {
            DB::table($table)->where('key', 'feedbackEnable')->delete();
        } elseif ($hasLegacy) {
            DB::table($table)->where('key', 'feedbackEnable')->update(['key' => 'feedbackEnabled']);
        }
    }

    /**
     * One-way data fix; nothing to reverse.
     */
    public function down(): void
    {
        //
    }
};
