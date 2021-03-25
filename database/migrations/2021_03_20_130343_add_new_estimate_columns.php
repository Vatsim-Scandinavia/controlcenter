<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewEstimateColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('area_rating', function (Blueprint $table) {
            $table->renameColumn('queue_length', 'queue_length_low');
            $table->unsignedInteger('queue_length_high')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('area_rating', function (Blueprint $table) {
            $table->renameColumn('queue_length_low', 'queue_length');
            $table->dropColumn('queue_length_high');
        });
    }
}
