<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContinuedInterestNotificationLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('continued_interest_notification_log', function (Blueprint $table) {
            $table->uuid('notification_id');
            $table->unsignedBigInteger('training_id');
            $table->string('key');
            $table->timestamps();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            $table->primary('notification_id', 'ci_notification_id_pk');

            $table->foreign('training_id', 'ci_training_id_fk')->references('id')->on('trainings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('continued_interest_notification_log');
    }
}
