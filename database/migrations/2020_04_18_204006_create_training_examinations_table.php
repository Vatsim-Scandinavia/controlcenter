<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingExaminationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_examinations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('examiner_id')->nullable();
            $table->enum('result', ['PASSED', 'FAILED', 'INCOMPLETE', 'POSTPONED'])->nullable();
            $table->date('examination_date');
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('CASCADE')->onUpdate('CASCADE');
            $table->foreign('position_id')->references('id')->on('positions')->onUpdate('CASCADE')->onDelete('NO ACTION');
            $table->foreign('examiner_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_examinations');
    }
}
