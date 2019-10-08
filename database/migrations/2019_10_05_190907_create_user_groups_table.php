<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description');
        });

        DB::table('user_groups')->insert([
            ['name' => 'Administrator', 'description' => 'Administrator role for director and technicians, access to whole system.'],
            ['name' => 'Training Director', 'description' => 'Access ment for Training Director to have full control over trainings and statistics.'],
            ['name' => 'Training Assistant', 'description' => 'Training Director\'s helpers, access to most of the trainings.'],
            ['name' => 'Mentor', 'description' => 'Access to their assigned students and mentor related functionality.'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_groups');
    }
}
