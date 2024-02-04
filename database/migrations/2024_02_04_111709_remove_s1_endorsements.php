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
        // Because we want to maintain SQlite compability for test suite, this is done a bit wierdly.

        Schema::table('endorsements', function (Blueprint $table) {
            $table->string('type_temp', 32)->after('type');
        });

        DB::statement('UPDATE endorsements SET type_temp = type');

        Schema::table('endorsements', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->renameColumn('type_temp', 'type');
        });

        // Delete all 'S1' endorsements
        DB::table('endorsements')->where('type', 'S1')->delete();

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking change
    }
};
