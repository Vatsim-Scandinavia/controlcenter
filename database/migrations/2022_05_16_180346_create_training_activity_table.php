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
        Schema::create('training_activity', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->enum('type', ['STATUS', 'TYPE', 'MENTOR', 'PAUSE', 'ENDORSEMENT', 'COMMENT']);
            $table->bigInteger('old_data')->nullable();
            $table->bigInteger('new_data')->nullable();
            $table->string('comment')->nullable();
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('triggered_by_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_activity');
    }
};
