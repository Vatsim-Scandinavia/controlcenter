<?php

use Illuminate\Database\Migrations\Migration;

class AddNewGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('groups')->insert([
            ['id' => 4, 'name' => 'Examinator', 'description' => 'Access to examine other students.'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('groups')->where('id', 4)->delete();
    }
}
