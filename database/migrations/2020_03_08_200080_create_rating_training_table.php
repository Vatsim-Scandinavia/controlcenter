<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingTrainingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_training', function (Blueprint $table) {
            $table->primary(['rating_id', 'training_id']);

            $table->unsignedInteger('rating_id');
            $table->unsignedBigInteger('training_id');

            $table->foreign('rating_id')->references('id')->on('ratings');
            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rating_training');
    }
}
