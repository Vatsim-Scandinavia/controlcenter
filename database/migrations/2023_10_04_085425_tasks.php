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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->tinyInteger('status')->default(0);
            $table->string('status_comment', 256)->nullable();
            $table->string('message', 256)->nullable();
            $table->unsignedBigInteger('reference_user_id');
            $table->unsignedBigInteger('reference_training_id')->nullable();
            $table->unsignedBigInteger('recipient_user_id');
            $table->unsignedBigInteger('sender_user_id')->nullable();
            $table->boolean('notified')->default(false);
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();

            $table->foreign('reference_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reference_training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sender_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
