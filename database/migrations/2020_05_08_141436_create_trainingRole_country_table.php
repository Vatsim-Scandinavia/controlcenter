<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingRoleCountryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_role_country', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('inserted_by')->nullable();
            $table->timestamps();

            // This is to allow a mentor to be a mentor in multiple countries
            $table->primary(['user_id', 'country_id']);
        });

        Schema::table('training_role_country', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('inserted_by')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('SET NULL');
            // TODO figure out why this foreign key doesn't work
            //            $table->foreign('country_id')->references('id')->on('countries')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mentor_country');
    }
}
