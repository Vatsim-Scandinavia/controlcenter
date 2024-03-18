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
        // Because we want to maintain SQlite compability for test suite, this is done step by step.

        Schema::table('endorsements', function (Blueprint $table) {
            $table->string('type_temp', 32)->after('type');
        });

        DB::statement('UPDATE endorsements SET type_temp = type');

        Schema::table('endorsements', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('endorsements', function (Blueprint $table) {
            $table->renameColumn('type_temp', 'type');
        });

        // We won't delete the old S1 endorsements, to keep the history for training activity logs. However, they have no effect anymore in the code.

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking change
    }
};
