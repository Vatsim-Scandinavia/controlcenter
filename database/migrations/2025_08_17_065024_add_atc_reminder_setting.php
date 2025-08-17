<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            DB::table(Config::get('settings.table'))->insert([
                ['key' => 'atcActivityInactivityReminder', 'value' => 0],
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            DB::table(Config::get('settings.table'))->where('key', 'atcActivityInactivityReminder')->delete();
        });
    }
};
