<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVatbooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vatbooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('eu_id')->unsigned();
            $table->bigInteger('local_id')->unsigned()->nullable();
            $table->string('callsign', 11);
            $table->bigInteger('position_id')->unsigned();
            $table->string('name');
            $table->dateTime('time_start');
            $table->dateTime('time_end');
            $table->bigInteger('cid')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->boolean('training')->default(false);
            $table->boolean('event')->default(false);
            $table->boolean('deleted')->default(false);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vatbooks');
    }
}
