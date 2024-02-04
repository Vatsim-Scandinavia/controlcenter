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

        // As always SQLite is acting up, so we need to disable foreign key checks
        if (Schema::getConnection()->getDriverName() == 'sqlite') {
            Schema::disableForeignKeyConstraints();
        }

        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('template_s1_positions');
        });

        // Re-enable foreign key checks for SQLite
        if (Schema::getConnection()->getDriverName() == 'sqlite') {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking change
    }
};
