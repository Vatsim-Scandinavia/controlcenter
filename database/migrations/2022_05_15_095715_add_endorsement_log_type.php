<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //2022-07-25: Altered this migration to not use MODIFY as it's not supported in SQLITE for PHP TESTS. Deletes and re-creates instead.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->enum('category', ['ACCESS', 'TRAINING', 'BOOKING', 'ENDORSEMENT', 'OTHER'])->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Breaking change
    }
};
