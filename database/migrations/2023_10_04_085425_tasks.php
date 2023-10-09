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
            $table->foreignId('subject_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_training_id')->constrained('trainings')->onDelete('cascade');
            $table->foreignId('assignee_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('creator_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->boolean('assignee_notified')->default(false);
            $table->boolean('creator_notified')->default(false);
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
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
