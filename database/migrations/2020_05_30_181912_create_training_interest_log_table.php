<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingInterestLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_interest_log', function (Blueprint $table) {
            $table->uuid('notification_id');
            $table->unsignedBigInteger('training_id');
            $table->string('key');
            $table->timestamps();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            $table->primary('notification_id', 'ci_notification_id_pk');

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
        Schema::dropIfExists('training_interest_log');
    }
}
