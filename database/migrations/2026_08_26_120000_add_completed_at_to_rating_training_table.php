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
        Schema::table('rating_training', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
        });

        // No backfill: closed_at is per-training, not per-rating, so stamping historic
        // trainings would invent per-part dates. Their parts stay NULL.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rating_training', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
