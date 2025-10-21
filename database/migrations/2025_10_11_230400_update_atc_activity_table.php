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
        Schema::table('atc_activities', function (Blueprint $table) {
            $table->timestamp('last_online')->nullable();
            $table->double('last_12_months')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atc_activities', function (Blueprint $table) {
            $table->dropColumn('last_online');
            $table->dropColumn('last_12_months');
        });
    }
};