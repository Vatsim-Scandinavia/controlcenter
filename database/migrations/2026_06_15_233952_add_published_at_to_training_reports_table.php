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
        Schema::table('training_reports', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('draft')->index();
        });

        // Backfill the publishing date for already-published reports. updated_at is
        // the best available proxy: it is the value the activities view has been
        // displaying as the "published" timestamp prior to this column existing.
        DB::table('training_reports')
            ->where('draft', false)
            ->update(['published_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_reports', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
