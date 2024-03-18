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
        if (Schema::hasColumn('positions', 'area')) { // Added this to backfix this migration since order was fixed
            Schema::table('positions', function (Blueprint $table) {
                $table->renameColumn('area', 'area_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('positions', 'area_id')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->renameColumn('area_id', 'area');
            });
        }
    }
};
