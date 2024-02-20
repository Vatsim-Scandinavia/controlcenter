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

        Schema::table('ratings', function (Blueprint $table) {
            $table->string('endorsement_type')->nullable()->after('vatsim_rating');
            $table->string('name', 16)->change(); // We are changing this to match requirement from Division API
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
        // As always SQLite is acting up, so we need to disable foreign key checks
        if (Schema::getConnection()->getDriverName() == 'sqlite') {
            Schema::disableForeignKeyConstraints();
        }

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('endorsement_type');
            $table->string('name', 50)->change();
        });

        // Re-enable foreign key checks for SQLite
        if (Schema::getConnection()->getDriverName() == 'sqlite') {
            Schema::enableForeignKeyConstraints();
        }
    }
};
