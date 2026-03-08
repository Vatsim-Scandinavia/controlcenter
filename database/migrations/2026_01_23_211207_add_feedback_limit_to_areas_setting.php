<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table(Config::get('settings.table'))->insert([
            ['key' => 'feedbackLimitToAreas', 'value' => false],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table(Config::get('settings.table'))->where('key', 'feedbackLimitToAreas')->delete();
    }
};
