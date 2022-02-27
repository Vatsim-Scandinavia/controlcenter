<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEndorsementPivot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('rating_user', 'endorsement_rating');
        Schema::table('endorsement_rating', function (Blueprint $table) {
            $table->renameColumn('rating_id', 'endorsement_id');
        });

        Schema::create('endorsement_position', function (Blueprint $table) {

            $table->unsignedBigInteger('endorsement_id');
            $table->unsignedBigInteger('position_id');

            $table->foreign('endorsement_id')->references('id')->on('endorsements')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');

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
}
