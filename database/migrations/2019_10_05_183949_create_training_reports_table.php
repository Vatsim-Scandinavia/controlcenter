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
            $table->increments('id');
            $table->unsignedInteger('training_id');
            $table->unsignedInteger('written_by_id');
            $table->string('content');
            $table->string('mentor_notes')->nullable();
            $table->string('position')->nullable();
            $table->boolean('draft')->default(false);
            $table->timestamps();

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
