<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('written_by_id')->nullable();
            $table->date('report_date');
            $table->text('content');
            $table->text('contentimprove')->nullable();
            $table->string('position')->nullable();
            $table->boolean('draft')->default(false);
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('written_by_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_reports');
    }
}
