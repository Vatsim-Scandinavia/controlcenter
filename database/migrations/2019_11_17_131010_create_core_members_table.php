<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql-noprefix')->create('core_members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 64);
            $table->string('firstName', 50);
            $table->string('lastName', 50);
            $table->tinyInteger('rating');
            $table->string('ratingShort', 3);
            $table->string('ratingLong', 24);
            $table->string('ratingGRP', 32);
            $table->tinyInteger('pilotRating');
            $table->dateTime('regDate');
            $table->string('country', 2);
            $table->string('region', 8);
            $table->string('division', 3);
            $table->string('subdivision', 3);
            $table->tinyInteger('active');
            $table->tinyInteger('acceptedPrivacy');
            $table->dateTime('lastLogin')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Do nothing, we don't want to delete core_members database, ever.
    }
}
