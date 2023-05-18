<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_user', function (Blueprint $table) {
            $table->primary(['rating_id', 'user_id']);

            $table->unsignedInteger('rating_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('rating_id')->references('id')->on('ratings');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rating_user');
    }
}
