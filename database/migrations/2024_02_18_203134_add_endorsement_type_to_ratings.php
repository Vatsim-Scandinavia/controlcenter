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
        Schema::table('ratings', function (Blueprint $table) {
            $table->string('endorsement_type')->nullable()->after('vatsim_rating');
            $table->string('name', 16)->change(); // We are changing this to match requirement from Division API
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('endorsement_type');
            $table->string('name', 50)->change();
        });
    }
};
