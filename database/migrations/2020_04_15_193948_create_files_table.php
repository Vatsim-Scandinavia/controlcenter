<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {

            $table->string('id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('path');
            $table->timestamps();

            $table->primary('id');

            // We want to keep all the files even though the user is deleted from the database
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('CASCADE');

        });

        Schema::table('training_report_attachments', function ($table) {
            $table->foreign('training_report_id')->references('id')->on('training_reports');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('CASCADE')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
