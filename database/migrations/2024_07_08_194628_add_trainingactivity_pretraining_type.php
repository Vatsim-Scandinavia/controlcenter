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

        // Since we're going away from enums, we need to create the new column, transfer the data and then delete the enum and finish with a rename.
        Schema::table('training_activity', function (Blueprint $table) {
            $table->string('type_new')->after('triggered_by_id');
        });

        DB::statement('UPDATE training_activity SET type_new = type');

        Schema::table('training_activity', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('training_activity', function (Blueprint $table) {
            $table->renameColumn('type_new', 'type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking but harmless change
    }
};
