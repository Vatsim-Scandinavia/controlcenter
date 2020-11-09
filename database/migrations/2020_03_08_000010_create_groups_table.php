<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description');
        });

        DB::table('groups')->insert([
            ['id' => 1, 'name' => 'Administrator', 'description' => 'Rank meant for vACC Director, Training Director and technicaians, access to whole system.'],
            ['id' => 2, 'name' => 'Moderator', 'description' => 'Access meant for FIR Director and Training assistants to have full control over trainings and statistics.'],
            ['id' => 3, 'name' => 'Mentor', 'description' => 'Access meant for mentors, to give them mentor-related functionality.'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups');
    }
}
