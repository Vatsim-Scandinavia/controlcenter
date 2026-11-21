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
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('pilot_callsign', 20)->nullable()->after('area_id');
            $table->date('occurred_on')->nullable()->after('pilot_callsign');
            $table->string('departure_airport', 4)->nullable()->after('occurred_on');
            $table->string('arrival_airport', 4)->nullable()->after('departure_airport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['pilot_callsign', 'occurred_on', 'departure_airport', 'arrival_airport']);
        });
    }
};
