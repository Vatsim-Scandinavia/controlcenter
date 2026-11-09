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
        $identityCollation = Schema::getConnection()->getDriverName() === 'mysql' ? 'utf8mb4_bin' : null;

        Schema::table('api_keys', function (Blueprint $table) use ($identityCollation) {
            $table->string('integration_id', 64)->nullable()->collation($identityCollation)->index();
        });

        Schema::table('bookings', function (Blueprint $table) use ($identityCollation) {
            $table->enum('source', ['CC', 'VATBOOK', 'DISCORD', 'EXTERNAL'])->default('CC')->change();
            $table->string('integration_id', 64)->nullable()->collation($identityCollation);
            $table->string('external_id', 128)->nullable()->collation($identityCollation);
            $table->unsignedInteger('external_revision')->nullable();
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

        // Older API code cannot preserve either the read or write scope of integration credentials.
        DB::table('api_keys')->whereNotNull('integration_id')->delete();

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['integration_id', 'external_id']);
            $table->dropColumn(['integration_id', 'external_id', 'external_revision', 'external_url']);
            $table->enum('source', ['CC', 'VATBOOK', 'DISCORD'])->default('CC')->change();
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex(['integration_id']);
            $table->dropColumn('integration_id');
        });
    }
};
