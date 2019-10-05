<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->primary(['id']);

            $table->integer('id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->tinyInteger('status');
            $table->integer('country_id');
            $table->string('notes');
            $table->string('motivation');
            $table->boolean('english_only_training');
            $table->boolean('paused');
            $table->string('closed_reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trainings');
    }
}
