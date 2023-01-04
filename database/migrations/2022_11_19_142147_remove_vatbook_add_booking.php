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
        Schema::rename('vatbooks', 'bookings');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['eu_id', 'local_id', 'cid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('bookings', 'vatbooks');
        Schema::table('vatbooks', function (Blueprint $table) {
            $table->bigInteger('eu_id')->unsigned()->after('source');
            $table->bigInteger('local_id')->unsigned()->nullable()->after('eu_id');
            $table->bigInteger('cid')->unsigned()->after('local_id');
        });
    }
};
