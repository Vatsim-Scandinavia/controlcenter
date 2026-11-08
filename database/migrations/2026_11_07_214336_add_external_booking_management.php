<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('integration_id', 64)->nullable()->index();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('source', ['CC', 'VATBOOK', 'DISCORD', 'EXTERNAL'])->default('CC')->change();
            $table->string('integration_id', 64)->nullable();
            $table->string('external_id', 128)->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->unique(['integration_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('bookings')->where('source', 'EXTERNAL')->exists()) {
            throw new RuntimeException('Cancel and remove externally managed bookings before rolling back this migration.');
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['integration_id', 'external_id']);
            $table->dropColumn(['integration_id', 'external_id', 'external_url']);
            $table->enum('source', ['CC', 'VATBOOK', 'DISCORD'])->default('CC')->change();
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex(['integration_id']);
            $table->dropColumn('integration_id');
        });
    }
};
